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

define('NS_DIRECT_MUC_INVITATION', 'jabber:x:conference');

class XEP_0249 extends XMPPXep {
	
	//
	// abstract method
	//
	
	public function init() {
		return array();
	}
	
	//
	// api methods
	//
	
	public function get_invite_pkt($to_bare_jid, $room_jid, $password=null, $reason=null, $thread=null, $continue=null) {
		$xattrs = array('jid'=>$room_jid);
		if($password) $xattrs['password'] = $password;
		if($reason) $xattrs['reason'] = $reason;
		if($thread) $xattrs['thread'] = $thread;
		if($continue) $xattrs['continue'] = $continue;
		
		return $this->jaxl->get_msg_pkt(
			array('from'=>$this->jaxl->full_jid->to_string(), 'to'=>$to_bare_jid),
			null, null, null,
			new JAXLXml('x', NS_DIRECT_MUC_INVITATION, $xattrs)
		);
	}
	
	public function invite($to_bare_jid, $room_jid, $password=null, $reason=null, $thread=null, $continue=null) {
		$this->jaxl->send($this->get_invite_pkt($to_bare_jid, $room_jid, $password, $reason, $thread, $continue));
	}
	
	//
	// event callbacks
	//
	
}

?>