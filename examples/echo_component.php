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
$comp = new JAXL(array(
	// same as component host
	'jid' => 'component.localhost',
	// same as component secret
	'pass' => 'secret',
	// required
	'host' => 'localhost',
	// required
	'port' => 5270
));

//
// XEP's required (required)
//
$comp->require_xep(array(
	'0114' // jabber component protocol
));

//
// add necessary event callbacks here
//

$comp->add_cb('on_auth_success', function() {
	echo "got on_auth_success cb\n";
});

$comp->add_cb('on_auth_failure', function($reason) {
	global $comp;
	$comp->send_end_stream();
	echo "got on_auth_failure cb with reason $reason\n";
});

$comp->add_cb('on_chat_message', function($stanza) {
	global $comp;
	
	// echo back incoming message stanza
	$stanza->to = $stanza->from;
	$stanza->from = $client->full_jid->to_string();
	$comp->send($stanza);
});

$comp->add_cb('on_disconnect', function() {
	echo "got on_disconnect cb\n";
});

//
// finally start configured xmpp stream
//
$comp->start();
echo "done\n";

?>