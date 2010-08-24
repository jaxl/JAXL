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
    
    // include required classes
    jaxl_require(array(
        'XMPPGet',
        'XMPPSend',
        'JAXLPlugin',
        'JAXLog'
    ));
    
    /*
     * Base XMPP class
    */
    class XMPP {
        
        /* User account related parameters */
        var $user = FALSE;
        var $pass = FALSE;
        var $host = FALSE;
        var $port = FALSE;
        var $jid = FALSE;
        var $domain = FALSE;
        var $resource = FALSE;  
        
        /* User session related parameters */
        var $auth = FALSE;
        var $isConnected = FALSE;
        var $sessionRequired = FALSE;
        var $secondChallenge = FALSE;
        var $lastid = 0;
        
        /* Socket stream related parameters */
        var $stream = FALSE;
        var $streamId = FALSE;
        var $streamHost = FALSE;
        var $streamVersion = FALSE;
        var $streamENum = FALSE;
        var $streamEStr = FALSE;
        var $streamTimeout = FALSE;
        var $streamBlocking = 0;
        
        /* XMPP working parameter */
        var $buffer = '';
        var $lastSendTime = FALSE;
        
        function __construct($config) {
            /* Parse configuration parameter */
            $this->user = isset($config['user']) ? $config['user'] : JAXL_USER_NAME;
            $this->pass = isset($config['pass']) ? $config['pass'] : JAXL_USER_PASS;
            $this->host = isset($config['host']) ? $config['host'] : JAXL_HOST_NAME;
            $this->port = isset($config['port']) ? $config['port'] : JAXL_HOST_PORT;
            $this->domain = isset($config['domain']) ? $config['domain'] : JAXL_HOST_DOMAIN;
            $this->streamTimeout = isset($config['streamTimeout']) ? $config['streamTimeout'] : JAXL_STREAM_TIMEOUT;
            $this->resource = isset($config['resource']) ? $config['resource'] : "jaxl.".time();    
        }
        
        function connect() {
            if(!$this->stream) {
                if($this->stream = @fsockopen($this->host, $this->port, $this->streamENum, $this->streamEStr, $this->streamTimeout)) {
                    JAXLog::log("Socket opened to the jabber host ".$this->host.":".$this->port." ...", 0);
                    stream_set_blocking($this->stream, $this->streamBlocking);
                    stream_set_timeout($this->stream, $this->streamTimeout);
                }
                else {
                    JAXLog::log("Unable to open socket to the jabber host ".$this->host.":".$this->port." ...", 0);
                }
            }
            else {
                JAXLog::log("Socket already opened to the jabber host ".$this->host.":".$this->port." ...", 0);
            }
            
            JAXLPlugin::execute('jaxl_post_connect');
            if($this->stream) return TRUE;
            else return FALSE;
        }
        
        function startStream() {
            return XMPPSend::startStream();
        }
        
        function startSession() {
            $payload = '';
            $payload .= '<session xmlns="urn:ietf:params:xml:ns:xmpp-session"/>';   
            return XMPPSend::iq("set", $payload, $this->domain, FALSE, array('XMPPGet', 'postSession'));
        }
        
        function startBind() {
            $payload = '';
            $payload .= '<bind xmlns="urn:ietf:params:xml:ns:xmpp-bind">';
            $payload .= '<resource>'.$this->resource.'</resource>';
            $payload .= '</bind>';
            return XMPPSend::iq("set", $payload, FALSE, FALSE, array('XMPPGet', 'postBind'));
        }
        
        function getXML() {
            return XMPPGet::xml();
        }
        
        function getId() {
            return ++$this->lastid;
        }
        
    }

?>
