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

JAXL::dummy();

/**
 *
 * @author abhinavsingh
 *
 */
class XMPPJidTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider jidPositiveProvider
     */
    public function test_xmpp_jid_construct($jidText)
    {
        $jid = new XMPPJid($jidText);
        $this->assertEquals($jidText, $jid->to_string());
    }

    public function jidPositiveProvider()
    {
        return array(
            array('domain'),
            array('domain.tld'),
            array('1@domain'),
            array('1@domain.tld'),
            array('domain/res'),
            array('domain.tld/res'),
            array('1@domain/res'),
            array('1@domain.tld/res'),
            array('component.domain.tld'),
            array('1@domain-2.tld/res'),
            array('1@domain-2.tld/@res'),
            array('1@domain-2.tld//res')
        );
    }

    /**
     * @dataProvider jidNegativeProvider
     * @expectedException InvalidArgumentException
     * @requires function XEP_0029::validateJID
     */
    public function testJidNegative($jidText)
    {
        $jid = new XMPPJid($jidText);
    }

    public function jidNegativeProvider()
    {
        return array(
            array('"@domain'),
            array('&@domain'),
            array("'@domain"),
            array('/@domain'),
            array(':@domain'),
            array('<@domain'),
            array('>@domain'),
            array('@@domain'),
            array("\x7F" . '@domain'),
            array("\xFF\xFE" . '@domain'),
            array("\xFF\xFF" . '@domain')
        );
    }
}
