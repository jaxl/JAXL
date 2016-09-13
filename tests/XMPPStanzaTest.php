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

/**
 *
 * @author abhinavsingh
 *
 */
class XMPPStanzaTest extends PHPUnit_Framework_TestCase
{

    public function test_xmpp_stanza_nested()
    {
        $xml = new JAXLXml('message', array('to' => '1@a.z', 'from' => '2@b.c'));
        $xml->c('body')->attrs(array('xml:lang' => 'en'))->t('hello')->up()
            ->c('thread')->t('1234')->up()
            ->c('nested')
            ->c('nest')->t('nest1')->up()
            ->c('nest')->t('nest2')->up()
            ->c('nest')->t('nest3')->up()->up()
            ->c('c')->attrs(array('hash' => '84jsdmnskd'));
        $stanza = new XMPPStanza($xml);

        $this->assertEquals(
            '<message to="1@a.z" from="2@b.c"><body xml:lang="en">hello</body><thread>1234</thread><nested>' .
            '<nest>nest1</nest><nest>nest2</nest><nest>nest3</nest></nested><c hash="84jsdmnskd"></c></message>',
            $stanza->to_string()
        );
    }

    public function test_xmpp_stanza_from_jaxl_xml()
    {
        // xml to stanza test
        $xml = new JAXLXml('message', XMPP::NS_JABBER_CLIENT, array('to' => '2@3.com', 'from' => '4@r.p/q'));
        $stanza = new XMPPStanza($xml);
        $stanza->c('body')->t('hello world');
        $this->assertEquals('XMPPStanza', get_class($stanza));
        $this->assertEquals('JAXLXml', get_class($stanza->exists('body')));
        $this->assertEquals('2@3.com', $stanza->to);
        $this->assertEquals(
            '<message xmlns="jabber:client" to="2@3.com" from="4@r.p/q">' .
            '<body>hello world</body></message>',
            $stanza->to_string()
        );
    }

    public function testXMPPStanzaAndJAXLXmlAreInterchangeable()
    {
        $test_data = array(
            'name' => 'msg',
            'ns' => 'NAMESPACE',
            'attrs' => array('a' => '1', 'b' => '2'),
            'text' => 'Test message'
        );
        $xml = new JAXLXml($test_data['name'], $test_data['ns'], $test_data['attrs'], $test_data['text']);
        $stanza = new XMPPStanza($xml);
        $this->checkJAXLXmlAccess($xml, $test_data);
        $this->checkJAXLXmlAccess($stanza, $test_data);
    }

    protected function checkJAXLXmlAccess(JAXLXmlAccess $xml_or_stanza, $test_data)
    {
        $this->assertEquals($test_data['name'], $xml_or_stanza->name);
        $this->assertEquals($test_data['ns'], $xml_or_stanza->ns);
        $this->assertEquals($test_data['attrs'], $xml_or_stanza->attrs);
        $this->assertEquals($test_data['text'], $xml_or_stanza->text);
        $this->assertEquals(array(), $xml_or_stanza->children);
        return true;
    }
}
