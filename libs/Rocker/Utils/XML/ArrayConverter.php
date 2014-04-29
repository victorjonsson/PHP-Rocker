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
            $parentElement->appendChild($element);
        } else {
            try {
                $element = new \DOMElement($key);
                $parentElement->appendChild($element);
            } catch(\DOMException $e) {
                $element = new \DOMElement('node');
                $parentElement->appendChild($element);
                $element->appendChild(new \DOMAttr('attr', $key));
            }
        }

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
     * @param \DOMDocument|string $xml
     * @return string JSON formatted
     */
    public function convertXMLToJSON($xml)
    {
        return json_encode(self::convertXMLToArray($xml));
    }

    /**
     * @param \DOMDocument|string $xml $xml
     * @return array
     */
    public function convertXMLToArray($xml)
    {
        $result = array();

        if ($xml->hasAttributes()) {
            $attrs = $xml->attributes;
            foreach ($attrs as $attr) {
                $result['@attributes'][$attr->name] = $attr->value;
            }
        }

        if ($xml->hasChildNodes()) {
            $children = $xml->childNodes;
            if ($children->length == 1) {
                $child = $children->item(0);
                if ($child->nodeType == XML_TEXT_NODE || $child->nodeType == XML_CDATA_SECTION_NODE) {
                    $result['_value'] = $child->nodeValue;
                    return count($result) == 1
                        ? $result['_value']
                        : $result;
                }
            }
            $groups = array();
            foreach ($children as $child) {
                if (!isset($result[$child->nodeName])) {
                    $result[$child->nodeName] = $this->convertXMLToArray($child);
                } else {
                    if (!isset($groups[$child->nodeName])) {
                        $result[$child->nodeName] = array($result[$child->nodeName]);
                        $groups[$child->nodeName] = 1;
                    }
                    $result[$child->nodeName][] = $this->convertXMLToArray($child);
                }

                if( empty($result[$child->nodeName]) ) {
                    unset($result[$child->nodeName]);
                } elseif( is_array($result[$child->nodeName]) ) {
                    $values = array();
                    foreach($result[$child->nodeName] as $key => $val) {
                        if( !empty($result[$child->nodeName][$key]) ) {
                            $values[] = $result[$child->nodeName][$key];
                            if( count($values) > 1 )
                                break;
                        }
                    }
                    if( empty($values) ) {
                        unset($result[$child->nodeName]);
                    }
                }
            }
        }

        return $result;
    }

}