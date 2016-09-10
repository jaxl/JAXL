<?php

class JAXLLoggerTest extends PHPUnit_Framework_TestCase
{

    /**
     * @runInSeparateProcess
     */
    public function testColorize()
    {
        $msg = 'Test message';

        JAXLLogger::$colorize = false;
        $this->assertEquals($msg, JAXLLogger::colorize($msg, JAXLLogger::ERROR));

        JAXLLogger::$colorize = true;
        $this->assertNotEquals($msg, JAXLLogger::colorize($msg, JAXLLogger::ERROR));

        $color = 123;
        JAXLLogger::setColors(array(
            JAXLLogger::ERROR => $color
        ));
        $this->assertEquals("\033[" . $color . "m" . $msg . "\033[0m", JAXLLogger::colorize($msg, JAXLLogger::ERROR));
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
