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
     *  name - Same as given name
     *  size - The file size in bytes
     *  extension - The file extension
     *
     * @param string|resource $file
     * @param string $name
     * @return array
     */
    function storeFile($file, $name);

    /**
     * @param $name
     * @return void
     */
    function removeFile($name);
}