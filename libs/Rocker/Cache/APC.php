<?php
namespace Rocker\Cache;


/**
 * Cache class using APC
 *
 * @package Rocker\Cache
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license GPL2 (http://www.gnu.org/licenses/gpl-2.0.html)
 */
class APC implements CacheInterface {

    /**
     * @var string
     */
    private $prefix;

    /**
     * @param string $prefix
     */
    public function __construct($prefix = '') {
        $this->prefix = $prefix;
    }

    /**
     * @inheritDoc
     */
    public function fetch($id)
    {
        return apc_fetch($this->prefix.$id);
    }

    /**
     * @inheritDoc
     */
    public function store($id, $data, $ttl = 0)
    {
        return apc_store($this->prefix.$id, $data, $ttl);
    }

    /**
     * @inheritDoc
     */
    public function delete($id)
    {
        return apc_delete($this->prefix.$id);
    }

    /**
     * @inheritDoc
     */
    public function clear() {
        apc_clear_cache('user');
    }
}