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
 * @subpackage core
 * @author Abhinav Singh <me@abhinavsingh.com>
 * @copyright Abhinav Singh
 * @link http://code.google.com/p/jaxl
 */

    /**
     * Jaxl SOCKS5 class
     * http://tools.ietf.org/html/rfc1928
    */
    class JAXLS5B {

        var $socket = null;
        var $connected = false;

        /**
         * Constructor connects to the server and sends a version identifier/method selection message
        */
        function __construct($ip, $port, $jaxl) {
            if($this->socket = @fsockopen($ip, (int)$port, $errno, $errstr)) {
                $jaxl->log("[[JAXLS5B]] Socket opened to $ip:$port");
                // send version/identifier selection message
                $pkt = pack("C3", 0x05, 0x01, 0x00);
                $this->write($pkt);
                $jaxl->log("[[JAXLS5B]] Sending selection message to $ip:$port");
                
                // recv method selection message
                $rcv = '';
                while($buffer = $this->read()) {
                    $rcv .= $buffer;
                    $pkt = unpack("Cversion/Cmethod", $rcv);
                    if($pkt['version'] == 0x05 && $pkt['method'] == 0x00) {
                        $jaxl->log("[[JAXLS5B]] Selection message accepted by $ip:$port");
                        return true;
                    }
                }
                
                // close socket if method not accepted
                $jaxl->log("[[JAXLS5B]] Selection message not accepted by $ip:$port");
                fclose($this->socket);
            }
            
            $jaxl->log("[[JAXLS5B]] Unable to open socket to $ip:$port");
            return false;
        }

        /**
         * Connect method send request details and receive replies from the server
        */
        function connect($sid, $rJid, $tJid, $jaxl) {
            if($this->socket) {
                // send request detail pkt
                $dstAddr = sha1($sid.$rJid.$tJid);
                $pkt = pack("C5", 0x05, 0x01, 0x00, 0x03, strlen($dstAddr)).$dstAddr.pack("n", 0);
                $this->write($pkt);
                $jaxl->log("[[JAXLS5B]] Sending request detail packet to $dstAddr:0");
                
                // recv server ack
                $rcv = '';
                while($buffer = fread($this->socket, 1024)) {
                    $rcv .= $buffer;
                    $pkt = unpack("Cversion/Cresult/Creg/Ctype/Lip/Sport", $rcv);
                    if($pkt['version'] == 0x05 && $pkt['result'] == 0x00) {
                        $jaxl->log("[[JAXLS5B]] Request detail packet accepted, S5B completed on $dstAddr:0");
                        $this->connected = true;
                        return true;
                    }
                }
            }
            
            $jaxl->log("[[JAXLS5B]] Unable to complete S5B on $dstAddr:0");
            $this->connected = false;
            return false;
        }

        function read($bytes=1024) {
            return fread($this->socket, $bytes);
        }

        function write($pkt) {
            return fwrite($this->socket, $pkt);
        }

    }
    
?>
