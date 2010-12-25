<?php

    /**
     * Pre-fetch XMPP/Jabber data for webpage without using BOSH XEP or Ajax requests
     *
     * This sample application demonstrate how to pre-fetch XMPP data from the jabber server
     * Specifically, this app will fetch logged in user VCard from the jabber server
     * Pre-fetched data can later be htmlized and displayed on the webpage
     *
     * Usage:
     * ------
     * 1) Put this file under your web folder
     * 2) Edit user/pass/domain/host below for your account
     * 3) Hit this file in your browser
     *
     * View jaxl.log for detail
    */

    // include JAXL core
    require_once '/usr/share/php/jaxl/core/jaxl.class.php';
    
    // initialize JAXL instance
    $xmpp = new JAXL(array(
        'user'=>'',
        'pass'=>'',
        'domain'=>'localhost',
        'host'=>'localhost',
        'logLevel'=>5
    ));

    // Force CLI mode since this app runs from browser but we don't intend to use BOSH or Ajax
    $xmpp->mode = "cli";

    // Demo requires VCard XEP
    $xmpp->requires('JAXL0054');
    
    function postConnect($payload, $xmpp) {
        $xmpp->startStream();
    }

    function doAuth($mechanism, $xmpp) {
        $xmpp->auth('DIGEST-MD5');
    }

    function postAuth($payload, $xmpp) {
        $xmpp->JAXL0054('getVCard', false, $xmpp->jid, 'handleVCard');
    }

    function handleVCard($payload, $xmpp) {
        echo "<b>Successfully fetched VCard</b><br/>";
        print_r($payload);
        $xmpp->shutdown();
    }

    function postAuthFailure($payload, $xmpp) {
        echo "OOPS! Auth failed";
    }

    // Register callbacks for required events
    $xmpp->addPlugin('jaxl_post_connect', 'postConnect');
    $xmpp->addPlugin('jaxl_get_auth_mech', 'doAuth');
    $xmpp->addPlugin('jaxl_post_auth', 'postAuth');
    $xmpp->addPlugin('jaxl_post_auth_failure', 'postAuthFailure');

    // Fire start JAXL Core
    $xmpp->startCore();

?>
