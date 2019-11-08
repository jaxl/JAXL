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

class HTTPServer
{
    // Carriage return and line feed.
    const HTTP_CRLF = "\r\n";

    // 1xx informational
    const HTTP_100 = 'Continue';
    const HTTP_101 = 'Switching Protocols';
    
    // 2xx success
    const HTTP_200 = 'OK';
    
    // 3xx redirection
    const HTTP_301 = 'Moved Permanently';
    const HTTP_304 = 'Not Modified';
    
    // 4xx client error
    const HTTP_400 = 'Bad Request';
    const HTTP_403 = 'Forbidden';
    const HTTP_404 = 'Not Found';
    const HTTP_405 = 'Method Not Allowed';
    const HTTP_499 = 'Client Closed Request'; // Nginx
    
    // 5xx server error
    const HTTP_500 = 'Internal Server Error';
    const HTTP_503 = 'Service Unavailable';

    /** @var JAXLSocketServer */
    private $server = null;
    /** @var callable */
    public $cb = null;

    private $dispatcher = null;
    private $requests = array();

    public function __construct($port = 9699, $address = "127.0.0.1")
    {
        $path = 'tcp://'.$address.':'.$port;

        $this->server = new JAXLSocketServer(
            $path,
            array(&$this, 'on_accept'),
            array(&$this, 'on_request')
        );

        $this->dispatcher = new HTTPDispatcher();
    }

    public function __destruct()
    {
        $this->server = null;
    }

    public function dispatch($rules)
    {
        foreach ($rules as $rule) {
            $this->dispatcher->add_rule($rule);
        }
    }

    /**
     * @param callable $cb
     */
    public function start($cb = null)
    {
        $this->cb = $cb;
        JAXLLoop::run();
    }

    public function on_accept($sock, $addr)
    {
        JAXLLogger::debug("on_accept for client#$sock, addr:$addr");

        // initialize new request obj
        $request = new HTTPRequest($sock, $addr);

        // setup sock cb
        $request->set_sock_cb(
            array(&$this->server, 'send'),
            array(&$this->server, 'read'),
            array(&$this->server, 'close')
        );

        // cache request object
        $this->requests[$sock] = &$request;

        // reactive client for further read
        $this->server->read($sock);
    }

    public function on_request($sock, $raw)
    {
        JAXLLogger::debug("on_request for client#$sock");
        $request = $this->requests[$sock];

        // 'wait_for_body' state is reached when ever
        // application calls recv_body() method
        // on received $request object
        if ($request->state() == 'wait_for_body') {
            $request->body($raw);
        } else {
            // break on crlf
            $lines = explode(self::HTTP_CRLF, $raw);

            // parse request line
            if ($request->state() == 'wait_for_request_line') {
                list($method, $resource, $version) = explode(" ", $lines[0]);
                $request->line($method, $resource, $version);
                unset($lines[0]);
                JAXLLogger::info($request->ip." ".$request->method." ".$request->resource." ".$request->version);
            }

            // parse headers
            foreach ($lines as $line) {
                $line_parts = explode(":", $line);

                if (count($line_parts) > 1) {
                    if (strlen($line_parts[0]) > 0) {
                        $k = $line_parts[0];
                        unset($line_parts[0]);
                        $v = implode(":", $line_parts);
                        $request->set_header($k, $v);
                    }
                } elseif (strlen(trim($line_parts[0])) == 0) {
                    $request->empty_line();
                } else {
                    // if exploded line array size is 1
                    // and there is something in $line_parts[0]
                    // must be request body

                    $request->body($line);
                }
            }
        }

        // if request has reached 'headers_received' state?
        if ($request->state() == 'headers_received') {
            // dispatch to any matching rule found
            JAXLLogger::debug("delegating to dispatcher for further routing");
            $dispatched = $this->dispatcher->dispatch($request);

            // if no dispatch rule matched call generic callback
            if (!$dispatched && $this->cb) {
                JAXLLogger::debug("no dispatch rule matched, sending to generic callback");
                call_user_func($this->cb, $request);
            } elseif (!$dispatched) {
                // elseif not dispatched and not generic callbacked
                // send 404 not_found

                // TODO: send 404 if no callback is registered for this request
                JAXLLogger::debug("dropping request since no matching dispatch rule or generic callback was specified");
                $request->not_found('404 Not Found');
            }
        } else {
            // if state is not 'headers_received'
            // reactivate client socket for read event

            $this->server->read($sock);
        }
    }
}
