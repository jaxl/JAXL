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

require_once JAXL_CWD.'/xmpp/xmpp_xep.php';

define('NS_HTTP_BIND', 'http://jabber.org/protocol/httpbind');
define('NS_BOSH', 'urn:xmpp:xbosh');

class XEP_0206 extends XMPPXep {
	
	private $recv_cb = null;
	
	public $headers = array(
		'Accept-Encoding: gzip, deflate',
		'Content-Type: text/xml; charset=utf-8'
	);
	
	//
	// abstract method
	//
	
	public function init() {
		return array(
			
		);
	}
	
	//
	// event callbacks
	//
	
	public function set_callback($recv_cb) {
		$this->recv_cb = $recv_cb;
	}
	
	public function unwrap($body) {
		if(substr($body -2, 2) == "/>") preg_match_all('/<body (.*?)\/>/smi', $body, $m);
		else preg_match_all('/<body (.*?)>(.*)<\/body>/smi', $body, $m);
		
		if(isset($m[1][0])) $envelop = "<body ".$m[1][0].">";
		else $envelop = "<body>";
		
		if(isset($m[2][0])) $payload = $m[2][0];
		else $payload = '';
		
		return array($envelop, $payload);
	}
	
	public function send($body) {
		$rs = JAXLUtil::curl($this->jaxl->cfg['bosh_url'], 'POST', $this->headers, $body->to_string());
		list($body, $stanza) = $this->unwrap($rs['content']);
		if($this->recv_cb) call_user_func($this->recv_cb, $stanza);
	}
	
	public function session_start() {
		$body = new JAXLXml('body', NS_HTTP_BIND, array(
			'content' => 'text/xml; charset=utf-8',
			'from' => $this->jaxl->cfg['jid'],
			'to' => $this->jaxl->jid->domain,
			//'route' => 'xmpp:'.$this->jaxl->cfg['host'].':'.$this->jaxl->cfg['port'],
			'secure' => 'true',
			'xml:lang' => 'en',
			'xmpp:version' => '1.0',
			'xmlns:xmpp' => NS_BOSH,
			'hold' => 1,
			'wait' => 30,
			'rid' => rand(1000, 10000)
		));
		$this->send($body);
	}
	
	public function session_end() {
		$body = new JAXLXml('body', NS_HTTP_BIND, array(
			'sid' => '',
			'rid' => '',
			'type' => 'terminate'
		));
		$this->send($body);
	}
	
	public function restart_stream() {
		$body = new JAXLXml('body', NS_HTTP_BIND, array(
			'sid' => '',
			'rid' => '',
			'to' => '',
			'xmpp:restart' => 'true',
			'xmlns:xmpp' => NS_BOSH
		));
		$this->send($body);
	}
	
	public function ping() {
		$body = new JAXLXml('body', NS_HTTP_BIND, array('sid' => '', 'rid' => ''));
		$this->send($body);
	}
	
}

?>