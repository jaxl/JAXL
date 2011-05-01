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
     * XEP-0166 : Jingle
    */
    class JAXL0166 {

        public static $ns = 'urn:xmpp:jingle:1';

        public static function init($jaxl) {
            $jaxl->features[] = self::$ns;

            JAXLXml::addTag('iq', 'j', '//iq/jingle/@xmlns');
            JAXLXml::addTag('iq', 'jAction', '//iq/jingle/@action');
            JAXLXml::addTag('iq', 'jSid', '//iq/jingle/@sid');
            JAXLXml::addTag('iq', 'jInitiator', '//iq/jingle/@initiator');
            JAXLXml::addTag('iq', 'jResponder', '//iq/jingle/@responder');
            JAXLXml::addTag('iq', 'jcCreator', '//iq/jingle/content/@creator');
            JAXLXml::addTag('iq', 'jcName', '//iq/jingle/content/@name');
            JAXLXml::addTag('iq', 'jcDisposition', '//iq/jingle/content/@disposition');
            JAXLXml::addTag('iq', 'jcSenders', '//iq/jingle/content/@senders');
            JAXLXml::addTag('iq', 'jrCondition', '//iq/jingle/reason/*[1]/name()');
            JAXLXml::addTag('iq', 'jrText', '//iq/jingle/reason/text');
        }

        public static function getReasonElement($condition, $text=false, $payload=false) {
            $xml = '<reason>';
            $xml .= '<'.$condition.'/>';
            if($text) $xml .= '<text>'.$text.'</text>';
            if($payload) $xml .= $payload;
            $xml .= '</reason>';
            return $xml;
        }

        public static function getContentElement($payload, $creator, $name, $disposition=false, $senders=false) {
            $xml = '<content creator="" name=""';
            if($disposition) $xml .= ' disposition=""';
            if($senders) $xml .= ' senders=""';
            $xml .= '>';
            $xml .= $payload;
            $xml .= '</content>';
            return $xml;
        }

        public static function getJingleElement($payload, $action, $sid, $initiator=false, $responder=false) {
            $xml = '<jingle xmlns="'.self::$ns.'" action="'.$action.'" sid="'.$sid.'"';
            if($initiator) $xml .= ' initiator="'.$initiator.'"';
            if($responder) $xml .= ' responder="'.$responder.'"';
            $xml .= '>';
            $xml .= $payload;
            $xml .= '</jingle>';
            return $xml;
        }

        public static function sessionInitiate($jaxl, $to, $payload, $sid, $initiator, $callback) {
            $xml = self::getJingleElement($payload, 'session-initiate', $sid, $initiator);
            return XMPPSend::iq($jaxl, 'set', $xml, $to, false, $callback);
        }

        public static function sessionAccept($jaxl, $to, $payload, $sid, $initiator, $responder, $callback) {
            $xml = self::getJingleElement($payload, 'session-accept', $sid, $initiator, $responder);
            return XMPPSend::iq($jaxl, 'set', $xml, $to, false, $callback);
        }

        public static function sessionTerminate($jaxl, $to, $payload, $sid, $initiator, $callback) {
            $xml = self::getJingleElement($payload, 'session-terminate', $sid, $initiator);
            return XMPPSend::iq($jaxl, 'set', $xml, $to, false, $callback);
        }

        public static function sendInfoMessage($jaxl, $to, $payload, $sid, $initiator, $callback) {
            $xml = self::getJingleElement($payload, 'session-info', $sid, $initiator);
            return XMPPSend::iq($jaxl, 'set', $xml, $to, false, $callback);
        }

    }

?>
