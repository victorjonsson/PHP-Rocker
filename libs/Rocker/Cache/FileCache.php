<?php
namespace Rocker\Cache;


/**
 * Cache class that uses file system
 *
 * @package Rocker\Cache
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license GPL2 (http://www.gnu.org/licenses/gpl-2.0.html)
 */
class FileCache implements CacheInterface {

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @param string $prefix
     * @param string|null $baseDir
     * @throws \Exception
     */
    public function __construct($prefix, $baseDir = null)
    {
        if( $baseDir === null ) {
            $baseDir = __DIR__.'/.rocker-cache/';
        }

        $this->cacheDir = rtrim($baseDir, '/').'/';

        if( $prefix ) {
            $this->cacheDir .= $prefix.'/';
        }
    }

    /**
     */
    private function createMissingDir() {
        if( stream_resolve_include_path($this->cacheDir) === false ) {
            $baseDir = dirname($this->cacheDir);
            if( !is_dir($baseDir) ) {
                mkdir($baseDir);
            }
            mkdir($this->cacheDir);
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function fetch($id)
    {
        $file = $this->cacheDir . $id . '.cache';
        if( stream_resolve_include_path($file) !== false ) {
            $data = unserialize(file_get_contents($file));
            if( $data['ttl'] !== 0 && $data['ttl'] > time() ) {
                unlink($file);
            } else {
                return $data['content'];
            }
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function store($id, $data, $ttl = 0)
    {
        $file = $this->cacheDir . $id . '.cache';
        try {
            file_put_contents(
                $file,
                serialize(array(
                        'content' => $data,
                        'ttl' => (int)$ttl
                    ))
            );
        } catch(\Exception $e) {
            // Create dir if missing, let cache be stored next time
            if( !$this->createMissingDir() ) {
                throw $e;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function delete($id)
    {
        $file = $this->cacheDir . $id . '.cache';
        if( stream_resolve_include_path($file) !== false ) {
            unlink($file);
        }
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        if( stream_resolve_include_path($this->cacheDir) !== false) {
            $this->removeDir($this->cacheDir);
            mkdir($this->cacheDir);
        }
    }

    /**
     * @param $path
     */
    private function removeDir($path)
    {
        /* @var \SplFileInfo $f */
        foreach(new \FilesystemIterator($path) as $f) {
            if( $f->isDir() ) {
                try {
                    $this->removeDir($f->getRealPath());
                } catch(\Exception $e) {
                    try {
                        $this->removeDir($f->getRealPath());
                    } catch(\Exception $e) {}
                }
            } else {
                try {
                    unlink($f->getRealPath());
                } catch(\Exception $e) {
                    try {
                        unlink($f->getRealPath());
                    } catch(\Exception $e) {}
                }
            }
            rmdir($path);
        }
    }
}