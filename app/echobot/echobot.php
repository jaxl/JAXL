<?php
	
	/*
	 * Example echobot application using Jaxl library
	 * Read more: http://bit.ly/bz9KXb
	*/
	
	// Initialize Jaxl Library
	$jaxl = new JAXL();
	
	// Include required XEP's
	jaxl_require(array(
		'JAXL0115', // Entity Capabilities
		'JAXL0085', // Chat State Notification
		'JAXL0092', // Software Version
		'JAXL0203', // Delayed Delivery
		'JAXL0199'  // XMPP Ping
	), $jaxl);
	
	// Sample Echobot class
	class echobot {
		
		function startStream() {
			global $jaxl;
			$jaxl->startStream();
		}
		
		function doAuth($mechanism) {
			global $jaxl;
			switch(TRUE) {
				case in_array("ANONYMOUS",$mechanism):
					$jaxl->auth("ANONYMOUS");
					break;
				case in_array("DIGEST-MD5",$mechanism):
					$jaxl->auth("DIGEST-MD5");
					break;
				case in_array("PLAIN",$mechanism):
					$jaxl->auth("PLAIN");
					break;
				case in_array("X-FACEBOOK-PLATFORM",$mechanism):
					/*
					 * Facebook chat connect using Jaxl library
					 * Read more: http://bit.ly/dkdFjL
					*/
					$jaxl->auth("X-FACEBOOK-PLATFORM");
					break;
				default:
					die("No prefered auth method exists...");
					break;
			}
		}
		
		function postAuth() {
			global $jaxl;
			$jaxl->setStatus(FALSE, FALSE, FALSE, TRUE);
			$jaxl->getRosterList(array($this, 'handleRosterList'));
		}
		
		function handleRosterList($payload) {
			if(is_array($payload['queryItemJid'])) {
				foreach($payload['queryItemJid'] as $key=>$jid) {
					$group = $payload['queryItemGrp'][$key];
					$subscription = $payload['queryItemSub'][$key];
				}
			}
		}
		
		function getMessage($payloads) {
			global $jaxl;
			foreach($payloads as $payload) {
				if($payload['offline'] != JAXL0203::$ns
				&& (!$payload['chatState'] || $payload['chatState'] = 'active')
				) {
					if(strlen($payload['body']) > 0) {
						// echo back the incoming message
						$jaxl->sendMessage($payload['from'], $payload['body']);
					}
				}
			}
		}
		
		function getPresence($payloads) {
			global $jaxl;	
			foreach($payloads as $payload) {
				if($payload['type'] == "subscribe") {
					// accept subscription
					$jaxl->subscribed($payload['from']);
				
					// go for mutual subscription
					$jaxl->subscribe($payload['from']);
				}
				else {
					if($payload['type'] == "unsubscribe") {
						// accept subscription
						$jaxl->unsubscribed($payload['from']);

						// go for mutual subscription
						$jaxl->unsubscribe($payload['from']);
					}
				}
			}
		}
		
	}
	
	// Add callbacks on various event handlers
	$echobot = new echobot();
	JAXLPlugin::add('jaxl_post_connect', array($echobot, 'startStream'));
	JAXLPlugin::add('jaxl_get_auth_mech', array($echobot, 'doAuth'));
	JAXLPlugin::add('jaxl_post_auth', array($echobot, 'postAuth'));
	JAXLPlugin::add('jaxl_get_message', array($echobot, 'getMessage'));
	JAXLPlugin::add('jaxl_get_presence', array($echobot, 'getPresence'));

?>
