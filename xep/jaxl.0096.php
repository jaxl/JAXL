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
     * XEP-0096: Stream Initiated File Transfer Profile
    */
    class JAXL0096 {
        
        // map of id used to initiate negotiation and related data
        public static $id = array();

        public static $ns = 'http://jabber.org/protocol/si/profile/file-transfer';
        
        public static function init($jaxl) {
            $jaxl->requires(array(
                'JAXL0095', // Require Stream Initiation XEP
                'JAXL0047', // Require IBB
                'JAXL0065'  // SOCKS5 Bytestream
            ));
            $jaxl->features[] = self::$ns;
            
            JAXLXml::addTag('iq', 'file', '//iq/si/file/@xmlns');
            JAXLXml::addTag('iq', 'fileSize', '//iq/si/file/@size');
            JAXLXml::addTag('iq', 'fileName', '//iq/si/file/@name');
            JAXLXml::addTag('iq', 'fileDate', '//iq/si/file/@date');
            JAXLXml::addTag('iq', 'fileHash', '//iq/si/file/@hash');
            JAXLXml::addTag('iq', 'fileDesc', '//iq/si/file/desc');
            JAXLXml::addTag('iq', 'fileStreamMethod', '//iq/si/feature/x/field/value');
            JAXLXml::addTag('iq', 'fileStreamMethods', '//iq/si/feature/x/field/option/value');
            
            $jaxl->addPlugin('jaxl_get_iq_set', array('JAXL0096', 'getFile'));
        }
        
        public static function accept($jaxl, $to, $id, $method) {
            $xml = '<file xmlns="'.self::$ns.'"/>';
            $xml .= '<feature xmlns="http://jabber.org/protocol/feature-neg">';
            $xml .= '<x xmlns="jabber:x:data" type="submit">';
            $xml .= '<field var="stream-method">';
            $xml .= '<value>'.$method.'</value>';
            $xml .= '</field>';
            $xml .= '</x>';
            $xml .= '</feature>';
            return JAXL0095::accept($jaxl, $to, $id, $xml);
        }
        
        public static function getFile($payload, $jaxl) {
            if($payload['file'] == self::$ns) $jaxl->executePlugin('jaxl_get_file_request', $payload);
            return $payload;
        }
        
        public static function sendFile($jaxl, $to, $file, $desc=false, $length=false, $offset=false, $siId=false, $siMime=false) {
            if(!file_exists($file)) {
                $jaxl->log("[[JAXL0096]] $file doesn't exists on the system");
                return false;
            }

            $xml = '<file xmlns="'.self::$ns.'" name="'.basename($file).'" size="'.filesize($file).'">';
            if($desc) $xml .= '<desc>'.$desc.'</desc>';
            $xml .= '<range';
            if($offset) $xml .= ' offset="'.$offset.'"';
            if($length) $xml .= ' length="'.$length.'"';
            $xml .= '/>';
            $xml .= '</file>';
            $xml .= '<feature xmlns="http://jabber.org/protocol/feature-neg">';
            $xml .= '<x xmlns="jabber:x:data" type="form">';
            $xml .= '<field var="stream-method" type="list-single">';
            $xml .= '<option><value>http://jabber.org/protocol/bytestreams</value></option>';
            $xml .= '<option><value>http://jabber.org/protocol/ibb</value></option>';
            $xml .= '</field>';
            $xml .= '</x>';
            $xml .= '</feature>';

            if(!$siId) $siId = md5($jaxl->clocked.$jaxl->jid.$to);
            if(!$siMime) $siMime = 'application/octet-stream';
            
            $id = JAXL0095::initiate($jaxl, $to, $xml, $siId, $siMime, self::$ns, array('JAXL0096', 'handleIQResult'));
            
            self::$id[$id] = array(
                'file'=>$file,
                'length'=>$length,
                'offset'=>$offset,
                'siId'=>$siId,
                'to'=>$to
            );
            return $id;
        }

        public static function handleIQResult($payload, $jaxl) {
            if(!isset(self::$id[$payload['id']]))
                return $payload;

            if($payload['fileStreamMethod'] == 'http://jabber.org/protocol/ibb')
                $jaxl->JAXL0047('sendFile', self::$id[$payload['id']]);
            else if($payload['fileStreamMethod'] == 'http://jabber.org/protocol/bytestreams')
                $jaxl->JAXL0065('sendFile', self::$id[$payload['id']]);

            unset(self::$id[$payload['id']]);
        }
        
    }

?>
