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

/**
 * 
 * Enter description here ...
 * @author abhinavsingh
 *
 */
class JAXLSocket {
	
	private $host = "localhost";
	private $port = 5222;
	private $transport = "tcp";
	private $blocking = false;
	private $stream_context = null;
	
	public $fd = null;
	
	public $errno = null;
	public $errstr = null;
	private $timeout = 10;
	
	private $ibuffer = "";
	private $obuffer = "";
	private $compressed = false;
	
	private $recv_bytes = 0;
	private $send_bytes = 0;
	
	private $recv_cb = null;
	private $recv_chunk_size = 1024;
	
	public function __construct($host="localhost", $port=5222, $stream_context=null) {
		$this->host = $host;
		$this->port = $port;
		$this->stream_context = $stream_context;
		if($this->port == 5223) $this->transport = 'ssl';
	}
	
	public function __destruct() {
		//_debug("cleaning up xmpp socket...");
		$this->disconnect();
	}
	
	public function set_callback($recv_cb) {
		$this->recv_cb = $recv_cb;
	}
	
	public function connect($host=null, $port=null) {
		$this->host = $host ? $host : $this->host;
		$this->port = $port ? $port : $this->port;
		$remote_socket = $this->transport."://".$this->host.":".$this->port;
		
		_debug("trying ".$remote_socket."");
		if($this->stream_context) $this->fd = @stream_socket_client($remote_socket, $this->errno, $this->errstr, $this->timeout, STREAM_CLIENT_CONNECT, $this->stream_context);
		else $this->fd = @stream_socket_client($remote_socket, $this->errno, $this->errstr, $this->timeout);
		
		if($this->fd) {
			_debug("connected to ".$remote_socket."");
			stream_set_blocking($this->fd, $this->blocking);
			
			// watch descriptor for read/write events
			JAXLLoop::watch($this->fd, array(
				'read' => array(&$this, 'on_read_ready'),
				'write' => array(&$this, 'on_write_ready')
			));
			
			return true;
		}
		else {
			_debug("unable to connect ".$remote_socket." with error no: ".$this->errno.", error str: ".$this->errstr."");
			$this->disconnect();
			return false;
		}
	}
	
	public function disconnect() {
		@fclose($this->fd);
		$this->fd = null;
	}
	
	public function compress() {
		$this->compressed = true;
		//stream_filter_append($this->fd, 'zlib.inflate', STREAM_FILTER_READ);
		//stream_filter_append($this->fd, 'zlib.deflate', STREAM_FILTER_WRITE);
	}
	
	public function crypt() {
		// set blocking (since tls negotiation fails if stream is non-blocking)
		stream_set_blocking($this->fd, true);
		
		$ret = stream_socket_enable_crypto($this->fd, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
		if($ret == false) {
			$ret = stream_socket_enable_crypto($this->fd, true, STREAM_CRYPTO_METHOD_SSLv3_CLIENT);
			if($ret == false) {
				$ret = stream_socket_enable_crypto($this->fd, true, STREAM_CRYPTO_METHOD_SSLv2_CLIENT);
				if($ret == false) {
					$ret = stream_socket_enable_crypto($this->fd, true, STREAM_CRYPTO_METHOD_SSLv23_CLIENT);
				}
			}
		}
		
		// switch back to non-blocking
		stream_set_blocking($this->fd, false);
	}
	
	public function send($data) {
		$this->obuffer .= $data;
		
		// add watch for write events
		JAXLLoop::watch($this->fd, array(
			'write' => array(&$this, 'on_write_ready')
		));
	}
	
	public function on_read_ready() {
		_debug("on read ready called");
		$raw = @fread($this->fd, $this->recv_chunk_size);
		$bytes = strlen($raw);
		
		if($bytes === 0) {
			$meta = stream_get_meta_data($this->fd);
			if($meta['eof'] === TRUE) {
				_debug("socket eof, disconnecting");
				$this->disconnect();
				return;
			}
		}
		
		$this->recv_bytes += $bytes;
		$total = $this->ibuffer.$raw;
			
		$this->ibuffer = "";
		_debug("read ".$bytes."/".$this->recv_bytes." of data");
		if($bytes > 0) _debug($raw);
			
		// callback
		if($this->recv_cb) call_user_func($this->recv_cb, $raw);
	}
	
	public function on_write_ready() {
		_debug("on write ready called");
		$total = strlen($this->obuffer);
		$bytes = @fwrite($this->fd, $this->obuffer);
		$this->send_bytes += $bytes;
		
		_debug("sent ".$bytes."/".$this->send_bytes." of data");
		_debug($this->obuffer);
		
		$this->obuffer = substr($this->obuffer, $bytes, $total-$bytes);
		
		// unwatch for write if obuffer is empty
		if(strlen($this->obuffer) === 0) {
			JAXLLoop::unwatch($this->fd, array(
				'write' => true
			));
		}
		
		//_debug("current obuffer size: ".strlen($this->obuffer)."");
	}
	
}

?>
