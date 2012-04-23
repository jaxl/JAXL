<?php

require_once 'xmpp/xml_node.php';
require_once 'xmpp/xml_stream.php';
require_once 'xmpp/xmpp_jid.php';

function test_xml_node() {
	$node = new XmlNode('message', array('to'=>'1@a.z', 'from'=>'2@b.c'));
	$node
	->c('body')->attrs(array('xml:lang'=>'en'))->t('hello')->up()
	->c('thread')->t('1234')->up()
	->c('nested')
	->c('nest')->t('nest1')->up()
	->c('nest')->t('nest2')->up()
	->c('nest')->t('nest3')->up()->up()
	->c('c')->attrs(array('hash'=>'84jsdmnskd'));
	echo $node->to_str()."\n";
}

function test_xmpp_jid() {
	$jid = new XmppJid("1@domain.tld/res");
	echo $jid->to_str()."\n";
	$jid = new XmppJid("domain.tld/res");
	echo $jid->to_str()."\n";
	$jid = new XmppJid("component.domain.tld");
	echo $jid->to_str()."\n";
	$jid = new XmppJid("1@domain.tld");
	echo $jid->to_str()."\n";
}

function xml_start_cb($node) {
	echo $node->to_str()."\n";
}

function xml_end_cb($node) {
	echo $node->to_str()."\n";
}

function xml_stanza_cb($node) {
	echo $node->to_str()."\n";
}

function test_xml_stream() {
	$xml = new XmlStream();
	$xml->set_callback("xml_start_cb", "xml_end_cb", "xml_stanza_cb");
	$xml->parse('<stream:stream xmlns:stream="http://etherx.jabber.org/streams" xmlns="jabber:client">');
	$xml->parse('<features>');
	$xml->parse('<mechanisms>');
	$xml->parse('</mechanisms>');
	$xml->parse('</features>');
	$xml->parse('</stream:stream>');
}

function test_xmpp_socket() {
	
}

function test_xmpp_stream() {
	
}

function test() {
	test_xml_node();
	test_xmpp_jid();
	test_xml_stream();
	test_xmpp_socket();
	test_xmpp_stream();
}

test();

?>