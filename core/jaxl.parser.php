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
     * Jaxl XML Parsing Framework
    */
    class JAXLXml {
        
        /*
         * Contains XPath Map for various XMPP stream and stanza's
         * @url http://tools.ietf.org/html/draft-ietf-xmpp-3920bis-10
         * 
        */
    
        protected static $tagMap = array(

            'starttls'      =>  array(
                'xmlns'     =>  '//starttls/@xmlns'
            ),
        
            'proceed'       =>  array(
                'xmlns'     =>  '//proceed/@xmlns'
            ),
        
            'challenge'     =>  array(
                'xmlns'     =>  '//challenge/@xmlns',
                'challenge' =>  '//challenge/text()'
            ),
        
            'success'       =>  array(
                'xmlns'     =>  '//success/@xmlns'
            ),
            
            'failure'       =>  array(
                'xmlns'     =>  '//failure/@xmlns',
                'condition' =>  '//failure/text()',
                'desc'      =>  '//failure/text',
                'descLang'  =>  '//failure/text/@xml:lang'
            ),
            
            'message'       =>  array(
                'to'        =>  '//message/@to',
                'from'      =>  '//message/@from',
                'id'        =>  '//message/@id',
                'type'      =>  '//message/@type',
                'xml:lang'  =>  '//message/@xml:lang',
                'body'      =>  '//message/body',
                'errorType' =>  '//message/error/@type',
                'errorCode' =>  '//message/error/@code'
            ),
            
            'presence'      =>  array(
                'to'        =>  '//presence/@to',
                'from'      =>  '//presence/@from',
                'id'        =>  '//presence/@id',
                'type'      =>  '//presence/@type',
                'xml:lang'  =>  '//presence/@xml:lang',
                'show'      =>  '//presence/show',
                'status'    =>  '//presence/status',
                'priority'  =>  '//presence/priority',
                'errorType' =>  '//presence/error/@type',
                'xXmlns'    =>  '//presence/x/@xmlns',
                'errorCode' =>  '//presence/error/@code'
            ),
            
            'iq'            =>  array(
                'to'        =>  '//iq/@to',
                'from'      =>  '//iq/@from',
                'id'        =>  '//iq/@id',
                'type'      =>  '//iq/@type',
                'xml:lang'  =>  '//iq/@xml:lang',
                'bindResource'  =>  '//iq/bind/resource',
                'bindJid'   =>  '//iq/bind/jid',
                'queryXmlns'    =>  '//iq/query/@xmlns',
                'queryVer'  =>  '//iq/query/@ver',
                'queryItemSub'  =>  '//iq/query/item/@subscription',
                'queryItemJid'  =>  '//iq/query/item/@jid',
                'queryItemName' =>  '//iq/query/item/@name',
                'queryItemAsk'  =>  '//iq/query/item/@ask',
                'queryItemGrp'  =>  '//iq/query/item/group',
                'errorType' =>  '//iq/error/@type'
            )
            
        );
        
        /*
         * parse method assumes passed $xml parameter to be a single xmpp packet
        */
        public static function parse($xml) {
            $payload = array();
            
            $xml = str_replace('xmlns=', 'ns=', $xml);
            $xml = new SimpleXMLElement($xml);
            $node = $xml->getName();
            $parents = array();
            
            foreach(self::$tagMap[$node] as $tag=>$xpath) {
                $xpath = str_replace('/@xmlns', '/@ns', $xpath);
                $parentXPath = implode('/', explode('/', $xpath, -1));
                $tagXPath = str_replace($parentXPath.'/', '', $xpath);
                
                if(!isset($parents[$parentXPath])) $parents[$parentXPath] = $xml->xpath($parentXPath);
                
                foreach($parents[$parentXPath] as $key=>$obj) {
                    if($tagXPath == 'text()') {
                        $values = $obj[0];
                    }
                    else if(substr($tagXPath, 0, 1) == '@') {
                        $txpath = str_replace('@', '', $tagXPath);
                        $values = $obj->attributes()->{$txpath};
                        unset($txpath);
                    }
                    else { $values = $obj->{$tagXPath}; }
                    
                    if(sizeof($values) > 1) {
                        $temp = array();
                        foreach($values as $value) $temp[] = (string)$value[0];
                        $payload[$node][$tag][] = $temp;
                        unset($temp);
                    }
                    else {
                        if(sizeof($parents[$parentXPath]) == 1) $payload[$node][$tag] = (string)$values[0];
                        else $payload[$node][$tag][] = (string)$values[0];
                    }
                }
            }
            
            unset($xml);
            return $payload;
        }
        
        public static function addTag($node, $tag, $map) {
            self::$tagMap[$node][$tag] = $map;
        }
        
        public static function removeTag($node, $tag) {
            unset(self::$tagMap[$node][$tag]);
        }
        
        public static function create($tagVals) {
            foreach($tagVals as $node=>$tagVal) {
                // initialize new XML document
                $dom = new DOMDocument();
                $superRoot = $dom->createElement($node);
                $dom->appendChild($superRoot);
                
                $childs = array();
                // iterate over all tag values
                foreach($tagVal as $tag=>$value) {
                    // find xpath where this $tag and $value should go
                    $xpath = self::$tagMap[$node][$tag];
                    
                    // xpath parts for depth detection
                    $xpath = str_replace('//'.$node.'/', '', $xpath);
                    $xpart = explode('/', $xpath);
                    $depth = sizeof($xpart);
                    
                    $root = $superRoot;
                    for($currDepth=0; $currDepth<$depth; $currDepth++) {
                        $element = $xpart[$currDepth];
                        $isAttr = (substr($element, 0, 1) == '@') ? true : false;
                        
                        if($isAttr) {
                            $element = str_replace('@', '', $element);
                            $attr = $dom->createAttribute($element);
                            $root->appendChild($attr);
                            $text = $dom->createTextNode($value);
                            $attr->appendChild($text);
                        }
                        else {
                            if(!isset($childs[$currDepth][$element])) {
                                $child = $dom->createElement($element);
                                $root->appendChild($child);
                                $childs[$currDepth][$element] = true;
                            }
                            else if($currDepth == $depth-1) {
                                //echo ' value '.$value.PHP_EOL.PHP_EOL;
                                $text = $dom->createTextNode($value);
                                $child->appendChild($text);
                            }
                            $root = $child;
                        }
                    }
                }
                
                $xml = $dom->saveXML();
                unset($dom); unset($attr); unset($child); unset($text);
                return $xml;
            }
        }
        
    }
    
?>
