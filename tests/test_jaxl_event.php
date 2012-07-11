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

// TODO: support for php unit and add more tests
error_reporting(E_ALL);
require_once "jaxl.php";

/**
 * 
 * @author abhinavsingh
 *
 */
class JAXLEventTest extends PHPUnit_Framework_TestCase {
	
	function test_jaxl_event() {
		$ev = new JAXLEvent();
	
		$ref1 = $ev->add('on_connect', 'some_func', 0);
		$ref2 = $ev->add('on_connect', 'some_func1', 0);
		$ref3 = $ev->add('on_connect', 'some_func2', 1);
		$ref4 = $ev->add('on_connect', 'some_func3', 4);
		$ref5 = $ev->add('on_disconnect', 'some_func', 1);
		$ref6 = $ev->add('on_disconnect', 'some_func1', 1);
	
		//$ev->emit('on_connect', null);
	
		$ev->del($ref2);
		$ev->del($ref1);
		$ev->del($ref6);
		$ev->del($ref5);
		$ev->del($ref4);
		$ev->del($ref3);
	
		//print_r($ev->reg);
	}
	
}