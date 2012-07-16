Jaxl v3.x:
-----------
Jaxl v3.x is a successor of v2.x (and is NOT backward compatible), 
carrying a lot of code from v2.x while throwing away the ugly parts.
A lot of components have been re-written keeping in mind the feedback from
the developer community over the last 4 years. Also Jaxl shares a few
philosophies from my experience with erlang and python languages.

Jaxl is an asynchronous, non-blocking I/O, event based PHP library 
for writing custom TCP/IP client and server implementations. 
From it's previous versions, library inherits a full blown stable support 
for XMPP protocol stack. In v3.0, support for HTTP protocol stack was 
also added.

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

* `HTTPServer`          base http server implementation
* `HTTPClient`          base http client implementation
* `HTTPRequest`         a http request object
* `HTTPResponse`        a http response object

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

Now lets see how can we debug our xmpp client application 
while it is running in the background.

Debugging JAXL daemons:
------------------------

You can start configured `JAXL` instance with additional parameters:

<pre>
$client->start(array(
    '--with-debug-shell' => true,
    '--with-unix-sock' => true
));
</pre>

`--with-debug-shell` Jaxl will start the configured instance
and straightaway take you into a live interactive console

`--with-unix-sock` Jaxl will enable a unix socket domain 
for accepting commands from a remotely attached 
live interative console

Remote Debugging:
------------------

1) open a new terminal and attach a live interactive console
   into any daemon started using `JAXL` instance start() method:

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

As we can see above, our xmpp client performed `[auth_type] => PLAIN`.

We can even send a chat message, group chat message, change status, 
monitor daemons remotely etc using this live interactive console and 
the methodology behind it.

Try to send a chat message:

<pre>
jaxl 2> global $client; $client->send_chat_msg("someone@somewhere.com", "hello buddy");
</pre>

Finally, come of out live console:

<pre>
jaxl 3> quit
^C
$ 
</pre>

Note: Live interactive console development is still in alpha stage.
      It blindly `eval` sent string from jaxl console.
      Know what you are doing while using it in production.
      
