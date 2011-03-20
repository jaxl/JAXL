<?php

    /**
     * Sample command line bot for sending a message
     * Usage: cd /path/to/jaxl
     * 	      Edit username/password below
     * 		  Run from command line: /path/to/php sendMessage.php "username@gmail.com" "Your message"
     * 		  View jaxl.log for detail
     * 
     * Read More: http://jaxl.net/examples/sendMessage.php
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
        'logLevel'=>4
    ));

    // Post successful auth send desired message
    function postAuth($payload, $jaxl) {
        global $argv;
        $jaxl->sendMessage($argv[1], $argv[2]);
        $jaxl->shutdown();
    }

    // Register callback on required hooks
    $jaxl->addPlugin('jaxl_post_auth', 'postAuth');

    // Fire start Jaxl core
    $jaxl->startCore("stream");

?>
