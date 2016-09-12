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

// TODO: an abstract JAXLCtlCommand class
// with seperate class per command
// a mechanism to register new commands
class JAXLCtl
{

    protected $ipc = null;

    protected $buffer = '';
    protected $buffer_cb = null;
    protected $cli = null;
    public $dots = "....... ";

    protected $symbols = array();

    public function __construct($command, $params)
    {
        global $exe;

        if (method_exists($this, $command)) {
            $r = call_user_func_array(array(&$this, $command), $params);
            if (count($r) == 2) {
                list($buffer_cb, $quit_cb) = $r;
                $this->buffer_cb = $buffer_cb;
                $this->cli = new JAXLCli(array(&$this, 'onTerminalInput'), $quit_cb);
                $this->run();
            } else {
                JAXLLogger::cliLog("oops! internal command error", JAXLLogger::ERROR);
                exit;
            }
        } else {
            JAXLLogger::cliLog("error: invalid command '$command' received", JAXLLogger::ERROR);
            JAXLLogger::cliLog("type '$exe help' for list of available commands", JAXLLogger::NOTICE);
            exit;
        }
    }

    public function run()
    {
        JAXLCli::prompt();
        JAXLLoop::run();
    }

    public function onTerminalInput($raw)
    {
        $raw = trim($raw);
        $last = substr($raw, -1, 1);

        if ($last == ";") {
            // dispatch to buffer callback
            call_user_func($this->buffer_cb, $this->buffer.$raw);
            $this->buffer = '';
        } elseif ($last == '\\') {
            $this->buffer .= substr($raw, 0, -1);
            echo $this->dots;
        } else {
            // buffer command
            $this->buffer .= $raw."; ";
            echo $this->dots;
        }
    }

    public static function printHelp()
    {
        global $exe;
        JAXLLogger::cliLog("Usage: $exe command [options...]".PHP_EOL, JAXLLogger::INFO);
        JAXLLogger::cliLog("Commands:", JAXLLogger::NOTICE);
        JAXLLogger::cliLog("    help      This help text", JAXLLogger::DEBUG);
        JAXLLogger::cliLog("    debug     Attach a debug console to a running JAXL daemon", JAXLLogger::DEBUG);
        JAXLLogger::cliLog("    shell     Open up Jaxl shell emulator", JAXLLogger::DEBUG);
        echo PHP_EOL;
    }

    protected function help()
    {
        JAXLCtl::printHelp();
        exit;
    }

    //
    // shell command
    //

    protected function shell()
    {
        return array(array(&$this, 'onShellInput'), array(&$this, 'onShellQuit'));
    }

    private function eval_($raw, $symbols)
    {
        extract($symbols);

        eval($raw);
        $g = get_defined_vars();

        unset($g['raw']);
        unset($g['symbols']);
        return $g;
    }

    public function onShellInput($raw)
    {
        $this->symbols = $this->eval_($raw, $this->symbols);
        JAXLCli::prompt();
    }

    public function onShellQuit()
    {
        exit;
    }

    //
    // debug command
    //

    protected function debug($sock_path)
    {
        $this->ipc = new JAXLSocketClient();
        $this->ipc->set_callback(array(&$this, 'onDebugResponse'));
        $this->ipc->connect('unix://'.$sock_path);
        return array(array(&$this, 'onDebugInput'), array(&$this, 'onDebugQuit'));
    }

    public function onDebugResponse($raw)
    {
        $ret = unserialize($raw);
        print_r($ret);
        echo PHP_EOL;
        JAXLCli::prompt();
    }

    public function onDebugInput($raw)
    {
        $this->ipc->send($this->buffer.$raw);
    }

    public function onDebugQuit()
    {
        $this->ipc->disconnect();
        exit;
    }
}
