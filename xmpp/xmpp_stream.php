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

require_once 'fsm.php';

/**
 * 
 * Enter description here ...
 * @author abhinavsingh
 *
 */
class XmppStream {
	
	private $fsm;
	
	public function __construct() {
		$ctx = $this->ctx_init();
		$fsm = new Fsm(array(&$this, "setup"), $ctx);
	}
	
	public function __destruct() {
		
	}
	
	//
	// Fsm States
	// 
	public function setup() {
		
	}
	
	public function connected() {
		
	}
	
	public function wait_for_stream_start() {
		
	}
	
	public function wait_for_stream_features() {
		
	}
	
	public function wait_for_tls_result() {
	
	}

	public function wait_for_compression_result() {
	
	}
	
	public function wait_for_sasl_response() {
	
	}
	
	public function wait_for_bind_response() {
	
	}
	
	public function wait_for_session_response() {
	
	}
	
	public function logged_in() {
		
	}
	
	//
	// Internal Methods
	//
	private function ctx_init() {
		return
		array(
			'jid'	=>	NULL,
			'pass'	=>	NULL,
			'sock'	=>	NULL,
			'xml'	=>	NULL
		);
	}
	
}

?>