<?php
namespace Rocker\Cache;


/**
 * Cache class that using memory
 *
 * @package Rocker\Cache
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license GPL2 (http://www.gnu.org/licenses/gpl-2.0.html)
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