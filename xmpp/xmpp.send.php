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
        'JAXLog'
    ));
    
    /*
     * XMPP Send Class
     * Provide methods for sending all kind of xmpp stream and stanza's
    */
    class XMPPSend {
        
        public static function startStream($jaxl) {
            $xml = '<stream:stream xmlns:stream="http://etherx.jabber.org/streams" version="1.0" xmlns="jabber:client" to="'.$jaxl->domain.'" xml:lang="en" xmlns:xml="http://www.w3.org/XML/1998/namespace">';
            return $jaxl->sendXML($xml);
        }
        
        public static function endStream($jaxl) {
            $xml = '</stream:stream>';
            return $jaxl->sendXML($xml);
        }
        
        public static function startTLS($jaxl) {
            $xml = '<starttls xmlns="urn:ietf:params:xml:ns:xmpp-tls"/>';
            return $jaxl->sendXML($xml);
        }
        
        public static function startAuth($type, $jaxl) {
            $jaxl->authType = $type;
            
            $xml = '<auth xmlns="urn:ietf:params:xml:ns:xmpp-sasl" mechanism="'.$type.'">';
            switch($type) {
                case 'DIGEST-MD5':
                    break;
                case 'PLAIN':
                    $xml .= base64_encode("\x00".$jaxl->user."\x00".$jaxl->pass);
                    break;
                case 'ANONYMOUS':
                    break;
                case 'X-FACEBOOK-PLATFORM':
                    break;
                case 'CRAM-MD5':
                    break;
                case 'SCRAM-SHA-1':
                    $xml .= base64_encode("n,,n=".$jaxl->user.",r=".base64_encode(JAXLUtil::generateNonce()));
                    break;
                default:
                    break;
            }
            $xml .= '</auth>';
            
            JAXLog::log("Performing Auth type: ".$type, 0, $jaxl);
            return $jaxl->sendXML($xml);
        }
        
        public static function message($to, $from=false, $child=false, $type='normal', $jaxl, $id=false, $ns='jabber:client') {
            $xml = '';
            
            if(is_array($to)) {
                foreach($to as $key => $value) {
                    $xml .= self::prepareMessage($to[$key], $from[$key], $child[$key], $type[$key], $ns[$key], $id[$key], $jaxl);  
                }
            }
            else {
                $xml .= self::prepareMessage($to, $from, $child, $type, $ns, $id, $jaxl);
            }
        
            JAXLPlugin::execute('jaxl_send_message', $xml, $jaxl); 
            return $jaxl->sendXML($xml);
        }
        
        private static function prepareMessage($to, $from, $child, $type, $ns, $id, $jaxl) {
            $xml = '<message';
            if($from) $xml .= ' from="'.$from.'"';
            $xml .= ' to="'.$to.'"';
            if($type) $xml .= ' type="'.$type.'"';
            if($id) $xml .= ' id="'.$jaxl->getId().'"';
            $xml .= '>';
            
            if($child) {
                if(isset($child['subject'])) $xml .= '<subject>'.$child['subject'].'</subject>';
                if(isset($child['body'])) $xml .= '<body>'.$child['body'].'</body>';
                if(isset($child['thread'])) $xml .= '<thread>'.$child['thread'].'</thread>';
                if(isset($child['payload'])) $xml .= $child['payload'];
            }
            
            $xml .= '</message>';
            return $xml;
        }
        
        public static function presence($to=false, $from=false, $child=false, $type=false, $jaxl, $id=false, $ns='jabber:client') {
            $xml = '';
            if(is_array($to)) {
                foreach($to as $key => $value) {
                    $xml .= self::preparePresence($to[$key], $from[$key], $child[$key], $type[$key], $jaxl, $id[$key], $ns[$key]);
                }
            }
            else {
                $xml .= self::preparePresence($to, $from, $child, $type, $jaxl, $id, $ns);
            }
                
            JAXLPlugin::execute('jaxl_send_presence', $xml, $jaxl);
            return $jaxl->sendXML($xml);
        }
    
        private static function preparePresence($to, $from, $child, $type, $jaxl, $id, $ns) {
            $xml = '<presence';
            if($type) $xml .= ' type="'.$type.'"';
            if($from) $xml .= ' from="'.$from.'"';
            if($to) $xml .= ' to="'.$to.'"';
            if($id) $xml .= ' id="'.$jaxl->getId().'"';
            $xml .= '>';
            
            if($child) {
                if(isset($child['show'])) $xml .= '<show>'.$child['show'].'</show>';
                if(isset($child['status'])) $xml .= '<status>'.$child['status'].'</status>';
                if(isset($child['priority'])) $xml .= '<priority>'.$child['priority'].'</priority>';
                if(isset($child['payload'])) $xml .= $child['payload'];
            }
            
            $xml.= '</presence>';
            return $xml;
        }
        
        public static function iq($type, $payload=false, $to=false, $from=false, $callback=false, $jaxl, $id=false, $ns='jabber:client') {
            if($type == 'get' || $type == 'set') {
                $id = $jaxl->getId();
                if($callback) JAXLPlugin::add('jaxl_get_iq_'.$id, $callback);
            }
            
            $types = array('get','set','result','error');
            
            $xml = '';
            $xml .= '<iq';
            $xml .= ' type="'.$type.'"';
            $xml .= ' id="'.$id.'"';
            if($to) $xml .= ' to="'.$to.'"';
            if($from) $xml .= ' from="'.$from.'"';
            $xml .= '>';
            if($payload) $xml .= $payload;
            $xml .= '</iq>';
            
            $jaxl->sendXML($xml);
            if($type == 'get' || $type == 'set') return $id;
            else return true;
        }
        
    }
    
?>
