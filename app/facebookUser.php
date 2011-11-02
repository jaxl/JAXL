<?php

require_once '../core/jaxl.class.php';


$jaxl = new JAXL(array(
    'user'=> '',	 		// username as in Facebook.com > Settings >  Account settings > Username
    'pass'=> '',			// password (for auth with access token, see facebookToken.php example)
    'host'=> 'chat.facebook.com',
    'domain'=> 'chat.facebook.com',
    'authType'=> 'DIGEST-MD5',
    'logLevel'=> 5,
));

// Send message after successful authentication
function postAuth($payload, $jaxl) {
    $jaxl->sendMessage("-1234567890@chat.facebook.com", "hello!");
    $jaxl->shutdown();
}

// Register callback on required hook (callback'd method will always receive 2 params)
$jaxl->addPlugin('jaxl_post_auth', 'postAuth');

// Start Jaxl core
$jaxl->startCore('stream');


