<?php


class CommonTestCase extends PHPUnit_Framework_TestCase {

    /**
     * @var \Fridge\DBAL\Connection\ConnectionInterface
     */
    protected static $db;

    public static function setUpBeforeClass()
    {
        require __DIR__.'/../../vendor/autoload.php';
        $config = require __DIR__.'/../../config.php';
        $config['application.db']['prefix'] = 'test__';
        self::$db = \Rocker\Object\DB::instance($config['application.db']);
    }

    public function testConnect() {
        self::$db->query('SHOW TABLES')->execute();
    }

}