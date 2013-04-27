<?php
namespace Rocker\Utils\FileStorage;


use Rocker\Utils\ErrorHandler;

/**
 * Interface for classes that can store files on the server
 *
 * @package PHP-Rocker
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class Storage implements StorageInterface {

    private $path;
    private $debug;

    public function __construct($config) {
        $this->path = rtrim($config['application.files']['path']).'/';
        $this->debug = $config['mode'] == 'development';
    }

    /**
     * @inheritdoc
     */
    function storeFile($file, $name) {

        $filePath = $this->path . $name;

        if( !is_dir( dirname($filePath) ) ) {
            mkdir( dirname($filePath) );
        }

        if( is_string($file) ) {
            copy($file, $filePath);
        }
        else {
            rewind($file);
            $newFile = fopen($filePath, 'w+');
            while( !feof($file) ) {
                $s = fread($file, 1024);
                if (is_string($s)) {
                    fwrite($newFile, $s);
                }
            }
        }

        return array(
            'name' => $name,
            'size' => filesize($filePath),
            'extension' => pathinfo($filePath, PATHINFO_EXTENSION)
        );
    }

    /**
     * @inheritdoc
     */
    function removeFile($name) {
        try {
            unlink( $this->path . $name);
            if( strpos($name, '/') !== false ) {
                $dir = $this->path . dirname($name);
                $hasFiles = false;
                /* @var \SplFileInfo $f */
                foreach( new \FilesystemIterator($dir) as $f ) {
                    if( $f->isFile() ) {
                        $hasFiles = true;
                        break;
                    }
                }
                if( !$hasFiles ) {
                    rmdir($dir);
                }
            }
        } catch(\Exception $e) {
            if( $this->debug ) {
                throw $e;
            }
        }
    }

}