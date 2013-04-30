<?php
namespace Rocker\Utils\FileStorage\Image;


/**
 * Class that can modify images
 *
 * @package PHP-Rocker
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class ImageModifier implements ImageModifierInterface {

    /**
     * @var string
     */
    private $f;

    /**
     * @ignore
     * @var resource
     */
    private $resource = null;

    /**
     * @var array
     */
    private $data;

    /**
     * @var string
     */
    private $newFilePath;

    /**
     * @param string $f
     */
    public function __construct($f) {
        $this->f = $f;
        $this->data = @getimagesize($f);
        if( !$this->data ) {
            throw new \ErrorException('Given file is not an image possible to modify '.$f);
        }
        $this->data['width'] = $this->data[0];
        $this->data['height'] = $this->data[1];
    }

    /**
     * @inheritdoc
     */
    public function crop($width, $height, $quality=100) {

        $this->newFilePath = $this->createNewImageFilePath($width, $height);
        if( stream_resolve_include_path($this->newFilePath) !== false )
            return $this->newFilePath;

        $new_source = imagecreatetruecolor($width, $height);
        list($dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) = $this->imageResizeDimensions($this->data['width'], $this->data['height'], $width, $height, true);
        imagecopyresampled($new_source, $this->getImageResource(), $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

        return $this->saveNewImage($new_source, $quality);
    }

    /**
     * @param $width
     * @param $height
     * @return string
     */
    private function createNewImageFilePath($width, $height)
    {
        $suffix = '-'.($width.'x'.$height);
        return dirname($this->f) .'/'. pathinfo($this->f, PATHINFO_FILENAME) . $suffix . '.' . pathinfo($this->f, PATHINFO_EXTENSION);
    }

    /**
     * Borrowed from wordpress
     * @ignore
     * @param  $orig_w
     * @param  $orig_h
     * @param  $dest_w
     * @param  $dest_h
     * @param bool $crop
     * @return array|bool
     */
    private function imageResizeDimensions($orig_w, $orig_h, $dest_w, $dest_h, $crop = false) {

        $new_w = -1;
        $new_h = -1;

        if ( $crop ) {
            // crop the largest possible portion of the original image that we can size to $dest_w x $dest_h
            $aspect_ratio = $orig_w / $orig_h;
            $new_w = min($dest_w, $orig_w);
            $new_h = min($dest_h, $orig_h);

            if ( !$new_w ) {
                $new_w = intval($new_h * $aspect_ratio);
            }

            if ( !$new_h ) {
                $new_h = intval($new_w / $aspect_ratio);
            }

            $size_ratio = max($new_w / $orig_w, $new_h / $orig_h);

            $crop_w = round($new_w / $size_ratio);
            $crop_h = round($new_h / $size_ratio);

            $s_x = floor( ($orig_w - $crop_w) / 2 );
            $s_y = floor( ($orig_h - $crop_h) / 2 );
        } else {
            // don't crop, just resize using $dest_w x $dest_h as a maximum bounding box
            $crop_w = $orig_w;
            $crop_h = $orig_h;

            $s_x = 0;
            $s_y = 0;
        }

        // the return array matches the parameters to imagecopyresampled()
        // int dst_x, int dst_y, int src_x, int src_y, int dst_w, int dst_h, int src_w, int src_h
        return array( 0, 0, (int) $s_x, (int) $s_y, (int) $new_w, (int) $new_h, (int) $crop_w, (int) $crop_h );
    }

    /**
     * @ignore
     * @param resource $source
     * @param int $quality
     * @return string
     */
    private function saveNewImage($source, $quality) {
        switch($this->data['mime']) {
            case 'image/gif':
                imagegif($source, $this->newFilePath);
                break;
            case 'image/png':
                imagepng($source, $this->newFilePath, $this->calculatePngQuality($quality));
                break;
            case 'image/bmp':
                image2wbmp($source, $this->newFilePath);
                break;
            default:
                imagejpeg($source, $this->newFilePath, $quality);
        }

        imagedestroy($source);
        imagedestroy($this->resource);
        $this->resource = null;

        return $this->newFilePath;
    }

    /**
     * @inheritdoc
     */
    public function resize($width, $height, $quality=100) {

        $this->newFilePath = $this->createNewImageFilePath($width, $height);
        if( stream_resolve_include_path($this->newFilePath) !== false )
            return $this->newFilePath;

        return $this->doResize($width, $height, $quality);
    }

    /**
     * @param $width
     * @param $height
     * @param $quality
     * @return string
     */
    private function doResize($width, $height, $quality)
    {
        if ( $width ) {
            $height = $this->data['height'] * ($width / $this->data['width']);
        } else {
            $width = $this->data['width'] * ($height / $this->data['height']);
        }

        $new_source = imagecreatetruecolor($width, $height);

        imagecopyresampled(
            $new_source,
            $this->getImageResource(),
            0,
            0,
            0,
            0,
            $width,
            $height,
            $this->data['width'],
            $this->data['height']
        );

        return $this->saveNewImage($new_source, $quality);
    }

    /**
     * @ignore
     * @param int $quality
     * @return int
     */
    private function calculatePngQuality($quality) {
        $pngQuality = round( $quality / 10 );
        if( $pngQuality > 9 )
            $pngQuality = 9;
        if( $pngQuality < 1)
            $pngQuality = 1;

        return $pngQuality;
    }

    /**
     * @ignore
     * @return resource
     */
    private function getImageResource() {
        if($this->resource === null) {
            switch(strtolower($this->data['mime'])) {
                case 'image/gif':
                    $this->resource = imagecreatefromgif($this->f);
                    break;
                case 'image/png':
                    $this->resource = imagecreatefrompng($this->f);
                    break;
                case 'image/bmp':
                    $this->resource = imagecreatefromwbmp($this->f);
                    break;
                default:
                    $this->resource = imagecreatefromjpeg($this->f);
                    break;
            }
        }

        return $this->resource;
    }

    /**
     * @inheritdoc
     */
    public function create($sizeName, $quality=100)
    {
        list($width, $height) = explode('x', $sizeName);
        if( !$width || !$height )
            return $this->resize($width, $height, $quality);
        else
            return $this->crop($width, $height, $quality);
    }
}