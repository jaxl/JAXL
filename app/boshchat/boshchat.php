<?php
	
	// Initialize Jaxl Library
	$jaxl = new JAXL();
	$jaxl->action = $_REQUEST['jaxl'];
		
	// Proceed with bosh request only if
	if($jaxl->mode != "cgi" || !isset($_REQUEST['jaxl'])) {
		JAXLog::log("Invalid BOSH request...");
		exit;
	}
	
	// Include required XEP's
	jaxl_require(array(
		'JAXL0115', // Entity Capabilities
		'JAXL0085', // Chat State Notification
		'JAXL0092', // Software Version
		'JAXL0203', // Delayed Delivery
		'JAXL0199',  // XMPP Ping
		'JAXL0206',
		'JAXL0124'
	));
	
	// Sample Bosh chat application class
	class boshchat {
		
		public static function doAuth($mechanism) {
			global $jaxl;
			$jaxl->auth("DIGEST-MD5");
		}
		
		public static function postAuth() {
			global $jaxl;		
			header('Content-type: application/json');
			echo json_encode(array('jaxl'=>'connected', 'jid'=>$jaxl->jid));
		}
		
		public static function postDisconnect() {
			header('Content-type: application/json');
			echo json_encode(array('jaxl'=>'disconnected'));
		}
		
		public static function postEmptyBody($body) {
			if($body == "<body xmlns='http://jabber.org/protocol/httpbind'>") {
				header('Content-type: application/json');
				echo json_encode(array('jaxl'=>'pinged'));
			}
		}
		
	}
	
	// Add callbacks on various event handlers
	JAXLPlugin::add('jaxl_get_auth_mech', array('boshchat', 'doAuth'));
	JAXLPlugin::add('jaxl_post_auth', array('boshchat', 'postAuth'));
	JAXLPlugin::add('jaxl_post_disconnect', array('boshchat', 'postDisconnect'));
	JAXLPlugin::add('jaxl_get_empty_body', array('boshchat', 'postEmptyBody'));
	
	// Handle incoming bosh request
	switch($jaxl->action) {
		case 'connect':
			$jaxl->user = $_POST['user'];
			$jaxl->pass = $_POST['pass'];
			JAXL0206::startStream(JAXL_HOST_NAME, JAXL_HOST_PORT);
			break;
		case 'disconnect':
			JAXL0206::endStream();
			break;
		case 'ping':
			JAXL0206::ping();
			break;
		default:
			header('Content-type: application/json');
			echo json_encode(array('jaxl'=>'400', 'desc'=>'Bad request'));
			break;
	}
	
?>
