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
	private $recv_cb = null;
	private $recv_secs = 0;
	private $recv_usecs = 200000;
	private $recv_chunk_size = 1024;
	
	private $send_bytes = 0;
	private $send_secs = 0;
	private $send_usecs = 100000;
	
	private $clock = 0;
	private $time = 0;
	
	public function __construct($host="localhost", $port=5222, $stream_context=null) {
		$this->host = $host;
		$this->port = $port;
		$this->stream_context = $stream_context;
		if($this->port == 5223)
			$this->transport = 'ssl';
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
		
		//_debug("trying ".$remote_socket."");
		$this->fd = @stream_socket_client($remote_socket, $this->errno, $this->errstr, $this->timeout, STREAM_CLIENT_CONNECT, $this->stream_context);
		
		if($this->fd) {
			//_debug("connected to ".$remote_socket."");
			stream_set_blocking($this->fd, $this->blocking);
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
	
	public function recv() {
		$read = array($this->fd);
		$write = $except = null;
		$secs = $this->recv_secs; $usecs = $this->recv_usecs;
		
		$changed = @stream_select($read, $write, $except, $secs, $usecs);
		if($changed === false) {
			_debug("error while selecting stream for read");
			//print_r(stream_get_meta_data($this->fd));
			$this->disconnect();
			return;
		}
		else if($changed === 1) {
			$raw = @fread($this->fd, $this->recv_chunk_size);
			$bytes = strlen($raw);
			
			if($bytes === 0) {
				$meta = stream_get_meta_data($this->fd);
				if($meta['eof'] === TRUE) {
					_debug("socket has reached eof, closing now");
					$this->disconnect();
					return;
				}
			}
			
			$this->recv_bytes += $bytes;
			$total = $this->ibuffer.$raw;
			
			$this->ibuffer = "";
			_debug("read ".$bytes."/".$this->recv_bytes." of data");
			_debug($raw);
			
			// callback
			if($this->recv_cb) call_user_func($this->recv_cb, $raw);
		}
		else if($changed === 0) {
			//_debug("nothing changed while selecting for read");
			$this->clock = $this->recv_secs + $this->recv_usecs/pow(10,6);
		}
		
		if($this->obuffer != "") $this->flush();
	}
	
	public function send($data) {
		$this->obuffer .= $data;
	}
	
	protected function flush() {
		$read = $except = array();
		$write = array($this->fd);
		$secs = $this->send_secs; $usecs = $this->send_usecs;
		
		$changed = @stream_select($read, $write, $except, $secs, $usecs);
		if($changed === false) {
			_debug("error while selecting stream for write");
			print_r(@stream_get_meta_data($this->fd));
			$this->disconnect();
			return;
		}
		else if($changed === 1) {
			$total = strlen($this->obuffer);
			$bytes = @fwrite($this->fd, $this->obuffer);
			$this->send_bytes += $bytes;
			
			_debug("sent ".$bytes."/".$this->send_bytes." of data");
			_debug($this->obuffer);
			
			$this->obuffer = substr($this->obuffer, $bytes, $total-$bytes);
			//_debug("current obuffer size: ".strlen($this->obuffer)."");
		}
		else if($changed === 0) {
			_debug("nothing changed while selecting for write");
		}
	}
	
}

?>
