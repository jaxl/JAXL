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

require_once JAXL_CWD.'/core/jaxl_loop.php';

class JAXLSocketServer {
	
	public $fd = null;
	private $clients = array();
	private $recv_chunk_size = 1024;
	private $req_cb = null;
	private $blocking = false;
	
	public function __construct($path, $req_cb) {
		$this->req_cb = $req_cb;
		if(($this->fd = @stream_socket_server($path, $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN)) !== false) {
			if(@stream_set_blocking($this->fd, $this->blocking)) {
				JAXLLoop::watch($this->fd, array(
					'read' => array(&$this, 'on_server_accept_ready')
				));
				_info("socket ready to accept on path ".$path);
			}
			else {
				_error("unable to set non block flag");
			}
		}
		else {
			_error("unable to establish socket server, errno: ".$errno.", errstr: ".$errstr);
		}
	}
	
	public function __destruct() {
		_info("shutting down socket server");
	}
	
	public function send($client, $data) {
		$client_id = (int) $client;
		$this->clients[$client_id]['obuffer'] .= $data;
		JAXLLoop::watch($client, array(
			'write' => array(&$this, 'on_client_write_ready')
		));
	}
	
	public function on_server_accept_ready($server) {
		//_debug("got server accept");
		$client = @stream_socket_accept($server, 0, $addr);
		if(!$client) {
			_error("unable to accept new client conn");
			return;
		}
		
		if(@stream_set_blocking($client, $this->blocking)) {
			$client_id = (int) $client;
			$this->clients[$client_id] = array(
				'fd' => $client,
				'ibuffer' => '',
				'obuffer' => ''
			);
			
			// listen for read events on this client
			JAXLLoop::watch($client, array(
				'read' => array(&$this, 'on_client_read_ready')
			));
			
			_debug("accepted connection from client#".$client_id.", addr:".$addr);
		}
		else {
			_error("unable to set non block flag");
		}
	}
	
	public function on_client_read_ready($client) {
		//_debug("got client read ready");
		$client_id = (int) $client;
		$raw = fread($client, $this->recv_chunk_size);
		$bytes = strlen($raw);
		
		if($bytes === 0) {
			$meta = stream_get_meta_data($client);
			if($meta['eof'] === TRUE) {
				_debug("socket eof client#".$client_id.", closing");
				JAXLLoop::unwatch($client, array(
					'read' => true
				));
				fclose($client);
				return;
			}
		}
		
		$total = $this->clients[$client_id]['ibuffer'] . $raw;
		if($this->req_cb) call_user_func($this->req_cb, $client, $total);
		$this->clients[$client_id]['ibuffer'] = '';
	}
	
	public function on_client_write_ready($client) {
		//_debug("got client write ready");
		$client_id = (int) $client;
		$total = $this->clients[$client_id]['obuffer'];
		
		$bytes = fwrite($client, $total);
		$this->clients[$client_id]['obuffer'] = substr($this->clients[$client_id]['obuffer'], $bytes, $total-$bytes);
		
		if(strlen($this->clients[$client_id]['obuffer']) === 0) {
			JAXLLoop::unwatch($client, array(
				'write' => true
			));
		}
	}
	
}

?>
