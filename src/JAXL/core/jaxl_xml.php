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
 *
 * Details: http://abhinavsingh.com/blog/2012/09/jaxlxml-strophe-style-xml-builder-working-with-jaxl-a-networking-library-in-php-part-2/
 * Doc: http://jaxl.readthedocs.org/en/latest/users/xml_objects.html#jaxlxml
 *
 * @author abhinavsingh
 *
 */
class JAXLXml extends JAXLXmlAccess
{

    public $parent = null;
    public $rover = null;
    public $xml = null;

    /**
     * Accepts any of the following constructors:
     *
     * * JAXLXml($name, $ns, $attrs, $text)
     * * JAXLXml($name, $ns, $attrs)
     * * JAXLXml($name, $ns, $text)
     * * JAXLXml($name, $attrs, $text)
     * * JAXLXml($name, $attrs)
     * * JAXLXml($name, $ns)
     * * JAXLXml($name)
     *
     * @param string $name
     * @param string $ns
     * @param array $attrs
     * @param string $text
     */
    public function __construct($name)
    {
        $argv = func_get_args();
        $argc = count($argv);

        $this->name = $name;

        switch ($argc) {
            case 4:
                $this->ns = $argv[1];
                $this->attrs = $argv[2];
                $this->text = $argv[3];
                break;
            case 3:
                if (is_array($argv[1])) {
                    $this->attrs = $argv[1];
                    $this->text = $argv[2];
                } else {
                    $this->ns = $argv[1];
                    if (is_array($argv[2])) {
                        $this->attrs = $argv[2];
                    } else {
                        $this->text = $argv[2];
                    }
                }
                break;
            case 2:
                if (is_array($argv[1])) {
                    $this->attrs = $argv[1];
                } else {
                    $this->ns = $argv[1];
                }
                break;
            default:
                break;
        }

        $this->rover = &$this;
    }

    public function __destruct()
    {
    }

    /**
     * Set or update attrs of element.
     *
     * @param array $attrs
     * @return JAXLXml
     */
    public function attrs(array $attrs)
    {
        $this->rover->attrs = array_merge($this->rover->attrs, $attrs);
        return $this;
    }

    /**
     * Check that element matches by attrs.
     *
     * @param array $attrs
     * @return bool
     */
    public function match_attrs(array $attrs)
    {
        $matches = true;
        foreach ($attrs as $k => $v) {
            if ($this->attrs[$k] !== $v) {
                $matches = false;
                break;
            }
        }
        return $matches;
    }

    /**
     * Set or append XML.
     *
     * @param JAXLXmlAccess $xml
     * @param bool $append
     * @return JAXLXml
     */
    public function x(JAXLXmlAccess $xml, $append = false)
    {
        if (!$append) {
            $this->rover->xml = $xml;
        } else {
            if ($this->rover->xml === null) {
                $this->rover->xml = '';
            }
            $this->rover->xml .= $xml;
        }
        return $this;
    }

    /**
     * Set or append text.
     *
     * @param string $text
     * @param bool $append
     * @return JAXLXml
     */
    public function t($text, $append = false)
    {
        if (!$append) {
            $this->rover->text = $text;
        } else {
            if ($this->rover->text === null) {
                $this->rover->text = '';
            }
            $this->rover->text .= $text;
        }
        return $this;
    }

    /**
     * Create child.
     *
     * @param string $name
     * @param string $ns
     * @param array $attrs
     * @param string $text
     * @return JAXLXml
     */
    public function c($name, $ns = null, array $attrs = array(), $text = null)
    {
        $node = new JAXLXml($name, $ns, $attrs, $text);
        $node->parent = &$this->rover;
        $this->rover->children[] = &$node;
        $this->rover = &$node;
        return $this;
    }

    /**
     * Append child node.
     *
     * @param JAXLXml $node
     * @return JAXLXml
     */
    public function cnode($node)
    {
        $node->parent = &$this->rover;
        $this->rover->children[] = &$node;
        $this->rover = &$node;
        return $this;
    }

    /**
     * @return JAXLXml
     */
    public function up()
    {
        if ($this->rover->parent) {
            $this->rover = &$this->rover->parent;
        }
        return $this;
    }

    /**
     * @return JAXLXml
     */
    public function top()
    {
        $this->rover = &$this;
        return $this;
    }

    /**
     * @param string $name
     * @param string $ns
     * @param array $attrs
     * @return JAXLXml|bool
     */
    public function exists($name, $ns = null, array $attrs = array())
    {
        foreach ($this->children as $child) {
            if ($ns) {
                if ($child->name == $name && $child->ns == $ns && $child->match_attrs($attrs)) {
                    return $child;
                }
            } elseif ($child->name == $name && $child->match_attrs($attrs)) {
                return $child;
            }
        }
        return false;
    }

    /**
     * Update child with name ``$name``.
     *
     * @param string $name
     * @param string $ns
     * @param array $attrs
     * @param string $text
     */
    public function update($name, $ns = null, array $attrs = array(), $text = null)
    {
        foreach ($this->children as $k => $child) {
            if ($child->name == $name) {
                $child->ns = $ns;
                $child->attrs($attrs);
                $child->text = $text;
                $this->children[$k] = $child;
                break;
            }
        }
    }

    /**
     * @param string $parent_ns
     * @return string
     */
    public function to_string($parent_ns = null)
    {
        $xml = '';

        $xml .= '<'.$this->name;
        if ($this->ns && $this->ns != $parent_ns) {
            $xml .= ' xmlns="'.$this->ns.'"';
        }
        foreach ($this->attrs as $k => $v) {
            if (!is_null($v) && $v !== false) {
                $xml .= ' '.$k.'="'.htmlspecialchars($v).'"';
            }
        }
        $xml .= '>';

        foreach ($this->children as $child) {
            $xml .= $child->to_string($this->ns);
        }

        if ($this->xml !== null) {
            $xml .= $this->xml;
        }

        if ($this->text !== null) {
            $xml .= htmlspecialchars($this->text);
        }
        $xml .= '</'.$this->name.'>';
        return $xml;
    }
}
