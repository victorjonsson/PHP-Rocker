<?php
namespace Rocker\Object;

use Fridge\DBAL\ConnectionFactory;


/**
 * Class used to load a singleton instance of the database class
 *
 * @package Rocker\Object
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license GPL2 (http://www.gnu.org/licenses/gpl-2.0.html)
 */
class DB {

    private static $instance = null;

    /**
     * @param array $config
     * @return \Fridge\DBAL\Connection\ConnectionInterface
     */
    public static function instance(array $config) {
        if( self::$instance === null ) {
            self::$instance = ConnectionFactory::create($config);
        }

        return self::$instance;
    }
}