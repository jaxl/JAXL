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
 * JAXLXml($name, $ns, $attrs, $text)
 * JAXLXml($name, $ns, $attrs)
 * JAXLXml($name, $ns, $text)
 * JAXLXml($name, $attrs, $text)
 * JAXLXml($name, $attrs)
 * JAXLXml($name, $ns)
 * JAXLXml($name)
 * 
 * @author abhinavsingh
 *
 */
class JAXLXml {
	
	public $name;
	public $ns = null;
	public $attrs = array();
	public $text = null;
	
	public $childrens = array();
	public $parent = null;
	public $rover = null;
	
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
		
		$this->rover = &$this;
	}
	
	public function __destruct() {
		
	}
	
	// merge new attrs with attributes of current rover
	public function attrs($attrs) {
		$this->rover->attrs = array_merge($this->rover->attrs, $attrs);
		return $this;
	}
	
	// pass a kv pair of attrs
	// this function return bool if all attribute 
	// keys passed matches their respective values
	public function match_attrs($attrs) {
		$matches = true;
		foreach($attrs as $k=>$v) {
			if($this->attrs[$k] !== $v) {
				$matches = false;
				break;
			}
		}
		return $matches;
	}
	
	// update text of current rover
	public function t($text, $append=FALSE) {
		if(!$append) {
			$this->rover->text = $text;
		}
		else {
			if($this->rover->text === null) 
				$this->rover->text = '';
			$this->rover->text .= $text;
		} 
		return $this;
	}
	
	// append a child node at current rover
	public function c($name, $ns=null, $attrs=array(), $text=null) {
		$node = new JAXLXml($name, $ns, $attrs, $text);
		$node->parent = &$this->rover;
		$this->rover->childrens[] = &$node;
		$this->rover = &$node;
		return $this;
	}
	
	// append a JAXLXml object at current rover
	public function cnode($node) {
		$node->parent = &$this->rover;
		$this->rover->childrens[] = &$node;
		$this->rover = &$node;
		return $this;
	}
	
	// move rover to one step up
	public function up() {
		if($this->rover->parent) $this->rover = &$this->rover->parent;
		return $this;
	}
	
	// move rover back to top element
	public function top() {
		$this->rover = &$this;
		return $this;
	}
	
	// checks if a child with $name exists
	// return child XmlStanza if found otherwise false
	// NOTE: if multiple children exists with same name,
	// this function returns on first child check
	public function exists($name, $ns=null, $attrs=array()) {
		foreach($this->childrens as $child) {
			if($ns) {
				if($child->name == $name && $child->ns == $ns && $child->match_attrs($attrs))
					return $child;
			}
			else if($child->name == $name && $child->match_attrs($attrs)) {
				return $child;
			}
		}
		return false;
	}
	
	// update child element
	public function update($name, $ns=null, $attrs=array(), $text=null) {
		foreach($this->childrens as $k=>$child) {
			if($child->name == $name) {
				$child->ns = $ns;
				$child->attrs($attrs);
				$child->text = $text;
				$this->childrens[$k] = $child;
				break;
			}
		}
	}
	
	// to string conversion
	public function to_string($parent_ns=null) {
		$xml = '';
		
		$xml .= '<'.$this->name;
		if($this->ns && $this->ns != $parent_ns) $xml .= ' xmlns="'.$this->ns.'"';
		foreach($this->attrs as $k=>$v) if(!is_null($v) && $v !== FALSE) $xml .= ' '.$k.'="'.htmlspecialchars($v).'"';
		$xml .= '>';
		
		foreach($this->childrens as $child) $xml .= $child->to_string($this->ns);
		
		if($this->text) $xml .= htmlspecialchars($this->text);
		$xml .= '</'.$this->name.'>';
		return $xml;
	}
	
}

?>
