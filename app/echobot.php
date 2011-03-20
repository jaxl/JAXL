<?php
	
	/**
	 * Sample command line echobot client using Jaxl library
     * Usage: cd /path/to/jaxl/app
     * 		  Edit passed config array to Jaxl constructor below to suit your environment
     * 		  Run from command line as: /path/to/php componentbot.php
     * 		  View /var/log/jaxl.log for debug info
     * 
	 * Read More: http://jaxl.net/examples/echobot.php
	*/
	
	// Initialize Jaxl Library
    require_once '../core/jaxl.class.php';
	
    // Values passed to the constructor can also be defined as constants
    // List of constants can be found inside "../../env/jaxl.ini"
    // Note: Values passed to the constructor always overwrite defined constants
    $jaxl = new JAXL(array(
        'user'=>'username',
        'pass'=>'password',
        'host'=>'talk.google.com',
        'domain'=>'gmail.com',
        'authType'=>'PLAIN',
        'autoSubscribe'=>true,
        'pingInterval'=>60,
        'logLevel'=>4
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
            $jaxl->getRosterList();
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

		function postRosterUpdate($payload, $jaxl) {
            // Use $jaxl->roster which holds retrived roster list
            // print_r($jaxl->roster);

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
			    // print_r($payload);
            }
		}

        function postSubscriptionRequest($payload, $jaxl) {
            $jaxl->log("Subscription request sent to ".$payload['from']);
        }

        function postSubscriptionAccept($payload, $jaxl) {
            $jaxl->log("Subscription accepted by ".$payload['from']);
        }

        function getId($payload, $jaxl) {
            return $payload;
        }
		
	}
	
	// Add callbacks on various event handlers
	$echobot = new echobot();
	$jaxl->addPlugin('jaxl_post_auth', array($echobot, 'postAuth'));
    $jaxl->addPlugin('jaxl_get_message', array($echobot, 'getMessage'));
	$jaxl->addPlugin('jaxl_get_presence', array($echobot, 'getPresence'));
    $jaxl->addPlugin('jaxl_post_roster_update', array($echobot, 'postRosterUpdate'));
    $jaxl->addPlugin('jaxl_post_subscription_request', array($echobot, 'postSubscriptionRequest'));
    $jaxl->addPlugin('jaxl_post_subscription_accept', array($echobot, 'postSubscriptionAccept'));
    $jaxl->addPlugin('jaxl_get_id', array($echobot, 'getId'));

    // Fire start Jaxl core
    $jaxl->startCore("stream");

?>
