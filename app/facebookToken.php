<?php

require_once '../core/jaxl.class.php';


$jaxl = new JAXL(array(
    'user'=>'987654321@chat.facebook.com', 			// user id
    'pass'=>'',							// no password; access_token is given through the jaxl_get_facebook_key plugin with the help of the  function getFacebookKey
    'host'=>'chat.facebook.com',
    'domain'=>'chat.facebook.com',
    'authType'=>'X-FACEBOOK-PLATFORM',
    'logLevel'=>10,
));

// Send message after successful authentication
function postAuth($payload, $jaxl) {
    $jaxl->sendMessage("-1234567890@chat.facebook.com", "bye!");
    $jaxl->shutdown();
}

function getFacebookKey() {
	return array(
		'secret_key', 		// Your application secret key
		'app_id', 		// Your application api key (app_id)
		'access_token' 		// Connecting user session key (access_token)
	);
}

// Register callback on required hook (callback'd method will always receive 2 params)
$jaxl->addPlugin('jaxl_post_auth', 'postAuth');
$jaxl->addPlugin('jaxl_get_facebook_key', 'getFacebookKey');

// Start Jaxl core
$jaxl->startCore('stream');


