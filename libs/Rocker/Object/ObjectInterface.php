<?php
namespace Rocker\Object;


/**
 * Interface for a generic object
 *
 * @package Rocker\Object
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license GPL2 (http://www.gnu.org/licenses/gpl-2.0.html)
 */
interface ObjectInterface extends MetaInterface {

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     */
    public function setName($name);

    /**
     * @return array mixed
     */
    public function changedName();

    /**
     * @return array
     */
    public function toArray();
}