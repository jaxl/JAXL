<?php
/**
 * Jaxl (Jabber XMPP Library)
 *
 * Copyright (c) 2009-2012, Abhinav Singh <me@abhinavsingh.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in
 * the documentation and/or other materials provided with the
 * distribution.
 *
 * * Neither the name of Abhinav Singh nor the names of his
 * contributors may be used to endorse or promote products derived
 * from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRIC
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 */

// TODO: support for php unit and add more tests
error_reporting(E_ALL);
require_once "jaxl.php";

/**
 * 
 * @author abhinavsingh
 *
 */
class JAXLTest extends PHPUnit_Framework_TestCase {
	
	function test_xml_stanza() {
		$stanza = new JAXLXml('message', array('to'=>'1@a.z', 'from'=>'2@b.c'));
		$stanza
		->c('body')->attrs(array('xml:lang'=>'en'))->t('hello')->up()
		->c('thread')->t('1234')->up()
		->c('nested')
		->c('nest')->t('nest1')->up()
		->c('nest')->t('nest2')->up()
		->c('nest')->t('nest3')->up()->up()
		->c('c')->attrs(array('hash'=>'84jsdmnskd'));
		
		$this->assertEquals(
			'<message to="1@a.z" from="2@b.c"><body xml:lang="en">hello</body><thread>1234</thread><nested><nest>nest1</nest><nest>nest2</nest><nest>nest3</nest></nested><c hash="84jsdmnskd"></c></message>', 
			$stanza->to_string()
		);
	}
	
	function test_xmpp_jid() {
		$jid = new XMPPJid("1@domain.tld/res");
		$this->assertEquals('1@domain.tld/res', $jid->to_string());
		
		$jid = new XMPPJid("domain.tld/res");
		$this->assertEquals('domain.tld/res', $jid->to_string());
		
		$jid = new XMPPJid("component.domain.tld");
		$this->assertEquals('component.domain.tld', $jid->to_string());
		
		$jid = new XMPPJid("1@domain.tld");
		$this->assertEquals('1@domain.tld', $jid->to_string());
	}
	
	function xml_start_cb($node) {
		$this->assertEquals('stream', $node->name);
		$this->assertEquals(NS_XMPP, $node->ns);
	}
	
	function xml_end_cb($node) {
		$this->assertEquals('stream', $node->name);
	}
	
	function xml_stanza_cb($node) {
		$this->assertEquals('features', $node->name);
		$this->assertEquals(1, sizeof($node->childrens));
	}
	
	function test_xml_stream() {
		$xml = new JAXLXmlStream();
		$xml->set_callback(array(&$this, "xml_start_cb"), array(&$this, "xml_end_cb"), array(&$this, "xml_stanza_cb"));
		$xml->parse('<stream:stream xmlns:stream="http://etherx.jabber.org/streams" xmlns="jabber:client">');
		$xml->parse('<features>');
		$xml->parse('<mechanisms>');
		$xml->parse('</mechanisms>');
		$xml->parse('</features>');
		$xml->parse('</stream:stream>');
	}
	
	/*function test_jaxl_socket() {
		$sock = new JAXLSocket("127.0.0.1", 5222);
		$sock->connect();
		
		$sock->send("<stream:stream>");
		while($sock->fd) {
			$sock->recv();
		}
	}
	
	function test_xmpp_stream() {
		$xmpp = new XMPPStream("test@localhost", "password");
		$xmpp->connect();
		
		$xmpp->start_stream();
		while($xmpp->sock->fd) {
			$xmpp->sock->recv();
		}
	}
	
	function test_jaxl_event() {
		$ev = new JAXLEvent();
		
		$ref1 = $ev->add('on_connect', 'some_func', 0);
		$ref2 = $ev->add('on_connect', 'some_func1', 0);
		$ref3 = $ev->add('on_connect', 'some_func2', 1);
		$ref4 = $ev->add('on_connect', 'some_func3', 4);
		$ref5 = $ev->add('on_disconnect', 'some_func', 1);
		$ref6 = $ev->add('on_disconnect', 'some_func1', 1);
		
		//$ev->emit('on_connect', null);
		
		$ev->del($ref2);
		$ev->del($ref1);
		$ev->del($ref6);
		$ev->del($ref5);
		$ev->del($ref4);
		$ev->del($ref3);
		
		//print_r($ev->reg);
	}
	
	function test_xmpp_msg() {
		$msg = new XMPPMsg(array('to'=>'2@w.c', 'from'=>'-0@q.p/~', 'type'=>'chat'), 'hi', 'thread1');

		echo $msg->to.PHP_EOL;
		echo $msg->to_node.PHP_EOL;
		echo $msg->from.PHP_EOL;
		echo $msg->to_string().PHP_EOL;
		
		$msg->to = '4@w.c/sp';
		$msg->body = 'hello world';
		$msg->subject = 'some subject';
		
		echo $msg->to.PHP_EOL;
		echo $msg->to_node.PHP_EOL;
		echo $msg->from.PHP_EOL;
		echo $msg->to_string().PHP_EOL;
	}
	
	function test_xmpp_stanza() {
		// stanza text
		$stanza = new XMPPStanza('message', array('to'=>'2@3.com', 'from'=>'4@r.p/q'));
		$stanza->c('body')->t('hello world');
		echo $stanza->to_string()."\n";
		
		// xml to stanza test
		$xml = new JAXLXml('message', NS_JABBER_CLIENT, array('to'=>'2@3.com', 'from'=>'4@r.p/q'));
		$stanza = new XMPPStanza($xml);
		$stanza->c('body')->t('hello world');
		echo $stanza->to."\n";
		echo $stanza->to_string()."\n";
	}*/

}

?>
