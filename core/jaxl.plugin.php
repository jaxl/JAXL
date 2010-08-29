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
     * Jaxl Plugin Framework
     *
     * Available methods:
     * add($hook, $callback)
     * remove($hook, $callback)
    */
    
    class JAXLPlugin {
        
        public static $registry = array();
        
        public static function add($hook, $callback, $priority=10) {
            if(!isset(self::$registry[$hook]))
                self::$registry[$hook] = array();
            
            if(!isset(self::$registry[$hook][$priority])) 
                self::$registry[$hook][$priority] = array();
            
            array_push(self::$registry[$hook][$priority], $callback);
        }
        
        public static function remove($hook, $callback) {
            if(isset(self::$registry[$hook][$callback])) {
                unset(self::$registry[$hook][$callback]);
            }
            
            if(count(self::$registry[$hook]) == 0) {
                unset(self::$registry[$hook]);
            }
        }
        
        /*
         * execute methods will only execute those callbacks
         * Which are passed as $filter paramater
        */
        public static function execute($hook, $payload=null, $jaxl=false, $filter=false) {
            if(isset(self::$registry[$hook]) && count(self::$registry[$hook]) > 0) {
                foreach(self::$registry[$hook] as $priority) {
                    foreach($priority as $callback) {
                        if($filter === false || (is_array($filter) && in_array($callback[0], $filter))) {
                            $payload = call_user_func($callback, $payload, $jaxl);
                        }
                    }
                }
            }
            return $payload;
        }
        
    }

?>
