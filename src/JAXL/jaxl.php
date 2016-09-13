<?php
/**
* Jaxl (Jabber XMPP Library)
*
* Copyright (c) 2009-2012, Abhinav Singh <me@abhinavsingh.com>.
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions
* are met:
*
* * Redistributions of source code must retain the above copyright
* notice, this list of conditions and the following disclaimer.
*
* * Redistributions in binary form must reproduce the above copyright
* notice, this list of conditions and the following disclaimer in
* the documentation and/or other materials provided with the
* distribution.
*
* * Neither the name of Abhinav Singh nor the names of his
* contributors may be used to endorse or promote products derived
* from this software without specific prior written permission.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
* "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
* LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
* FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
* COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
* INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
* BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
* CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRIC
* LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
* ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
* POSSIBILITY OF SUCH DAMAGE.
*
*/

declare(ticks = 1);

/**
 * Jaxl class extends base XMPPStream class with following functionalities:
 * 1) Adds an event based wrapper over xmpp stream lifecycle
 * 2) Provides restart strategy and signal handling to ensure connectivity of xmpp stream
 * 3) Roster management as specified in XMPP-IM
 * 4) Management of XEP's inside xmpp stream lifecycle
 * 5) Adds a logging facility
 * 6) Adds a cron job facility in sync with connected xmpp stream timeline
 *
 * @author abhinavsingh
 *
 */
class JAXL extends XMPPStream
{
    
    // lib meta info
    const VERSION = '3.1.0';
    const NAME = 'JAXL :: Jabber XMPP Library';
    
    // cached init config array
    public $cfg = array();
    

    /**
     * Event callback engine for XMPP stream lifecycle.
     * @var JAXLEvent
     */
    protected $ev = null;
    
    // reference to various xep instance objects
    public $xeps = array();
    

    /**
     * Local cache of roster list.
     * @var XMPPRosterItem[]
     */
    public $roster = array();
    
    // whether jaxl must also populate local roster cache with
    // received presence information about the contacts
    public $manage_roster = true;
    
    // what to do with presence sub requests
    // "none" | "accept" | "mutual"
    public $manage_subscribe = "none";
    
    // path variables
    public $log_level = JAXLLogger::INFO;
    public $log_colorize = true;
    public $priv_dir;
    public $tmp_dir;
    public $log_dir;
    public $pid_dir;
    public $sock_dir;
    
    // ipc utils
    private $sock;
    private $cli;
    
    // env
    public $local_ip;
    public $pid;
    public $mode;
    
    // current status message
    public $status;
    
    // identity
    public $features = array();
    public $category = 'client';
    public $type = 'bot';
    public $lang = 'en';
    
    // after cth failed attempt
    // retry connect after k * $retry_interval seconds
    // where k is a random number between 0 and 2^c - 1.
    public $retry = true;
    private $retry_interval = 1;
    private $retry_attempt = 0;
    private $retry_max_interval = 32; // 2^5 seconds (means 5 max tries)
    
    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $cfg_defaults = array(
            'auth_type' => 'PLAIN',
            'bosh_hold' => null,
            'bosh_rid' => null,
            'bosh_url' => null,
            'bosh_wait' => null,
            'domain' => null,
            'force_tls' => false,
            'host' => null,
            'jid' => null,
            'log_colorize' => $this->log_colorize,
            'log_level' => $this->log_level,
            'log_path' => JAXLLogger::$path,
            'multi_client' => false,
            'pass' => false,
            'port' => null,
            'priv_dir' => getcwd().'/.jaxl',
            'protocol' => null,
            'resource' => null,
            'stream_context' => null,
            'strict' => true
        );
        $this->cfg = array_merge($cfg_defaults, $config);

        // setup logger
        JAXLLogger::$path = $this->cfg['log_path'];
        JAXLLogger::$level = $this->log_level = $this->cfg['log_level'];
        JAXLLogger::$colorize = $this->log_colorize = $this->cfg['log_colorize'];

