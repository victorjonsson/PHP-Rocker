<?php
namespace Rocker\Utils\FileStorage\Image;


/**
 * Interface for classes that can modify images
 *
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
interface ImageManipulatorInterface {

    /**
     * Resize image keeping its proportions.
     * @param int $maxWidth
     * @param int $maxHeight
     * @param int $quality
     * @return string Path to the new image
     */
    function resize($maxWidth, $maxHeight, $quality=100);

    /**
     * Crops image to an exact size
     * @param int $width
     * @param int $height
     * @param int $quality
     * @return string Path to the new image
     */
    function crop($width, $height, $quality=100);

    /**
     * Size is a string with format [WIDTH]x[HEIGHT]. To resize image set one of
     * the dimensions to 0 (eg 400x0)
     * @param string $size
     * @param int $quality
     * @return string Path to the new image
     */
    function create($size, $quality=100);
}