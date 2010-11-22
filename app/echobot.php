<?php
	
	/*
	 * Sample command line echobot using Jaxl library
     * Usage: php echobot.php
	 * Read More: http://bit.ly/bz9KXb
	*/
	
	// Initialize Jaxl Library
    require_once '../core/jaxl.class.php';

    // Values passed to the constructor can also be defined as constants
    // List of constants can be found inside "../../env/jaxl.ini"
    // Note: Values passed to the constructor always overwrite defined constants
    $jaxl = new JAXL(array(
        'user'=>'',
        'pass'=>'',
        'host'=>'talk.google.com',
        'domain'=>'gmail.com',
        'authType'=>'PLAIN',
        'pingInterval'=>60,
        'logLevel'=>5
    ));
	
	// Include required XEP's
	$jaxl->requires(array(
		'JAXL0115', // Entity Capabilities
		'JAXL0092', // Software Version
        'JAXL0199', // XMPP Ping
		'JAXL0203', // Delayed Delivery
        'JAXL0202'  // Entity Time
	));
	
	// Sample Echobot class
	class echobot {
		
		function postAuth($payload, $jaxl) {
			$jaxl->discoItems($jaxl->domain, array($this, 'handleDiscoItems'));
            $jaxl->getRosterList(array($this, 'handleRosterList'));
		}

        function handleDiscoItems($payload, $jaxl) {
            if(!is_array($payload['queryItemJid']))
                return $payload;

            $items = array_unique($payload['queryItemJid']);
            foreach($items as $item)
                $jaxl->discoInfo($item, array($this, 'handleDiscoInfo'));
        }

        function handleDiscoInfo($payload, $jaxl) {
            // print_r($payload);
        }

		function handleRosterList($payload, $jaxl) {
			if(is_array($payload['queryItemJid'])) {
				foreach($payload['queryItemJid'] as $key=>$jid) {
					$group = $payload['queryItemGrp'][$key];
					$subscription = $payload['queryItemSub'][$key];
				}
			}
			
            // set echobot status
            $jaxl->setStatus(false, false, false, true);
		}
		
		function getMessage($payloads, $jaxl) {
			foreach($payloads as $payload) {
				if($payload['offline'] != JAXL0203::$ns) {
					if(strlen($payload['body']) > 0) {
						// echo back the incoming message
						$jaxl->sendMessage($payload['from'], $payload['body']);
					}
				}
			}
		}
		
		function getPresence($payloads, $jaxl) {
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
	JAXLPlugin::add('jaxl_post_auth', array($echobot, 'postAuth'));
	JAXLPlugin::add('jaxl_get_message', array($echobot, 'getMessage'));
	JAXLPlugin::add('jaxl_get_presence', array($echobot, 'getPresence'));

    // Fire start Jaxl core
    $jaxl->startCore("stream");

?>
