<?php

// TODO: support for php unit and add more tests
error_reporting(E_ALL);

require_once 'xmpp/xml_stanza.php';
require_once 'xmpp/xml_stream.php';
require_once 'xmpp/xmpp_jid.php';
require_once 'xmpp/xmpp_stream.php';
require_once 'xmpp/xmpp_socket.php';

function fsm_state_connected($data, $param) {
	print_r($data);
	print_r($param);
}

function fsm_state_setup($data, $param) {
	print_r($data);
	print_r($param);
	$data['setup']=true;
	return array("fsm_state_connected", $data);
}

function test_fsm() {
	$fsm = new Fsm("fsm_state_setup", array());
	$fsm->move(array('dummy'=>'data'));
	$fsm->move(array('dummy'=>'data'));
}

function test_xml_stanza() {
	$stanza = new XmlStanza('message', array('to'=>'1@a.z', 'from'=>'2@b.c'));
	$stanza
	->c('body')->attrs(array('xml:lang'=>'en'))->t('hello')->up()
	->c('thread')->t('1234')->up()
	->c('nested')
	->c('nest')->t('nest1')->up()
	->c('nest')->t('nest2')->up()
	->c('nest')->t('nest3')->up()->up()
	->c('c')->attrs(array('hash'=>'84jsdmnskd'));
	echo $stanza->to_string()."\n";
}

function test_xmpp_jid() {
	$jid = new XmppJid("1@domain.tld/res");
	echo $jid->to_string()."\n";
	$jid = new XmppJid("domain.tld/res");
	echo $jid->to_string()."\n";
	$jid = new XmppJid("component.domain.tld");
	echo $jid->to_string()."\n";
	$jid = new XmppJid("1@domain.tld");
	echo $jid->to_string()."\n";
}

function xml_start_cb($node) {
	echo $node->to_string()."\n";
}

function xml_end_cb($node) {
	echo $node->to_string()."\n";
}

function xml_stanza_cb($node) {
	echo $node->to_string()."\n";
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
	$sock = new XmppSocket("127.0.0.1", 5222);
	$sock->connect();
	
	$sock->send("<stream:stream>");
	while($sock->fd) {
		$sock->recv();
	}
}

function test_xmpp_stream() {
	$xmpp = new XmppStream("test@localhost", "password");
	$xmpp->connect();
	
	$xmpp->start_stream();
	while($xmpp->sock->fd) {
		$xmpp->sock->recv();
	}
}

function test() {
	//test_fsm();
	//test_xml_stanza();
	//test_xmpp_jid();
	//test_xml_stream();
	//test_xmpp_socket();
	//test_xmpp_stream();
}

test();

?>