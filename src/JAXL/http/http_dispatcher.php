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

class HTTPDispatcher
{

    protected $rules = array();

    public function __construct()
    {
        $this->rules = array();
    }

    public function add_rule($rule)
    {
        $s = count($rule);
        if ($s > 4) {
            JAXLLogger::debug("invalid rule");
            return;
        }

        // fill up defaults
        if ($s == 3) {
            $rule[] = array();
        } elseif ($s == 2) {
            $rule[] = array('GET');
            $rule[] = array();
        } else {
            JAXLLogger::debug("invalid rule");
            return;
        }

        $this->rules[] = new HTTPDispatchRule($rule[0], $rule[1], $rule[2], $rule[3]);
    }

    public function dispatch($request)
    {
        foreach ($this->rules as $rule) {
            //JAXLLogger::debug("matching $request->path with pattern $rule->pattern");
            if (($matches = $rule->match($request->path, $request->method)) !== false) {
                JAXLLogger::debug("matching rule found, dispatching");
                $params = array($request);
                // TODO: a bad way to restrict on 'pk', fix me for generalization
                if (isset($matches['pk'])) {
                    $params[] = $matches['pk'];
                }
                call_user_func_array($rule->cb, $params);
                return true;
            }
        }
        return false;
    }
}
