<?php
namespace Rocker\Console\Method;


/**
 * Interface for console methods
 *
 * @package Rocker\Console\Method
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
interface ConsoleMethodInterface 
{
    /**
     * @param array $args
     * @param array $flags
     * @return mixed
     */
    public function call($args, $flags);

    /**
     * @return mixed
     */
    public function help();

}