<?php
namespace Rocker\Object;

/**
 * Interface for factory that handles CRUD operations on objects
 *
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
interface FactoryInterface {

    /**
     * @param string|int $input Either id number or name
     * @return ObjectInterface
     */
    public function load($input);

    /**
     * @param $name
     * @return ObjectInterface
     */
    public function create($name);

    /**
     * @param ObjectInterface $obj
     * @return void
     */
    public function delete($obj);

    /**
     * @param ObjectInterface $obj
     * @return void
     */
    public function update($obj);

    /**
     * @param array $where
     * @param int $offset
     * @param int $limit
     * @param string $idOrder
     * @throws \InvalidArgumentException
     * @return \Rocker\Object\ObjectInterface[]|\Rocker\Object\SearchResult
     */
    public function metaSearch(array $where, $offset=0, $limit=50, $idOrder='DESC');

    /**
     * Preforms a full text search on object name
     * @param null $search
     * @param int $offset
     * @param int $limit
     * @param string $sortBy
     * @param string $sortOrder
     * @return  \Rocker\Object\ObjectInterface[]|\Rocker\Object\SearchResult
     */
    public function search($search=null, $offset = 0, $limit = 50, $sortBy='id', $sortOrder='DESC');
}