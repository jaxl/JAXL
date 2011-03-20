<?php

    /**
     * Pre-fetch XMPP/Jabber data using BOSH before loading a web page
     *
     * This sample application demonstrate how to pre-fetch XMPP data from the jabber server
     * Specifically, this app will fetch logged in user VCard from the jabber server
     * Pre-fetched data can later be htmlized and displayed on the webpage
     *
     * Usage: Symlink or copy whole Jaxl library folder inside your web folder
     * 		  Edit user/pass/domain/host below for your account
     * 		  Run this app file from the browser e.g. http://path/to/jaxl/app/preFetchBOSH.php
     * 		  View /var/log/jaxl.log and your web server error log for debug info
     * 
     * Read More: http://jaxl.net/examples/preFetchBOSH.php
    */

    // include JAXL core
    require_once '../core/jaxl.class.php';
    
    // initialize JAXL instance
    $xmpp = new JAXL(array(
        'user'=>'',
        'pass'=>'',
        'domain'=>'localhost',
        'boshHost'=>'localhost',
        'boshPort'=>5280,
        'boshSuffix'=>'http-bind',
        'boshOut'=>false, // Disable auto-output of Jaxl Bosh Module
        'logLevel'=>4
    ));
    
    // Include required XEP's
    $xmpp->requires(array(
        'JAXL0054', // VCard
        'JAXL0206'  // XMPP over Bosh
    ));

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
        $xmpp->JAXL0206('endStream');
    }

    function postDisconnect($payload, $xmpp) {
        exit;
    }

    function postAuthFailure($payload, $xmpp) {
        echo "OOPS! Auth failed";
    }

    // Register callbacks for required events
    $xmpp->addPlugin('jaxl_get_auth_mech', 'doAuth');
    $xmpp->addPlugin('jaxl_post_auth', 'postAuth');
    $xmpp->addPlugin('jaxl_post_auth_failure', 'postAuthFailure');
    $xmpp->addPlugin('jaxl_post_disconnect', 'postDisconnect');

    // Fire start Jaxl in bosh mode
    $xmpp->startCore('bosh');

?>
