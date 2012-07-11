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

// http pre bind php script
$body = file_get_contents("php://input");
$body = new SimpleXMLElement($body);
$attrs = $body->attributes();

if(!@$attrs['to'] && !@$attrs['rid'] && !@$attrs['wait'] && !@$attrs['hold']) {
	echo "invalid input";
	exit;
}

define('JAXL_SRC_DIR', '/Users/abhinavsingh/git/JAXL');

//
// initialize JAXL object with initial config
//
require_once JAXL_SRC_DIR.'/jaxl.php';

$to = $attrs['to'];
$rid = $attrs['rid'];
$wait = $attrs['wait'];
$hold = $attrs['hold'];
echo $to." ".$rid." ".$wait." ".$hold; exit;
list($host, $port) = JAXLUtil::get_dns_srv($to);

$client = new JAXL(array(
	'domain' => $to,
	'host' => $host,
	'port' => $port,
	'bosh_url' => 'http://localhost:5280/http-bind',
	'bosh_rid' => $rid,
	'bosh_wait' => $wait,
	'bosh_hold' => $hold,
	'auth_type' => 'ANONYMOUS'
));

$client->add_cb('on_auth_success', function() {
	global $client;
	_debug($client->full_jid->to_string());
	_debug($client->xeps['0206']->sid);
	_debug($client->xeps['0206']->rid);
	exit;
});

//
// finally start configured xmpp stream
//
$client->start();
echo "done\n";

?>