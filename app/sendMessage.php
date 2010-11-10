<?php

    /**
     * Sample command line bot for sending a message
     * Usage: php sendMessage.php "username@gmail.com" "Your message"
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
        'logLevel'=>5
    ));

    // Post successful auth send desired message
    function postAuth() {
        global $jaxl, $argv;
        $jaxl->sendMessage($argv[1], $argv[2]);
        $jaxl->shutdown();
    }

    // Register callback on required hooks
    JAXLPlugin::add('jaxl_post_auth', 'postAuth');

    // Fire start Jaxl core
    $jaxl->startCore("stream");

?>
