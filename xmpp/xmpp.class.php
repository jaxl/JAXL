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
        'JAXLPlugin',
        'JAXLog',
        'XMPPGet',
        'XMPPSend'
    ));
    
    /*
     * Base XMPP class
    */
    class XMPP {
        
        /* User account related parameters */
        var $user = false;
        var $pass = false;
        var $host = false;
        var $port = false;
        var $jid = false;
        var $domain = false;
        var $resource = false;
        var $component = false;
        
        /* User session related parameters */
        var $auth = false;
        var $isConnected = false;
        var $sessionRequired = false;
        var $secondChallenge = false;
        var $lastid = 0;
        
        /* Socket stream related parameters */
        var $stream = false;
        var $streamId = false;
        var $streamHost = false;
        var $streamVersion = false;
        var $streamENum = false;
        var $streamEStr = false;
        var $streamTimeout = false;
        var $streamBlocking = 0;
        
        /* XMPP working parameter */
        var $buffer = '';
        var $obuffer = '';
        var $clock = false;
        var $clocked = false;
        var $rateLimit = true;
        var $lastSendTime = false;
        
        function __construct($config) {
            $this->clock = 0;
            $this->clocked = time();
            
            /* Parse configuration parameter */
            $this->user = isset($config['user']) ? $config['user'] : JAXL_USER_NAME;
            $this->pass = isset($config['pass']) ? $config['pass'] : JAXL_USER_PASS;
            $this->host = isset($config['host']) ? $config['host'] : JAXL_HOST_NAME;
            $this->port = isset($config['port']) ? $config['port'] : JAXL_HOST_PORT;
            $this->domain = isset($config['domain']) ? $config['domain'] : JAXL_HOST_DOMAIN;
            $this->streamTimeout = isset($config['streamTimeout']) ? $config['streamTimeout'] : JAXL_STREAM_TIMEOUT;
            $this->resource = isset($config['resource']) ? $config['resource'] : "jaxl.".time();
            $this->component = isset($config['component']) ? $config['component'] : JAXL_COMPONENT_HOST;
            $this->rateLimit = isset($config['rateLimit']) ? $config['rateLimit'] : true;
        }
        
        function connect() {
            if(!$this->stream) {
                if($this->stream = @fsockopen($this->host, $this->port, $this->streamENum, $this->streamEStr, $this->streamTimeout)) {
                    JAXLog::log("Socket opened to the jabber host ".$this->host.":".$this->port." ...", 0, $this);
                    stream_set_blocking($this->stream, $this->streamBlocking);
                    stream_set_timeout($this->stream, $this->streamTimeout);
                }
                else {
                    JAXLog::log("Unable to open socket to the jabber host ".$this->host.":".$this->port." ...", 0, $this);
                }
            }
            else {
                JAXLog::log("Socket already opened to the jabber host ".$this->host.":".$this->port." ...", 0, $this);
            }
            
            JAXLPlugin::execute('jaxl_post_connect', false, $this);
            if($this->stream) return true;
            else return false;
        }
        
        function startStream() {
            return XMPPSend::startStream($this);
        }
        
        function startSession() {
            $payload = '';
            $payload .= '<session xmlns="urn:ietf:params:xml:ns:xmpp-session"/>';   
            return XMPPSend::iq($this, "set", $payload, $this->domain, false, array('XMPPGet', 'postSession'));
        }
        
        function startBind() {
            $payload = '';
            $payload .= '<bind xmlns="urn:ietf:params:xml:ns:xmpp-bind">';
            $payload .= '<resource>'.$this->resource.'</resource>';
            $payload .= '</bind>';
            return XMPPSend::iq($this, "set", $payload, false, false, array('XMPPGet', 'postBind'));
        }
        
        function getId() {
            return ++$this->lastid;
        }
        
        function getXML($nap=TRUE) {
            // sleep between two reads
            if($nap) sleep(JAXL_XMPP_GET_SLEEP);
            
            // initialize empty lines read
            $emptyLine = 0;
            
            // read previous buffer
            $payload = $this->buffer;
            $this->buffer = '';
            
            // read socket data
            for($i=0; $i<JAXL_XMPP_GET_PCKTS; $i++) {
                if($this->stream) {
                    $line = fread($this->stream, JAXL_XMPP_GET_PCKT_SIZE);
                    if(strlen($line) == 0) {
                        $emptyLine++;
                        if($emptyLine > JAXL_XMPP_GET_EMPTY_LINES)
                            break;
                    }
                    else {
                        $payload .= $line;
                    }
                }
            }
            
            // update clock
            $now = time();
            $this->clock += $now-$this->clocked;
            $this->clocked = $now;

            // trim read data
            $payload = trim($payload);
            $payload = JAXLPlugin::execute('jaxl_get_xml', $payload, $this);
            if($payload != '') $this->handler($payload);
            
            // flush obuffer
            if($this->obuffer != '') {
                $payload = $this->obuffer;
                $this->obuffer = '';
                $this->_sendXML($payload);
            }
        }
        
        function sendXML($xml) {
            $xml = JAXLPlugin::execute('jaxl_send_xml', $xml, $this);
            
            if($this->mode == "cgi") {
                JAXLPlugin::execute('jaxl_send_body', $xml, $this);
            }
            else {
                if($this->rateLimit
                && $this->lastSendTime
                && JAXLUtil::getTime() - $this->lastSendTime < JAXL_XMPP_SEND_RATE
                ) { $this->obuffer .= $xml; }
                else {
                    $xml = $this->obuffer.$xml;
                    $this->obuffer = '';
                    return $this->_sendXML($xml);
                }
            }    
        }

        function _sendXML($xml) {
            if($this->stream) {
                $this->lastSendTime = JAXLUtil::getTime();
                if(($ret = fwrite($this->stream, $xml)) !== false) JAXLog::log("[[XMPPSend]] $ret\n".$xml, 4, $this);
                else JAXLog::log("[[XMPPSend]] Failed\n".$xml, 1, $this);  
                return $ret;
            }
            else {
                JAXLog::log("Jaxl stream not connected to jabber host, unable to send xmpp payload...", 1, $this);
                return false;
            }
        }
        
        function handler($payload) {
            JAXLog::log("[[XMPPGet]] \n".$payload, 4, $this);
            
            $buffer = array();
            $payload = JAXLPlugin::execute('jaxl_pre_handler', $payload, $this);
            
            $xmls = JAXLUtil::splitXML($payload);
            $pktCnt = count($xmls);
            
            foreach($xmls as $pktNo => $xml) {  
                if($pktNo == $pktCnt-1) {
                    if(substr($xml, -1, 1) != '>') {
                        $this->buffer = $xml;
                        break;
                    }
                }
                
                if(substr($xml, 0, 7) == '<stream') 
                    $arr = $this->xml->xmlize($xml);
                else 
                    $arr = JAXLXml::parse($xml);
                
                switch(true) {
                    case isset($arr['stream:stream']):
                        XMPPGet::streamStream($arr['stream:stream'], $this);
                        break;
                    case isset($arr['stream:features']):
                        XMPPGet::streamFeatures($arr['stream:features'], $this);
                        break;
                    case isset($arr['stream:error']):
                        XMPPGet::streamError($arr['stream:error'], $this);
                        break;
                    case isset($arr['failure']);
                        XMPPGet::failure($arr['failure'], $this);
                        break;
                    case isset($arr['proceed']):
                        XMPPGet::proceed($arr['proceed'], $this);
                        break;
                    case isset($arr['challenge']):
                        XMPPGet::challenge($arr['challenge'], $this);
                        break;
                    case isset($arr['success']):
                        XMPPGet::success($arr['success'], $this);
                        break;
                    case isset($arr['presence']):
                        $buffer['presence'][] = $arr['presence'];
                        break;
                    case isset($arr['message']):
                        $buffer['message'][] = $arr['message'];
                        break;
                    case isset($arr['iq']):
                        XMPPGet::iq($arr['iq'], $this);
                        break;
                    default:
                        print "Unrecognized payload received from jabber server...";
                        break;
                }
            }
            
            if(isset($buffer['presence'])) XMPPGet::presence($buffer['presence'], $this);
            if(isset($buffer['message'])) XMPPGet::message($buffer['message'], $this);
            unset($buffer);
            
            JAXLPlugin::execute('jaxl_post_handler', $payload, $this);
        }

    }

?>
