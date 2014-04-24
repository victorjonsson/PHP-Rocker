<?php
/**
 * PHP Rocker - Config
 * ---------------------------------
 * Main configuration file for Rocker REST Server
 *
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */


return array(

    /*
     * Database
     * ------------------
     * Configuration parameters for your database. Supported drivers
     * are pdo_mysql, postgrsql, mssql, oracle (see Fridge DBAL)
     */
    'application.db' => array(
        'host' => '',
        'dbname' => '',
        'username' => '',
        'password' => '',
        'prefix' => 'rocker_',
        'collate' => 'utf8_swedish_ci',
        'engine' => 'InnoDB',
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
     * supported by your server. You can of course also change this
     * to a class of your own, as long as it implements Rocker\Cache\CacheInterface
     */
    'application.cache' => array(
        'prefix' => 'rocker_',
        'class' => '\\Rocker\\Cache\\TempMemoryCache'
    ),

    /*
     * Base path
     * --------------
     * Base path of the API (eg. https://webservice.com/api/). This parameter
     * should only contain the path of the API, excluding host and protocol.
     * If the URI of your API should be for example https://api.myservice.com/
     * you set this parameter to '/'
     */
    'application.path' => '/api/',

    /*
     * Default response format
     * ---------------------
     * The default content type of the server response. Can be either 'json' or 'xml'.
     *
     * You can always add the extension .json or .xml to the URI of an API operation
     * to override the default content type.
     *
     * ! Note that the console program can't communicate with Rocker servers responding
     * with an XML content type by default.
     */
    'application.output' => 'json',

    /**
     * Set this parameter to false if the client should'nt be able to determine
     * the content type or the response by adding extensions .xml or .json to the URI.
     */
    'application.allow_output_extensions' => true,

    /*
     * API operations
     * ------------------
     * Here is where you define which operations that should be available
     * through the API. You may remove/add operations as you want as long
     * as you keep "system/version" which is used by the console program.
     * The operation classes must implement the Rocker\REST\OperationInterface
     */
    'application.operations' => array(

        // List all available operations (does not require authentication)
        'operations' => '\\Rocker\\API\\ListOperations',

        // Get current version of PHP-Rocker (does not require authentication)
        'system/version' => '\\Rocker\\API\\Version',

        // Add/remove admin privileges from users
        'admin' => '\\Rocker\\API\\AdminPrivilegeOperation',

        // CRUD operations for users
        'user/*' => '\\Rocker\\API\\UserOperation',

        // Get user data of the authenticated user
        'me' => '\\Rocker\\API\\Me',

        // Clear cache
        'cache/clear' => '\\Rocker\\API\\ClearCache',

        // CRUD operations for files (file storage)
        'file/*' => '\\Rocker\\API\\FileOperation'
    ),

    /*
     * Authentication
     * -----------------
     * $class — The class that manages authentication. You may change
     * this to a class of your own as long as it implements Rocker\REST\AuthenticationInterface
     *
     * $mechanism — The authentication mechanism sent with the www-authentication
     * header. Rocker supports only basic authentication and RC4 encrypted authentication
     * out of the box but it exists additional packages that gives your API support
     * for facebook login and google authentication (more info available in README.md)
     *
     * $secret - Only used in case you're using RC4 encrypted authentication
     */
    'application.auth' => array(
        'class' => '\\Rocker\\REST\\Authenticator',
        'mechanism' => 'Basic realm="some.service.com"',
        'secret' => 'Some hard to guess string'
    ),

    /*
     * File storage
     * -----------------
     * $class - Any class that implements Rocker\Utils\FileStorage\StorageInterface
     *
     * $path - File path to the directory where files will be saved
     *
     * $base - Base URI of file directory
     *
     * $max_size - The maximum allowed size of a single file
     *
     * $img_manipulation_max_size - Image versions will not be generated for images
     * that has a file size exceeding this limit.
     *
     * $img_manipulation_max_dimensions - Image versions will not be generated for
     * images which dimension exceeds this value ([width]x[height])
     *
     * $img_manipulation_quality - Quality of generated image versions
     */
    'application.files' => array(
        'class' => '\\Rocker\\Utils\\FileStorage\\Storage',
        'path' => __DIR__.'/static/',
        'base' => 'http://localhost/PHP-Rocker/static/',
        'max_size' => '20MB',
        'img_manipulation_max_size' => '5MB',
        'img_manipulation_max_dimensions' => '1024x1024',
        'img_manipulation_quality' => 90,
    ),

    /*
     * Console methods
     * -------------------
     * Array with additional console methods. Key being the method name called in the console and
     * value being the method class. The class has to implement \Rocker\Console\Method\MethodInterface
     */
    'application.console' => array(),

    /*
     * System mode (slim parameter)
     * ----------------
     * Either "production" or "development". Having this parameter
     * set to the latter means that the server will output an entire
     * stack trace in case of an error occurring.
     */
    'mode' => 'development',

    /*
     * Application events
     * -------------------
     * With this parameter you can add event listener. Example:
     * array(
     *      array('delete.user'=>'\\MyCompany\\SomeClass::eventListener')
     * )
     */
    'application.events' => array(
        // This action is only needed if the application supports file storage
        array('delete.user' => '\\Rocker\\API\\FileOperation::deleteUserEvent')
    ),

    /*
     * Application filters
     * ----------------------
     * Use this parameter to setup filters applied by the server
     */
    'application.filters' => array(
        // This filter is only needed if the application supports file storage
        array('user.array' => '\\Rocker\\API\\FileOperation::userFilter')
    ),

    /*
     * Application install
     * -----------------------
     * Classes implementing Rocker\Utils\InstallableInterface that should
     * run the install procedure when the application gets installed or updated
     */
    'application.install' => array(
        '\\Rocker\\Object\\User\\UserFactory'
    ),

    /*
     * User objects
     * -----------------------
     * $meta_limit - The maximum number of meta entries allowed per object
     *
     * $meta_max_size - The maximum size of each meta entry
     *
     * $authenticated_search - Whether or not the client must authenticate
     * when searching for users
     *
     * $factory - Factory class for users
     */
    'application.user_object' => array(
        'meta_limit' => 20,
        'meta_max_size' => '1024kb',
        'authenticated_search' => false,
        'factory' => '\\Rocker\\Object\\User\\UserFactory'
    )
);