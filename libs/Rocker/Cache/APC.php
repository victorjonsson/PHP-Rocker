<?php
namespace Rocker\Cache;


use Fridge\DBAL\Exception\Exception;
use Rocker\Utils\ErrorHandler;

/**
 * Cache class using APC
 * (Be aware of "Potential cache slam averted for key")
 *
 *
 * @package PHP-Rocker
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class APC implements CacheInterface {

    /**
     * @var string
     */
    private $prefix;

    /**
     * @param string $prefix
     */
    public function __construct($prefix = '')
    {
        $this->prefix = $prefix;
    }

    /**
     * @inheritDoc
     */
    public function fetch($id)
    {
        try {
            return apc_fetch($this->prefix.$id);
        } catch(Exception $e) {
            ErrorHandler::log($e);
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function store($id, $data, $ttl = 0)
    {
        try {
            return apc_store($this->prefix.$id, $data, $ttl);
        } catch(\Exception $e) {
            ErrorHandler::log($e);
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function delete($id)
    {
        try {
            return apc_delete($this->prefix.$id);
        } catch(\Exception $e) {
            ErrorHandler::log($e);
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        apc_clear_cache('user');
    }
}