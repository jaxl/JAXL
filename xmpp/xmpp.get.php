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
		'JAXLXml',
		'XMPPSend'
	));
	
	/*
	 * XMPP Get Class
	 * Provide methods for receiving all kind of xmpp streams and stanza's
	*/
	class XMPPGet {
		
		public static function xml() {
			global $jaxl;
			
			// sleep between two reads
			sleep(JAXL_XMPP_GET_SLEEP);
			
			// initialize empty lines read
			$emptyLine = 0;
			
			// read previous buffer
			$payload = $jaxl->buffer;
			$jaxl->buffer = '';
			
			// read socket data
			for($i=0; $i<JAXL_XMPP_GET_PCKTS; $i++) {
				if($jaxl->stream) {
					$line = fread($jaxl->stream, JAXL_XMPP_GET_PCKT_SIZE);
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
			
			// trim read data
			$payload = trim($payload);
			if($payload != '') self::handler($payload);
		}
		
		public static function handler($payload) {
			global $jaxl;
			JAXLog::log("[[XMPPGet]] \n".$payload, 5);
			
			$buffer = array();
			$payload = JAXLPlugin::execute('jaxl_pre_handler', $payload);	
			
			$xmls = JAXLUtil::splitXML($payload);
			$pktCnt = count($xmls);
			
			foreach($xmls as $pktNo => $xml) {	
				if($pktNo == $pktCnt-1) {
					if(substr($xml, -1, 1) != '>') {
						$jaxl->buffer = $xml;
						break;
					}
				}
				
				if(substr($xml, 0, 7) == '<stream') 
					$arr = $jaxl->xml->xmlize($xml);
				else 
					$arr = JAXLXml::parse($xml);
				
				switch(TRUE) {
					case isset($arr['stream:stream']):
						self::streamStream($arr['stream:stream']);
						break;
					case isset($arr['stream:features']):
						self::streamFeatures($arr['stream:features']);
						break;
					case isset($arr['stream:error']):
						self::streamError($arr['stream:error']);
						break;
					case isset($arr['failure']);
						self::failure($arr['failure']);
						break;
					case isset($arr['proceed']):
						self::proceed($arr['proceed']);
						break;
					case isset($arr['challenge']):
						self::challenge($arr['challenge']);
						break;
					case isset($arr['success']):
						self::success($arr['success']);
						break;
					case isset($arr['presence']):
						$buffer['presence'][] = $arr['presence'];
						break;
					case isset($arr['message']):
						$buffer['message'][] = $arr['message'];
						break;
					case isset($arr['iq']):
						self::iq($arr['iq']);
						break;
					default:
						print "Unrecognized payload received from jabber server...";
						break;
				}
			}
			
			if(isset($buffer['presence'])) self::presence($buffer['presence']);
			if(isset($buffer['message'])) self::message($buffer['message']);
			unset($buffer);
		}
		
		public static function streamStream($arr) {
			if($arr['@']["xmlns:stream"] != "http://etherx.jabber.org/streams") {
				print "Unrecognized XMPP Stream...\n";
			}
			else if($arr['@']['xmlns'] == "jabber:component:accept") {
				JAXLPlugin::execute('jaxl_post_start', $arr['@']['id']);
			}
			else if($arr['@']['xmlns'] == "jabber:client") {
				global $jaxl;
				$jaxl->streamId = $arr['@']['id'];
	        		$jaxl->streamHost = $arr['@']['from'];
	        		$jaxl->streamVersion = $arr['@']['version'];
			}
		}
		
		public static function streamFeatures($arr) {
			if(isset($arr["#"]["starttls"]) && ($arr["#"]["starttls"][0]["@"]["xmlns"] == "urn:ietf:params:xml:ns:xmpp-tls")) {
				XMPPSend::startTLS();
			}
			else if(isset($arr["#"]["mechanisms"]) && ($arr["#"]["mechanisms"][0]["@"]["xmlns"] == "urn:ietf:params:xml:ns:xmpp-sasl")) {
				$mechanism = array();
				
				foreach ($arr["#"]["mechanisms"][0]["#"]["mechanism"] as $row)
					$mechanism[] = $row["#"];
				
				JAXLPlugin::execute('jaxl_get_auth_mech', $mechanism);
			}
			else if(isset($arr["#"]["bind"]) && ($arr["#"]["bind"][0]["@"]["xmlns"] == "urn:ietf:params:xml:ns:xmpp-bind")) {
				global $jaxl;
				$jaxl->sessionRequired = isset($arr["#"]["session"]);
				$jaxl->startBind();
			}
		}
		
		public static function streamError($arr) {
			$desc = key($arr['#']);
			$xmlns = $arr['#']['0']['@']['xmlns'];
			JAXLog::log("Stream error with description ".$desc." and xmlns ".$xmlns, 0);
			return TRUE;
		}

		public static function failure($arr) {
			global $jaxl;
			
			$xmlns = $arr['xmlns'];
			switch($xmlns) {
				case 'urn:ietf:params:xml:ns:xmpp-tls':
					JAXLog::log("Unable to start TLS negotiation, see logs for detail...", 0);
					$jaxl->shutdown('tlsFailure');
					break;
				case 'urn:ietf:params:xml:ns:xmpp-sasl':
					JAXLog::log("Unable to complete SASL Auth, see logs for detail...", 0);
					$jaxl->shutdown('saslFailure');
					break;
				default:
					JAXLog::log("Uncatched failure xmlns received...", 0);
					break;
			}
		}
		
		public static function proceed($arr) {
			if($arr['xmlns'] == "urn:ietf:params:xml:ns:xmpp-tls") {
				global $jaxl;
				
				stream_set_blocking($jaxl->stream, 1);
				if(!stream_socket_enable_crypto($jaxl->stream, TRUE, STREAM_CRYPTO_METHOD_TLS_CLIENT))
			  		stream_socket_enable_crypto($jaxl->stream, TRUE, STREAM_CRYPTO_METHOD_SSLv3_CLIENT);
				stream_set_blocking($jaxl->stream, 0);
				
				XMPPSend::startStream();
			}
		}
		
		public static function challenge($arr) {
			if($arr['xmlns'] == "urn:ietf:params:xml:ns:xmpp-sasl") {
				global $jaxl;
				
				if($jaxl->secondChallenge) {
					$xml = '<response xmlns="urn:ietf:params:xml:ns:xmpp-sasl"/>';
				}
				else {
					$response = array();
					$decoded = base64_decode($arr['challenge']);
					
					// Some cleanup required in below methods in future
					if($jaxl->authType == 'X-FACEBOOK-PLATFORM') {
						$decoded = explode('&', $decoded);
						foreach($decoded as $k=>$v) {
							list($kk, $vv) = explode('=', $v);
							$decoded[$kk] = $vv;
							unset($decoded[$k]);
						}
						
						list($secret, $decoded['api_key'], $decoded['session_key']) = JAXLPlugin::execute('jaxl_get_facebook_key');
						
						$decoded['call_id'] = time();
						$decoded['v'] = '1.0';
						
						$base_string = '';
						foreach(array('api_key', 'call_id', 'method', 'nonce', 'session_key', 'v') as $key) {
							if(isset($decoded[$key])) {
								$response[$key] = $decoded[$key];
								$base_string .= $key.'='.$decoded[$key];
							}
						}
						
						$base_string .= $secret;
						$response['sig'] = md5($base_string);
						
						$responseURI = '';
						foreach($response as $k=>$v) {
							if($responseURI == '')
								$responseURI .= $k.'='.urlencode($v);
							else 
								$responseURI .= '&'.$k.'='.urlencode($v);
						}
						
						$xml = '<response xmlns="urn:ietf:params:xml:ns:xmpp-sasl">';
						$xml .= base64_encode($responseURI);
						$xml .= '</response>';
					}
					else if($jaxl->authType == 'DIGEST-MD5') {
			        		$decoded = JAXLUtil::explodeData($decoded);		
						if(!isset($decoded['digest-uri'])) $decoded['digest-uri'] = 'xmpp/'.$jaxl->domain;	
						$decoded['cnonce'] = base64_encode(JAXLUtil::generateNonce());
						
						if(isset($decoded['qop'])
						&& $decoded['qop'] != 'auth' 
						&& strpos($decoded['qop'],'auth') !== false
						) {
			           			$decoded['qop'] = 'auth';
						}
						
						$response = array('username'=>$jaxl->user,
						'response' => JAXLUtil::encryptPassword(array_merge($decoded,array('nc'=>'00000001'))),
						'charset' => 'utf-8',
						'nc' => '00000001',
						'qop' => 'auth');
						
						foreach(array('nonce', 'digest-uri', 'realm', 'cnonce') as $key)
							if(isset($decoded[$key]))
								$response[$key] = $decoded[$key];
					
						$xml = '<response xmlns="urn:ietf:params:xml:ns:xmpp-sasl">';
						$xml .= base64_encode(JAXLUtil::implodeData($response));
						$xml .= '</response>';
					}
					
					$jaxl->secondChallenge = TRUE;
				}
				XMPPSend::xml($xml);
			}
		}
		
		public static function success($arr) {
			global $jaxl;
			if($arr['xmlns'] == "urn:ietf:params:xml:ns:xmpp-sasl") {
				if($jaxl->mode == "cgi") JAXL0206::restartStream();
				else XMPPSend::startStream();
			}
		}
		
		public static function presence($arrs) {
			$payload = array();
			foreach($arrs as $arr) {
				$payload[] = $arr;
			}
			
			JAXLPlugin::execute('jaxl_get_presence', $payload);
		}
		
		public static function message($arrs) {
			$payload = array();
			
			foreach($arrs as $arr) {
				$payload[] = $arr;
			}
			
			JAXLPlugin::execute('jaxl_get_message', $payload);
			unset($payload);
		}
		
		public static function postBind($arr) {
			if($arr["type"] == "result") {
				global $jaxl;
				$jaxl->jid = $arr["bindJid"];
				$jaxl->bosh['jid'] = $jaxl->jid;
				
				if($jaxl->sessionRequired) {
					$jaxl->startSession();
				}
				else {
					$jaxl->auth = TRUE;
					JAXLog::log("Auth completed...", 0);
					JAXLPlugin::execute('jaxl_post_auth');
				}
			}
		}
		
		public static function postSession($arr) {
			if($arr["type"] == "result") {
				$jaxl->auth = TRUE;
				JAXLog::log("Auth completed...", 0);
				JAXLPlugin::execute('jaxl_post_auth');
			}
		}
		
		public static function iq($arr) {
			switch($arr['type']) {
				case 'result':
					$id = $arr['id'];
					JAXLPlugin::execute('jaxl_get_iq_'.$id, $arr);
					break;
				case 'get':
					JAXLPlugin::execute('jaxl_get_iq_get', $arr);
					break;
				case 'set':
					JAXLPlugin::execute('jaxl_get_iq_set', $arr);
					break;
				case 'error':
					JAXLPlugin::execute('jaxl_get_iq_error', $arr);
					break;
				default:
					JAXLog::log('Unhandled iq type ...'.json_encode($arr), 0);
					break;
			}
		}
		
	}
	
?>
