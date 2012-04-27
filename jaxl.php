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

declare(ticks = 1);

require_once 'xmpp/xmpp_stream.php';
require_once 'core/jaxl_event.php';

class JAXL {
	
	protected $cfg = array();
	protected $ev = null;
	protected $xmpp = null;
	
	public function __construct($config) {
		pcntl_signal(SIGINT, array(&$this, 'signal_handler'));
		pcntl_signal(SIGTERM, array(&$this, 'signal_handler'));
		
		$this->cfg = $config;
		$this->ev = new JAXLEvent();
		
		$this->xmpp = new XMPPStream(
			$this->cfg['user']."@".$this->cfg['domain'], 
			$this->cfg['pass'], 
			$this->cfg['auth_type']
		);
	}
	
	public function __destruct() {
		
	}
	
	public function start($as) {
		switch($as) {
			case 'client':
				$this->start_client();
				break;
			case 'component':
				break;
			default:
				break;
		}
	}
	
	public function add_cb($ev, $cb, $pri=1) {
		return $this->ev->add($ev, $cb, $pri);
	}
	
	public function del_cb($ref) {
		$this->ev->del($ref);
	}
	
	public function signal_handler($sig) {
		$this->xmpp->end_stream();
		switch($sig) {
			case SIGINT:
				echo "caught sigint\n";
				break;
			case SIGTERM:
				echo "caught sigterm\n";
				break;
		}
	}
	
	private function start_client() {
		if($this->xmpp->connect($this->cfg['host'])) {
			$this->xmpp->start_stream();
			while($this->xmpp->sock->fd) {
				$this->xmpp->sock->recv();
			}
		}
	}
	
}

?>