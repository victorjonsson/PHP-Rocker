<?php
namespace Rocker\Object;


/**
 * Interface for objects that has meta data
 *
 * @package Rocker\Object
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
interface MetaInterface {

    /**
     * The id number of this object
     * @return number
     */
    function getId();

    /**
     * @param \Rocker\Object\MetaData $meta
     * @return void
     */
    function setMeta(MetaData $meta);

    /**
     * @return \Rocker\Object\MetaData
     */
    function getMeta();

    /**
     * Short hand alias of getMeta()
     * @return \Rocker\Object\MetaData
     */
    function meta();

}