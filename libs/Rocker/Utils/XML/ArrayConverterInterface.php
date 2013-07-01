<?php
namespace Rocker\Utils\XML;


/**
 * Interface for a class that can take an traversable object
 * and turn it into a DOMDocument
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

}