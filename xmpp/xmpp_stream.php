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
require_once 'core/jaxl_util.php';

/**
 * 
 * Enter description here ...
 * @author abhinavsingh
 *
 */
class XmppStream {
	
	private $state = "setup";
	public $jid = null;
	private $pass = null;
	public $sock = null;
	private $xml = null;
	public $mechs = array();
	private $pref_auth_type = "PLAIN";
	private $force_tls = true;
	private $last_id = 0;
	
	//
	// public api
	// 
	
	public function __construct($jid, $pass, $pref_auth_type="PLAIN") {
		$this->pref_auth_type = $pref_auth_type;
		$this->jid = new XmppJid($jid);
		$this->pass = $pass;
		$this->sock = new XmppSocket($this->jid->domain);
		$this->xml = new XmlStream();
		$this->sock->set_callback(array(&$this->xml, "parse"));
		$this->xml->set_callback(array(&$this, "start_cb"), array(&$this, "end_cb"), array(&$this, "stanza_cb"));
	}
	
	public function __destruct() {
		echo "cleaning up xmpp stream...\n";
	}
	
	public function __call($event, $args) {
		if($this->state) {
			echo "calling ".$this->state." for event ".$event."\n";
			$this->state = call_user_func(array(&$this, $this->state), $event, $args);
		}
		else {
			echo "nothing called for event ".$event."\n";
		}
	}
	
	public function send($stanza) {
		$this->sock->send($stanza->to_string());
	}
	
	public function send_raw($data) {
		$this->sock->send($data);
	}
	
	//
	// pkt creation utilities
	//
	
	public function get_start_stream($domain) {
		return '<stream:stream xmlns:stream="'.NS_XMPP.'" version="1.0" to="'.$domain.'" xmlns="'.NS_JABBER_CLIENT.'" xml:lang="en" xmlns:xml="'.NS_XML.'">';
	}
	
	public function get_end_stream() {
		return '</stream:stream>';
	}
	
	public function get_starttls_stanza() {
		$stanza = new XmlStanza('starttls', NS_TLS);
		return $stanza;
	}
	
	public function get_auth_stanza($mechanism, $user, $pass) {
		$stanza = new XmlStanza('auth', NS_SASL, array('mechanism'=>$mechanism));
		
		switch($mechanism) {
			case 'PLAIN':
				$stanza->t(base64_encode("\x00".$user."\x00".$pass));
				break;
			case 'DIGEST-MD5':
				break;
			case 'ANONYMOUS':
				break;
			default:
				break;
		}
		
		return $stanza;
	}
	
	public function get_challenge_response_pkt($challenge) {
		$stanza = new XmlStanza('response', NS_SASL);
		$decoded = $this->explode_data(base64_decode($challenge));
		
		if(!isset($decoded['rspauth'])) {
			echo "calculating response to challenge\n";
			$stanza->t($this->get_challenge_response($decoded));
		}
		
		return $stanza;
	}
	
	public function get_challenge_response($decoded) {
		$response = array();
		$nc = '00000001';
		
		if(!isset($decoded['digest-uri']))
			$decoded['digest-uri'] = 'xmpp/'.$this->jid->domain;
		
		$decoded['cnonce'] = base64_encode(JAXLUtil::get_nonce());
		
		if(isset($decoded['qop']) && $decoded['qop'] != 'auth' && strpos($decoded['qop'], 'auth') !== false)
			$decoded['qop'] = 'auth';
		
		$data = array_merge($decoded, array('nc'=>$nc));
			
		$response = array(
			'username'=> $this->jid->node,
			'response' => $this->encrypt_password($data, $this->jid->node, $this->pass),
			'charset' => 'utf-8',
			'nc' => $nc,
			'qop' => 'auth'
		);
			
		foreach(array('nonce', 'digest-uri', 'realm', 'cnonce') as $key)
			if(isset($decoded[$key]))
				$response[$key] = $decoded[$key];
		
		return base64_encode($this->implode_data($response));
	}
	
	public function get_bind_pkt($resource) {
		$stanza = new XmlStanza('bind', NS_BIND);
		$stanza->c('resource')->t($resource);
		return $this->get_iq_pkt(array(
			'type' => 'set'
		), $stanza);
	}
	
	public function get_session_pkt() {
		$stanza = new XmlStanza('session', NS_SESSION);
		return $this->get_iq_pkt(array(
			'type' => 'set'
		), $stanza);
	}
	
