<?php
namespace Rocker\Object;


/**
 * Object representing a search result
 *
 * @package Rocker\Object
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license GPL2 (http://www.gnu.org/licenses/gpl-2.0.html)
 */
class SearchResult implements \Iterator, \ArrayAccess
{

    private $query = array();
    private $position = 0;
    private $numMatching;
    private $offset;
    private $limit;
    private $objects;

    public function __construct($offest, $limit)
    {
        $this->offset = $offest;
        $this->limit = $limit;
    }

    public function setQuery($query)
    {
        $this->query = $query;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function setNumMatching($numMatching)
    {
        $this->numMatching = $numMatching;
    }

    public function getNumMatching()
    {
        return $this->numMatching;
    }

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

    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    function rewind()
    {
        $this->position = 0;
    }

    function current()
    {
        return $this->objects[$this->position];
    }

    function key()
    {
        return $this->position;
    }

    function next()
    {
        ++$this->position;
    }

    function valid()
    {
        return isset($this->objects[$this->position]);
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->objects[] = $value;
        } else {
            $this->objects[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->objects[$offset]);
    }

    public function offsetUnset($offset)
    {
        if( isset($this->objects[$offset]) ) {
            unset($this->objects[$offset]);
        }
    }

    public function offsetGet($offset)
    {
        return isset($this->objects[$offset]) ? $this->objects[$offset] : null;
    }
}