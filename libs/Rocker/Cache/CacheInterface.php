<?php
namespace Rocker\Cache;

/**
 * Interface that is implemented by classes that serves as a cache pool
 *
 * @package Rocker\Cache
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license GPL2 (http://www.gnu.org/licenses/gpl-2.0.html)
 */
interface CacheInterface {

    /**
     * Load the object
     * @param string $id
     */
    public function fetch($id);

    /**
     * Save new data for object with this id
     * @param string $id
     * @param mixed $data
     * @param int $ttl
     */
    public function store($id, $data, $ttl=0);

    /**
     * Removes the object with given id in this group
     * @param string $id
     */
    public function delete($id);

    /**
     * Removes all saved cache
     * @return mixed
     */
    public function clear();

}