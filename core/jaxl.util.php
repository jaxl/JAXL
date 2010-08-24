<?php
/* Jaxl (Jabber XMPP Library)
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
 */

    /*
     * Jaxl Utility Class
    */
    class JAXLUtil {
        
        public static function curl($url, $type='GET', $headers=false, $data=false, $user=false, $pass=false) {
            $ch = curl_init($url);
            
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, false);
            if($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_VERBOSE, false);
            
            if($type == 'POST') {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
            
            if($user && $pass) {
                curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$pass);
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            }
            
            $rs = array();
            $rs['content'] = curl_exec($ch);
            $rs['errno'] = curl_errno($ch);
            $rs['errmsg'] = curl_error($ch);
            $rs['header'] = curl_getinfo($ch);
            
            curl_close($ch);
            return $rs;
        }
        
        public static function includePath($path) {
            $include_path = ini_get('include_path');
            $include_path .= ':'.$path;
            ini_set('include_path', $include_path);
        }
        
        public static function getAppFilePath() {
            if(php_sapi_name() == 'cli') {
                global $argv;
                if(sizeof($argv) == 2) {
                    $value = array_pop($argv);
                    $botPath = JAXL_APP_BASE_PATH.'/'.$value;
                }
                else {
                    die("invalid number of parameters passed ...");
                }
            }
            else {
                if(isset($_REQUEST['jaxl'])) {
                    $botPath = JAXL_BOSH_APP_ABS_PATH;
                }
                else {
                    header("HTTP/1.1 400 Bad Request");
                    die("missing action ...");
                }
            }
            
            self::includePath(dirname($botPath));
            if($botPath && file_exists($botPath)) return $botPath;
            else return false;
        }
        
        public static function isWin() {
            return strtoupper(substr(PHP_OS,0,3)) == "WIN" ? true : false;
        }
        
        public static function pcntlEnabled() {
            return extension_loaded('pcntl');
        }
        
        public static function sslEnabled() {
            return extension_loaded('openssl');
        }
        
        public static function getTime() {
            list($usec, $sec) = explode(" ",microtime());
                return (float) $sec + (float) $usec;
        }
        
        public static function splitXML($xml) {
            $xmlarr = array();
            $temp = preg_split("/<(message|iq|presence|stream|proceed|challenge|success|failure)(?=[\:\s\>])/", $xml, -1, PREG_SPLIT_DELIM_CAPTURE);
                for($a=1; $a<count($temp); $a=$a+2) $xmlarr[] = "<".$temp[$a].$temp[($a+1)];
            return $xmlarr;
        }

        public static function explodeData($data) {
            $data = explode(',', $data);
            $pairs = array();
            $key = false;
            
                foreach($data as $pair) {
                    $dd = strpos($pair, '=');

                    if($dd) {
                        $key = trim(substr($pair, 0, $dd));
                        $pairs[$key] = trim(trim(substr($pair, $dd + 1)), '"');
                    }
                else if(strpos(strrev(trim($pair)), '"') === 0 && $key) {
                        $pairs[$key] .= ',' . trim(trim($pair), '"');
                        continue;
                    }
                }
            
            return $pairs;
        }
        
        public static function implodeData($data) {
            $return = array();
            foreach($data as $key => $value)
                $return[] = $key . '="' . $value . '"';
            return implode(',', $return);
        }
        
        public static function generateNonce() {
            $str = '';
                mt_srand((double) microtime()*10000000);
            for($i=0; $i<32; $i++) $str .= chr(mt_rand(0, 255));
            return $str;
        }
        
        public static function encryptPassword($data) {
            global $jaxl;
            
            foreach(array('realm', 'cnonce', 'digest-uri') as $key)
                if(!isset($data[$key]))
                    $data[$key] = '';
            
            $pack = md5($jaxl->user.':'.$data['realm'].':'.$jaxl->pass);
            
            if(isset($data['authzid'])) {
                    $a1 = pack('H32',$pack).sprintf(':%s:%s:%s',$data['nonce'],$data['cnonce'],$data['authzid']);
                }
                else {
                    $a1 = pack('H32',$pack).sprintf(':%s:%s',$data['nonce'],$data['cnonce']);
                }
                $a2 = 'AUTHENTICATE:'.$data['digest-uri'];
            
            return md5(sprintf('%s:%s:%s:%s:%s:%s', md5($a1), $data['nonce'], $data['nc'], $data['cnonce'], $data['qop'], md5($a2)));
        }

        public static function hmacMD5($key, $data) {
            if(strlen($key) > 64) $key = pack('H32', md5($key));
            if(strlen($key) < 64) $key = str_pad($key, 64, chr(0));
            $k_ipad = substr($key, 0, 64) ^ str_repeat(chr(0x36), 64);
            $k_opad = substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64);
            $inner  = pack('H32', md5($k_ipad . $data));
            $digest = md5($k_opad . $inner);
            return $digest;
        }
        
        public static function pbkdf2($data, $secret, $iteration, $dkLen=32, $algo='sha1') {
            $hLen = strlen(hash($algo, null, true));
            
            $l = ceil($dkLen/$hLen);
            $t = null;
            for($i=1; $i<=$l; $i++) {
                $f = $u = hash_hmac($algo, $s.pack('N', $i), $p, true);
                for($j=1; $j<$c; $j++) {
                    $f ^= ($u = hash_hmac($algo, $u, $p, true));
                }
                $t .= $f;
            }
            return substr($t, 0, $dk_len);
        }
        
        public static function getBareJid($jid) {
            list($user,$domain,$resource) = self::splitJid($jid);
            return ($user ? $user."@" : "").$domain;
        }
        
        public static function splitJid($jid) {
            preg_match("/(?:([^\@]+)\@)?([^\/]+)(?:\/(.*))?$/",$jid,$matches);
            return array($matches[1],$matches[2],@$matches[3]);
        }
        
    }
    
?>
