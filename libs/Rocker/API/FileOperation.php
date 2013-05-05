<?php
namespace Rocker\API;

use Fridge\DBAL\Connection\ConnectionInterface;
use Rocker\Object\ObjectInterface;
use Rocker\Object\User\UserFactory;
use Rocker\REST\AbstractOperation;
use Rocker\Cache\CacheInterface;
use Rocker\REST\OperationResponse;
use Rocker\Server;


/**
 * CRUD operations for static files:
 *
 *  - Put a new file (and create image versions)
 *  - Delete a file
 *  - Get info about all files
 *  - Delete all files
 *  - Remove image versions
 *  - Create image versions
 *
 * @package PHP-Rocker
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class FileOperation extends AbstractOperation {

    /**
     * @inheritdoc
     */
    public function exec(Server $server, ConnectionInterface $db, CacheInterface $cache)
    {
        $storage = self::loadStorageClass($server);
        $userFiles = $this->user->meta()->get('files', array());
        $method = $server->request()->getMethod();
        $fileConf = $server->config('application.files');
        $obj = $this->requestedObject();

        if( $obj ) {
            $obj = basename($obj);
            if( basename($this->getPath()) == $obj )
                $obj = false;
        }

        if( $method != 'PUT' && $obj && empty($userFiles[$obj]) ) {
            return new OperationResponse(404, array('error'=>'file not found'));
        }


        if( $method == 'POST' ) {

            /*
             * Create new image versions
             */
            if( empty($_REQUEST['versions']) ) {
                return new OperationResponse(400, array('error'=>'Request parameter "versions" missing'));
            } else {

                // Create
                if( is_array($_REQUEST['versions']) ) {
                    $status = 201;
                    // todo: remove old files if version existed before this request
                    $fileName = $userFiles[$obj]['name'];
                    foreach($_REQUEST['versions'] as $versionName => $versionSize) {
                        if( $versionBaseName = $storage->generateVersion($fileName, $versionSize) ) {
                            $userFiles[$obj]['versions'][$versionName] = $versionBaseName;
                        }
                    }
                }
                // Remove versions
                else {
                    $status = 200;
                    $storage->removeVersions($userFiles[$obj]['name'], $userFiles[$obj]['versions']);
                    unset($userFiles[$obj]['versions']);
                }

                $this->user->meta()->set('files', $userFiles);
                $userFactory = new UserFactory($db, $cache);
                $userFactory->update($this->user);
                return new OperationResponse($status, $this->addLocation($userFiles[$obj], $fileConf));
            }

        }
        if( $method == 'GET' || $method == 'DELETE' ) {

            /*
             * Get file info
             */
            if( $obj ) {
                if( $method == 'DELETE' ) {
                    $file = $userFiles[$obj];

                    // Remove versions
                    if( !empty($_REQUEST['versions']) ) {
                        if( !empty($file['versions']) ) {
                            $versionFiles = array();
                            foreach($_REQUEST['versions'] as $versionName) {
                                if( isset($file['versions'][$versionName]) ) {
                                    $versionFiles[] = $file['versions'][$versionName];
                                    unset($file['versions'][$versionName]);
                                }
                            }
                            $storage->removeVersions($file['name'], $versionFiles);
                        }
                        $message = 'Version removed';
                        $userFiles[$obj] = $file;
                    }
                    // Remove file
                    else {
                        $storage->removeFile($file['name']);
                        unset($userFiles[$obj]);
                        $message = 'File removed';
                    }

                    $this->user->meta()->set('files', $userFiles);
                    $userFactory = new UserFactory($db, $cache);
                    $userFactory->update($this->user);
                    return new OperationResponse(200, array('message'=>$message));

                } else {
                    return new OperationResponse(200, $this->addLocation($userFiles[$obj], $fileConf));
                }
            }
            else {

                /*
                 * Delete all files
                 */
                if( $method == 'DELETE' ) {
                    self::deleteAllFiles($this->user, new UserFactory($db, $cache), $userFiles, $storage);
                    return new OperationResponse(204);
                }

                /*
                 * Get info about all files
                 */
                else {
                    foreach($userFiles as $name => $fileData) {
                        $userFiles[$name] = $this->addLocation($fileData, $fileConf);
                    }
                    return new OperationResponse(200, $obj);
                }
            }
        }

        /*
         * Add file
         */
        else {

            if( empty($obj) ) {
                return new OperationResponse(400, array('error' => 'no file given'));
            }

            // No php files please...
            if( strpos(pathinfo($obj, PATHINFO_EXTENSION), 'php') !== false ) {
                $obj = pathinfo($obj, PATHINFO_FILENAME) .'.nfw';
                $mime = 'text/plain';
            }
            else {
                $mime = $server->request()->getContentType();
                if( !$mime ) {
                    $mime = 'text/plain';
                }
            }

            // Store the file
            $tmpFile = $this->saveRequestBodyToFile($server->request()->getBody(), isset($_GET['base64_decode']));
            $newFileName = $this->createFileName($obj);
            $versions = !empty($_GET['versions']) && is_array($_GET['versions']) ? $_GET['versions']:array();
            $fileData = $storage->storeFile( $tmpFile, $newFileName, $mime, $versions );
            fclose($tmpFile);

            // Add file data to user meta and update user
            $userFiles[$obj] = $fileData;
            $this->user->meta()->set('files', $userFiles);
            $userFactory = new UserFactory($db, $cache);
            $userFactory->update($this->user);

            // Return file info
            return new OperationResponse(201, $this->addLocation($fileData, $fileConf));
        }
    }

    /**
     * @param array $fileData
     * @return bool
     */
    protected function isImage($fileData)
    {
        return in_array(strtolower($fileData['extension']), array('jpg', 'jpeg', 'png', 'gif'));
    }

    /**
     * @param ObjectInterface $user
     * @param \Rocker\Object\User\UserFactory $factory
     * @param array $userFiles
     * @param \Rocker\Utils\FileStorage\StorageInterface $storage
     */
    protected static function deleteAllFiles(ObjectInterface $user, UserFactory $factory, $userFiles, $storage)
    {
        foreach($userFiles as $f) {
            if( !empty($f['versions']) ) {
                $storage->removeVersions($f['name'], $f['versions']);
            }
            $storage->removeFile($f['name']);
        }

        $user->meta()->delete('files');
        $factory->update($user);
    }

    /**
     * @param $fileData
     * @param $fileConf
     * @return mixed
     */
    protected function addLocation($fileData, $fileConf)
    {
        if( empty($fileData['location']) ) {
            $fileData['location'] = $fileConf['base'] . $fileData['name'];
        }
        return $fileData;
    }

    /**
     * @param string $name
     * @return string
     */
    protected function createFileName($name)
    {
        return $this->user->getId() .'/'. $name;
    }

    /**
     * @param string $input
     * @param bool $base64Decode
     * @throws \ErrorException
     * @return resource
     */
    protected function saveRequestBodyToFile($input, $base64Decode = false)
    {
        $file = tmpfile();

        if( !$file ) {
            throw new \ErrorException('Unable to open temp file for writing');
        }
        fwrite($file, $base64Decode ? base64_decode($input):$input);

        return $file;
    }

    /**
     * @param Server $server
     * @return \Rocker\Utils\FileStorage\StorageInterface
     */
    protected static function loadStorageClass(Server $server)
    {
        $conf = $server->config('application.files');
        return new $conf['class']($server->getConfig());
    }

    /**
     * @inheritDoc
     */
    public function requiresAuth()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function allowedMethods()
    {
        return array('PUT', 'GET', 'DELETE', 'POST');
    }

    /**
     * @param Server $server
     * @param array $content
     * @param \Fridge\DBAL\Adapter\ConnectionInterface $db
     * @param CacheInterface $cache
     * @return array
     */
    public static function userFilter($server, $content, $db, $cache)
    {
        if( isset($content['meta']['files']) ) {
            $fileConf = $server->config('application.files');
            foreach($content['meta']['files'] as $id => $file) {
                $content['meta']['files'][$id]['location'] = $fileConf['base'] . $file['name'];
            }
        }
        return $content;
    }

    /**
     * @param Server $server
     * @param ConnectionInterface $db
     * @param \Rocker\Cache\CacheInterface $cache
     * @return array
     */
    public static function deleteUserEvent($server, $db, $cache)
    {
        $userFactory = new UserFactory($db, $cache);
        $user = $userFactory->load( basename($server->request()->getPath()) );
        if( $user !== null && $files = $user->meta()->get('files', array())) {
            $storage = self::loadStorageClass($server);
            self::deleteAllFiles($user, $userFactory, $files, $storage);
        }
    }
}
