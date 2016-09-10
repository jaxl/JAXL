<?php

class JAXLXmlTest extends PHPUnit_Framework_TestCase
{

    public static $NS = 'SOME_NAMESPACE';
    public static $attrs = array('attr1' => 'value1');

    /**
     * @runInSeparateProcess-
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testJAXLXml_0()
    {
        $xml = new JAXLXml();
        $this->assertEquals('<></>', $xml->to_string());
    }

    public function testJAXLXml_1_1()
    {
        $xml = new JAXLXml('');
        $this->assertEquals('<></>', $xml->to_string());
    }

    public function testJAXLXml_1_2()
    {
        $xml = new JAXLXml('html');
        $this->assertEquals('<html></html>', $xml->to_string());
    }

    public function testJAXLXml_2_1()
    {
        $xml = new JAXLXml('html', self::$NS);
        $this->assertEquals(
            '<html xmlns="SOME_NAMESPACE"></html>',
            $xml->to_string()
        );
    }

    public function testJAXLXml_2_2()
    {
        $xml = new JAXLXml('html', self::$attrs);
        $this->assertEquals(
            '<html attr1="value1"></html>',
            $xml->to_string()
        );
    }

    public function testJAXLXml_3_1()
    {
        $xml = new JAXLXml('html', self::$attrs, 'Some text');
        $this->assertEquals(
            '<html attr1="value1">Some text</html>',
            $xml->to_string()
        );
    }

    public function testJAXLXml_3_2()
    {
        $xml = new JAXLXml('html', self::$NS, 'Some text');
        $this->assertEquals(
            '<html xmlns="SOME_NAMESPACE">Some text</html>',
            $xml->to_string()
        );
    }

    public function testJAXLXml_3_3()
    {
        $xml = new JAXLXml('html', self::$NS, self::$attrs);
        $this->assertEquals(
            '<html xmlns="SOME_NAMESPACE" attr1="value1"></html>',
            $xml->to_string()
        );
    }

    public function testJAXLXml_4()
    {
        $xml = new JAXLXml('html', self::$NS, self::$attrs, 'Some text');
        $this->assertEquals(
            '<html xmlns="SOME_NAMESPACE" attr1="value1">Some text</html>',
            $xml->to_string()
        );
    }
}
