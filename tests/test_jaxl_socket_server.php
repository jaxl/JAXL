<?php

require_once 'jaxl.php';
JAXLLogger::$level = JAXL_DEBUG;

// AF_UNIX sock path
$sock = 'unix://'.JAXL_CWD.'/.jaxl/sock/server.sock';

// server start
if($argc == 1) {

require_once JAXL_CWD.'/core/jaxl_socket_server.php';
$server = null;
	
function on_request($client, $raw) {
	global $server;
	$server->send($client, $raw);
	_debug("got client callback ".$raw);
}

@unlink($sock);
$server = new JAXLSocketServer($sock, 'on_request');

}
// client start
else {

require_once JAXL_CWD.'/core/jaxl_socket_client.php';
$client = null;

function on_response($raw) {
	global $client;
	_debug("got response ".$raw);
}

$client = new JAXLSocketClient();
$client->set_callback('on_response');
$client->connect($sock);
$client->send("hello world!");

}

JAXLLoop::run();
echo "done\n";

?>
