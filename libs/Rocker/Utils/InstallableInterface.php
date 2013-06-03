<?php
namespace Rocker\Utils;


/**
 * Interface for classes that has an installable process that
 * should run once before the class is used
 *
 * @package Rocker\Utils
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
interface InstallableInterface
{
    /**
     * Whether or not the install process has been executed
     * @return bool
     */
    function isInstalled();

    /**
     * Run install process
     */
    function install();
}