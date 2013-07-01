<?php
namespace Rocker\Object;


/**
 * This class serves as a container from where you
 * can fetch and store data of any kind
 *
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class MetaData extends \stdClass implements \Countable  {

    /**
     * @var array
     */
    private $meta;

    /**
     * @var array
     */
    private $deleted = array();

    /**
     * @var array
     */
    private $updated = array();

    /**
     * @param array $meta_data
     */
    public function __construct(array $meta_data)
    {
        $this->meta = $meta_data;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get($name, $default=null)
    {
        return isset($this->meta[$name]) ? $this->meta[$name] : $default;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->meta[$name]);
    }

    /**
     * @param string $name
     * @param mixed $val
     */
    public function set($name, $val)
    {
        if( $this->get($name) !== $val ) {
            if( $val === null ) {
                unset($this->meta[$name]);
                $this->deleted[] = $name;
            }
            else {
                $this->meta[$name] = is_numeric($val) ? (int)$val:$val;
                $this->updated[$name] = $this->meta[$name];
            }
        }
    }

    /**
     * @see MetaData:merge()
     * @param array $arr
     */
    public function setByArray($arr)
    {
        foreach($arr as $key => $val) {
            $this->set($key, $val);
        }
    }

    /**
     * @param string $name
     */
    public function delete($name)
    {
        $this->set($name, null);
    }

    /**
     * @return array
     */
    public function getUpdatedValues()
    {
        return $this->updated;
    }

    /**
     * @param array $values
     */
    public function setUpdatedValues(array $values)
    {
        $this->updated = $values;
    }

    /**
     * @return array
     */
    public function getDeletedValues()
    {
        return $this->deleted;
    }

    /**
     * @param array $values
     */
    public function setDeletedValues(array $values)
    {
        $this->deleted = $values;
    }

    /**
     * Get an array copy of the meta values
     * @return array
     */
    public function toArray()
    {
        return $this->meta;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * @param string $key
     * @param mixed $val
     */
    public function __set($key, $val)
    {
        $this->set($key, $val);
    }

    /**
     * @throws \Exception
     */
    public function __sleep()
    {
        throw new \Exception('Can not be serialized');
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        return count($this->meta);
    }
}