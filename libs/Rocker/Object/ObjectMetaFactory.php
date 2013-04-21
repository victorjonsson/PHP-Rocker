<?php
namespace Rocker\Object;

use Fridge\DBAL\Connection\ConnectionInterface;
use Rocker\Cache\CacheInterface;


/**
 * Factory class that can store and retrieve meta data belonging to
 * objects implementing the MetaInterface
 *
 * @package Rocker\Object
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class ObjectMetaFactory {

    /**
     * @var ConnectionInterface
     */
    private $db;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var string
     */
    private $cachePrefix;

    /**
     * @var string
     */
    private $dbTable;

    /**
     * @param string $dbTable
     * @param \Fridge\DBAL\Connection\ConnectionInterface $db
     * @param \Rocker\Cache\CacheInterface $cache
     */
    function __construct($dbTable, ConnectionInterface $db, CacheInterface $cache)
    {
        $this->dbTable = $dbTable;
        $this->db = $db;
        $this->cache = $cache;
        $this->cachePrefix = 'meta_' . $dbTable . '_';
    }

    /**
     */
    public function createTable()
    {
        $engine = $this->db->getParameter('engine');
        $collate = $this->db->getParameter('collate');
        $charset = $this->db->getParameter('charset');

        $sql = "
        CREATE TABLE IF NOT EXISTS `" . $this->dbTable . "` (
            `object` int(11) NOT NULL,
            `name` varchar(200) COLLATE $collate NOT NULL DEFAULT '',
            `value` text COLLATE $collate,
            PRIMARY KEY (`object`,`name`)
        ) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collate";

        $this->db->exec($sql);
    }

    /**
     * Remove all of the meta data added to given obj
     * @param \Rocker\Object\MetaInterface $obj
     */
    function removeMetaData(MetaInterface $obj)
    {
        $this->db->prepare("DELETE FROM " . $this->dbTable . " WHERE object=?")
            ->execute(array($obj->getId()));

        $this->cache->delete($this->cachePrefix . $obj->getId());
    }

    /**
     * Save changed meta data
     * @param \Rocker\Object\MetaInterface $obj
     * @throws \Exception
     */
    function saveMetaData(MetaInterface $obj)
    {
        $meta = $obj->meta();
        $id = $obj->getId();

        // Update / insert of values
        foreach ($meta->getUpdatedValues() as $name => $val) {
            $value = is_string($val) || is_int($val) ? trim($val) : serialize($val);
            try {

                $this->db->prepare("INSERT INTO " . $this->dbTable . " (`value`, `object`, `name`) VALUES (?, ?, ?)")
                    ->execute(array($value, $id, $name));

            } catch (\Exception $e) {
                if ( $e->getCode() == 23000 ) {
                    $this->db->prepare("UPDATE " . $this->dbTable . " SET `value`=? WHERE `object`=? AND `name`=?")
                        ->execute(array($value, $id, $name));
                } else {
                    throw $e;
                }
            }
        }

        // Deleted values
        foreach ($meta->getDeletedValues() as $name) {
            $this->db->prepare("DELETE FROM " . $this->dbTable . " WHERE `object`=? AND `name`=?")
                ->execute(array($id, $name));
        }

        // Clear the meta object of data that needs to be updated
        $meta->setDeletedValues(array());
        $meta->setUpdatedValues(array());
        $this->cache->delete($this->cachePrefix . $obj->getId());
    }

    /**
     * Add meta data belonging to given object
     * @param \Rocker\Object\MetaInterface $obj
     */
    function applyMetaData(MetaInterface $obj)
    {
        $meta_values = $this->cache->fetch($this->cachePrefix . $obj->getId());
        if ( !is_array($meta_values) ) {
            $meta_values = array();
            $sql = $this->db->prepare("SELECT name, value FROM " . $this->dbTable . " WHERE object=?");
            $sql->execute(array($obj->getId()));

            while ($row = $sql->fetch()) {
                if( $this->isSerialized($row['value']) )
                    $meta_values[$row['name']] = unserialize($row['value']);
                else
                    $meta_values[$row['name']] = is_numeric($row['value']) ? (int)$row['value']:$row['value'];
            }

            $this->cache->store($this->cachePrefix . $obj->getId(), $meta_values);
        }

        $obj->setMeta(new MetaData($meta_values));
    }

    /**
     * Borrowed partly from wordpress
     * @param string $str
     * @return bool
     */
    private function isSerialized($str)
    {
        if ( is_numeric($str) ) {
            return false;
        }
        $str = trim($str);
        if ( 'N;' == $str ) {
            return true;
        }
        $length = strlen($str);
        if ( $length < 4 ) {
            return false;
        }
        if ( ':' !== $str[1] ) {
            return false;
        }
        $lastc = $str[$length - 1];
        if ( ';' !== $lastc && '}' !== $lastc ) {
            return false;
        }
        $token = $str[0];
        switch ($token) {
            case 's' :
                if ( '"' !== $str[$length - 2] ) {
                    return false;
                }
            case 'a' :
            case 'O' :
                return (bool)preg_match("/^{$token}:[0-9]+:/s", $str);
            case 'b' :
            case 'i' :
            case 'd' :
                return (bool)preg_match("/^{$token}:[0-9.E-]+;\$/", $str);
        }
        return false;
    }

}