        // env
        if ($this->cfg['strict']) {
            JAXLLogger::info("strict mode enabled, adding exception handlers. ' .
                'Set 'strict' => false inside JAXL config to disable this");
            JAXLException::addHandlers();
        }
        $this->mode = PHP_SAPI;
        $this->local_ip = gethostbyname(php_uname('n'));
        $this->pid = getmypid();

        // jid object
        $jid = ($this->cfg['jid'] !== null) ? new XMPPJid($this->cfg['jid']) : null;

        // handle signals
        if (extension_loaded('pcntl')) {
            pcntl_signal(SIGHUP, array($this, 'signal_handler'));
            pcntl_signal(SIGINT, array($this, 'signal_handler'));
            pcntl_signal(SIGTERM, array($this, 'signal_handler'));
        }

        // Create .jaxl directory for our /tmp, /run and /log folders
        // overwrite these using jaxl config array
        $this->priv_dir = $this->cfg['priv_dir'];
        $this->tmp_dir = $this->priv_dir."/tmp";
        $this->pid_dir = $this->priv_dir."/run";
        $this->log_dir = $this->priv_dir."/log";
        $this->sock_dir = $this->priv_dir."/sock";
        if (!is_dir($this->priv_dir)) {
            mkdir($this->priv_dir);
        }
        if (!is_dir($this->tmp_dir)) {
            mkdir($this->tmp_dir);
        }
        if (!is_dir($this->pid_dir)) {
            mkdir($this->pid_dir);
        }
        if (!is_dir($this->log_dir)) {
            mkdir($this->log_dir);
        }
        if (!is_dir($this->sock_dir)) {
            mkdir($this->sock_dir);
        }

        // touch pid file
        if ($this->mode == "cli") {
            touch($this->get_pid_file_path());
            JAXLLogger::info("created pid file ".$this->get_pid_file_path());
        }
        
        // include mandatory xmpp xeps
        // service discovery and entity caps
        // are recommended for every xmpp entity
        $this->require_xep(array('0030', '0115'));

        // do dns lookup, update $cfg['host'] and $cfg['port'] if not already specified
        if (($this->cfg['host'] === null || $this->cfg['port'] === null) && $jid) {
            // this dns lookup is blocking
            JAXLLogger::info("dns srv lookup for ".$jid->domain);
            list($host, $port) = JAXLUtil::get_dns_srv($jid->domain);
            if ($this->cfg['host'] === null) {
                $this->cfg['host'] = $host;
            }
            if ($this->cfg['port'] === null) {
                $this->cfg['port'] = $port;
            }
        }

        // choose appropriate transport
        // if 'bosh_url' cfg is defined include 0206
        if (isset($this->cfg['bosh_url'])) {
            JAXLLogger::debug("including bosh xep");
            $this->require_xep('0206');
            $transport = $this->xeps['0206'];
        } else {
            $transport = new JAXLSocketClient($this->cfg['stream_context']);
        }

        // lifecycle events callback
        $this->ev = new JAXLEvent($this->cfg['multi_client'] ? array(&$this) : array());

        // initialize xmpp stream with configured transport
        parent::__construct(
            $transport,
            $jid,
            $this->cfg['pass'],
            $this->cfg['resource'] !== null ? 'jaxl#'.$this->cfg['resource'] : 'jaxl#'.md5(time()),
            $this->cfg['force_tls']
        );
    }

    public function __destruct()
    {
        // delete pid file
        JAXLLogger::info("cleaning up pid and unix sock files");
        if (file_exists($this->get_pid_file_path())) {
            unlink($this->get_pid_file_path());
        }
        if (file_exists($this->get_sock_file_path())) {
            unlink($this->get_sock_file_path());
        }
        
        parent::__destruct();
    }

    public function get_pid_file_path()
    {
        return $this->pid_dir."/jaxl_".$this->pid.".pid";
    }

    /**
     * @return JAXLClientBase
     */
    public function getTransport()
    {
        return $this->trans;
    }

    public function get_sock_file_path()
    {
        return $this->sock_dir."/jaxl_".$this->pid.".sock";
    }
    
    /**
     * @param array $xeps
     */
    public function require_xep(array $xeps)
    {
        if (!is_array($xeps)) {
            $xeps = array($xeps);
        }
        
        foreach ($xeps as $xep) {
            $classname = 'XEP'.$xep;
            $this->xeps[$xep] = new $classname($this);

            // add necessary requested callback on events
            foreach ($this->xeps[$xep]->init() as $ev => $cb) {
                $this->add_cb($ev, array($this->xeps[$xep], $cb));
            }
        }
    }
    
