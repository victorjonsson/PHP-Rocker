<?php

require_once __DIR__.'/CommonTestCase.php';

class TestMeta extends CommonTestCase {

    /**
     * @var \Rocker\Object\ObjectMetaFactory
     */
    private static $f;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$f = new \Rocker\Object\ObjectMetaFactory('testCaseMeta', self::$db, new \Rocker\Cache\TempMemoryCache());
        self::$f->createTable();
    }

    function testApplyMeta() {
        $obj = new \Rocker\Object\PlainObject('test', 1);
        self::$f->applyMetaData($obj);
        $this->assertTrue($obj->meta() instanceof \Rocker\Object\MetaData);
        $this->assertNull($obj->meta()->get('test'));
    }

    function testWriteMeta() {
        $obj = new \Rocker\Object\PlainObject('test', 1);
        self::$f->applyMetaData($obj);
        $obj->meta()->set('test', 1);
        $this->assertEquals(1, $obj->meta()->get('test'));
        $this->assertEquals(1, $obj->getMeta()->get('test'));
        self::$f->saveMetaData($obj);

        // reload object
        $obj = new \Rocker\Object\PlainObject('test', 1);
        self::$f->applyMetaData($obj);
        $this->assertEquals(1, $obj->meta()->get('test'));
        $this->assertEquals(1, $obj->getMeta()->get('test'));
        $this->assertEquals(null, $obj->getMeta()->get('test-other'));

        $obj->meta()->set('test', array(1,2,3));
        $obj->meta()->set('other-test', array(1));
        self::$f->saveMetaData($obj);

        // reload object
        $obj = new \Rocker\Object\PlainObject('test', 1);
        self::$f->applyMetaData($obj);
        $this->assertEquals(array(1,2,3), $obj->meta()->get('test'));
        $this->assertEquals(array(1), $obj->meta()->get('other-test'));


        $this->assertEquals(array(
                'test' => array(1,2,3),
                'other-test' => array(1)
            ), $obj->meta()->toArray());

    }

    function testRemove() {
        $obj = new \Rocker\Object\PlainObject('test', 2);
        self::$f->applyMetaData($obj);
        $obj->meta()->set('test', 1);
        self::$f->saveMetaData($obj);

        $obj->meta()->delete('test');
        self::$f->saveMetaData($obj);

        self::$f->applyMetaData($obj);
        $this->assertNull($obj->getMeta()->get('test'));
    }

    function testClear() {
        $obj = new \Rocker\Object\PlainObject('test', 3);
        self::$f->applyMetaData($obj);
        $obj->meta()->set('test', 1);
        $obj->meta()->set('test-2', 1);
        $obj->meta()->set('test-3', 1);
        self::$f->saveMetaData($obj);

        self::$f->removeMetaData( $obj );

        self::$f->applyMetaData($obj);
        $this->assertEquals(array(), $obj->meta()->toArray());
    }

    public static function tearDownAfterClass() {
        self::$db->exec('DROP TABLE testCaseMeta');
    }

    function testMagicMethods() {
        $obj = new \Rocker\Object\PlainObject('testo', 1);
        self::$f->applyMetaData($obj);
        $obj->meta()->key = 'value';
        $this->assertEquals('value', $obj->meta()->key);
        $this->assertTrue($obj->meta()->has('key'));
        $this->assertEquals(null, $obj->meta()->unknownKey);
        self::$f->saveMetaData($obj);
        self::$f->applyMetaData($obj);
        $obj->meta()->key = 'value';
        $this->assertEquals('value', $obj->meta()->key);
        $obj->meta()->key = null;
        $this->assertFalse($obj->meta()->has('key'));
        self::$f->saveMetaData($obj);
        self::$f->applyMetaData($obj);
        $this->assertNull($obj->meta()->key);
        $this->assertFalse($obj->meta()->has('key'));
    }

    public function testArrayToXML()
    {

    }

}