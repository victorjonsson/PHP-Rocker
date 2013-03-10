<?php
namespace Rocker\Object;


/**
 * Exception thrown when using arguments that causes data, needed
 * to be unique, to become duplicated
 *
 * @package Rocker\Object
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license GPL2 (http://www.gnu.org/licenses/gpl-2.0.html)
 */
class DuplicationException extends \InvalidArgumentException { }