<?php
/**
 * Jaxl (Jabber XMPP Library)
 *
 * Copyright (c) 2009-2010, Abhinav Singh <me@abhinavsingh.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Abhinav Singh nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
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
 * @package jaxl
 * @subpackage xep
 * @author Abhinav Singh <me@abhinavsingh.com>
 * @copyright Abhinav Singh
 * @link http://code.google.com/p/jaxl
 */

    /**
     * XEP-0176: Jingle ICE-UDP Transport Method
    */
    class JAXL0176 {

        public static $ns = 'urn:xmpp:jingle:transports:ice-udp:1';

        public static function init($jaxl) {
            $jaxl->features[] = self::$ns;

            JAXLXml::addTag('iq', 'jtpTrans', '//iq/jingle/content/transport/@xmlns');
            JAXLXml::addTag('iq', 'jtpTransPWD', '//iq/jingle/content/transport/@pwd');
            JAXLXml::addTag('iq', 'jtpTransUFrag', '//iq/jingle/content/transport/@ufrag');
        
            JAXLXml::addTag('iq', 'jtpCandiComp', '//iq/jingle/content/transport/candidate/@component');
            JAXLXml::addTag('iq', 'jtpCandiComp', '//iq/jingle/content/transport/candidate/@foundation');
            JAXLXml::addTag('iq', 'jtpCandiComp', '//iq/jingle/content/transport/candidate/@generation');
            JAXLXml::addTag('iq', 'jtpCandiComp', '//iq/jingle/content/transport/candidate/@id');
            JAXLXml::addTag('iq', 'jtpCandiComp', '//iq/jingle/content/transport/candidate/@ip');
            JAXLXml::addTag('iq', 'jtpCandiComp', '//iq/jingle/content/transport/candidate/@network');
            JAXLXml::addTag('iq', 'jtpCandiComp', '//iq/jingle/content/transport/candidate/@port');
            JAXLXml::addTag('iq', 'jtpCandiComp', '//iq/jingle/content/transport/candidate/@priority');
            JAXLXml::addTag('iq', 'jtpCandiComp', '//iq/jingle/content/transport/candidate/@protocol');
            JAXLXml::addTag('iq', 'jtpCandiComp', '//iq/jingle/content/transport/candidate/@rel-addr');
            JAXLXml::addTag('iq', 'jtpCandiComp', '//iq/jingle/content/transport/candidate/@rel-port');
            JAXLXml::addTag('iq', 'jtpCandiComp', '//iq/jingle/content/transport/candidate/@type');
        }

        public static function getTransportElement() {

        }

        public static function getCandidateElement() {

        }

    }

?>
