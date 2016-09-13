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
 * TODO: convert into a finite state machine
 *
 * @author abhinavsingh
 *
 */
class HTTPClient
{

    /** @var string */
    private $url = null;
    private $parts = array();

    private $headers = array();
    private $data = null;
    public $method = null;

    /** @var JAXLSocketClient */
    private $client = null;

    /**
     * @param string $url
     * @param array $headers TODO: Currently not used.
     * @param unknown $data TODO: Currently not used.
     */
    public function __construct($url, array $headers = array(), $data = null)
    {
        $this->url = $url;
        $this->headers = $headers;
        $this->data = $data;

        $this->client = new JAXLSocketClient();
        $this->client->set_callback(array(&$this, 'on_response'));
    }

    public function start($method = 'GET')
    {
        $this->method = $method;

        $this->parts = parse_url($this->url);
        $transport = $this->transport();
        $ip = $this->ip();
        $port = $this->port();

        $socket_path = $transport.'://'.$ip.':'.$port;
        if ($this->client->connect($socket_path)) {
            JAXLLogger::debug("connection to $this->url established");

            // send request data
            $this->send_request();

            // start main loop
            JAXLLoop::run();
        } else {
            JAXLLogger::debug("unable to open $this->url");
        }
    }

    public function on_response($raw)
    {
        JAXLLogger::info("got http response");
    }

    protected function send_request()
    {
        $this->client->send($this->line().HTTPServer::HTTP_CRLF);
        $this->client->send($this->ua().HTTPServer::HTTP_CRLF);
        $this->client->send($this->host().HTTPServer::HTTP_CRLF);
        $this->client->send(HTTPServer::HTTP_CRLF);
    }

    //
    // private methods on uri parts
    //

    private function line()
    {
        return $this->method.' '.$this->uri().' HTTP/1.1';
    }

    private function ua()
    {
        return 'User-Agent: jaxl_http_client/3.x';
    }

    private function host()
    {
        return 'Host: '.$this->parts['host'].':'.$this->port();
    }

    private function transport()
    {
        return ($this->parts['scheme'] == 'http' ? 'tcp' : 'ssl');
    }

    private function ip()
    {
        return gethostbyname($this->parts['host']);
    }

    private function port()
    {
        return isset($this->parts['port']) ? $this->parts['port'] : 80;
    }

    private function uri()
    {
        $uri = $this->parts['path'];
        if (isset($this->parts['query'])) {
            $uri .= '?'.$this->parts['query'];
        }
        if (isset($this->parts['fragment'])) {
            $uri .= '#'.$this->parts['fragment'];
        }
        return $uri;
    }
}
