<?php
	
	/**
	 * Sample external jabber component echobot using Jaxl library
     * Usage: cd /path/to/jaxl/app
     * 		  Edit compHost, compPass and port below to suit your environment
     * 		  Run from command line as: /path/to/php componentbot.php
     * 		  View /var/log/jaxl.log for debug info
     * 
	 * Read more: http://jaxl.net/examples/componentbot.php
	*/
	
	// Initialize Jaxl Library
    require_once '../core/jaxl.class.php';
	$jaxl = new JAXL(array(
		'port'=>5559,
        'compHost'=>'component.localhost',
        'compPass'=>'secret',
        'logLevel'=>4
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