    /**
     * Add callback.
     *
     * @see JAXLEvent::add
     *
     * @param string $ev Event to subscribe.
     * @param callable $cb
     * @param int $priority
     * @return string
     */
    public function add_cb($ev, $cb, $priority = 1)
    {
        return $this->ev->add($ev, $cb, $priority);
    }
    
    public function del_cb($ref)
    {
        $this->ev->del($ref);
    }
    
    public function set_status($status, $show = 'chat', $priority = 10)
    {
        $this->send($this->get_pres_pkt(
            array(),
            $status,
            $show,
            $priority
        ));
    }
    
    public function send_chat_msg($to, $body, $thread = null, $subject = null)
    {
        $msg = new XMPPMsg(
            array(
                'type'=>'chat',
                'to'=>$to,
                'from'=>$this->full_jid->to_string()
            ),
            $body,
            $thread,
            $subject
        );
        $this->send($msg);
    }
    
    public function get_vcard($jid = null, $cb = null)
    {
        $attrs = array(
            'type'=>'get',
            'from'=>$this->full_jid->to_string()
        );
        
        if ($jid) {
            $jid = new XMPPJid($jid);
            $attrs['to'] = $jid->node."@".$jid->domain;
        }
        
        $pkt = $this->get_iq_pkt(
            $attrs,
            new JAXLXml('vCard', 'vcard-temp')
        );
        if ($cb) {
            $this->add_cb('on_stanza_id_'.$pkt->id, $cb);
        }
        $this->send($pkt);
    }
    
    public function get_roster($cb = null)
    {
        $pkt = $this->get_iq_pkt(
            array(
                'type'=>'get',
                'from'=>$this->full_jid->to_string()
            ),
            new JAXLXml('query', 'jabber:iq:roster')
        );
        if ($cb) {
            $this->add_cb('on_stanza_id_'.$pkt->id, $cb);
        }
        $this->send($pkt);
    }
    
    public function subscribe($to)
    {
        $this->send($this->get_pres_pkt(
            array('to'=>$to, 'type'=>'subscribe')
        ));
    }
    
    public function subscribed($to)
    {
        $this->send($this->get_pres_pkt(
            array('to'=>$to, 'type'=>'subscribed')
        ));
    }
    
    public function unsubscribe($to)
    {
        $this->send($this->get_pres_pkt(
            array('to'=>$to, 'type'=>'unsubscribe')
        ));
    }
    
    public function unsubscribed($to)
    {
        $this->send($this->get_pres_pkt(
            array('to'=>$to, 'type'=>'unsubscribed')
        ));
    }
    
    public function get_socket_path()
    {
        if ($this->cfg['protocol'] !== null) {
            $protocol = $this->cfg['protocol'];
        } else {
            $protocol = ($this->cfg['port'] == 5223 ? "ssl" : "tcp");
        }
        return $protocol."://".$this->cfg['host'].":".$this->cfg['port'];
    }
    
    public function retry()
    {
        $retry_after = pow(2, $this->retry_attempt) * $this->retry_interval;
        $this->retry_attempt++;
        JAXLLogger::info("Will try to restart in ".$retry_after." seconds");
        
        // TODO: use jaxl cron if sigalarms cannnot be used
        sleep($retry_after);
        $this->start();
    }

    public function start(array $opts = array())
    {
        // is bosh bot?
        if (isset($this->cfg['bosh_url'])) {
            $this->trans->session_start();
            
            for (;;) {
                // while any of the curl request is pending
                // keep receiving response
                while (count($this->trans->chs) != 0) {
                    $this->trans->recv();
                }
                
                // if no request in queue, ping bosh end point
                // and repeat recv
                $this->trans->ping();
            }
            
            $this->trans->session_end();
            return;
        }
        
        // is xmpp client or component?
        // if on_connect event have no callbacks
        // set default on_connect callback to $this->start_stream()
        // i.e. xmpp client mode
        if (!$this->ev->exists('on_connect')) {
            $this->add_cb('on_connect', array($this, 'start_stream'));
        }
        
        // connect to the destination host/port
        if ($this->connect($this->get_socket_path())) {
            // reset in case we connected back after retries
            $this->retry_attempt = 0;
            
            // emit
            $this->ev->emit('on_connect');
            
            // parse opts
            if (isset($opts['--with-debug-shell']) && $opts['--with-debug-shell']) {
                $this->enable_debug_shell();
            }
            if (isset($opts['--with-unix-sock']) && $opts['--with-unix-sock']) {
                $this->enable_unix_sock();
            }
            
            // run main loop
            JAXLLoop::run();
            
            // emit
            $this->ev->emit('on_disconnect');
        } else {
            // If connection to the destination fails.
            if ($this->trans->errno == 61 ||
                $this->trans->errno == 110 ||
                $this->trans->errno == 111
            ) {
                JAXLLogger::debug("unable to connect with errno ".$this->trans->errno." (".$this->trans->errstr.")");
                $this->retry();
            } else {
                $this->ev->emit('on_connect_error', array(
                    $this->trans->errno,
                    $this->trans->errstr
                ));
            }
        }
    }
    
