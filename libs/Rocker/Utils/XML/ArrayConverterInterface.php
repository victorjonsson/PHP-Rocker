<?php
namespace Rocker\Utils\XML;


/**
 * Interface for a class that can take an traversable object
 * and turn it into a DOMDocument, and vice versa
 *
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
interface ArrayConverterInterface {

    /**
     * @param array|\Traversable $arr
     * @return \DOMDocument
     */
    public function convert($arr);

    /**
     * @param \DOMDocument $xml
     * @return string JSON formatted
     */
    public function convertXMLToJSON($xml);
}