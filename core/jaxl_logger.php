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

// generic global logging shortcuts for different level of verbosity
function _error($msg)
{
    JAXLLogger::log($msg, JAXLLogger::ERROR);
}
function _warning($msg)
{
    JAXLLogger::log($msg, JAXLLogger::WARNING);
}
function _notice($msg)
{
    JAXLLogger::log($msg, JAXLLogger::NOTICE);
}
function _info($msg)
{
    JAXLLogger::log($msg, JAXLLogger::INFO);
}
function _debug($msg)
{
    JAXLLogger::log($msg, JAXLLogger::DEBUG);
}

// generic global terminal output colorize method
// finally sends colorized message to terminal using error_log/1
// this method is mainly to escape $msg from file:line and time
// prefix done by _debug, _error, ... methods
function _colorize($msg, $verbosity)
{
    error_log(JAXLLogger::colorize($msg, $verbosity));
}

class JAXLLogger
{

    // Log levels.
    const ERROR = 1;
    const WARNING = 2;
    const NOTICE = 3;
    const INFO = 4;
    const DEBUG = 5;

    public static $colorize = true;
    public static $level = self::DEBUG;
    public static $path = null;
    public static $max_log_size = 1000;

    protected static $colors = array(
        self::ERROR => 31,  // red
        self::WARNING => 34,  // blue
        self::NOTICE => 33,  // yellow
        self::INFO => 32,  // green
        self::DEBUG => 37  // white
    );

    public static function log($msg, $verbosity = self::ERROR)
    {
        if ($verbosity <= self::$level) {
            $bt = debug_backtrace();
            array_shift($bt);
            $callee = array_shift($bt);
            $msg = basename($callee['file'], '.php').":".$callee['line']." - ".@date('Y-m-d H:i:s')." - ".$msg;
            
            $size = strlen($msg);
            if ($size > self::$max_log_size) {
                $msg = substr($msg, 0, self::$max_log_size) . ' ...';
            }
            
            if (isset(self::$path)) {
                error_log($msg . PHP_EOL, 3, self::$path);
            } else {
                error_log(self::colorize($msg, $verbosity));
            }
        }
    }

    /**
     * @param string $msg
     * @param int $verbosity
     * @return string
     */
    public static function colorize($msg, $verbosity)
    {
        if (self::$colorize) {
            return "\033[".self::$colors[$verbosity]."m".$msg."\033[0m";
        } else {
            return $msg;
        }
    }

    /**
     * @param array $colors
     */
    public static function setColors(array $colors)
    {
        foreach ($colors as $k => $v) {
            self::$colors[$k] = $v;
        }
    }
}
