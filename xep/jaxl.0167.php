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
     * XEP-0167 : Jingle RTP Sessions
    */
    class JAXL0167 {

        public static $ns = 'urn:xmpp:jingle:apps:rtp:1';

        public static function init($jaxl) {
            $jaxl->features[] = self::$ns;
            
            JAXLXml::addTag('iq', 'jtpDescription', '//iq/jingle/content/description/@xmlns');
            JAXLXml::addTag('iq', 'jtpMedia', '//iq/jingle/content/description/@media');
            JAXLXml::addTag('iq', 'jtpSSRC', '//iq/jingle/content/description/@ssrc');

            JAXLXml::addTag('iq', 'jtpPTypeId', '//iq/jingle/content/description/payload-type/@id');
            JAXLXml::addTag('iq', 'jtpPTypeName', '//iq/jingle/content/description/payload-type/@name');
            JAXLXml::addTag('iq', 'jtpPTypeClockRate', '//iq/jingle/content/description/payload-type/@clockrate');
            JAXLXml::addTag('iq', 'jtpPTypeChannels', '//iq/jingle/content/description/payload-type/@channels');
            JAXLXml::addTag('iq', 'jtpPTypeMAXPTime', '//iq/jingle/content/description/payload-type/@maxptime');
            JAXLXml::addTag('iq', 'jtpPTypePTime', '//iq/jingle/content/description/payload-type/@ptime');

            JAXLXml::addTag('iq', 'jtpEncryptReq', '//iq/jingle/content/description/encryption/@required');
            JAXLXml::addTag('iq', 'jtpCryptoSuite', '//iq/jingle/content/description/encryption/crypto/@crypto-suite');
            JAXLXml::addTag('iq', 'jtpCryptoKey', '//iq/jingle/content/description/encryption/crypto/@key-params');
            JAXLXml::addTag('iq', 'jtpCryptoSession', '//iq/jingle/content/description/encryption/crypto/@session-params');
            JAXLXml::addTag('iq', 'jtpCryptoTag', '//iq/jingle/content/description/encryption/crypto/@tag');

            JAXLXml::addTag('iq', 'jtpBWType', '//iq/jingle/content/description/bandwidth/@type');
            JAXLXml::addTag('iq', 'jtpBW', '//iq/jingle/content/description/bandwidth');

            JAXLXml::addTag('iq', 'jtpPTypeParamName', '//iq/jingle/content/description/payload-type/parameter/@name');
            JAXLXml::addTag('iq', 'jtpPTypeParamValue', '//iq/jingle/content/description/payload-type/parameter/@value');
        }

        public static function getDescriptionElement() {
            $xml = '<description xmlns="" media="" ssrc="">';
            $xml .= '<payload-type id="" name="" clockrate="" channels="" maxptime="" ptime="">';
            $xml .= '<parameter name="" value=""/>';
            $xml .= '</payload>';
            $xml .= '<bandwidth type=""></bandwidth>';
            $xml .= '<description>';
            return $xml;
        }

        public static function active() {

        }

        public static function hold() {

        }

        public static function mute() {

        }

        public static function ringing() {

        }

        public static function sendAppParam() {
            $xml = self::getDescriptionElement();
            $xml = JAXL0166::getContentElement($xml, $creator, $name);
            $xml = JAXL0166::getJingleElement($xml, 'description-info', $sid, $initiator);
        }

    }

?>
