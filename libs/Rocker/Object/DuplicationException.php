<?php
namespace Rocker\Object;


/**
 * Exception thrown when using arguments that causes data, needed
 * to be unique, to become duplicated
 *
 * @package Rocker\Object
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class DuplicationException extends \InvalidArgumentException { }