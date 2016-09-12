<?php

/**
 * @method JAXLXml attrs(array $attrs) Set or update attrs of element.
 * @method bool match_attrs(array $attrs) Check that element matches by attrs.
 * @method JAXLXml x(JAXLXmlAccess $xml, $append = false) Set or append XML.
 * @method JAXLXml t($text, $append = false) Set or append text.
 * @method JAXLXml c($name, $ns = null, array $attrs = array(), $text = null) Create child.
 * @method JAXLXml cnode($node) Append child node.
 * @method JAXLXml up()
 * @method JAXLXml top()
 * @method JAXLXml|bool exists($name, $ns = null, array $attrs = array())
 * @method void update($name, $ns = null, array $attrs = array(), $text = null) Update child with name ``$name``.
 * @method string to_string($parent_ns = null)
 */
abstract class JAXLXmlAccess
{

    /** @var string */
    public $name = null;

    /** @var string */
    public $ns = null;

    /** @var array */
    public $attrs = array();

    /** @var string */
    public $text = null;

    /** @var JAXLXml[] */
    public $children = array();
}
