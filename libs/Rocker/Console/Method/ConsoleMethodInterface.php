<?php
namespace Rocker\Console\Method;


/**
 * Interface for console methods
 *
 * @package Rocker\Console\Method
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license GPL2 (http://www.gnu.org/licenses/gpl-2.0.html)
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