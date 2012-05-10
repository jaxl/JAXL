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

require_once 'xmpp/xmpp_nss.php';
require_once 'xmpp/xmpp_jid.php';
require_once 'core/jaxl_xml.php';

class XMPPStanza extends JAXLXml {
	
	public function __construct($name, $attrs=array(), $ns=NS_JABBER_CLIENT) {
		parent::__construct($name, $ns, $attrs);
	}
	
	public function __get($prop) {
		switch($prop) {
			case 'to':
			case 'from':
			case 'id':
			case 'type':
				return @$this->attrs[$prop] ? $this->attrs[$prop] : null;
				break;
			
			case 'to_node':
			case 'to_domain':
			case 'to_resource':
			case 'from_node':
			case 'from_domain':
			case 'from_resource':
				list($attr, $key) = explode('_', $prop);
				$val = @$this->attrs[$attr] ? $this->attrs[$attr] : null;
				if(!$val) return null;
	
				$val = new XMPPJid($val);
				return $val->$key;
				break;
			
			case 'status':
			case 'show':
			case 'priority':
			case 'body':
			case 'thread':
			case 'subject':
				$val = $this->exists($prop);
				if(!$val) return null;
				return $val->text;
				break;
	
			default:
				return null;
				break;
		}
	}
	
	public function __set($prop, $val) {
		switch($prop) {
			case 'to':
			case 'from':
			case 'id':
			case 'type':
				$this->attrs[$prop] = $val;
				return true;
				break;
			
			case 'to_node':
			case 'to_domain':
			case 'to_resource':
			case 'from_node':
			case 'from_domain':
			case 'from_resource':
				list($attr, $key) = explode('_', $prop);
				$val1 = @$this->attrs[$attr];
				if(!$val1) $val1 = '';
				
				$val1 = new XMPPJid($val1);
				$val1->$key = $val;
				
				$this->attrs[$attr] = $val1->to_string();
				return true;
				break;
			
			case 'status':
			case 'show':
			case 'priority':
			case 'body':
			case 'thread':
			case 'subject':
				// TODO:
				return false;
				break;
	
			default:
				return null;
				break;
		}
	}
}

?>