    //
    // callback methods
    //

    /**
     * Signals callback handler.
     *
     * Not for public API consumption.
     *
     * @param int $sig
     */
    public function signal_handler($sig)
    {
        $this->end_stream();
        $this->trans->disconnect();
        $this->ev->emit('on_disconnect');
        
        switch ($sig) {
            // terminal line hangup
            case SIGHUP:
                JAXLLogger::debug("got sighup");
                break;
                // interrupt program
            case SIGINT:
                JAXLLogger::debug("got sigint");
                break;
                // software termination signal
            case SIGTERM:
                JAXLLogger::debug("got sigterm");
                break;
        }
        
        exit;
    }
    
    // called internally for ipc
    // not for public consumption
    public function on_unix_sock_accept($_c, $addr)
    {
        $this->sock->read($_c);
    }
    
    // this currently simply evals the incoming raw string
    // know what you are doing while in production
    public function on_unix_sock_request($_c, $_raw)
    {
        JAXLLogger::debug("evaling raw string rcvd over unix sock: ".$_raw);
        $this->sock->send($_c, serialize(eval($_raw)));
        $this->sock->read($_c);
    }
    
    public function enable_unix_sock()
    {
        $this->sock = new JAXLSocketServer(
            'unix://'.$this->get_sock_file_path(),
            array(&$this, 'on_unix_sock_accept'),
            array(&$this, 'on_unix_sock_request')
        );
    }
    
    // this simply eval the incoming raw data
    // inside current jaxl environment
    // security is all upto you, no checks made here
    public function handle_debug_shell($_raw)
    {
        print_r(eval($_raw));
        echo PHP_EOL;
        JAXLCli::prompt();
    }
    
    protected function enable_debug_shell()
    {
        $this->cli = new JAXLCli(array(&$this, 'handle_debug_shell'));
        JAXLCli::prompt();
    }
    
    //
    // abstract method implementation
    //

    // someday this needs to go inside xmpp stream
    public function wait_for_cram_md5_response($event, $args)
    {
        switch ($event) {
            case "stanza_cb":
                $stanza = $args[0];
        
                if ($stanza->name == 'challenge' && $stanza->ns == XMPP::NS_SASL) {
                    $challenge = base64_decode($stanza->text);
                    $resp = new JAXLXml('response', XMPP::NS_SASL);
                    $resp->t(base64_encode($this->jid->to_string().' '.hash_hmac('md5', $challenge, $this->pass)));
                    $this->send($resp);
                    return "wait_for_sasl_response";
                } else {
                    JAXLLogger::debug("got unhandled sasl response, should never happen here");
                    exit;
                }
                break;
            default:
                JAXLLogger::debug("not catched $event, should never happen here");
                exit;
                break;
        }
    }
    
    // http://tools.ietf.org/html/rfc5802#section-5
    public function get_scram_sha1_response($pass, $challenge)
    {
        // it contains users iteration count i and the user salt
        // also server will append it's own nonce to the one we specified
        $decoded = $this->explode_data(base64_decode($challenge));
        
        // r=,s=,i=
        $nonce = $decoded['r'];
        $salt = base64_decode($decoded['s']);
        $iteration = intval($decoded['i']);
        
        // SaltedPassword  := Hi(Normalize(password), salt, i)
        $salted = JAXLUtil::pbkdf2($this->pass, $salt, $iteration);
        // ClientKey       := HMAC(SaltedPassword, "Client Key")
        $client_key = hash_hmac('sha1', $salted, "Client Key", true);
        // StoredKey       := H(ClientKey)
        $stored_key = hash('sha1', $client_key, true);
        // AuthMessage     := client-first-message-bare + "," + server-first-message + "," +
        //                    client-final-message-without-proof
        $auth_message = '';
        // ClientSignature := HMAC(StoredKey, AuthMessage)
        $signature = hash_hmac('sha1', $stored_key, $auth_message, true);
        // ClientProof     := ClientKey XOR ClientSignature
        $client_proof = $client_key ^ $signature;
        
        $proof = 'c=biws,r='.$nonce.',p='.base64_encode($client_proof);
        return base64_encode($proof);
    }
    
