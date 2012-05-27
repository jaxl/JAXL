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
define('JAXL_CWD', dirname(__FILE__));

require_once JAXL_CWD.'/xmpp/xmpp_stream.php';
require_once JAXL_CWD.'/core/jaxl_event.php';
require_once JAXL_CWD.'/core/jaxl_logger.php';

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
class JAXL extends XMPPStream {
	
	// lib meta info
	const version = '3.0.0-alpha-1';
	const name = 'JAXL :: Jabber XMPP Library';
	
	// cached init config array
	public $cfg = array();
	
	// event callback engine for xmpp stream lifecycle
	protected $ev = null;
	
	// reference to various xep instance objects
	protected $xeps = array();
	
	// local cache of roster list
	public $roster = array();
	
	// whether jaxl must also populate local roster cache with
	// received presence information about the contacts
	public $manager_roster = true;
	
	// what to do with presence sub requests
	// "none" | "accept" | "accept_and_add"
	public $subscription = "none";
	
	// path variables
	public $log_level = 4;
	public $tmp_dir;
	public $log_dir;
	public $pid_dir;
	
	// env
	public $local_ip;
	public $pid;
	public $mode;
	
	// periodically dump stats
	public $dump_stats = true;
	
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
	
	public function __construct($config) {
		// handle signals
		if(extension_loaded('pcntl')) {
			pcntl_signal(SIGHUP, array($this, 'signal_handler'));
			pcntl_signal(SIGINT, array($this, 'signal_handler'));
			pcntl_signal(SIGTERM, array($this, 'signal_handler'));
		}
		
		// TODO: check permissions and existence
		$this->tmp_dir = JAXL_CWD."/priv/tmp";
		$this->pid_dir = JAXL_CWD."/priv/run";
		$this->log_dir = JAXL_CWD."/priv/log";
		
		// touch pid file
		$this->pid = getmypid();
		touch($this->pid_dir."/jaxl_".$this->pid.".pid");
		
		$this->mode = PHP_SAPI;
		$this->local_ip = gethostbyname(php_uname('n'));
		
		// setup logger
		JAXLLogger::$path = $this->log_dir."/jaxl.log";
		JAXLLogger::$level = $this->log_level;
		
		// initialize event api
		$this->ev = new JAXLEvent();
		
		// save config
		$this->cfg = $config;
		$jid = new XMPPJid($this->cfg['jid']);
		
		// include mandatory xmpp xeps
		// service discovery and entity caps
		$this->require_xep(array('0030', '0115'));
		
		// do dns lookup, update $cfg
		// if not already specified
		$host = @$this->cfg['host']; $port = @$this->cfg['port'];
		if(!$host && !$port) list($host, $port) = JAXLUtil::get_dns_srv($jid->domain);
		$this->cfg['host'] = $host; $this->cfg['port'] = $port;
		
		// if 'bosh_url' cfg is defined include 0206
		if(@$this->cfg['bosh_url']) {
			$this->require_xep('0206');
			$transport = $this->xeps['0206'];
		}
		else {
			list($host, $port) = JAXLUtil::get_dns_srv($jid->domain);
			$transport = new JAXLSocket($host, $port);
		}
		
		// initialize xmpp stream with configured transport
		parent::__construct(
			$transport,
			$jid,
			@$this->cfg['pass'],
			@$this->cfg['resource'] ? 'jaxl.'.$this->cfg['resource'] : 'jaxl.'.md5(time()),
			@$this->cfg['force_tls']
		);
	}
	
	public function __destruct() {
		// delete pid file
		unlink($this->pid_dir."/jaxl_".$this->pid.".pid");
		
		parent::__destruct();
	}
	
	public function signal_handler($sig) {
		$this->end_stream();
		
		switch($sig) {
			// terminal line hangup
			case SIGHUP:
				_debug("got sighup");
				break;
				// interrupt program
			case SIGINT:
				_debug("got sigint");
				break;
				// software termination signal
			case SIGTERM:
				_debug("got sigterm");
				break;
		}
	}
	
	public function require_xep($xeps) {
		if(!is_array($xeps)) 
			$xeps = array($xeps);
		
		foreach($xeps as $xep) {
			$filename = 'xep_'.$xep.'.php';
			$classname = 'XEP_'.$xep;
			
			// include xep
			require_once JAXL_CWD.'/xep/'.$filename;
			$this->xeps[$xep] = new $classname($this);
			
			// add necessary requested callback on events
			foreach($this->xeps[$xep]->init() as $ev=>$cb) {
				$this->add_cb($ev, array($this->xeps[$xep], $cb));
			}
		}
	}
	
	public function add_cb($ev, $cb, $pri=1) {
		return $this->ev->add($ev, $cb, $pri);
	}
	
	public function del_cb($ref) {
		$this->ev->del($ref);
	}
	
