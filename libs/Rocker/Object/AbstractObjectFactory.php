<?php
namespace Rocker\Object;

use Fridge\DBAL\Connection\ConnectionInterface;
use Rocker\Cache\CacheInterface;
use Rocker\Cache\TempMemoryCache;


/**
 * Base class for factory classes that creates objects of any kind
 *
 * @package Rocker\Object
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
abstract class AbstractObjectFactory
{

    /**
     * @var ConnectionInterface
     */
    private $db;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var ObjectMetaFactory
     */
    private $metaFactory;

    /**
     * @var string
     */
    private $cachePrefix;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var
     */
    private $metaTableName;


    /**
     * @param \Fridge\DBAL\Connection\ConnectionInterface $db
     * @param \Rocker\Cache\CacheInterface $cache
     */
    public function __construct(ConnectionInterface $db, CacheInterface $cache = null)
    {
        if ( $cache === null ) {
            $cache = new TempMemoryCache();
        }
        $this->db = $db;
        $this->cache = $cache;
        $this->tableName = $this->db->getParameter('prefix') . $this->objectTypeName();
        $this->metaTableName = $this->db->getParameter('prefix') . 'meta_' . $this->objectTypeName();
        $this->metaFactory = new ObjectMetaFactory($this->metaTableName, $db, $cache);
        $this->cachePrefix = 'object_' . $this->objectTypeName() . '_';
    }

    /**
     * @param string $name
     * @throws DuplicationException
     * @throws \Exception
     * @return \Rocker\Object\ObjectInterface
     */
    protected function create($name)
    {
        /* @var ObjectInterface $obj */
        try {
            $this->db->prepare("INSERT INTO " . $this->tableName . " (`name`) VALUES (?)")
                        ->execute(array(trim($name)));
        } catch (\Exception $e) {
            $this->handlePossibleDuplication($e);
        }
        $objClass = $this->objectClassName();
        $obj = new $objClass($name, $this->db->lastInsertId(), $this->objectTypeName());
        $obj->setMeta(new MetaData(array()));
        return $obj;
    }

    /**
     * @param \Rocker\Object\ObjectInterface $obj
     * @throws \Rocker\Object\DuplicationException
     * @throws \Exception
     */
    function update(ObjectInterface $obj)
    {
        $this->metaFactory->saveMetaData($obj);
        $newName = trim($obj->changedName());
        if ( $newName ) {
            try {
                $this->db->prepare("UPDATE " . $this->tableName . " SET name=? WHERE id=?")
                    ->execute(array($newName, $obj->getId()));

            } catch (\Exception $e) {
                $this->handlePossibleDuplication($e);
            }

            $this->deleteObjectCache($obj);
        }
    }

    /**
     * @param ObjectInterface $obj
     */
    protected function deleteObjectCache(ObjectInterface $obj)
    {
        $this->cache->delete($this->cachePrefix . $obj->getId());
        $this->cache->delete($this->cachePrefix . 'name_' . $obj->getName());
    }

    /**
     * @param \Exception $e
     * @throws \Rocker\Object\DuplicationException
     * @throws \Exception
     */
    private function handlePossibleDuplication($e)
    {
        if ( $e->getCode() == 23000 ) {
            throw new DuplicationException('An object with given name already exists');
        }
        throw $e;
    }

    /**
     * @param \Rocker\Object\ObjectInterface $obj
     */
    function delete(ObjectInterface $obj)
    {
        $this->db->prepare("DELETE FROM " . $this->tableName . " WHERE id=?")
            ->execute(array($obj->getId()));

        $this->metaFactory->removeMetaData($obj);
        $this->deleteObjectCache($obj);
    }

    /**
     * @param array $where
     * @param int $offset
     * @param int $limit
     * @param string $idOrder
     * @throws \InvalidArgumentException
     * @return \Rocker\Object\ObjectInterface[]|\Rocker\Object\SearchResult
     */
    function metaSearch(array $where, $offset=0, $limit=50, $idOrder='DESC')
    {
        if(empty($where))
            throw new \InvalidArgumentException('Empty search is not allowed, use AbstractObjectFactory::search');

        $result = new SearchResult($offset, $limit);
        $sql = 'SELECT object FROM '.$this->metaTableName.' AS t WHERE ';
        $args = array();
        $index = 0;
        foreach($where as $key => $val) {

            $col = $key;
            $key = str_replace(array('>','<', '!'), '', $col); // remove chars that modifies the query

            if( $index == 0 ) {
                if( is_array($val) ) {
                    $midQuery = '';
                    foreach($val as $midVal) {
                        $eq = $this->getEqualOperator($midVal, $col);
                        $args[] = $key;
                        if( $eq == ' > ? ' || $eq == ' < ? ') {
                            $midQuery .= ' (t.name=? AND t.value'.str_replace('?', intval($midVal), $eq).') OR ';
                        } else {
                            $midQuery .= ' (t.name=? AND t.value'.$eq.') OR ';
                            $args[] = str_replace('*', '%', $midVal);
                        }
                    }
                    $sql .= rtrim($midQuery, 'OR ');
                } else {
                    $eq = $this->getEqualOperator($val, $col);
                    $args[] = $key;
                    if( $eq == ' > ? ' || $eq == ' < ? ') {
                        $sql .= " t.name=? AND t.value ".str_replace('?', intval($val), $eq);
                    } else {
                        $sql .= " t.name=? AND t.value$eq ";
                        $args[] = str_replace('*', '%', $val);
                    }

                }
            } else {

                $key = str_replace('!', '', key($val));
                $sql .= (strtoupper($key) == 'AND' ? ' AND ':' OR ').
                        ( substr(key($val), -1) == '!' ? ' NOT ':'').
                        "EXISTS(SELECT $index FROM ".$this->metaTableName." t$index
                            WHERE t$index.object=t.object
                        AND (
        	                t$index.name=?
        	            AND ( ";

                $val = current($val);
                $col = key($val);
                $args[] = str_replace(array('>','<'), '', $col); // remove chars that modifies the query
                $val = current($val);

                if(is_array($val)) {
                    $midQuery = '';
                    foreach($val as $midVal) {
                        $eq = $this->getEqualOperator($midVal, $col);
                        if( $eq == ' > ? ' || $eq == ' < ? ') {
                            $midQuery .= " t$index.value ".str_replace('?', intval($midVal), $eq)." OR ";
                        } else {
                            $midQuery .= " t$index.value $eq OR ";
                            $args[] = str_replace('*', '%', $midVal);
                        }
                    }
                    $sql .= rtrim($midQuery, 'OR ');
                } else {
                    $eq = $this->getEqualOperator($val, $col);
                    if( $eq == ' > ? ' || $eq == ' < ? ') {
                        $sql .= " t$index.value ".str_replace('?', intval($val), $eq);
                    } else {
                        $sql .= " t$index.value$eq ";
                        $args[] = str_replace('*', '%', $val);
                    }
                }

                $sql .= ') ) )';

            }

            $index++;
        }

        $sort = ' ORDER BY t.object  '.($idOrder == 'DESC' ? 'DESC':'ASC').
                ' LIMIT '.intval($offset).','.intval($limit);

        $this->executeSearchQuery($sql, $args, $result, $sort, 'object');
        return $result;
    }

    /**
     * @param $val
     * @param $col
     * @return string
     */
    private function getEqualOperator($val, $col)
    {
        $last = substr($col, -1);
        if( $last == '!' ) {
            return ' != ?';
        }
        elseif( $last == '>' || $last == '<' ) {
            return ' '.$last.' ? ';
        }
        else {
            return strpos($val, '*') === 0 ? ' LIKE ?' : '=?';
        }
    }

    /**
     * Preforms a full text search on object name
     * @param null $search
     * @param int $offset
     * @param int $limit
     * @param string $sortBy
     * @param string $sortOrder
     * @return  \Rocker\Object\ObjectInterface[]|\Rocker\Object\SearchResult
     */
    function search($search=null, $offset = 0, $limit = 50, $sortBy='id', $sortOrder='DESC')
    {
        $result = new SearchResult($offset, $limit);
        $sql = 'SELECT id FROM '.$this->tableName;
        $args = array();
        if( !empty($search) ) {
            $sql .= " WHERE name LIKE ? ";
            $args[] = '%'.$search.'%';
        }

        $sort = ' ORDER BY '.($sortBy == 'id' ? 'id':'name').
                ' '.($sortOrder == 'DESC' ? 'DESC':'ASC').
                ' LIMIT '.intval($offset).','.intval($limit);

        $this->executeSearchQuery($sql, $args, $result, $sort);

        return $result;
    }

    /**
     * @param string $sql
     * @param array $args
     * @param SearchResult $result
     * @param string $orderAndLimitQuery
     * @param string $idColumn
     */
    private function executeSearchQuery($sql, $args, $result, $orderAndLimitQuery, $idColumn='id')
    {
        $numStatement = $this->db->prepare($sql);
        $numStatement->execute($args);
        $result->setNumMatching($numStatement->rowCount());

        $sql .= $orderAndLimitQuery;

        $searchStatement = $this->db->prepare($sql);
        $searchStatement->execute($args);
        $objects = array();
        while( $row = $searchStatement->fetch() ) {
            $objects[] = $this->load($row[$idColumn]);
        }

        $result->setObjects($objects);
    }

    /**
     * @param int|string $id Name or user id
     * @return \Rocker\Object\ObjectInterface
     */
    function load($id)
    {
        $col = is_numeric($id) ? 'id' : 'name';
        $cacheID = $col == 'id' ? $this->cachePrefix . $id : $this->cachePrefix . 'name_' . $id;
        $data = $this->cache->fetch($cacheID);
        if ( empty($data) ) {
            $sql = $this->db->prepare('SELECT name, id FROM ' . $this->tableName . ' WHERE ' . $col . '=?');
            $sql->execute(array($id));
            $data = $sql->fetch();
            if ( isset($data['name']) ) {
                $this->cache->store($cacheID, $data);
            } else {
                return null;
            }
        }

        $objClass = $this->objectClassName();
        $obj = new $objClass($data['name'], $data['id'], $this->objectTypeName());
        $this->metaFactory->applyMetaData($obj);

        return $obj;
    }

    /**
     * Name of the type of objects created by the factory
     * @return string
     */
    abstract function objectTypeName();

    /**
     * The class used when instantiating objects
     * created by the factory
     * @return mixed
     */
    abstract function objectClassName();

    /**
     * Creates needed database tables
     */
    function install()
    {
        $engine = $this->db->getParameter('engine');
        $collate = $this->db->getParameter('collate');
        $charset = $this->db->getParameter('charset');

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS `" . $this->tableName . "` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(150) NOT NULL DEFAULT '',
            PRIMARY KEY (`id`),
            UNIQUE KEY by_name (`name`)
            ) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collate"
        );

        $this->metaFactory->createTable();
    }
}