<?php
namespace Rocker\Utils\XML;


/**
 * Class that can take an array or traversable object
 * and turn it into a DOMDocument, and vice versa
 *
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class ArrayConverter implements ArrayConverterInterface {

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
        if( is_numeric($key) ) {
            $element = new \DOMElement('node');
        } else {
            $element = new \DOMElement($key);
        }
        $parentElement->appendChild($element);

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

    /**
     * @param \DOMDocument $xml
     * @return string JSON formatted
     */
    public function convertXMLToJSON($xml)
    {
        return json_encode((array)simplexml_load_string($xml->saveXML()));
    }
}