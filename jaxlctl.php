#!/usr/bin/env php
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

if($argc < 2) {
	echo "Usage: $argv[0] /path/to/JAXL/.jaxl/sock/jaxl_XXXXX.sock\n";
	exit;
}

require_once 'jaxl.php';
JAXLLogger::$level = JAXL_INFO;

class JAXLCtl {
	
	protected $client = null;
	protected $cli = null;
	
	public function __construct() {
		global $argv;
		
		$this->client = new JAXLSocketClient();
		$this->client->set_callback(array(&$this, 'on_response'));
		$this->client->connect('unix://'.$argv[1]);
		
		$this->cli = new JAXLCli(array(&$this, 'on_shell_input'));
	}
	
	public function run() {
		JAXLCli::prompt();
		JAXLLoop::run();
	}
	
	public function on_shell_input($raw) {
		$this->client->send($raw);
	}
	
	public function on_response($raw) {
		$ret = unserialize($raw);
		print_r($ret);
		echo PHP_EOL;
		JAXLCli::prompt();
	}

}

$ctl = new JAXLCtl();
$ctl->run();
echo "done\n";

?>
