<?php
namespace Rocker\Utils\XML;


/**
 * Class that can take an traversable object
 * and turn it into a DOMDocument
 *
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class ArrayConverter {

    /**
     * @var string
     */
    private $version = '1.0';

    /**
     * @var string
     */
    private $charset = 'utf-8';

    /**
     * @var string
     */
    private $rootElementName = 'result';

    /**
     * @param string $rootElementName
     */
    public function setRootElementName($rootElementName)
    {
        $this->rootElementName = $rootElementName;
    }

    /**
     * @return string
     */
    public function getRootElementName()
    {
        return $this->rootElementName;
    }

    /**
     * @param string $charset
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    /**
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param array|\Traversable $arr
     * @return \DOMDocument
     */
    public function convert($arr)
    {
        $doc = new \DOMDocument($this->version, $this->charset);
        $root = new \DOMElement($this->rootElementName);
        $doc->appendChild($root);
        foreach($arr as $key => $val) {
            $this->appendData($key, $val, $root, $doc);
        }
        return $doc;
    }

    /**
     * @param string $key
     * @param string $value
     * @param \DOMElement $parentElement
     * @param \DOMDocument $doc
     */
    private function appendData($key, $value, $parentElement, $doc)
    {
        $element = new \DOMElement('node');
        $parentElement->appendChild($element);
        if( !is_numeric($key) )
            $element->setAttribute('name', $key);

        if( is_array($value) || $value instanceof \Traversable ) {
            foreach($value as $childKey => $childValue) {
                $this->appendData($childKey, $childValue, $element, $doc);
            }
        } else {
            if( is_numeric($value) ) {
                $element->appendChild(new \DOMText($value));
            } else {
                $element->appendChild($doc->createCDATASection($value));
            }
        }
    }
}