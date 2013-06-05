<?php
namespace Rocker\Console\Method;


/**
 * Interface for console methods
 *
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
interface MethodInterface {

    /**
     * Execute the method
     * @param array $args
     * @param array $flags
     * @return void
     */
    public function call($args, $flags);

    /**
     * Outputs info about how to call this method
     * @return void
     */
    public function help();

}