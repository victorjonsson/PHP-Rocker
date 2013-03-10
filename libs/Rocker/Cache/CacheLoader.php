<?php
namespace Rocker\Cache;


/**
 * Class used to load singleton instances of the cache class
 * defined in the main configuration file (config.php)
 *
 * @package Rocker\Cache
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license GPL2 (http://www.gnu.org/licenses/gpl-2.0.html)
 */
class CacheLoader {

    /**
     * @var \Rocker\Cache\CacheInterface
     */
    private static $instance = null;

    /**
     * @param array $config
     * @return \Rocker\Cache\CacheInterface
     */
    public static function instance($config)
    {
        if( self::$instance === null ) {
            self::$instance = new $config['class']($config['prefix']);
        }
        return self::$instance;
    }

}