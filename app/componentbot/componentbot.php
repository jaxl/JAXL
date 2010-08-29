<?php
	
	/*
	 * Example componentbot application using Jaxl library
	 * Read more: http://bit.ly/aGpYf8
	*/
	
	// Initialize Jaxl Library
	$jaxl = new JAXL(array(
        'host' => JAXL_HOST_NAME,
        'domain' => JAXL_HOST_DOMAIN,
        'component' => JAXL_COMPONENT_HOST,
		'port' => JAXL_COMPONENT_PORT
	));

	// Include required XEP's
	jaxl_require('JAXL0114', $jaxl); // Jabber Component Protocol

	// Sample Component class
	class componentbot {
		
		function doAuth() {
            global $jaxl;
			$jaxl->log("Going for component handshake ...", 1);
			return JAXL_COMPONENT_PASS;
		}

		function postAuth() {
            global $jaxl;
			$jaxl->log("Component handshake completed ...", 1);
		}
		
		function getMessage($payloads) {
			global $jaxl;
			
			// echo back
			foreach($payloads as $payload) {
				$jaxl->sendMessage($payload['from'], $payload['body'], $payload['to']);
			}
		}
		
	}
	
	// Add callbacks on various event handlers
	$componentbot = new componentbot();
	JAXLPlugin::add('jaxl_pre_handshake', array($componentbot, 'doAuth'));
	JAXLPlugin::add('jaxl_post_handshake', array($componentbot, 'postAuth'));
	JAXLPlugin::add('jaxl_get_message', array($componentbot, 'getMessage'));
	
?>
