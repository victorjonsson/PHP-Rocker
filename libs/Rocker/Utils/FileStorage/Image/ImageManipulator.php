<?php
namespace Rocker\Utils\FileStorage\Image;

use Gregwar\Image\Image;


/**
 * Class that can modify images
 *
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class ImageManipulator implements ImageManipulatorInterface
{
    /**
     * @var string
     */
    private $originalFile;

    /**
     * @var string
     */
    private $newFilePath;

    /**
     * @var string
     */
    private $guessedType;

    /**
     * @var string
     */
    private $extension;

    /**
     * @param $imgFilePath
     * @throws \ErrorException
     */
    public function __construct($imgFilePath)
    {
        $this->originalFile = $imgFilePath;
        $this->extension = pathinfo($imgFilePath, PATHINFO_EXTENSION);
        $this->guessedType = strtolower($this->extension);
        if( $this->guessedType == 'jpg' )
            $this->guessedType = 'jpeg';
    }

    /**
     * @inheritdoc
     */
    public function crop($width, $height, $quality = 100)
    {
        $this->newFilePath = $this->createNewImageFilePath($width, $height);
        Image::open($this->originalFile)
                ->zoomCrop($width, $height)
                ->save($this->newFilePath, $this->guessedType, $quality);

        return $this->newFilePath;
    }

    /**
     * @param $width
     * @param $height
     * @return string
     */
    private function createNewImageFilePath($width, $height)
    {
        $suffix = '-' . ($width . 'x' . $height);
        return dirname($this->originalFile) . '/' . pathinfo($this->originalFile, PATHINFO_FILENAME) . $suffix . '.' . $this->extension;
    }


    /**
     * @inheritdoc
     */
    public function resize($width, $height, $quality = 100)
    {
        $this->newFilePath = $this->createNewImageFilePath($width, $height);
        Image::open($this->originalFile)
            ->resize(max($width, $height), max($width, $height))
            ->save($this->newFilePath, $this->guessedType, $quality);

        return $this->newFilePath;
    }

    /**
     * @inheritdoc
     */
    public function create($sizeName, $quality = 100)
    {
        list($width, $height) = explode('x', $sizeName);
        if ( !$width || !$height ) {
            return $this->resize($width, $height, $quality);
        } else {
            return $this->crop($width, $height, $quality);
        }
    }
}