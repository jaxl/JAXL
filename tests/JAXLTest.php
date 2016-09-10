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
class JAXLTest extends PHPUnit_Framework_TestCase
{
    public function testProtocolOption()
    {
        $config = array(
            'host' => 'domain.tld',
            'port' => 5223,
            'protocol' => 'tcp',
            'strict' => false
        );
        $jaxl = new JAXL($config);
        $this->assertEquals('tcp://domain.tld:5223', $jaxl->get_socket_path());
        $this->assertInstanceOf('JAXLSocketClient', $jaxl->getTransport());
    }

    public function testConfig()
    {
        $config = array('strict' => false);
        $jaxl = new JAXL($config);

        $this->assertEquals('PLAIN', $jaxl->cfg['auth_type']);
        $this->assertNull($jaxl->cfg['bosh_hold']);
        $this->assertNull($jaxl->cfg['bosh_rid']);
        $this->assertNull($jaxl->cfg['bosh_url']);
        $this->assertNull($jaxl->cfg['bosh_wait']);
        $this->assertNull($jaxl->cfg['domain']);
        $this->assertEquals($jaxl->force_tls, $jaxl->cfg['force_tls']);
        $this->assertNull($jaxl->cfg['host']);
        $this->assertNull($jaxl->cfg['jid']);
        $this->assertEquals($jaxl->log_colorize, $jaxl->cfg['log_colorize']);
        $this->assertEquals($jaxl->log_level, $jaxl->cfg['log_level']);
        $this->assertEquals(JAXLLogger::$path, $jaxl->cfg['log_path']);
        $this->assertFalse($jaxl->cfg['multi_client']);
        $this->assertFalse($jaxl->cfg['pass']);
        $this->assertNull($jaxl->cfg['port']);
        $this->assertContains('.jaxl', $jaxl->cfg['priv_dir']);
        $this->assertNull($jaxl->cfg['protocol']);
        $this->assertNull($jaxl->cfg['resource']);
        $this->assertNull($jaxl->cfg['stream_context']);
        $this->assertFalse($jaxl->cfg['strict']);
    }
}
