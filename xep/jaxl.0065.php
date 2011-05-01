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
     * XEP-0065: SOCKS5 Bytestreams
    */
    class JAXL0065 {
        
        public static $id = array();
        public static $ns = 'http://jabber.org/protocol/bytestreams';
        
        public static function init($jaxl) {
            jaxl_require('JAXLS5B'); // SOCKS5
            $jaxl->features[] = self::$ns;
           
            JAXLXml::addTag('iq', 'streamSid', '//iq/query/@sid');
            JAXLXml::addTag('iq', 'streamMode', '//iq/query/@mode');
            JAXLXml::addTag('iq', 'streamHost', '//iq/query/streamhost/@host');
            JAXLXml::addTag('iq', 'streamPort', '//iq/query/streamhost/@port');
            JAXLXml::addTag('iq', 'streamJid', '//iq/query/streamhost/@jid');
            JAXLXml::addTag('iq', 'streamHostUsed', '//iq/query/streamhost-used/@jid');
        
            $jaxl->addPlugin('jaxl_get_iq_set', array('JAXL0065', 'getStreamHost'));
        }
        
        /**
         * Method listens for incoming iq set packets. If incoming packet matches namespace:
         * a) Establishes S5B with one of the stream host
         * b) Notify requestor about user stream host
         * c) Read S5B socket for data
        */
        public static function getStreamHost($payload, $jaxl) {
            if($payload['queryXmlns'] == self::$ns) {
                if(!is_array($payload['streamHost'])) $payload['streamHost'] = array($payload['streamHost']);
                if(!is_array($payload['streamPort'])) $payload['streamPort'] = array($payload['streamPort']);
                if(!is_array($payload['streamJid'])) $payload['streamJid'] = array($payload['streamJid']);
                
                foreach($payload['streamHost'] as $key => $streamHost) {
                    $s5b = new JAXLS5B($streamHost, $payload['streamPort'][$key], $jaxl);
                    $s5b->connect($payload['streamSid'], $payload['from'], $payload['to'], $jaxl);
                    if($s5b->connected) {
                        // connected to proxy, use this streamHost
                        self::notifyRequestor($payload, $jaxl);

                        // read socket data
                        $jaxl->log("[[JAXL0065]] Using stream host $streamHost");
                        while($buffer = $s5b->read()) {
                            $jaxl->log("[[JAXL0065]] Reading 1024 bytes of data via proxy");
                            file_put_contents($jaxl->tmpPath.'/'.$payload['streamSid'], $buffer, FILE_APPEND);
                        }

                        unset($s5b);
                        $jaxl->executePlugin('jaxl_post_file_request', array('tmp'=>$jaxl->tmpPath.'/'.$payload['streamSid']));
                        break;
                    }
                }
            }
            return $payload;
        }

        public static function notifyRequestor($payload, $jaxl) {
            $xml = '<query xmlns="'.self::$ns.'" sid="'.$payload['streamSid'].'">';
            $xml .= '<streamhost-used jid="'.$payload['streamJid'][0].'"/>';
            $xml .= '</query>';
            return XMPPSend::iq($jaxl, 'result', $xml, $payload['from'], $jaxl->jid, false, $payload['id']);
        }

        public static function sendFile($jaxl, $payload) {
            $xml = '<query xmlns="'.self::$ns.'" sid="'.$payload['siId'].'">';
            $xml .= '<streamhost host="127.0.1.1" jid="proxy.dev.jaxl.im" port="7777"/>';
            $xml .= '</query>';
            $id = XMPPSend::iq($jaxl, 'set', $xml, $payload['to'], $jaxl->jid, array('JAXL0065', 'establishS5B'));
            
            self::$id[$id] = $payload;
            return $id;
        }

        public static function establishS5B($payload, $jaxl) {
            if(!isset(self::$id[$payload['id']]))
                return $payload;

            // establish S5B connection
            $s5b = new JAXLS5B('127.0.1.1', 7777, $jaxl);
            $s5b->connect($payload['streamSid'], $payload['to'], $payload['from'], $jaxl);
            if($s5b->connected) {
                // activate bytestream
                $id = self::activateS5B('proxy.dev.jaxl.im', $payload['streamSid'], $payload['from'], $jaxl);
                self::$id[$id] = self::$id[$payload['id']];
                self::$id[$id]['s5b'] = &$s5b;
            }

            unset($s5b);
            unset(self::$id[$payload['id']]);
            return $payload;
        }

        public static function activateS5B($streamHost, $sid, $tJid, $jaxl) {
            $xml = '<query xmlns="'.self::$ns.'" sid="'.$sid.'">';
            $xml .= '<activate>'.$tJid.'</activate>';
            $xml .= '</query>';
            return XMPPSend::iq($jaxl, 'set', $xml, $streamHost, false, array('JAXL0065', 'transferFile'));
        }

        public static function transferFile($payload, $jaxl) {
            // send data via proxy
            $fh = fopen(self::$id[$payload['id']]['file'], 'r');
            while($data = fread($fh, 1024)) {
                $jaxl->log("[[JAXL0065]] Sending 1024 bytes of data via proxy");
                self::$id[$payload['id']]['s5b']->write($data);
            }
            $jaxl->executePlugin('jaxl_post_file_transfer', null);
            unset(self::$id[$payload['id']]);
        }

    }

?>