    public function wait_for_scram_sha1_response($event, $args)
    {
        switch ($event) {
            case "stanza_cb":
                $stanza = $args[0];
                
                if ($stanza->name == 'challenge' && $stanza->ns == XMPP::NS_SASL) {
                    $challenge = $stanza->text;
                    
                    $resp = new JAXLXml('response', XMPP::NS_SASL);
                    $resp->t($this->get_scram_sha1_response($this->pass, $challenge));
                    $this->send($resp);
                    
                    return "wait_for_sasl_response";
                } else {
                    JAXLLogger::debug("got unhandled sasl response, should never happen here");
                    exit;
                }
                break;
            default:
                JAXLLogger::debug("not catched $event, should never happen here");
                exit;
                break;
        }
    }
    
    public function handle_auth_mechs($stanza, $mechanisms)
    {
        if ($this->ev->exists('on_stream_features')) {
            return $this->ev->emit('on_stream_features', array($stanza));
        }
        
        // extract available mechanisms
        $mechs = array();
        if ($mechanisms) {
            foreach ($mechanisms->children as $mechanism) {
                $mechs[$mechanism->text] = true;
            }
        }

        $pref_auth = $this->cfg['auth_type'];

        // check if preferred auth type exists in available mechanisms
        if (isset($mechs[$pref_auth]) && $mechs[$pref_auth]) {
            JAXLLogger::debug("pref_auth ".$pref_auth." exists");
        } else {
            JAXLLogger::debug("pref_auth ".$pref_auth." doesn't exists");
            JAXLLogger::error("preferred auth type not supported, trying $pref_auth");
        }
        
        $this->send_auth_pkt(
            $pref_auth,
            isset($this->jid) ? $this->jid->to_string() : null,
            $this->pass
        );

        if ($pref_auth == 'CRAM-MD5') {
            return "wait_for_cram_md5_response";
        } elseif ($pref_auth == 'SCRAM-SHA-1') {
            return "wait_for_scram_sha1_response";
        }
    }
    
    public function handle_auth_success()
    {
        // if not a component
        /*if(!isset($this->xeps['0114'])) {
			$this->xeps['0030']->get_info($this->full_jid->domain, array(&$this, 'handle_domain_info'));
			$this->xeps['0030']->get_items($this->full_jid->domain, array(&$this, 'handle_domain_items'));
		}*/
        
        $this->ev->emit('on_auth_success');
    }

    /**
     * @param string $reason
     */
    public function handle_auth_failure($reason)
    {
        $this->ev->emit('on_auth_failure', array($reason));
    }

    public function handle_stream_start($stanza)
    {
        $stanza = new XMPPStanza($stanza);

        $this->ev->emit('on_stream_start', array($stanza));
        return array(isset($this->cfg['bosh_url']) ? 'wait_for_stream_features' : 'connected', 1);
    }

