<?php
namespace Rocker\Object;

/**
 * A generic object
 *
 * @package Rocker\Object
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license GPL2 (http://www.gnu.org/licenses/gpl-2.0.html)
 */
class PlainObject implements ObjectInterface {

    /**
     * @var MetaInterface
     */
    private $meta;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $changedName = false;


    /**
     * @param string $name
     * @param int $id
     */
    public function __construct($name, $id)
    {
        $this->name = $name;
        $this->id = $id;
    }

    /**
     * @inheritDoc
     */
    function getId()
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    function setMeta(MetaData $meta)
    {
        $this->meta = $meta;
    }

    /**
     * @inheritDoc
     */
    function getMeta()
    {
        return $this->meta();
    }

    /**
     * @inheritDoc
     */
    function meta()
    {
        return $this->meta;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function setName($name)
    {
        $name = trim($name);
        if($name != $this->getName()) {
            $this->changedName = $name;
            $this->name = $name;
        }
    }

    /**
     * @inheritDoc
     */
    public function changedName()
    {
        return $this->changedName;
    }

    /**
     * @inheritDoc
     */
    public function toArray() {
        return array(
            'id' => $this->getId(),
            'name' => $this->getName(),
            'meta' => $this->meta()->toArray()
        );
    }

    /**
     * @inheritDoc
     */
    function __sleep()
    {
        throw new \Exception('Object can not be serialized');
    }
}
