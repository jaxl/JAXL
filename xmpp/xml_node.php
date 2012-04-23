<?php
/**
* Jaxl (Jabber XMPP Library)
*
* Copyright (c) 2009-2012, Abhinav Singh <me@abhinavsingh.com>.
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions
* are met:
*
* * Redistributions of source code must retain the above copyright
* notice, this list of conditions and the following disclaimer.
*
* * Redistributions in binary form must reproduce the above copyright
* notice, this list of conditions and the following disclaimer in
* the documentation and/or other materials provided with the
* distribution.
*
* * Neither the name of Abhinav Singh nor the names of his
* contributors may be used to endorse or promote products derived
* from this software without specific prior written permission.
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
*/

/**
 * 
 * Usage:
 * ------
 * XmlNode($name, $ns, $attrs, $text)
 * XmlNode($name, $ns, $attrs)
 * XmlNode($name, $ns, $text)
 * XmlNode($name, $attrs, $text)
 * XmlNode($name, $attrs)
 * XmlNode($name, $ns)
 * XmlNode($name)
 * 
 * @author abhinavsingh
 *
 */
class XmlNode {
	
	public $name;
	public $ns = '';
	public $attrs = array();
	public $text = '';
	
	public $childrens = array();
	public $parent = NULL;
	public $rover = NULL;
	
	public function __construct() {
		$argv = func_get_args();
		$argc = sizeof($argv);
		
		$this->name = $argv[0];
		
		switch($argc) {
			case 4:
				$this->ns = $argv[1];
				$this->attrs = $argv[2];
				$this->text = $argv[3];
				break;
			case 3:
				if(is_array($argv[1])) {
					$this->attrs = $argv[1];
					$this->text = $argv[2];
				}
				else {
					$this->ns = $argv[1];
					if(is_array($argv[2])) {
						$this->attrs = $argv[2];
					}
					else {
						$this->text = $argv[2];
					}
				}
				break;
			case 2:
				if(is_array($argv[1])) {
					$this->attrs = $argv[1];
				}
				else {
					$this->ns = $argv[1];
				}
				break;
			default:
				break;
		}
	}
	
	public function attrs($attrs) {
		
	}
	
	public function c($name, $ns, $attrs, $text) {
		
	}
	
	public function cnode($node) {
		
	}
	
	public function t($text) {
		
	}
	
	public function to_str() {
		
	}
	
}

?>