	public function set_status($status, $show, $priority) {
		$this->send($this->get_pres_pkt(
			array(),
			$status,
			$show,
			$priority
		));
	}
	
	public function send_chat_msg($to, $body) {
		$msg = new XMPPMsg(array('type'=>'chat', 'to'=>$to, 'from'=>$this->full_jid->to_string()), $body);
		$this->send($msg);
	}
	
	public function start() {
		// is bosh bot?
		if(@$this->cfg['bosh_url'] && $this->mode == 'cli') {
			$this->trans->session_start();
			
			for(;;) {
				// while any of the curl request is pending
				// keep receiving response
				while(sizeof($this->trans->chs) != 0) {
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
		if(!isset($this->ev->reg['on_connect']))
			$this->add_cb('on_connect', array($this, 'start_stream'));
		
		// start
		if($this->connect(@$this->cfg['host'], @$this->cfg['port'])) {
			$this->ev->emit('on_connect');
			
			while($this->trans->fd) {
				$this->trans->recv();
			}
			
			$this->ev->emit('on_disconnect');
		}
		else {
			if($this->trans->errno == 61 
			|| $this->trans->errno == 110 
			|| $this->trans->errno == 111
			) {
				$retry_after = pow(2, $this->retry_attempt) * $this->retry_interval;
				$this->retry_attempt++;
				
				_debug("unable to connect with errno ".$this->trans->errno." (".$this->trans->errstr."), will try again in ".$retry_after." seconds");
				
				// TODO: use sigalrm instead (if possible)
				sleep($retry_after);
				$this->start();
			}
			else {
				$this->ev->emit('on_connect_error', array(
					$this->trans->errno,
					$this->trans->errstr
				));
			}
		}
	}
	
	//
	// abstract method implementation
	//
	
	protected function send_fb_challenge_response($challenge) {
		$this->send($this->get_fb_challenge_response_pkt($challenge));
	}
	
	// refer https://developers.facebook.com/docs/chat/#jabber
	public function get_fb_challenge_response_pkt($challenge) {
		$stanza = new JAXLXml('response', NS_SASL);
		
		$challenge = base64_decode($challenge);
		$challenge = urldecode($challenge);
		parse_str($challenge, $challenge_arr);
		
		$response = http_build_query(array(
			'method' => $challenge_arr['method'],
			'nonce' => $challenge_arr['nonce'],
			'access_token' => $this->cfg['fb_access_token'],
			'api_key' => $this->cfg['fb_app_key'],
			'call_id' => 0,
			'v' => '1.0',
		));
		
		$stanza->t(base64_encode($response));
		return $stanza;
	}
	
	public function wait_for_fb_sasl_response($event, $args) {
		switch($event) {
			case "stanza_cb":
				$stanza = $args[0];
				
				if($stanza->name == 'challenge' && $stanza->ns == NS_SASL) {
					$challenge = $stanza->text;
					$this->send_fb_challenge_response($challenge);
					return "wait_for_sasl_response";
				}
				else {
					_debug("got unhandled sasl response, should never happen here");
					exit;
				}
				break;
			default:
				_debug("not catched $event, should never happen here");
				exit;
				break;
		}
	}
	
	public function handle_auth_mechs($mechs) {
		$pref_auth = @$this->cfg['auth_type'] ? $this->cfg['auth_type'] : 'PLAIN';
		$pref_auth_exists = isset($mechs[$pref_auth]) ? true : false;
		
		if($pref_auth_exists) {
			$this->send_auth_pkt($pref_auth, $this->jid->to_string(), $this->pass);
			if($pref_auth == 'X-FACEBOOK-PLATFORM') {
				return "wait_for_fb_sasl_response";
			}
		}
		else {
			_error("preferred auth type not supported");
		}
	}
	
	public function handle_auth_success() {
		$this->ev->emit('on_auth_success');
	}
	
	public function handle_auth_failure($reason) {
		$this->ev->emit('on_auth_failure', array(
			$reason
		));
	}
	
	public function handle_stream_start($stanza) {
		$stanza = new XMPPStanza($stanza);
		
		$this->ev->emit('on_stream_start', array($stanza));
		return array(@$this->cfg['bosh_url'] ? 'wait_for_stream_features' : 'connected', 1);
	}
	
	public function handle_iq($stanza) {
		$stanza = new XMPPStanza($stanza);
	}
	
	public function handle_presence($stanza) {
		$stanza = new XMPPStanza($stanza);
	}
	
	public function handle_message($stanza) {
		$stanza = new XMPPStanza($stanza);
		$this->ev->emit('on_'.$stanza->type.'_message', array($stanza));
	}
	
	// unhandled event and arguments bubbled up
	// TODO: in a lot of cases this will be called, need more checks
	public function handle_other($event, $args) {
		$stanza = $args[0];
		$stanza = new XMPPStanza($stanza);
		return $this->ev->emit('on_'.$stanza->name.'_stanza', array($stanza));
	}
	
}

?>