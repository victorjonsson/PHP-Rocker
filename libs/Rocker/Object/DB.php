<?php
namespace Rocker\Object;

use Fridge\DBAL\ConnectionFactory;


/**
 * Class used to load a singleton instance of the database class
 *
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class DB {

    private static $instance = null;

    /**
     * @param array $config
     * @return \Fridge\DBAL\Connection\ConnectionInterface
     */
    public static function instance(array $config=array()) {
        if( self::$instance === null ) {
            self::$instance = ConnectionFactory::create($config);
        }

        return self::$instance;
    }

    /**
     * @return bool
     */
    public static function isInitiated()
    {
        return self::$instance !== null;
    }
}