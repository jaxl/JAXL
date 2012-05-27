Jaxl v3.x (A work in progress):
--------------------------------
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

* `JAXLEvent`       generic event registry and execution class
* `JAXLSocket`      generic socket level operations
* `JAXLXmlStream`   generic SAX style XML parser
* `JAXLXml`         generic XML object implementation
* `XMPPStream`      abstract xmpp stream
* `XMPPIq`          xmpp iq stanza object
* `XMPPMsg`         xmpp msg stanza object
* `XMPPPres`        xmpp pres stanza object
* `XMPPStanza`      any xmpp stanza object
* `XMPPXep`         abstract xmpp extension
* `XMPPJid`         xmpp jid object

Getting Started:
----------------
1) include `jaxl.php` and initialize a new JAXL instance

<pre>
$cfg = array('jid'=>'user@domain.dtl', 'pass'=>'password', ...);
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
</pre>
   
3) finally start configured JAXL instance

<pre>
$xmpp->start();
</pre>