	public function get_msg_pkt($attrs, $subject=null, $body=null, $thread=null, $payload=null) {
		if(!isset($attrs['id'])) $attrs['id'] = $this->get_id();
		$stanza = new XmlStanza('message', NS_JABBER_CLIENT);
		$stanza->attrs($attrs);
		
		if($subject) $stanza->c('subject')->t($subject)->up();
		if($body) $stanza->c('body')->t($body)->up();
		if($thread) $stanza->c('thread')->t($thread)->up();
		
		if($payload) $stanza->cnode($payload);
		return $stanza;
	}
	
	public function get_pres_pkt($attrs, $show, $status, $priority, $payload) {
		if(!isset($attrs['id'])) $attrs['id'] = $this->get_id();
		$stanza = new XmlStanza('presence', NS_JABBER_CLIENT);
		$stanza->attrs($attrs);
		
		if($show) $stanza->c('show')->t($show)->up();
		if($status) $stanza->c('status')->t($status)->up();
		if($priority) $stanza->c('priority')->t($priority)->up();
		
		if($payload) $stanza->cnode($payload);
		return $stanza;
	}
	
	public function get_iq_pkt($attrs, $payload) {
		if(!isset($attrs['id'])) $attrs['id'] = $this->get_id();
		$stanza = new XmlStanza('iq', NS_JABBER_CLIENT);
		$stanza->attrs($attrs);
		
		if($payload) $stanza->cnode($payload);
		return $stanza;
	}
	
	public function get_id() {
		++$this->last_id;
		return dechex($this->last_id);
	}
	
