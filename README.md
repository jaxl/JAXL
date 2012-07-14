Jaxl v3.x:
-----------
Jaxl v3.x is a successor of v2.x (and is NOT backward compatible), 
carrying a lot of code from v2.x while throwing away the redundant part.
A lot of components have been re-written keeping in my mind feedback from
the developer community over the last 4 years.

Jaxl v3.x is an event-driven, non-blocking i/o based daemon writing library 
for custom TCP/IP client and servers implementation in PHP. 
It also bundles full blown HTTP and XMPP protocol stacks.

Read [why v3.x was written](https://groups.google.com/d/msg/jaxl/hjARH6oQEQo/vQ3RP5O5dLUJ) 
and what traffic it has served in the past.

Structure:
----------
Library src folder contains following sub-folders:

* `/examples`       a bunch of working examples
* `/xmpp`           contains generic xmpp rfc implementation
* `/xep`            contains various xmpp xep implementation
* `/core`           contains generic networking and event components
* `/tests`          test suite
* `/jaxl.php`       main file

In v3.x, everything that you will interact with will be an object. Usually 
that object will emit an event/callback, which we will be able to catch
in our application/daemons for custom processing/routing.

Core Stack:

* `JAXLLoop`            main select loop
* `JAXLClock`           timed job/callback dispatcher
* `JAXLEvent`           event registry and emitter
* `JAXLFsm`             generic finite state machine
* `JAXLSocketClient`    generic tcp/udp client
* `JAXLSocketServer`    generic tcp/udp server
* `JAXLXmlStream`       streaming XML parser
* `JAXLXml`             custom XML object implementation
* `JAXLLogger`          logging facility

XMPP Stack:

* `XMPPStream`          base xmpp rfc implementation
* `XMPPStanza`          provides easy access patterns over xmpp stanza (wraps `JAXLXml`)
* `XMPPIq`              xmpp iq stanza object (extends `XMPPStanza`)
* `XMPPMsg`             xmpp msg stanza object (extends `XMPPStanza`)
* `XMPPPres`            xmpp pres stanza object (extends `XMPPStanza`)
* `XMPPXep`             abstract xmpp extension (extended by XEP implementations)
* `XMPPJid`             xmpp jid object

HTTP Stack:

* `HTTPServer`          http server implementation
* `HTTPClient`          http client implementation
* `HTTPRequest`         http request object
* `HTTPResponse`        http response object

Writing an XMPP Client:
------------------------
1) include `jaxl.php` and initialize a new JAXL instance:

<pre>
$client = new JAXL(array(
    'jid'=>'user@domain.tld', 
    'pass'=>'password'
));
</pre>

`JAXL` constructor accepts an array of kv options. A detailed 
list of available options can be found inside [echo_bot.php](https://github.com/abhinavsingh/JAXL/blob/v3.x/examples/echo_bot.php)

2) register callbacks on events that we will require in our application:

<pre>
$client->add_cb('on_auth_success', function() {
	global $client;
	$client->set_status("available!");  // set your status
	$client->get_vcard();               // fetch your vcard
	$client->get_roster();              // fetch your roster list
});

$client->add_cb('on_chat_message', function($msg) {
	global $xmpp;
	
	// echo back
	$msg->to = $msg->from;
	$msg->from = $client->full_jid->to_string();
	$client->send($msg);
});
</pre>

`$client->full_jid` is an instance of `XMPPJid`

3) finally start above configured `JAXL` instance:

<pre>
$client->start();
</pre>

4) lets try this out:

[download](https://github.com/abhinavsingh/JAXL/tarball/v3.x) and unzip Jaxl v3.x. Then run [echo_bot.php](https://github.com/abhinavsingh/JAXL/blob/v3.x/examples/echo_bot.php) sample example.

<pre>
$ php examples/echo_bot.php user@domain.tld password

jaxl:180 - 2012-07-15 01:05:51 - created pid file /path/to/JAXL/.jaxl/run/jaxl_18901.pid
jaxl:192 - 2012-07-15 01:05:51 - dns srv lookup for domain.tld
jaxl_socket_client:90 - 2012-07-15 01:05:51 - trying tcp://domain.tld:5222
...
...
...
jaxl_socket_client:172 - 2012-07-15 01:05:51 - read 106/1977 of data
jaxl_fsm:73 - 2012-07-15 01:05:51 - current state 'logged_in'
</pre>

Default `log_level` is `JAXL_INFO` and you should see some info as shown above.

Now lets debug our xmpp client while it's running.

4) open another terminal and attach a live interactive console into our running xmpp client:

<pre>
$ ./jaxlctl.php .jaxl/sock/jaxl_18901.sock 

jaxl 1> global $client; return $client->cfg;

Array
(
    [jid] => user@domain.tld
    [pass] => password
    [auth_type] => PLAIN
    [host] => domain.tld
    [port] => 5222
)
</pre>

As we can see above, our running xmpp client perform `[auth_type] => PLAIN`.

We can even send a chat message, group chat message, change status etc using this
live interactive console. Try it:

<pre>
jaxl 2> global $client; $client->send_chat_msg("someone@somewhere.com", "hello buddy");
</pre>

Finally, come of out live console:

<pre>
jaxl 3> quit
^C
$ 
</pre>

Archives:
---------
Following branches are deprecated. Browse them for fun/study:

[v2.x branch](https://github.com/abhinavsingh/JAXL/tree/master)

[v1.x branch](http://code.google.com/p/jaxl/source/browse/#svn%2Ftrunk)
