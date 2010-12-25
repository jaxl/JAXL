<?php
	
	/*
	 * Sample command line XMPP component bot using Jaxl library
     * Usage: php componentbot.php
	 * Read more: http://bit.ly/aGpYf8
	*/
	
	// Initialize Jaxl Library
    require_once '../core/jaxl.class.php';
	$jaxl = new JAXL(array(
		'port'=>5559,
        'compHost'=>'component.localhost',
        'compPass'=>'',
        'logLevel'=>5
	));

	// Include required XEP's
	$jaxl->requires('JAXL0114'); // Jabber Component Protocol

	// Sample Component class
	class componentbot {
		
		function postAuth($payload, $jaxl) {
			$jaxl->log("Component handshake completed ...");
		}
		
		function getMessage($payloads, $jaxl) {
			foreach($payloads as $payload)
				if(strlen($payload['body']) > 0)
                    $jaxl->sendMessage($payload['from'], $payload['body'], $payload['to']);	
		}
		
	}
	
	// Add callbacks on various event handlers
	$componentbot = new componentbot();
	$jaxl->addPlugin('jaxl_post_handshake', array($componentbot, 'postAuth'));
	$jaxl->addPlugin('jaxl_get_message', array($componentbot, 'getMessage'));

    // Fire start Jaxl core
    $jaxl->startCore("component");

?>
