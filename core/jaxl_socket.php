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
	private $blocking = 0;
	
	public $compressed = false;
	
	public $fd = null;
	
	private $errno = null;
	private $errstr = null;
	private $timeout = 10;
	
	private $ibuffer = "";
	private $obuffer = "";
	
	private $recv_bytes = 0;
	private $send_bytes = 0;
	
	private $recv_cb = null;
	
	// TODO: logic moves to jaxl class
	// after cth failed attempt
	// retry connect after k * $retry_interval seconds
	// where k is a random number between 0 and 2^c − 1.
	public $retry = true;
	private $retry_interval = 1;
	private $retry_attempt = 0;
	private $retry_max = 10; // -1 means infinite
	private $retry_max_interval = 64; // 2^5 seconds
	
	public function __construct($host="localhost", $port=5222) {
		$this->host = $host;
		$this->port = $port;
	}
	
	public function __destruct() {
		echo "cleaning up xmpp socket...\n";
		$this->disconnect();
	}
	
	public function set_callback($recv_cb) {
		$this->recv_cb = $recv_cb;
	}
	
	public function connect($host=null, $port=null) {
		$this->host = $host ? $host : $this->host;
		$this->port = $port ? $port : $this->port;
		
		$remote_socket = $this->transport."://".$this->host.":".$this->port;
		$flags = STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT;

		echo "trying ".$remote_socket."\n";
		$this->fd = @stream_socket_client($remote_socket, $this->errno, $this->errstr, $this->timeout, $flags);
		if($this->fd) {
			echo "connected to ".$remote_socket."\n";
			stream_set_blocking($this->fd, $this->blocking);
			return true;
		}
		// 110 : Connection timed out
		// 111 : Connection refused
		else if($this->errno == 110 || $this->errno == 111) {
			$retry_after = pow(2, $this->retry_attempt) * $this->retry_interval;
			$this->retry_attempt++;
			
			echo "unable to connect, will try again in ".$retry_after." seconds\n";
			sleep($retry_after);
			
			$this->connect($host, $port);
		}
		else {
			echo "unable to connect ".$remote_socket." with error no: ".$this->errno.", error str: ".$this->errstr."\n";
			$this->disconnect();
			return false;
		}
	}
	
	public function disconnect() {
		@fclose($this->fd);
		$this->fd = null;
	}
	
	public function recv() {
		$read = array($this->fd);
		$write = $except = null;
		$secs = 0; $usecs = 200000;
		
		$changed = @stream_select($read, $write, $except, $secs, $usecs);
		if($changed === false) {
			echo "error while selecting stream for read\n";
			print_r(stream_get_meta_data($this->fd));
			$this->disconnect();
			return;
		}
		else if($changed === 1) {
			$raw = @fread($this->fd, 1024);
			if($this->compressed) $raw = gzuncompress($raw);
			$bytes = strlen($raw);
			
			if($bytes === 0) {
				$meta = stream_get_meta_data($this->fd);
				if($meta['eof'] === TRUE) {
					echo "socket has reached eof, closing now\n";
					$this->disconnect();
					return;
				}
			}
			
			$this->recv_bytes += $bytes;
			$total = $this->ibuffer.$raw;
			$this->ibuffer = "";
			echo "read ".$bytes."/".$this->recv_bytes." of data\n";
			echo $raw."\n\n";
			
			// callback
			if($this->recv_cb) call_user_func($this->recv_cb, $raw);
		}
		//else if($changed === 0) {
			//echo "nothing changed while selecting for read\n";
		//}
		
		if($this->obuffer != "") $this->flush();
	}
	
	public function send($data) {
		if($this->compressed) $data = gzcompress($data);
		$this->obuffer .= $data;
	}
	
	protected function flush() {
		$read = $except = array();
		$write = array($this->fd);
		$secs = 0; $usecs = 100000;
		
		$changed = @stream_select($read, $write, $except, $secs, $usecs);
		if($changed === false) {
			echo "error while selecting stream for write\n";
			print_r(stream_get_meta_data($this->fd));
			$this->disconnect();
			return;
		}
		else if($changed === 1) {
			$total = strlen($this->obuffer);
			$bytes = @fwrite($this->fd, $this->obuffer);
			$this->send_bytes += $bytes;
			
			echo "sent ".$bytes."/".$this->send_bytes." of data\n";
			echo $this->obuffer."\n\n";
			
			$this->obuffer = substr($this->obuffer, $bytes, $total-$bytes);
			//echo "current obuffer size: ".strlen($this->obuffer)."\n";
		}
		//else if($changed === 0) {
			//echo "nothing changed while selecting for write\n";
		//}
	}
	
}

?>
