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

require_once JAXL_CWD.'/core/jaxl_fsm.php';

class HTTPRequest extends JAXLFsm {
	
	public $sock = null;
	public $ip = null;
	public $port = null;
	
	public $version = null;
	public $method = null;
	public $resource = null;
	public $path = null;
	public $query = array();
	
	public $headers = array();
	public $body = null;
	
	public function __construct($sock, $addr) {
		$this->sock = $sock;
		
		$addr = explode(":", $addr);
		$this->ip = $addr[0];
		if(sizeof($addr) == 2) {
			$this->port = $addr[1];
		}
		
		parent::__construct("wait_for_request_line");
	}
	
	public function __destruct() {
		_debug("http request going down in ".$this->state." state");
	}
	
	public function state() {
		return $this->state;
	}
	
	//
	// abstract method implementation
	public function handle_invalid_state($r) {
		_debug("handle invalid state called with");
		print_r($r);
	}
	
	//
	// fsm States
	//
	
	public function wait_for_request_line($event, $args) {
		switch($event) {
			case 'line':
				$this->_line($args[0], $args[1], $args[2]);
				return 'wait_for_headers';
				break;
			default:
				_debug("uncatched $event");
				return 'wait_for_request_line';
		}
	}
	
	public function wait_for_headers($event, $args) {
		switch($event) {
			case 'set_header':
				$this->headers[$args[0]] = $args[1];
				return 'wait_for_headers';
				break;
			case 'empty_line':
				return 'maybe_headers_received';
			default:
				_debug("uncatched $event");
				return 'wait_for_headers';
		}
	}
	
	public function maybe_headers_received($event, $args) {
		switch($event) {
			case 'set_header':
				$this->headers[$args[0]] = $args[1];
				return 'wait_for_headers';
				break;
			case 'empty_line':
				return 'request_received';
				break;
			case 'body':
				$this->body = $args[0];
				return 'request_received';
				break;
			default:
				_debug("uncatched $event");
				return 'maybe_headers_received';
		}
	}
	
	public function request_received($event, $args) {
		switch($event) {
			case 'empty_line':
				return 'request_received';
				break;
			default:
				_debug("uncatched $event");
				return 'request_received';
		}
	}
	
	//
	// internal methods
	//
	
	protected function _line($method, $resource, $version) {
		$this->method = $method;
		$this->resource = $resource;
		
		$resource = explode("?", $resource);
		$this->path = $resource[0];
		if(sizeof($resource) == 2) {
			$query = $resource[1];
			$query = explode("&", $query);
			foreach($query as $q) {
				$q = explode("=", $q);
				if(sizeof($q) == 1) $q[1] = ""; 
				$this->query[$q[0]] = $q[1];
			}
		}
		
		$this->version = $version;
	}
	
}

?>
