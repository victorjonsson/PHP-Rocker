<?php
namespace Rocker\Utils\FileStorage;

use Rocker\Utils\ErrorHandler;
use Rocker\Utils\FileStorage\Image\ImageModifier;


/**
 * Class that can store files locally
 *
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class Storage implements StorageInterface {

    /**
     * @var string
     */
    private $path;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @var string
     */
    protected $maxImageDim;

    /**
     * @var string
     */
    protected $maxImageSize;

    /**
     * @var int
     */
    protected $versionQuality;

    /**
     * @param array $config
     */
    public function __construct($config)
    {
        $this->path = rtrim(@$config['application.files']['path']).'/';
        $this->debug = $config['mode'] == 'development';
        $this->maxImageSize = self::convertFileSizeNameToBytes($config['application.files']['img_manipulation_max_size']);
        $this->maxImageDim = explode('x', $config['application.files']['img_manipulation_max_dimensions']);
        $this->versionQuality = $config['application.files']['img_manipulation_quality'];
    }

    /**
     * @inheritdoc
     */
    public function storeFile($file, $name, $mime, array $versions=array()) {

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

        $fileSize = filesize($filePath);
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);

        $data = array(
            'name' => $name,
            'size' => $fileSize,
            'extension' => $ext,
            'mime' => $mime
        );

        // Generate image versions
        if( self::isImage($ext) ) {
            $generatedVersions = $this->generateImageVersions($versions, $ext, $fileSize, $filePath);
            try {
                $imgDim = @getimagesize($filePath);
                if( !$imgDim )
                    throw new \Exception('getimagesize() could not analyze image');
                $data['width'] = $imgDim[0];
                $data['height'] = $imgDim[1];
                $data['mime'] = $imgDim['mime'];
            } catch(\Exception $e) {
                ErrorHandler::log($e);
            }
        }

        if( !empty($generatedVersions) ) {
            $data['versions'] = $generatedVersions;
        }

        if( empty($data['mime']) )
            $data['mime'] = 'text/plain';

        return $data;
    }

    /**
     * Will return either an array with file names of the generated image versions or a
     * string with the value 'skipped' in case the image versions could'nt be
     * generated, what rules that the image has to meet in order to be generated
     * is defined in config.php (application.files)
     * @param array $versions
     * @param string $ext
     * @param int $fileSize
     * @param string $filePath
     * @param null|string $newName
     * @return array|string
     */
    protected function generateImageVersions(array $versions, $ext, $fileSize, $filePath, $newName=null)
    {
        $generatedVersions = array();
        if ( self::isImage($ext) && !empty($versions) ) {
            if ( $fileSize > $this->maxImageSize || !$this->hasAllowedDimension($filePath) ) {
                $generatedVersions = 'skipped';
            } else {
                if( $newName ) {
                    copy($filePath, dirname($filePath).'/'.$newName);
                    $versionGenerator = new ImageModifier(dirname($filePath).'/'.$newName);
                } else {
                    $versionGenerator = new ImageModifier($filePath);
                }
                $generatedVersions = array();
                foreach ($versions as $name => $sizeName) {
                    $generatedVersions[$name] = basename($versionGenerator->create($sizeName, $this->versionQuality));
                }
                if( $newName ) {
                    @unlink(dirname($filePath).'/'.$newName);
                }
            }
        }
        return $generatedVersions;
    }

    /**
     * @param string $extension
     * @return bool
     */
    public static function isImage($extension)
    {
        return in_array(strtolower($extension), array('jpeg', 'jpg', 'gif', 'png'));
    }

    /**
     * @param string $file
     * @return bool
     */
    protected function hasAllowedDimension($file)
    {
        $img = getimagesize($file);
        return $img[0] <= $this->maxImageDim[0] && $img[1] <= $this->maxImageDim[1];
    }

    /**
     * @inheritdoc
     */
    public function removeFile($name) {
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
            ErrorHandler::log($e);
        }
    }


    /**
     * @inheritdoc
     */
    public function removeVersions($name, array $versions)
    {
        $basePath = dirname($this->path . $name) . '/';
        foreach($versions as $v) {
            try {
                unlink($basePath . $v);
            } catch(\Exception $e) {
                ErrorHandler::log($e);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function generateVersion($name, $sizeName)
    {
        $file = $this->path . $name;
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        if( self::isImage($extension) && filesize($file) < $this->maxImageSize && $this->hasAllowedDimension($file) ) {
            $versionGenerator = new ImageModifier($file);
            $version = $versionGenerator->create($sizeName,  $this->versionQuality);
            return basename($version);
        }
        return false;
    }

    /**
     * Example:
     *  100M = 100 Mega bytes
     *  100kb = 100 Kilobytes
     *  100b = 100 bytes
     *  100 = 100 bytes
     *
     * @param string $sizeName
     * @param $sizeName
     * @throws \InvalidArgumentException
     * @return \InvalidArgumentException
     */
    public static function convertFileSizeNameToBytes($sizeName)
    {
        $last = substr($sizeName, -1);
        if( is_numeric($last) ) {
            return (int)$sizeName;
        }
        elseif( substr($sizeName, -2) == 'kb' ) {
            $kb = (int)substr($sizeName, 0, strlen($sizeName)-2);
            return $kb * 1024;
        }
        elseif( substr($sizeName, -2) == 'MB' ) {
            $megaBytes = (int)substr($sizeName, 0, strlen($sizeName)-2);
            return $megaBytes * 1024 * 1024;
        }
        elseif( $last == 'b' ) {
            return (int)substr($sizeName, 0, strlen($sizeName) - 1);
        }
        elseif( $last == 'M' ) {
            $megaBytes = (int)substr($sizeName, 0, strlen($sizeName) - 1);
            return $megaBytes * 1024 * 1024;
        }
        else {
            throw new \InvalidArgumentException('Unable to convert "'.$sizeName.'" to bytes');
        }
    }
}