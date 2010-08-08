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
		'XML',
		'XMPP',
	));
	
	/*
	 * Jaxl Core Class extending Base XMPP Class
	*/
	class JAXL extends XMPP {
		
		var $pid = FALSE;	
		var $mode = FALSE;
		var $authType = FALSE;
		var $features = array();
		
		function __construct($config=array()) {
			$this->configure();
			parent::__construct($config);
			$this->xml = new XML();
		}
		
		function configure() {
			$this->pid = getmypid();
			$this->mode = isset($_REQUEST['jaxl']) ? "cgi" : "cli";			
			
			if(!JAXLUtil::isWin() && JAXLUtil::pcntlEnabled()) {
				pcntl_signal(SIGTERM, array($this, "shutdown"));
				pcntl_signal(SIGINT, array($this, "shutdown"));
				JAXLog::log("Registering shutdown for SIGH Terms ...", 0);
			}
			
			if(JAXLUtil::sslEnabled()) {
				JAXLog::log("Openssl enabled ...", 0);
			}
			
			if($this->mode == "cli") {
				if(!function_exists('fsockopen')) 
					die("Jaxl requires fsockopen method ...");	
				file_put_contents(JAXL_PID_PATH, $this->pid);
			}
			
			if($this->mode == "cgi") {
				if(!function_exists('curl_init'))
					die("Jaxl requires curl_init method ...");
			}

			// include service discovery XEP, recommended for every IM client
			jaxl_require('JAXL0030', array(
				'category'=>'client',
				'type'=>'bot',
				'name'=>JAXL_NAME,
				'lang'=>'en',
				'instance'=>$this
			));
		}
		
		function shutdown($signal) {
			global $jaxl;
			JAXLog::log("Jaxl Shutting down ...", 0);
			JAXLPlugin::execute('jaxl_pre_shutdown', $signal);
			
			XMPPSend::endStream();
			$jaxl->stream = FALSE;
		}
		
		function auth($type) {
			return XMPPSend::startAuth($type);
		}
		
		function setStatus($status=FALSE, $show=FALSE, $priority=FALSE, $caps=FALSE) {
			$child = array();
			$child['status'] = ($status === FALSE ? 'Online using Jaxl (Jabber XMPP Library in PHP)' : $status);
			$child['show'] = ($show === FALSE ? 'chat' : $show);
			$child['priority'] = ($priority === FALSE ? 1 : $priority);
			if($caps) $child['payload'] = JAXL0115::getCaps();
			
			return XMPPSend::presence(FALSE, FALSE, $child);
		}
		
		function subscribe($toJid) {
			return XMPPSend::presence($toJid, FALSE, FALSE, 'subscribe');
		}
		
		function subscribed($toJid) {
			return XMPPSend::presence($toJid, FALSE, FALSE, 'subscribed');
		}
		
		function unsubscribe($toJid) {
			return XMPPSend::presence($toJid, FALSE, FALSE, 'unsubscribe');
		}
		
		function unsubscribed($toJid) {
			return XMPPSend::presence($toJid, FALSE, FALSE, 'unsubscribed');
		}
		
		function getRosterList($callback=FALSE) {
			$payload = '<query xmlns="jabber:iq:roster"/>';
			return XMPPSend::iq("get", $payload, FALSE, $this->jid, $callback);
		}
		
		function addRoster($jid, $group, $name=FALSE) {
			$payload = '<query xmlns="jabber:iq:roster">';
			$payload .= '<item jid="'.$jid.'"';
			if($name) $payload .= ' name="'.$name.'"';
			$payload .= '>';	
			$payload .= '<group>'.$group.'</group>';
			$payload .= '</item>';
			$payload .= '</query>';
			return XMPPSend::iq("set", $payload, FALSE, $this->jid);
		}
		
		function updateRoster($jid, $group, $name=FALSE, $subscription=FALSE) {
			$payload = '<query xmlns="jabber:iq:roster">';
			$payload .= '<item jid="'.$jid.'"';
			if($name) $payload .= ' name="'.$name.'"';
			if($subscription) $payload .= ' subscription="'.$subscription.'"';
			$payload .= '>';
			$payload .= '<group>'.$group.'</group>';
			$payload .= '</item>';
			$payload .= '</query>';
			return XMPPSend::iq("set", $payload, FALSE, $this->jid);
		}
		
		function deleteRoster($jid) {
			$payload = '<query xmlns="jabber:iq:roster">';
			$payload .= '<item jid="'.$jid.'" subscription="remove">';
			$payload .= '</item>';
			$payload .= '</query>';
			return XMPPSend::iq("set", $payload, FALSE, $this->jid);
		}
		
		function sendMessage($to, $message, $from=FALSE, $type='chat') {
			$child = array();
			$child['body'] = $message;
			return XMPPSend::message($to, $from, $child, $type);
		}
		
	}

?>
