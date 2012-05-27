Jaxl v3.x:
-----------
Jaxl v3.x is a successor of v2.x (and is NOT backward compatible), 
carrying a lot of code from v2.x while throwing away the redundant part.
Several components have been re-written keeping in my mind feedback from
the developer community over the last 4 years.

Jaxl v3.x is an object oriented, non-blocking, event based modular 
XMPP client/component library.

Structure:
----------
Library src folder contains following sub-folders:

* `/examples`   a bunch of working examples
* `/xmpp`       contains generic xmpp rfc implementation
* `/xep`        contains various xmpp xep implementation
* `/core`       contains generic networking and event components
* `/tests`      test suite
* `/jaxl.php`   main file

With v3.x, every thing has been mapped into an object:

* `JAXLEvent`       event registry and emitter class
* `JAXLSocket`      socket level operations
* `JAXLXmlStream`   streaming XML parser
* `JAXLXml`         internal XML object implementation
* `XMPPStream`      base xmpp rfc implementation
* `XMPPStanza`      wrapper over `JAXLXml` for easy access patterns
* `XMPPIq`          xmpp iq stanza object (extends `XMPPStanza`)
* `XMPPMsg`         xmpp msg stanza object (extends `XMPPStanza`)
* `XMPPPres`        xmpp pres stanza object (extends `XMPPStanza`)
* `XMPPXep`         abstract xmpp extension (extended by every XEP implementation)
* `XMPPJid`         xmpp jid object

Getting Started:
----------------
1) include `jaxl.php` and initialize a new JAXL instance

<pre>
$cfg = array('jid'=>'user@domain.dtl', 'pass'=>'password');
$xmpp = new JAXL($cfg);
</pre>
   
2) register callbacks on events

<pre>
$xmpp->add_cb('on_auth_success', function() {
	global $xmpp;
	$xmpp->set_status("available!");  // set your status
	$xmpp->get_vcard();               // fetch your vcard
	$xmpp->get_roster();              // fetch your roster list
});

$xmpp->add_cb('on_chat_message', function($msg) {
	global $xmpp;
	
	// echo back
	$msg->to = $msg->from;
	$msg->from = $xmpp->full_jid->to_string();
	$xmpp->send($msg);
});
</pre>
   
3) finally start configured JAXL instance

<pre>
$xmpp->start();
</pre>