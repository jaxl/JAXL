<?php
/* Jaxl (Jabber XMPP Library)
 *
 * Copyright (c) 2009-2010, Abhinav Singh <me@abhinavsingh.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Abhinav Singh nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
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
 */

    /*
     * include core, xmpp base
     * (basic requirements for every Jaxl instance)
    */
    jaxl_require(array(
        'JAXLog',
        'JAXLUtil',
        'JAXLPlugin',
        'JAXLCron',
        'XML',
        'XMPP',
    ));
    
    /*
     * Jaxl Core Class extending Base XMPP Class
    */
    class JAXL extends XMPP {
        
        var $logLevel = 1;
        var $logRotate = false;
        var $logPath = '/var/log/jaxl.log';
        var $pidPath = '/var/run/jaxl.pid';
        var $boshHost = false;
        var $boshPort = false;
        var $boshSuffix = false;
        var $sigh = true;
        var $dumpStat = 300;

        var $pid = false;
        var $mode = false;
        var $action = false;
        var $authType = false;
        var $features = array();
        
        /*
         * Constructor accepts following configuration parameters
         * Passed param will overwrite corresponding jaxl.ini values
         *
         * $config = array(
         *  'user'=>'', // JAXL_USER_NAME
         *  'pass'=>'', // JAXL_USER_PASS
         *  'host'=>'', // JAXL_HOST_NAME
         *  'port'=>'', // JAXL_HOST_PORT
         *  'domain'=>'', // JAXL_HOST_DOMAIN
         *  'component'=>'', // JAXL_COMPONENT_HOST
         *  'logPath'=>'', // JAXL_LOG_PATH
         *  'logRotate'=>'', // false or second in which to rotate log
         *  'logLevel'=>'', // JAXL_LOG_LEVEL
         *  'pidPath'=>'', // JAXL_PID_PATH
         *  'boshHost'=>'', // JAXL_BOSH_HOST
         *  'boshPort'=>'', // JAXL_BOSH_PORT
         *  'boshSuffix'=>'', // JAXL_BOSH_SUFFIX
         *  'resource'=>'', // connecting user resource identifier
         *  'streamTimeout'=>'', // connecting stream timeout
         *  'sigh'=>'', // boolean to forcible enable/disable sigh term
         *  'dumpStat'=>'', // false or numeric specifying second after which to dump stat
         * );
        */
        function __construct($config=array()) {
            /* Parse configuration parameter */
            $this->logLevel = isset($config['logLevel']) ? $config['logLevel'] : JAXL_LOG_LEVEL;
            $this->logPath = isset($config['logPath']) ? $config['logPath'] : JAXL_LOG_PATH;
            $this->logRotate = isset($config['logRotate']) ? $config['logRotate'] : false;
            $this->pidPath = isset($config['pidPath']) ? $config['pidPath'] : JAXL_PID_PATH;
            $this->boshHost = isset($config['boshHost']) ? $config['boshHost'] : JAXL_BOSH_HOST;
            $this->boshPort = isset($config['boshPort']) ? $config['boshPort'] : JAXL_BOSH_PORT;
            $this->boshSuffix = isset($config['boshSuffix']) ? $config['boshSuffix'] : JAXL_BOSH_SUFFIX;
            $this->sigh = isset($config['sigh']) ? $config['sigh'] : true;
            $this->dumpStat = isset($config['dumpStat']) ? $config['dumpStat'] : 300;
            
            $this->configure($config);
            parent::__construct($config);
            $this->xml = new XML();
            
            JAXLCron::init();
            if($this->dumpStat) JAXLCron::add(array($this, 'dumpStat'), $this->dumpStat);
            if($this->logRotate) JAXLCron::add(array('JAXLog', 'logRotate'), $this->logRotate);
        }

        /*
         * Configures Jaxl instance to run across various systems
        */
        protected function configure($config) {
            $this->pid = getmypid();
            $this->mode = isset($_REQUEST['jaxl']) ? "cgi" : "cli";
            
            if(!JAXLUtil::isWin() && JAXLUtil::pcntlEnabled() && $this->sigh) {
                pcntl_signal(SIGTERM, array($this, "shutdown"));
                pcntl_signal(SIGINT, array($this, "shutdown"));
                JAXLog::log("Registering shutdown for SIGH Terms ...", 0, $this);
            }
            
            if(JAXLUtil::sslEnabled()) {
                JAXLog::log("Openssl enabled ...", 0, $this);
            }
              
            if($this->mode == "cli") {
                if(!function_exists('fsockopen')) die("Jaxl requires fsockopen method ...");  
                file_put_contents($this->pidPath, $this->pid);
            }
            
            if($this->mode == "cgi") {
                if(!function_exists('curl_init')) die("Jaxl requires curl_init method ...");
            }

            // include service discovery XEP, recommended for every IM client
            jaxl_require('JAXL0030', $this, array(
                'category'=>'client',
                'type'=>'bot',
                'name'=>JAXL_NAME,
                'lang'=>'en'
            ));
        }
       
        // periodically dumps jaxl instance usage stats
        function dumpStat() {
            $this->log("Memory usage: ".round(memory_get_usage()/pow(1024,2), 2)." Mb, peak: ".round(memory_get_peak_usage()/pow(1024,2), 2)." Mb", 0);
        }
       
        /************************************/
        /*** User space available methods ***/
        /************************************/

        function shutdown($signal) {
            JAXLog::log("Jaxl Shutting down ...", 0, $this);
            JAXLPlugin::execute('jaxl_pre_shutdown', $signal, $this);
            
            if($this->stream) XMPPSend::endStream($this);
            $this->stream = false;
        }
        
        function auth($type) {
            $this->authType = $type;
            return XMPPSend::startAuth($this);
        }
        
        function setStatus($status=false, $show=false, $priority=false, $caps=false) {
            $child = array();
            $child['status'] = ($status === false ? 'Online using Jaxl library http://code.google.com/p/jaxl' : $status);
            $child['show'] = ($show === false ? 'chat' : $show);
            $child['priority'] = ($priority === false ? 1 : $priority);
            if($caps) $child['payload'] = JAXL0115::getCaps($this->features);
            return XMPPSend::presence($this, false, false, $child, false);
        }
        
        function subscribe($toJid) {
            return XMPPSend::presence($this, $toJid, false, false, 'subscribe');
        }
        
        function subscribed($toJid) {
            return XMPPSend::presence($this, $toJid, false, false, 'subscribed');
        }
        
        function unsubscribe($toJid) {
            return XMPPSend::presence($this, $toJid, false, false, 'unsubscribe');
        }
        
        function unsubscribed($toJid) {
            return XMPPSend::presence($this, $toJid, false, false, 'unsubscribed');
        }
        
        function getRosterList($callback=false) {
            $payload = '<query xmlns="jabber:iq:roster"/>';
            return XMPPSend::iq($this, "get", $payload, false, $this->jid, $callback);
        }
        
        function addRoster($jid, $group, $name=false) {
            $payload = '<query xmlns="jabber:iq:roster">';
            $payload .= '<item jid="'.$jid.'"';
            if($name) $payload .= ' name="'.$name.'"';
            $payload .= '>';    
            $payload .= '<group>'.$group.'</group>';
            $payload .= '</item>';
            $payload .= '</query>';
            return XMPPSend::iq($this, "set", $payload, false, $this->jid, false);
        }
        
        function updateRoster($jid, $group, $name=false, $subscription=false) {
            $payload = '<query xmlns="jabber:iq:roster">';
            $payload .= '<item jid="'.$jid.'"';
            if($name) $payload .= ' name="'.$name.'"';
            if($subscription) $payload .= ' subscription="'.$subscription.'"';
            $payload .= '>';
            $payload .= '<group>'.$group.'</group>';
            $payload .= '</item>';
            $payload .= '</query>';
            return XMPPSend::iq($this, "set", $payload, false, $this->jid, false);
        }
        
        function deleteRoster($jid) {
            $payload = '<query xmlns="jabber:iq:roster">';
            $payload .= '<item jid="'.$jid.'" subscription="remove">';
            $payload .= '</item>';
            $payload .= '</query>';
            return XMPPSend::iq($this, "set", $payload, false, $this->jid, false);
        }
        
        function sendMessage($to, $message, $from=false, $type='chat') {
            $child = array();
            $child['body'] = $message;
            return XMPPSend::message($this, $to, $from, $child, $type);
        }
        
        function sendMessages($to, $from, $child, $type) {
            return XMPPSend::message($this, $to, $from, $child, $type);
        }

        function sendPresence($to, $from, $child, $type) {
           XMPPSend::presence($this, $to, $from, $child, $type);
        }

        function log($log, $level) {
            JAXLog::log($log, $level, $this);
        }

        function requires($class) {
            jaxl_require($class, $this);
        }

        function __call($xep, $param) {
            $param[] = $this;
            $method = array_shift($param);
            if(substr($xep, 0, 4) == 'JAXL') {
                $xep = substr($xep, 4, 4);
                if(is_numeric($xep)
                && class_exists('JAXL'.$xep)
                ) { return call_user_func_array(array('JAXL'.$xep, $method), $param); }
            }
        }
        
    }

?>
