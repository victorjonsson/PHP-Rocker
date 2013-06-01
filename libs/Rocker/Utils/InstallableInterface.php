<?php
namespace Rocker\Utils;


/**
 * Interface for classes that has an installable process that
 * should run once before the class is used
 * @package Rocker\Utils
 */
interface InstallableInterface
{
    /**
     * Whether or not the install process has ben executed
     * @return bool
     */
    function isInstalled();

    /**
     * Run install process
     */
    function install();
}