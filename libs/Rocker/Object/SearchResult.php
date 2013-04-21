<?php
namespace Rocker\Object;


/**
 * Object representing a search result
 *
 * @package PHP-Rocker
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class SearchResult implements \Iterator, \ArrayAccess {

    /**
     * @var array
     */
    private $query = array();

    /**
     * @var int
     */
    private $position = 0;

    /**
     * @var int
     */
    private $numMatching;

    /**
     * @var int
     */
    private $offset;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var int
     */
    private $objects;


    /**
     * @param int $offset
     * @param int $limit
     */
    public function __construct($offset, $limit)
    {
        $this->offset = $offset;
        $this->limit = $limit;
    }

    /**
     * @param array $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * @return array
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param int $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $numMatching
     */
    public function setNumMatching($numMatching)
    {
        $this->numMatching = $numMatching;
    }

    /**
     * @return int
     */
    public function getNumMatching()
    {
        return $this->numMatching;
    }

    /**
     * @param \Rocker\Object\ObjectInterface[]|\stdClass[] $objects
     */
    public function setObjects($objects)
    {
        $this->objects = $objects;
    }

    /**
     * @return \Rocker\Object\ObjectInterface[]|\stdClass[]
     */
    public function getObjects()
    {
        return $this->objects;
    }

    /**
     * @param int $offset
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     */
    function rewind()
    {
        $this->position = 0;
    }

    /**
     * @return mixed
     */
    function current()
    {
        return $this->objects[$this->position];
    }

    /**
     * @return int|mixed
     */
    function key()
    {
        return $this->position;
    }

    /**
     */
    function next()
    {
        ++$this->position;
    }

    /**
     * @return bool
     */
    function valid()
    {
        return isset($this->objects[$this->position]);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->objects[] = $value;
        } else {
            $this->objects[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->objects[$offset]);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        if( isset($this->objects[$offset]) ) {
            unset($this->objects[$offset]);
        }
    }

    /**
     * @param mixed $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return isset($this->objects[$offset]) ? $this->objects[$offset] : null;
    }
}