	public function explode_data($data) {
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
	
	public function implode_data($data) {
		$return = array();
		foreach($data as $key => $value) $return[] = $key . '="' . $value . '"';
		return implode(',', $return);
	}
	
	public function encrypt_password($data, $user, $pass) {
		foreach(array('realm', 'cnonce', 'digest-uri') as $key)
			if(!isset($data[$key])) 
				$data[$key] = '';
	
		$pack = md5($user.':'.$data['realm'].':'.$pass);
		
		if(isset($data['authzid'])) 
			$a1 = pack('H32',$pack).sprintf(':%s:%s:%s',$data['nonce'],$data['cnonce'],$data['authzid']);
		else 
			$a1 = pack('H32',$pack).sprintf(':%s:%s',$data['nonce'],$data['cnonce']);
		
		$a2 = 'AUTHENTICATE:'.$data['digest-uri'];
		return md5(sprintf('%s:%s:%s:%s:%s:%s', md5($a1), $data['nonce'], $data['nc'], $data['cnonce'], $data['qop'], md5($a2)));
	}
	
	//
	// socket senders
	//
	
	protected function send_start_stream($domain) {
		$this->send_raw($this->get_start_stream($domain));
	}
	
	protected function send_end_stream() {
		$this->send_raw($this->get_end_stream());
	}
	
	protected function send_auth_pkt($type, $user, $pass) {
		$this->send($this->get_auth_stanza($type, $user, $pass));
	}
	
	protected function send_starttls_pkt() {
		$this->send($this->get_starttls_stanza());
	}
	
	protected function send_challenge_response($challenge) {
		$this->send($this->get_challenge_response_pkt($challenge));
	}
	
	protected function send_bind_pkt($resource) {
		$this->send($this->get_bind_pkt($resource));
	}
	
	protected function send_session_pkt() {
		$this->send($this->get_session_pkt());
	}
	
	//
	// fsm States
	// 
	
	public function setup($event, $args) {
		switch($event) {
			case "connect":
				echo "got $event\n";
				$host = isset($args[0]) ? $args[0] : null;
				$port = isset($args[1]) ? $args[1] : null;
				
				if(($this->connected = $this->sock->connect($host, $port))) {
					return "connected";
				}
				else {
					return "disconnected";
				}
			default:
				echo "not catched $event\n";
				print_r($args);
				break;
		}
	}
	
	public function connected($event, $args) {
		switch($event) {
			case "start_stream":
				$this->send_start_stream($this->jid->domain);
				return "wait_for_stream_start";
				break;
			default:
				echo "not catched $event\n";
				break;
		}
	}
	
	public function wait_for_stream_start($event, $args) {
		switch($event) {
			case "start_cb":
				// TODO: save stream id and other meta info
				echo "stream started\n";
				return "wait_for_stream_features";
				break;
			default:
				echo "not catched $event\n";
				break;
		}
	}
	
	public function wait_for_stream_features($event, $args) {
		switch($event) {
			case "stanza_cb":
				$stanza = $args[0];
				
				// get starttls requirements
				$starttls = $stanza->exists('starttls', NS_TLS);
				$required = $starttls ? ($starttls->exists('required') ? true : false) : false;
				
				// get available mechs
				$this->mechs = array();
				$mechs = $stanza->exists('mechanisms', NS_SASL);
				if($mechs) foreach($mechs->childrens as $mech) $this->mechs[$mech->text] = true;
				
				// post auth
				$bind = $stanza->exists('bind', NS_BIND) ? true : false;
				$sess = $stanza->exists('session', NS_SESSION) ? true : false;
				
				if($bind) {
					$resource = md5(time());
					$this->send_bind_pkt($resource);
					return "wait_for_bind_response";
				}
				else if($starttls && $required) {
					$this->send_starttls_pkt();
					return "wait_for_tls_result";
				}
				else if(sizeof($this->mechs) > 0) {
					// TODO: user land callback api
					if(isset($this->mechs[$this->pref_auth_type])) {
						// try preferred auth mechanism
						$this->send_auth_pkt($this->pref_auth_type, $this->jid->to_string(), $this->pass);
					}
					return "wait_for_sasl_response";
				}
				else {
					echo "no catch\n";
				}
				
				break;
			default:
				echo "not catched $event\n";
				break;
		}
	}
	
	public function wait_for_tls_result($event, $args) {
		switch($event) {
			case "stanza_cb":
				$stanza = $args[0];
				
				if($stanza->name == 'proceed' && $stanza->ns == NS_TLS) {
					$ret = stream_socket_enable_crypto($this->sock->fd, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
					if($ret == false) {
						echo "encryption failed ===========>\n";
						$ret = stream_socket_enable_crypto($this->sock->fd, true, STREAM_CRYPTO_METHOD_SSLv3_CLIENT);
						if($ret == false) {
							echo "encryption failed yet again ===========>\n";
						}
					}
					
					$this->xml->reset_parser();
					$this->send_start_stream($this->jid->domain);
					return "wait_for_stream_start";
				}
				else {
					// FIXME: here
				}
				
				break;
			default:
				echo "not catched $event\n";
				break;
		}
	}

	public function wait_for_compression_result($event, $args) {
		switch($event) {
			default:
				echo "not catched $event\n";
				break;
		}
	}
	
	public function wait_for_sasl_response($event, $args) {
		switch($event) {
			case "stanza_cb":
				$stanza = $args[0];
				
				if($stanza->name == 'failure' && $stanza->ns == NS_SASL) {
					$reason = $stanza->childrens[0]->name;
					echo "sasl failed with reason ".$reason."\n";
					$this->send_end_stream();
					return "shutting_down";
				}
				else if($stanza->name == 'challenge' && $stanza->ns == NS_SASL) {
					$challenge = $stanza->text;
					$this->send_challenge_response($challenge);
					return "wait_for_sasl_response";
				}
				else if($stanza->name == 'success' && $stanza->ns == NS_SASL) {
					$this->xml->reset_parser();
					$this->send_start_stream($this->jid->domain);
					return "wait_for_stream_start";
				}
				else {
					echo "got unhandled sasl response\n";
				}
				
				return "wait_for_sasl_response";
				break;
			default:
				echo "not catched $event\n";
				break;
		}
	}
	
	public function wait_for_bind_response($event, $args) {
		switch($event) {
			case "stanza_cb":
				$this->send_session_pkt();
				return "wait_for_session_response";
				break;
			default:
				echo "not catched $event\n";
				break;
		}
	}
	
	public function wait_for_session_response($event, $args) {
		switch($event) {
			case "stanza_cb":
				return "logged_in";
				break;
			default:
				echo "not catched $event\n";
				break;
		}
	}
	
	public function logged_in($event, $args) {
		switch($event) {
			case "stanza_cb":
				echo "got it in logged in\n";
				return "logged_in";
				break;
			case "end_cb":
				$this->send_end_stream();
				return "shutting_down";
				break;
			default:
				echo "not catched $event\n";
				break;
		}
	}
	
	public function shutting_down($event, $args) {
		switch($event) {
			case "end_cb":
				$this->sock->disconnect();
				return "disconnected";
				break;
			default:
				echo "not catched $event\n";
				break;
		}
	}
	
	public function disconnected($event, $args) {
		switch($event) {
			default:
				echo "not catched $event\n";
				break;
		}
	}
	
}

?>