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
    
    session_set_cookie_params(
        JAXL_BOSH_COOKIE_TTL,
        JAXL_BOSH_COOKIE_PATH,
        JAXL_BOSH_COOKIE_DOMAIN,
        JAXL_BOSH_COOKIE_HTTPS,
        JAXL_BOSH_COOKIE_HTTP_ONLY
    );
    session_start();

    /*
     * XEP-0124: Bosh Implementation
     * Maintain various attributes like rid, sid across requests
    */
    class JAXL0124 {
        
        private static $buffer = array();
        private static $sess = false;
        public static $ns = '';
        
        public static function init($jaxl) {
            JAXLPlugin::add('jaxl_post_bind', array('JAXL0124', 'postBind'));
            JAXLPlugin::add('jaxl_send_xml', array('JAXL0124', 'wrapBody'));
            JAXLPlugin::add('jaxl_pre_handler', array('JAXL0124', 'preHandler'));
            JAXLPlugin::add('jaxl_post_handler', array('JAXL0124', 'postHandler'));
            JAXLPlugin::add('jaxl_get_body', array('JAXL0124', 'processBody'));
            JAXLPlugin::add('jaxl_pre_curl', array('JAXL0124', 'saveSession'));
            JAXLPlugin::add('jaxl_send_body', array('JAXL0124', 'sendBody'));   
            
            self::setEnv($jaxl);
            self::loadSession($jaxl);
        }
        
        public static function postHandler($payload, $jaxl) {
            $payload = json_encode(self::$buffer);
            JAXLog::log("[[BoshOut]]\n".$payload, 5, $jaxl);
            header('Content-type: application/json');
            echo $payload;
            exit;
        }
        
        public static function postBind($jaxl) {
            $jaxl->bosh['jid'] = $jaxl->jid;
            $_SESSION['auth'] = true;
            return;
        }
        
        public static function out($payload) {
            self::$buffer[] = $payload;
        }
        
        public static function setEnv($jaxl) {
            $jaxl->bosh = array();
            $jaxl->bosh['hold'] = "1";
            $jaxl->bosh['wait'] = "30";
            $jaxl->bosh['polling'] = "0";
            $jaxl->bosh['version'] = "1.6";
            $jaxl->bosh['xmppversion'] = "1.0";
            $jaxl->bosh['secure'] = "true";
            $jaxl->bosh['xmlns'] = "http://jabber.org/protocol/httpbind";
            $jaxl->bosh['xmlnsxmpp'] = "urn:xmpp:xbosh";
            $jaxl->bosh['content'] = "text/xml; charset=utf-8";
            $jaxl->bosh['url'] = "http://".$jaxl->boshHost.":".$jaxl->boshPort."/".$jaxl->boshSuffix."/";
            $jaxl->bosh['headers'] = array("Accept-Encoding: gzip, deflate","Content-Type: text/xml; charset=utf-8");
        }
        
        public static function loadSession($jaxl) {
            $jaxl->bosh['rid'] = isset($_SESSION['rid']) ? (string) $_SESSION['rid'] : rand(1000, 10000);
            $jaxl->bosh['sid'] = isset($_SESSION['sid']) ? (string) $_SESSION['sid'] : false;
            $jaxl->lastid = isset($_SESSION['id']) ? $_SESSION['id'] : $jaxl->lastid;
            $jaxl->jid = isset($_SESSION['jid']) ? $_SESSION['jid'] : $jaxl->jid;
            JAXLog::log("Loading session data\n".json_encode($_SESSION), 5, $jaxl);
        }
        
        public static function saveSession($xml, $jaxl) {
            if($_SESSION['auth']) {
                $_SESSION['rid'] = isset($jaxl->bosh['rid']) ? $jaxl->bosh['rid'] : false;
                $_SESSION['sid'] = isset($jaxl->bosh['sid']) ? $jaxl->bosh['sid'] : false;
                $_SESSION['jid'] = $jaxl->jid;
                $_SESSION['id'] = $jaxl->lastid;
                
                session_write_close();
                
                if(self::$sess) { // session already closed?
                    list($body, $xml) = self::unwrapBody($xml);
                    JAXLog::log("[[".$jaxl->action."]] Auth complete, sync now\n".json_encode($_SESSION), 5, $jaxl);
                    return self::out(array('jaxl'=>'jaxl', 'xml'=>urlencode($xml)));
                }
                else {
                    self::$sess = true;
                    JAXLog::log("[[".$jaxl->action."]] Auth complete, commiting session now\n".json_encode($_SESSION), 5, $jaxl);
                }
            }
            else {
                JAXLog::log("[[".$jaxl->action."]] Not authed yet, Not commiting session\n".json_encode($_SESSION), 5, $jaxl);
            }
            
            return $xml;
        }
        
        public static function wrapBody($xml, $jaxl) {
            $body = trim($xml);
            
            if(substr($body, 1, 4) != 'body') {
                $body = '';
                $body .= '<body rid="'.++$jaxl->bosh['rid'].'"';
                $body .= ' sid="'.$jaxl->bosh['sid'].'"';
                $body .= ' xmlns="http://jabber.org/protocol/httpbind">';
                $body .= $xml;
                $body .= "</body>";
                
                $_SESSION['rid'] = $jaxl->bosh['rid'];
            }
            
            return $body;
        }
        
        public static function sendBody($xml, $jaxl) {
            $xml = JAXLPlugin::execute('jaxl_pre_curl', $xml, $jaxl);
            if($xml != false) {
                JAXLog::log("[[XMPPSend]] body\n".$xml, 4, $jaxl);
                
                $payload = JAXLUtil::curl($jaxl->bosh['url'], 'POST', $jaxl->bosh['headers'], $xml);
                $payload = $payload['content'];
                
                $jaxl->handler($payload);
            }
            return $xml;
        }
        
        public static function unwrapBody($payload) {
            if(substr($payload, -2, 2) == "/>") preg_match_all('/<body (.*?)\/>/i', $payload, $m);
            else preg_match_all('/<body (.*?)>(.*)<\/body>/i', $payload, $m);
            
            if(isset($m[1][0])) $body = "<body ".$m[1][0].">";
            else $body = "<body>";
            
            if(isset($m[2][0])) $payload = $m[2][0];
            else $payload = '';
            
            return array($body, $payload);
        }
        
        public static function preHandler($payload, $jaxl) {
            if(substr($payload, 1, 4) == "body") {
                list($body, $payload) = self::unwrapBody($payload);
                JAXLPlugin::execute('jaxl_get_body', $body, $jaxl);
                if($payload == '') JAXLPlugin::execute('jaxl_get_empty_body', $body, $jaxl);
            }
            return $payload;
        }
        
        public static function processBody($xml, $jaxl) {
            $arr = $jaxl->xml->xmlize($xml);
            
            switch($jaxl->action) {
                case 'connect':
                    if(isset($arr["body"]["@"]["sid"])) {
                        $_SESSION['sid'] = $arr["body"]["@"]["sid"];
                        $jaxl->bosh['sid'] = $arr["body"]["@"]["sid"];
                    }
                    break;
                case 'disconnect':
                    JAXLPlugin::execute('jaxl_post_disconnect');
                    break;
                case 'ping':
                    break;
                default:
                    break;
            }
            
            return $xml;
        }
        
    }
    
?>