    public function handle_iq($stanza)
    {
        $stanza = new XMPPStanza($stanza);

        // emit callback registered on stanza id's
        $emited = false;
        if ($stanza->id && $this->ev->exists('on_stanza_id_'.$stanza->id)) {
            //JAXLLogger::debug("on stanza id callbackd");
            $emited = true;
            $this->ev->emit('on_stanza_id_'.$stanza->id, array($stanza));
        }
        
        // catch roster list
        if ($stanza->type == 'result' && ($query = $stanza->exists('query', 'jabber:iq:roster'))) {
            foreach ($query->children as $child) {
                if ($child->name == 'item') {
                    $jid = $child->attrs['jid'];
                    $subscription = $child->attrs['subscription'];
                    
                    $groups = array();
                    foreach ($child->children as $group) {
                        if ($group->name == 'group') {
                            $groups[] = $group->text;
                        }
                    }
                    
                    $this->roster[$jid] = new XMPPRosterItem($jid, $subscription, $groups);
                }
            }
            
            // emit this event if not emited above
            if (!$emited) {
                $this->ev->emit('on_roster_update');
            }
        }
        
        // if managing roster
        // catch contact vcard results
        if ($this->manage_roster && $stanza->type == 'result' && ($query = $stanza->exists('vCard', 'vcard-temp'))) {
            if (isset($this->roster[$stanza->from])) {
                $this->roster[$stanza->from]->vcard = $query;
            }
        }
        
        // on_get_iq, on_result_iq, and other events are only
        // emitted if on_stanza_id_{id} wasn't emitted above
        // TODO: can we add more checks here before calling back
        // e.g. checks on existence of an attribute, check on 1st level child ns and so on
        if (!$emited) {
            $this->ev->emit('on_'.$stanza->type.'_iq', array($stanza));
        }
    }
    
    public function handle_presence($stanza)
    {
        $stanza = new XMPPStanza($stanza);
        
        // if managing roster
        // catch available/unavailable type stanza
        if ($this->manage_roster) {
            $type = ($stanza->type ? $stanza->type : "available");
            $jid = new XMPPJid($stanza->from);
            
            if ($type == 'available') {
                $this->roster[$jid->bare]->resources[$jid->resource] = $stanza;
            } elseif ($type == 'unavailable') {
                if (isset($this->roster[$jid->bare]) && isset($this->roster[$jid->bare]->resources[$jid->resource])) {
                    unset($this->roster[$jid->bare]->resources[$jid->resource]);
                }
            }
        }
        
        // if managing subscription requests
        // we need to automate stuff here
        if ($stanza->type == "subscribe" && $this->manage_subscribe != "none") {
            $this->subscribed($stanza->from);
            if ($this->manage_subscribe == "mutual") {
                $this->subscribe($stanza->from);
            }
        }
        
        $this->ev->emit('on_presence_stanza', array($stanza));
    }
    
    public function handle_message($stanza)
    {
        $stanza = new XMPPStanza($stanza);
        $stanza->type = ($stanza->type ? $stanza->type : 'normal');
        $this->ev->emit('on_'.$stanza->type.'_message', array($stanza));
    }
    
    // unhandled event and arguments bubbled up
    // TODO: in a lot of cases this will be called, need more checks
    public function handle_other($event, $args)
    {
        $stanza = isset($args[0]) ? $args[0] : null;
        $stanza = new XMPPStanza($stanza);
        $ev = 'on_'.$stanza->name.'_stanza';
        if ($this->ev->exists($ev)) {
            return $this->ev->emit($ev, array($stanza));
        } else {
            JAXLLogger::warning("event '".$event."' catched in handle_other with stanza name ".$stanza->name);
        }
    }

    public function handle_domain_info($stanza)
    {
        $query = $stanza->exists('query', XEP0030::NS_DISCO_INFO);
        foreach ($query->children as $k => $child) {
            if ($child->name == 'identity') {
                //echo 'identity '.
                //    'category:' . (isset($child->attrs['category']) ? $child->attrs['category'] : 'NULL').
                //    ', type:'.(isset($child->attrs['type']) ? $child->attrs['type'] : 'NULL').
                //    ', name:'.(isset($child->attrs['name']) ? $child->attrs['name'] : 'NULL').PHP_EOL;
            } elseif ($child->name == 'x') {
                //echo 'x ns:'.$child->ns.PHP_EOL;
            } elseif ($child->name == 'feature') {
                //echo 'feature var:'.$child->attrs['var'].PHP_EOL;
            }
        }
    }

    public function handle_domain_items($stanza)
    {
        $query = $stanza->exists('query', XEP0030::NS_DISCO_ITEMS);
        foreach ($query->children as $k => $child) {
            if ($child->name == 'item') {
                //echo 'item '.
                //    'jid:'.(isset($child->attrs['jid']) ? $child->attrs['jid'] : 'NULL').
                //    ', name:'.(isset($child->attrs['name']) ? $child->attrs['name'] : 'NULL').
                //    ', node:'.(isset($child->attrs['node']) ? $child->attrs['node'] : 'NULL').PHP_EOL;
            }
        }
    }
}
