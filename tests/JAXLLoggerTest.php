<?php

class JAXLLoggerTest extends PHPUnit_Framework_TestCase
{

    /**
     * @runInSeparateProcess<--fixme
     */
    public function testColorize()
    {
        $msg = 'Test message';

        // TODO: Fix JAXL to run with @runInSeparateProcess and remove following line.
        $current = JAXLLogger::$colorize;
        
        JAXLLogger::$colorize = false;
        $this->assertEquals($msg, JAXLLogger::colorize($msg, JAXLLogger::ERROR));

        JAXLLogger::$colorize = true;
        $this->assertNotEquals($msg, JAXLLogger::colorize($msg, JAXLLogger::ERROR));

        $color = 123;
        JAXLLogger::setColors(array(
            JAXLLogger::ERROR => $color
        ));
        $this->assertEquals("\033[" . $color . "m" . $msg . "\033[0m", JAXLLogger::colorize($msg, JAXLLogger::ERROR));

        // TODO: Fix JAXL to run with @runInSeparateProcess and remove following line.
        JAXLLogger::$colorize = $current;
    }

    /**
     * @requires PHP 5.4
     * @requires function uopz_backup
     */
    public function testLog()
    {
        $msg = 'Test message';
        uopz_backup('error_log');
        JAXLLogger::log($msg);
    }
}
