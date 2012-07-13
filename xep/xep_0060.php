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

require_once JAXL_CWD.'/xmpp/xmpp_xep.php';

define('NS_CAPS', 'http://jabber.org/protocol/caps');

class XEP_0060 extends XMPPXep {

	//
	// abstract method
	//

	public function init() {
		return array();
	}

	//
	// api methods (entity use case)
	//
	
	//
	// api methods (subscriber use case)
	//
	
	public function subscribe() {
		
	}
	
	public function unsubscribe() {
		
	}
	
	public function get_subscription_options() {
		
	}
	
	public function set_subscription_options() {
		
	}
	
	public function get_node_items() {
		
	}
	
	//
	// api methods (publisher use case)
	//
	
	public function publish_item() {
		
	}
	
	public function delete_item() {
		
	}
	
	public function create_node() {
		
	}
	
	public function delete_node() {
		
	}
	
	public function purge_node() {
		
	}
	
	public function set_node_config() {
		
	}
	
	public function get_node_config() {
		
	}
	
	public function get_subscriber_list() {
		
	}
	
	public function update_subscription() {
		
	}
	
	public function get_affiliation_list() {
		
	}
	
	public function update_affiliation() {
		
	}
	
	//
	// api methods (owner use case)
	//
	
	//
	// event callbacks
	//

}

?>
