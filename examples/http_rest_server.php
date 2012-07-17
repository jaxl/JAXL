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

// include and configure logger
require_once 'jaxl.php';
JAXLLogger::$level = JAXL_INFO;

// print usage notice and parse addr/port parameters if passed
_colorize("Usage: $argv[0] port (default: 9699)", JAXL_NOTICE);
$port = ($argc == 2 ? $argv[1] : 9699);

// initialize http server
require_once JAXL_CWD.'/http/http_server.php';
$http = new HTTPServer($port);

// callback method for dispatch rule (see below)
function index($request) {
	$request->send_response(
		200, array('Content-Type'=>'text/html'), 
		'<html><head/><body><h1>Jaxl Http Server</h1><a href="/upload">upload a file</a></body></html>'
	);
	$request->close();
}

// callback method for dispatch rule (see below)
function upload($request) {
	if($request->method == 'GET') {
		$request->send_response(
			200, array('Content-Type'=>'text/html'),
			'<html><head/><body><h1>Jaxl Http Server</h1><form enctype="multipart/form-data" method="POST" action=""><input type="file" name="file"/><input type="submit" value="upload"/></form></body></html>'
		);
	}
	else if($request->method == 'POST') {
		if($request->body === null && $request->expect) {
			$request->recv_body();
		}
		else {
			// got upload body, save it
			_debug("file upload complete, got ".strlen($request->body)." bytes of data");
			$request->close();
		}
	}
}

// add dispatch rules with callback method as first argument
$index = array('index', '^/$');
$upload = array('upload', '^/upload', array('GET', 'POST'));
$rules = array($index, $upload);
$http->dispatch($rules);

// start http server
$http->start();
