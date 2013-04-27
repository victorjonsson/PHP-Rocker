<?php

require_once __DIR__.'/CommonTestCase.php';

class TestFileStorage extends CommonTestCase {

    /**
     * @var \Rocker\Utils\FileStorage\StorageInterface
     */
    private static $storage;

    public function testConnect() {}

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        mkdir(__DIR__.'/tmp-storage');
        file_put_contents(__DIR__.'/dummy-file.txt', 'lorem');
        self::$storage = new Rocker\Utils\FileStorage\Storage(array(
            'mode' => 'development',
            'application.files' => array(
                'path' => __DIR__.'/tmp-storage'
            )
        ));
    }

    public function testStorage() {
        $f = __DIR__.'/dummy-file.txt';
        $data = self::$storage->storeFile($f, 'test.txt');
        $this->assertEquals(array(
                'name' => 'test.txt',
                'size' => 5,
                'ext' => 'txt'
            ), $data);

        $data = self::$storage->storeFile($f, 'test2.axc');
        $this->assertEquals(array(
                'name' => 'test2.axc',
                'size' => 5,
                'ext' => 'axc'
            ), $data);

        $data = self::$storage->storeFile($f, 'aloha/test.txt');
        $this->assertEquals(array(
                'name' => 'aloha/test.txt',
                'size' => 5,
                'ext' => 'txt'
            ), $data);

        $files= array();
        $dirs = array();
        foreach( new FilesystemIterator(__DIR__.'/tmp-storage/') as $f ) {
            if( is_dir($f->getRealPath()) ) {
                $dirs[] = basename($f->getRealPath());
            } else {
                $files[] = basename($f->getRealPath());
            }
        }

        $this->assertEquals(array('test.txt', 'test2.axc'), $files);
        $this->assertEquals(array('aloha'), $dirs);
        $this->assertTrue(file_exists(__DIR__.'/tmp-storage/aloha/test.txt'));

        self::$storage->removeFile('aloha/test.txt');

        $this->assertFalse(file_exists(__DIR__.'/tmp-storage/aloha/test.txt'));
    }

    public static function tearDownAfterClass() {
        if( is_dir(__DIR__.'/tmp-storage/aloha') )
            rmdir(__DIR__.'/tmp-storage/aloha');
        if( is_dir(__DIR__.'/tmp-storage') ) {
            /* @var \SplFileInfo $file  */
            foreach( new FilesystemIterator(__DIR__.'/tmp-storage') as $file) {
                unlink($file->getRealPath());
            }
            rmdir(__DIR__.'/tmp-storage');
        }
        if( file_exists(__DIR__.'/dummy-file.txt') ) {
            unlink(__DIR__.'/dummy-file.txt');
        }
    }

}