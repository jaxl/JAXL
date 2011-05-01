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
     * XEP-0047: In-Band Bytestreams
    */
    class JAXL0047 {
        
        public static $id = array();
        public static $ns = 'http://jabber.org/protocol/ibb';
        
        public static function init($jaxl) {
            $jaxl->features[] = self::$ns;
            
            JAXLXml::addTag('iq', 'open', '//iq/open/@xmlns');
            JAXLXml::addTag('iq', 'openSid', '//iq/open/@sid');
            JAXLXml::addTag('iq', 'openBlkSize', '//iq/open/@block-size');
            JAXLXml::addTag('iq', 'data', '//iq/data/@xmlns');
            JAXLXml::addTag('iq', 'dataSid', '//iq/data/@sid');
            JAXLXml::addTag('iq', 'dataSeq', '//iq/data/@seq');
            JAXLXml::addTag('iq', 'dataEncoded', '//iq/data');
            JAXLXml::addTag('iq', 'close', '//iq/close/@xmlns');
            JAXLXml::addTag('iq', 'closeSid', '//iq/close/@sid');   
            
            $jaxl->addPlugin('jaxl_get_iq_set', array('JAXL0047', 'handleTransfer'));
        }
        
        public static function handleTransfer($payload, $jaxl) {
            if($payload['open'] == self::$ns) {
                $jaxl->log("[[JAXL0047]] File transfer opened for siId ".$payload['openSid']);
                file_put_contents($jaxl->tmpPath.'/'.$payload['openSid'], '');
                XMPPSend::iq($jaxl, 'result', '', $payload['from'], $payload['to'], false, $payload['id']);
            }
            else if($payload['data'] == self::$ns) {
                $jaxl->log("[[JAXL0047]] Accepting transfer data for siId ".$payload['dataSid']);
                $data = base64_decode($payload['dataEncoded']);
                file_put_contents($jaxl->tmpPath.'/'.$payload['dataSid'], $data, FILE_APPEND);
                
                XMPPSend::iq($jaxl, 'result', '', $payload['from'], $payload['to'], false, $payload['id']);
            }
            else if($payload['close'] == self::$ns) {
                $jaxl->log("[[JAXL0047]] Transfer complete for siId ".$payload['closeSid']);
                $jaxl->executePlugin('jaxl_post_file_request', array('tmp'=>$jaxl->tmpPath.'/'.$payload['closeSid']));
                XMPPSend::iq($jaxl, 'result', '', $payload['from'], $payload['to'], false, $payload['id']);    
            }

            return $payload;
        }

        public static function sendFile($jaxl, $payload) {
            // initiate file request session
            $jaxl->log("[[JAXL0047]] Opening file transfer with siId ".$payload['siId']);
            $xml = '<open xmlns="'.self::$ns.'" block-size="4096" sid="'.$payload['siId'].'" stanza="iq"/>';
            $id = XMPPSend::iq($jaxl, 'set', $xml, $payload['to'], false, array('JAXL0047', 'transferFile'));
            
            self::$id[$id] = $payload;
            self::$id[$id]['seq'] = 0;
            self::$id[$id]['block-size'] = 4096;
            self::$id[$id]['stanza'] = 'iq';
            
            return $id;
        }

        public static function transferFile($payload, $jaxl) {
            if(!isset(self::$id[$payload['id']]))
                return $payload;

            // iq id buffered for file transfer, transmit data
            $fp = fopen(self::$id[$payload['id']]['file'], 'r');
            fseek($fp, self::$id[$payload['id']]['seq']*self::$id[$payload['id']]['block-size']);
            $data = fread($fp, self::$id[$payload['id']]['block-size']);
            fclose($fp);

            if(self::$id[$payload['id']]['seq'] == -1) {
                $jaxl->log("[[JAXL0047]] File transfer complete for siId ".self::$id[$payload['id']]['siId']);
                $jaxl->executePlugin('jaxl_post_file_transfer', self::$id[$payload['id']]);
                unset(self::$id[$payload['id']]);
                return;
            }
            else if(strlen($data) == 0) {
                $jaxl->log("[[JAXL0047]] File transfer closed for siId ".self::$id[$payload['id']]['siId']);
                $xml = '<close xmlns="'.self::$ns.'" sid="'.self::$id[$payload['id']]['siId'].'"/>';
                
                $id = XMPPSend::iq($jaxl, 'set', $xml, self::$id[$payload['id']]['to'], false, array('JAXL0047', 'transferFile'));
                self::$id[$id] = self::$id[$payload['id']];
                self::$id[$id]['seq'] = -1;
                
                unset(self::$id[$payload['id']]);
                return $id;
            }
            else {
                $jaxl->log("[[JAXL0047]] Transfering file data for seq ".self::$id[$payload['id']]['seq']." for siId ".self::$id[$payload['id']]['siId']);
                $xml = '<data xmlns="'.self::$ns.'" seq="'.self::$id[$payload['id']]['seq'].'" sid="'.self::$id[$payload['id']]['siId'].'">';
                $xml .= base64_encode($data);
                $xml .= '</data>';

                $id = XMPPSend::iq($jaxl, 'set', $xml, self::$id[$payload['id']]['to'], false, array('JAXL0047', 'transferFile'));
                self::$id[$id] = self::$id[$payload['id']];
                self::$id[$id]['seq'] = self::$id[$id]['seq']+1;
                
                unset(self::$id[$payload['id']]);
                return $id;
            }
        }
        
    }

?>
