<?php
namespace Rocker\Cache;


/**
 * Class used to load a singleton instance of the cache class
 * defined in the main configuration file (config.php)
 *
 * @package PHP-Rocker
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class Cache {

    /**
     * @var \Rocker\Cache\CacheInterface
     */
    private static $instance = null;

    /**
     * @param array $config
     * @return \Rocker\Cache\CacheInterface
     */
    public static function instance($config=array())
    {
        if( self::$instance === null ) {
            self::$instance = new $config['class']($config['prefix']);
        }
        return self::$instance;
    }

}