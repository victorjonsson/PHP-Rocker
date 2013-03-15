<?php
namespace Rocker\Cache;


/**
 * Cache class that uses a PHP variable as memory
 *
 * @package Rocker\Cache
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class TempMemoryCache implements CacheInterface {

    /**
     * @var array
     */
    private $memory = array();

    /**
     * @inheritDoc
     */
    public function fetch($id)
    {
        return isset($this->memory[$id]) &&
            ($this->memory[$id]['ttl'] == 0 ||
                $this->memory[$id]['ttl'] > time()) ? $this->memory[$id]['content'] : null;
    }

    /**
     * @inheritDoc
     */
    public function store($id, $data, $ttl = 0)
    {
        $this->memory[$id] = array(
            'content' => $data,
            'ttl' => $ttl ? time()+$ttl : 0
        );
    }

    /**
     * @inheritDoc
     */
    public function delete($id)
    {
        if( isset($this->memory[$id]) )
            unset($this->memory[$id]);
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        $this->memory = array();
    }
}