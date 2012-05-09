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

//
// initialize JAXL object with initial config
//
require_once 'jaxl.php';
$client = new JAXL(array(
	'jid' => 'user@domain.tld',
	'pass' => 'password',
	// (optional) srv lookup is done if not provided
	//'host' => 'xmpp.domain.tld',
	// (optional) result from srv lookup used by default
	//'port' => 5222,
	// (optional)
	'auth_type' => 'PLAIN'
));

//
// XEP's required
//
$client->requires(array(
	'0115', // entity capibilities
	'0092', // software version
	'0199', // xmpp ping
	'0203', // delayed delivery
	'0202'  // entity time
));

//
// add necessary event callbacks here
//
$client->add_cb('on_connect', function() {
	echo "got on_connect cb\n";
});

$client->add_cb('on_connect_error', function($errno, $errstr) {
	echo "got on_connect_error cb with errno $errno and errstr $errstr\n";
});

$client->add_cb('on_auth_success', function() {
	echo "got on_auth_success cb\n";
});

$client->add_cb('on_auth_failure', function($reason) {
	global $client;
	$client->send_end_stream();
	echo "got on_auth_failure cb with reason $reason\n";
});

$client->add_cb('on_disconnect', function() {
	echo "got on_disconnect cb\n";
});

//
// finally start configured xmpp stream
//
$client->start();

?>