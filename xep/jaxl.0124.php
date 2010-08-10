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
	 * XEP-0124: Bosh Implementation
	 * Maintain various attributes like rid, sid across requests
	*/
	class JAXL0124 {
		
		public static $ns = '';
		
		public static function init() {
			JAXLPlugin::add('jaxl_send_xml', array('JAXL0124', 'wrapBody'));
			JAXLPlugin::add('jaxl_pre_handler', array('JAXL0124', 'preHandler'));
			JAXLPlugin::add('jaxl_get_body', array('JAXL0124', 'processBody'));
			JAXLPlugin::add('jaxl_pre_curl', array('JAXL0124', 'saveSession'));
			JAXLPlugin::add('jaxl_send_body', array('JAXL0124', 'sendBody'));
			
			self::setEnv();
		}
		
		public static function sendBody($xml) {
			global $jaxl;
			JAXLog::log("[[XMPPSend]] body\n".$xml, 5);	
			JAXLPlugin::execute('jaxl_pre_curl');
			$payload = JAXLUtil::curl($jaxl->bosh['url'], 'POST', $jaxl->bosh['headers'], $xml);
			$payload = $payload['content'];
			XMPPGet::handler($payload);
		}
		
		public static function preHandler($payload) {	
			if(substr($payload, 1, 4) == "body") {
				if(substr($payload, -2, 2) == "/>") preg_match_all('/<body (.*?)\/>/i', $payload, $m);
				else preg_match_all('/<body (.*?)>(.*)<\/body>/i', $payload, $m);
				
				if(isset($m[1][0])) $body = "<body ".$m[1][0].">";
				else $body = "<body>";
				
				JAXLPlugin::execute('jaxl_get_body', $body);
				
				if(isset($m[2][0])) $payload = $m[2][0];
				else $payload = '';
				
				if($payload == '') JAXLPlugin::execute('jaxl_get_empty_body', $body);
			}
			return $payload;
		}
		
		public static function setEnv() {
			global $jaxl;
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
			$jaxl->bosh['url'] = "http://".JAXL_HOST_NAME.":".JAXL_BOSH_PORT."/".JAXL_BOSH_SUFFIX."/";
			$jaxl->bosh['headers'] = array("Accept-Encoding: gzip, deflate","Content-Type: text/xml; charset=utf-8");
			
			self::loadSession();
		}
		
		public static function loadSession() {
			global $jaxl;
			
			session_set_cookie_params('3600', '/', '.'.JAXL_BOSH_COOKIE_DOMAIN, false, true);
			session_start();
			
			if(isset($_SESSION['rid'])) $jaxl->bosh['rid'] = $_SESSION['rid'];
			else $jaxl->bosh['rid'] = rand(1000, 10000);

			if(isset($_SESSION['id'])) $jaxl->bosh['id'] = $_SESSION['id'];
			else $jaxl->bosh['id'] = rand(10, 1000);

			if(isset($_SESSION['sid'])) $jaxl->bosh['sid'] = $_SESSION['sid'];
			if(isset($_SESSION['jid'])) $jaxl->bosh['jid'] = $_SESSION['jid'];
		}
		
		public static function saveSession() {
			global $jaxl;
			
			$_SESSION['rid'] = isset($jaxl->bosh['rid']) ? $jaxl->bosh['rid'] : FALSE;
			$_SESSION['sid'] = isset($jaxl->bosh['sid']) ? $jaxl->bosh['sid'] : FALSE;
			$_SESSION['jid'] = isset($jaxl->bosh['jid']) ? $jaxl->bosh['jid'] : FALSE;
			$_SESSION['id'] = isset($jaxl->bosh['id']) ? $jaxl->bosh['id'] : FALSE;
			
			if($jaxl->action == "session"
			|| $jaxl->action == "ping"
			|| $jaxl->action == "message"
			|| $jaxl->action == "disconnect"
			) {
				session_write_close();
			}
		}
		
		public static function processBody($xml) {
			global $jaxl;
			
			$arr = $jaxl->xml->xmlize($xml);
			switch($jaxl->action) {
				case 'connect':
					$jaxl->bosh['sid'] = $arr["body"]["@"]["sid"];
					return TRUE;
					break;
				case 'disconnect':
					if($arr["body"]["@"]["type"] == 'terminate') {
						JAXLPlugin::execute('jaxl_post_disconnect');
					}
					return TRUE;
					break;
				case 'ping':
					return TRUE;
				default:
					return TRUE;
					break;
			}
		}
		
		public static function wrapBody($xml) {
			$body = $xml;
			
			if(substr($xml, 1, 4) != "body") {
				preg_match_all('/<(.*?)([\s*]|[>])/i', $xml, $m);
				
				global $jaxl;
				$jaxl->action = ($m[1][0] == "iq") ? $m[1][1] : $m[1][0];
				
				$body = '';
				$body .= '<body rid="'.++$jaxl->bosh['rid'].'" sid="'.$jaxl->bosh['sid'].'" xmlns="http://jabber.org/protocol/httpbind">';
				$body .= $xml;
				$body .= "</body>";
			}
			
			return $body;
		}
		
	}
	
?>
