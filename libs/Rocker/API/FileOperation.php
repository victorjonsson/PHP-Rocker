<?php
namespace Rocker\API;

use Fridge\DBAL\Connection\ConnectionInterface;
use Rocker\Object\ObjectInterface;
use Rocker\Object\User\UserFactory;
use Rocker\Object\User\UserInterface;
use Rocker\Utils\FileStorage\StorageInterface;
use Rocker\REST\AbstractOperation;
use Rocker\Cache\CacheInterface;
use Rocker\REST\OperationResponse;
use Rocker\Server;
use Rocker\Utils\Security\Utils;
use Slim\Slim;


/**
 * Returns the user data of the user that authenticates the request
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

        if( $method == 'GET' || $method == 'DELETE' ) {

            /*
             * Get file info
             */
            if( $obj ) {
                if( $method == 'DELETE' ) {
                    $file = $userFiles[$obj];
                    $storage->removeFile($file['name']);
                    unset($userFiles[$obj]);
                    $this->user->meta()->set('files', $userFiles);
                    $userFactory = new UserFactory($db, $cache);
                    $userFactory->update($this->user);
                    return new OperationResponse(204);
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
                 * Get all files
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

            $tmpFile = $this->saveRequestBodyToFile($server->request()->getBody());
            $newFileName = $this->createFileName($obj);
            $fileData = $storage->storeFile( $tmpFile, $newFileName );

           # if( $this->isImage($fileData) && !empty($_GET['versions']) && is_array($_GET['versions']) ) {
                // todo: generate image versions
            #    $fileData['versions'] = $_GET['versions'];
            #}

            fclose($tmpFile);

            $userFiles[$obj] = $fileData;
            $this->user->meta()->set('files', $userFiles);

            $userFactory = new UserFactory($db, $cache);
            $userFactory->update($this->user);

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
     * @param StorageInterface $storage
     */
    protected static function deleteAllFiles(ObjectInterface $user, UserFactory $factory, $userFiles, $storage)
    {
        foreach($userFiles as $f) {
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
        $fileData['location'] = $fileConf['base'] . $fileData['name'];
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
     * @throws \ErrorException
     * @return resource
     */
    protected function saveRequestBodyToFile($input)
    {
        $file = tmpfile();

        if( !$file ) {
            throw new \ErrorException('Unable to open temp file for writing');
        }

        fwrite($file, $input);

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
        return array('PUT', 'GET', 'DELETE');
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
