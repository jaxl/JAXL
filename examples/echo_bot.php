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

if($argc < 3) {
	echo "Usage: $argv[0] jid pass\n";
	exit;
}

//
// initialize JAXL object with initial config
//
require_once 'jaxl.php';
$client = new JAXL(array(
	// (required) credentials
	'jid' => $argv[1],
	'pass' => $argv[2],
	
	// (optional) srv lookup is done if not provided
	//'host' => 'xmpp.domain.tld',

	// (optional) result from srv lookup used by default
	//'port' => 5222,

	// (optional) defaults to false
	//'force_tls' => true,

	// (optional)
	//'resource' => 'resource',
	
	// (optional) defaults to PLAIN if supported, else other methods will be automatically tried
	'auth_type' => @$argv[3] ? $argv[3] : 'PLAIN'
));

//
// add necessary event callbacks here
//

$client->add_cb('on_auth_success', function() {
	global $client;
	_debug("got on_auth_success cb, jid ".$client->full_jid->to_string());
	
	// set status
	$client->set_status("available!", "dnd", 10);
	
	// fetch vcard
	$client->get_vcard();
	
	// fetch roster list
	$client->get_roster();
});

$client->add_cb('on_auth_failure', function($reason) {
	global $client;
	$client->send_end_stream();
	_debug("got on_auth_failure cb with reason $reason");
});

$client->add_cb('on_chat_message', function($stanza) {
	global $client;
	
	if($stanza->type == 'chat') {
		// echo back incoming chat message stanza
		$stanza->to = $stanza->from;
		$stanza->from = $client->full_jid->to_string();
		$client->send($stanza);
	}
});

$client->add_cb('on_presence_stanza', function($stanza) {
	global $client;
	
	$type = ($stanza->type ? $stanza->type : "available");
	$show = ($stanza->show ? $stanza->show : "???");
	_debug($stanza->from." is now ".$type." ($show)");
	
	if($type == "available") {
		// fetch vcard
		$client->get_vcard($stanza->from);
	}
});

$client->add_cb('on_disconnect', function() {
	_debug("got on_disconnect cb");
});

//
// finally start configured xmpp stream
//
$client->start();
echo "done\n";

?>