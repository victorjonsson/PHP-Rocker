<?php
namespace Rocker\Utils\FileStorage;

use Rocker\Utils\ErrorHandler;
use Rocker\Utils\FileStorage\Image\ImageModifier;


/**
 * Class that can store files locally
 *
 * @package PHP-Rocker
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
        $this->maxImageSize = round(floatval($config['application.files']['img_manipulation_max_size']) * 1024 * 1024);
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

        if( isset($generatedVersions) ) {
            $data['versions'] = $generatedVersions;
        }

        if( empty($data['mime']) )
            $data['mime'] = 'text/plain';

        return $data;
    }

    /**
     * @param array $versions
     * @param string $ext
     * @param int $fileSize
     * @param string $filePath
     * @param null|string $newName
     * @return array
     */
    protected function generateImageVersions(array $versions, $ext, $fileSize, $filePath, $newName=null)
    {
        $generatedVersions = null;
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

}