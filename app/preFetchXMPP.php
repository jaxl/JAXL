<?php

    /**
     * Pre-fetch XMPP/Jabber data for webpage without using BOSH extension or Ajax requests
     *
     * This sample application demonstrate how to pre-fetch XMPP data from the jabber server
     * Specifically, this app will fetch logged in user VCard from the jabber server
     * Pre-fetched data can later be htmlized and displayed on the webpage
     *
     * Usage: Put this file under your web folder
     * 		  Edit user/pass/domain/host below for your account
     * 		  Hit this file in your browser
     *		  View jaxl.log and your web server error log for debug info
     *
     * Read More: http://jaxl.net/examples/preFetchXMPP.php
    */

    // include JAXL core
    require_once '../core/jaxl.class.php';
    
    // initialize JAXL instance
    $xmpp = new JAXL(array(
        'user'=>'username',
        'pass'=>'password',
        'domain'=>'localhost',
        'logLevel'=>4,
        // Force CLI mode since this app runs from browser but we don't intend to use BOSH or Ajax
        'mode'=>'cli'
    ));

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
        echo "<form action='' method=''>";
        echo "<img src='data:".$payload['vCardPhotoType'].";base64,".$payload['vCardPhotoBinVal']."' alt='".$payload['vCardFN']."' title='".$payload['vCardFN']."'/>";
        echo "<p><b>Nickname:</b>".$payload['vCardNickname']."</p>";
        echo "<p><b>Url:</b>".$payload['vCardUrl']."</p>";
        echo "<p><b>BDay:</b>".$payload['vCardBDay']."</p>";
        echo "<p><b>OrgName:</b>".$payload['vCardOrgName']."</p>";
        echo "<p><b>OrgUnit:</b>".$payload['vCardOrgUnit']."</p>";
        echo "<p><b>Title:</b>".$payload['vCardTitle']."</p>";
        echo "<p><b>Role:</b>".$payload['vCardRole']."</p>";
        echo "<p><b>Desc:</b>".$payload['vCardDesc']."</p>";
        echo "<input type='button' name='submit' value='Submit'/>";
        echo "</form>";
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
