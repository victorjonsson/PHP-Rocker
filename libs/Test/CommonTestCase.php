<?php


class CommonTestCase extends PHPUnit_Framework_TestCase {

    /**
     * @var \Fridge\DBAL\Connection\ConnectionInterface
     */
    protected static $db;

    public static function setUpBeforeClass()
    {
        // Add global vars expected to exist by Slim
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REMOTE_ADDR'] = '?';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['SERVER_NAME'] = '?';
        $_SERVER['SERVER_PORT'] = 80;

        require __DIR__.'/../../vendor/autoload.php';
        $config = require __DIR__.'/../../config.php';
        if( file_exists(__DIR__.'/config-local.php') ) {
            require __DIR__.'/config-local.php';
        } else {
            $config['application.db']['host'] = 'localhost';
            $config['application.db']['dbname'] = 'rocker';
            $config['application.db']['username'] = 'root';
            $config['application.db']['password'] = 'root';
            $config['application.db']['prefix'] = 'test__';
        }
        self::$db = \Rocker\Object\DB::instance($config['application.db']);
    }

    public function testConnect() {
        self::$db->query('SHOW TABLES')->execute();
    }

}