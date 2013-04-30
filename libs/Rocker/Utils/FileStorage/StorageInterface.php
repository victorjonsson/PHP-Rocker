<?php
namespace Rocker\Utils\FileStorage;


/**
 * Interface for classes that can store files on the server
 *
 * @package PHP-Rocker
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
interface StorageInterface {

    /**
     * Should return an array with the following
     *  name - Name of file
     *  size - The file size in bytes
     *  extension - The file extension
     *  versions - Array with image versions (if created)
     *
     * Example of storing an image and generating versions:
     * <code>
     * <?php
     *   $storage->storeFile('img/dog.jpg', 'dog.jpg', array('thumb'=>'100x100', 'medium'=>'400x0'));
     * </code>
     *
     * @param string|resource $file
     * @param string $name
     * @param array $versions Only used when storing an image
     * @return array
     */
    function storeFile($file, $name, array $versions=array());

    /**
     * @param $name
     * @return void
     */
    function removeFile($name);

    /**
     * Remove image versions
     * @param string $name
     * @param array $versions
     */
    function removeVersions($name, array $versions);

    /**
     * @see \Rocker\Utils\FileStorage\Image\ImageModifier::create()
     * @param string $name
     * @param string $sizeName eg. 300x200 400x100
     * @return string|bool Base name of file or false if failed
     */
    function generateVersion($name, $sizeName);
}