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
		
		public static function xml($xml) {
			global $jaxl;
			$xml = JAXLPlugin::execute('jaxl_send_xml', $xml);
			
			if($jaxl->mode == "cgi") {
				JAXLPlugin::execute('jaxl_send_body', $xml);
			}
			else {
				if($jaxl->lastSendTime && (JAXLUtil::getTime() - $jaxl->lastSendTime < JAXL_XMPP_SEND_RATE)) {
					sleep(JAXL_XMPP_SEND_SLEEP);
				}
				$jaxl->lastSendTime = JAXLUtil::getTime();
				
				if($jaxl->stream) {
					if(($ret = fwrite($jaxl->stream, $xml)) !== FALSE) JAXLog::log("[[XMPPSend]] $ret\n".$xml, 5);
					else JAXLog::log("[[XMPPSend]] Failed\n".$xml, 1);
					
					return $ret;
				}
				else {
					JAXLog::log("Jaxl stream not connected to jabber host, unable to send xmpp payload...", 1);
					return FALSE;
				}
			}
			
		}
		
		public static function startStream() {
			global $jaxl;
      			$xml = '<stream:stream xmlns:stream="http://etherx.jabber.org/streams" version="1.0" xmlns="jabber:client" to="'.$jaxl->domain.'" xml:lang="en" xmlns:xml="http://www.w3.org/XML/1998/namespace">';
      			return self::xml($xml);
		}
		
		public static function startTLS() {
			$xml = '<starttls xmlns="urn:ietf:params:xml:ns:xmpp-tls"/>';
			return self::xml($xml);
		}
		
		public static function startAuth($type) {
			switch($type) {
				case 'DIGEST-MD5':
					$xml = '<auth xmlns="urn:ietf:params:xml:ns:xmpp-sasl" mechanism="DIGEST-MD5"/>';
					break;
				case 'PLAIN':
					global $jaxl;
					$xml = '<auth xmlns="urn:ietf:params:xml:ns:xmpp-sasl" mechanism="PLAIN">';
					$xml .= base64_encode("\x00".$jaxl->user."\x00".$jaxl->pass);
					$xml .= '</auth>';
					break;
				case 'ANONYMOUS':
					$xml = '<auth xmlns="urn:ietf:params:xml:ns:xmpp-sasl" mechanism="ANONYMOUS"/>';
					break;
				default:
					break;
			}
			JAXLog::log("Performing Auth type: ".$type, 0);
			return self::xml($xml);
		}
		
		/*
		 * Following attributes are common for all XMPP Stanza
		 * 1. to		If missing, XMPP Stanza is handled by the server
		 * 2. from
		 * 3. id
		 * 4. type
		 * 5. xml:lang
		*/
		
		/*
		 * 'type' attribute of a message stanza is RECOMMENDED. Possible values are:
		 * 1. chat
		 * 2. error
		 * 3. groupchat
		 * 4. headline	means message is kind of auto-generated and do not expect a reply back
		 * 5. normal	Default
		 * 
		 * Message stanza's can have child nodes:
		 * 1. <subject/>
		 * 2. <body/>
		 * 3. <thread/>
		*/
		public static function message($to, $from=FALSE, $child=FALSE, $type='normal', $ns='jabber:client') {
			$xml = '';
			
			if(is_array($to)) {
				foreach($to as $key => $value) {
					$xml .= self::prepareMessage($to[$key], $from[$key], $child[$key], $type[$key], $ns[$key]);	
				}
			}
			else {
				$xml .= self::prepareMessage($to, $from, $child, $type, $ns);
			}
		
			JAXLPlugin::execute('jaxl_send_message', $xml);	
			return self::xml($xml);
		}
		
		private static function prepareMessage($to, $from, $child, $type, $ns) {
			$xml = '<message';
			if($from) $xml .= ' from="'.$from.'"';
			$xml .= ' to="'.$to.'"';
			$xml .= ' type="'.$type.'"';
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
		
		/*
		 * 'type' attribute of a presence stanza is OPTIONAL.
		 * 
		 * If 'type' attribute is absent
		 * 		Presence stanza is used to signal to the server that the sender is online and available for communication
		 * If 'type' attribute is present
		 * 		Presence stanza specifies lack of availability
		 * 		A request to manage a subscription to another entity's presence
		 * 		A request for another entity's current presence
		 * 		An error related to a previously-sent presence stanza
		 * 
		 * Possible values for 'type' attribute are:
		 * 1. unavailable
		 * 2. subscribe
		 * 3. subscribed
		 * 4. unsubscribe
		 * 5. unsubscribed
		 * 6. probe
		 * 7. error
		 *
		 * Presence stanza's can have child nodes:
		 * 1. <show/>		away, chat, dnd, xa (If no show element is provided, sender is assumed online and available)
		 * 2. <status/>		
		 * 3. <priority/>	Value between -128 to +127
		*/
		public static function presence($to=FALSE, $from=FALSE, $child=FALSE, $type=FALSE, $ns='jabber:client') {
			$xml = '';
			if(is_array($to)) {
				foreach($to as $key => $value) {
					$xml .= self::preparePresence($to[$key], $from[$key], $child[$key], $type[$key], $ns[$key]);	
				}
			}
			else {
				$xml .= self::preparePresence($to, $from, $child, $type, $ns);
			}
				
			JAXLPlugin::execute('jaxl_send_presence', $xml);
			return self::xml($xml);
		}
	
		private static function preparePresence($to, $from, $child, $type, $ns) {
			$xml = '<presence';
			if($type) $xml .= ' type="'.$type.'"';
			if($from) $xml .= ' from="'.$from.'"';
			if($to) $xml .= ' to="'.$to.'"';
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
		
		/*
		 * 1. 'id' attribute is required
		 * 2. 'type' is required (get, set, result, error)
		 * 3. IQ of type 'get' and 'set' should have only 1 child element
		 * 4. IQ of type 'result' must have none or 1 child element
		 * 5. Of type 'error' must contain corresponding received child element and <error/> child element
		 * 
		 * Note:
		 * $callback is the methods which gets control back after a response from the jabber server for sent <iq/>
		 *
		*/
		public static function iq($type, $payload=FALSE, $to=FALSE, $from=FALSE, $callback=FALSE, $id=FALSE, $ns='jabber:client') {
			if($type == 'get' || $type == 'set') {
				global $jaxl;
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
			
			self::xml($xml);
			if($type == 'get' || $type == 'set') return $id;
			else return TRUE;
		}
		
	}
	
?>
