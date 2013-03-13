<?php
/**
 * PHP Rocker - Config
 * ---------------------------------
 * Main configuration file for Rocker REST Server
 *
 * @package Rocker
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */


return array(

    /*
     * Database
     * ------------------
     * Configuration parameters for your database. Supported drivers
     * are pdo_mysql, postgrsql, mssql (see Fridge DBAL)
     */
    'application.db' => array(
        'host' => 'localhost',
        'dbname' => 'rocker',
        'username' => 'root',
        'password' => 'root',
        'prefix' => 'rocker_',
        'collate' => 'utf8_swedish_ci',
        'engine' => 'innoDb',
        'charset' => 'utf8',
        'driver' => 'pdo_mysql'
    ),

    /*
     * Object caching
     * ---------------------
     * $prefix — Makes it possible to manage several server instances
     * in the same cache pool
     *
     * $class — Which ever cache class you want to use. You can choose
     * between TempMemoryCache, FileCache and APC depending on what's
     * supported by your system. You can of course also change this
     * to a class of your own, as long as it implements Rocker\Cache\CacheInterface
     */
    'application.cache' => array(
        'prefix' => 'rocker_',
        'class' => '\\Rocker\\Cache\\TempMemoryCache'
    ),

    /*
     * Base path of the API
     */
    'application.path' => '/api/',

    /*
     * API operations
     * ------------------
     * Here is where you define which operations that should be available
     * through the API. You may remove/add operations as you want as long
     * as you keep "system/version" and "auth" since those operations is
     * used by the console program. The operation classes must implement
     * the Rocker\REST\OperationInterface
     */
    'application.operations' => array(
        'operations' => '\\Rocker\\API\\ListOperations',
        'system/version' => '\\Rocker\\API\\Version',
        'admin' => '\\Rocker\\API\\AdminPrivilegeOperation',
        'user/*' => '\\Rocker\\API\\UserOperation',
        'auth' => '\\Rocker\\API\\Auth',
        'cache/clear' => '\\Rocker\\API\\ClearCache'
    ),

    /*
     * Authentication
     * -----------------
     * $class — The class that manages authentication. You may change
     * this to a class of your own as long as it implements Rocker\REST\AuthenticationInterface
     *
     * $mechanism — The authentication mechanism sent with the www-authentication
     * header. Rocker supports only basic authentication and RC4 encrypted authentication
     *
     * $secret - Only used in case you're using RC4 encrypted authentication
     */
    'application.auth' => array(
        'class' => '\\Rocker\\REST\\Authenticator',
        'mechanism' => 'Basic realm="some.service.com"',
        'secret' => 'Some hard '
    ),

    /*
     * System mode
     * ----------------
     * Either "production" or "development". Having this parameter
     * set to the latter means that the server will output an entire
     * stack trace in case of an error occurring.
     */
    'mode' => 'development'
);