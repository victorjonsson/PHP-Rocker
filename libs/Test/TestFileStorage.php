<?php

require_once __DIR__.'/CommonTestCase.php';

use Rocker\Utils\FileStorage\Storage;

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
        self::$storage = new Storage(array(
            'mode' => 'development',
            'application.files' => array(
                'path' => __DIR__.'/tmp-storage',
                'img_manipulation_max_size' => '5MB',
                'img_manipulation_max_dimensions' => '300x300',
                'img_manipulation_quality' => 90
            )
        ));
    }

    public function testStorage() {
        $f = __DIR__.'/dummy-file.txt';
        $data = self::$storage->storeFile($f, 'test.txt', 'plain/text');
        $this->assertEquals(array(
                'name' => 'test.txt',
                'size' => 5,
                'extension' => 'txt',
                'mime' => 'plain/text'
            ), $data);

        $data = self::$storage->storeFile($f, 'test2.axc', 'plain/text');
        $this->assertEquals(array(
                'name' => 'test2.axc',
                'size' => 5,
                'extension' => 'axc',
                'mime' => 'plain/text'
            ), $data);

        $data = self::$storage->storeFile($f, 'aloha/test.txt', 'plain/text');
        $this->assertEquals(array(
                'name' => 'aloha/test.txt',
                'size' => 5,
                'extension' => 'txt',
                'mime' => 'plain/text'
            ), $data);

        $files= array();
        $dirs = array();
        /* @var SplFileInfo $f */
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

    public function testImageVersionsFailed() {
        $data = self::$storage->storeFile(__DIR__.'/img-large.jpg', 'large.jpg', '', array('thumb'=>'100x100'));
        $this->assertEquals('skipped', $data['versions']);
    }

    public function testImageVersions() {
        $data = self::$storage->storeFile(__DIR__.'/img.jpg', 'image.jpg', 'image/jpeg', array('thumb'=>'30x30', 'medium'=>'100x0'));
        $this->assertEquals(array(
                'thumb'=> 'image-30x30.jpg',
                'medium' => 'image-100x0.jpg'
            ), $data['versions']);

        list($width, $height) = getimagesize( __DIR__.'/tmp-storage/image-30x30.jpg' );
        $this->assertEquals(30, $width);
        $this->assertEquals(30, $height);
        list($width, $height) = getimagesize( __DIR__.'/tmp-storage/image-100x0.jpg' );
        $this->assertEquals(100, $width);
        $this->assertEquals(100, $height);

        self::$storage->removeVersions($data['name'], array('image-30x30.jpg'));
        $this->assertFalse( file_exists(__DIR__.'/tmp-storage/image-30x30.jpg') );
        $this->assertTrue( file_exists(__DIR__.'/tmp-storage/image-100x0.jpg') );

        self::$storage->generateVersion($data['name'], '10x40');
        list($width, $height) = getimagesize( __DIR__.'/tmp-storage/image-10x40.jpg' );
        $this->assertEquals(10, $width);
        $this->assertEquals(40, $height);
    }

    public function testFileSizeNameConversion() {
        $this->assertEquals(100, Storage::convertFileSizeNameToBytes('100'));
        $this->assertEquals(100, Storage::convertFileSizeNameToBytes('100b'));
        $this->assertEquals(102400, Storage::convertFileSizeNameToBytes('100kb'));
        $this->assertEquals(1024*1024, Storage::convertFileSizeNameToBytes('1M'));
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