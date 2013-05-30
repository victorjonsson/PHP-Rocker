<?php
namespace Rocker\API;

use Fridge\DBAL\Connection\ConnectionInterface;
use Rocker\Cache\CacheInterface;
use Rocker\Object\DuplicationException;
use Rocker\Object\User\UserFactory;
use Rocker\REST\OperationResponse;
use Rocker\Server;
use Slim\Http\Request;

/**
 * API Operation used to manage user objects
 *
 * @package PHP-Rocker
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class UserOperation extends AbstractObjectOperation {

    /**
     * @var null|string|bool
     */
    private $requestedObject;

    /**
     * @inheritdoc
     */
    public function exec(Server $server, ConnectionInterface $db, CacheInterface $cache)
    {
        $userFactory = new UserFactory($db, $cache);
        $method = $this->request->getMethod();
        $requestedUser = $this->requestedObject() ? $userFactory->load( $this->requestedObject() ) : false;

        if( ($method == 'POST' || $method == 'DELETE') &&
            $requestedUser &&
            !$this->user->isAdmin() &&
            !$this->user->isEqual($requestedUser) ) {
            return new OperationResponse(401, array('error'=>'Only admins can edit/remove other users'));
        }

        if( $method == 'DELETE' && $requestedUser && $requestedUser->isAdmin() ) {
            return new OperationResponse(403, array('error'=>'A user with admin privileges can not be removed. You have to remove admin privileges first (/api/admin)'));
        }

        // Trigger event
        $server->triggerEvent(strtolower($method).'.user', $db, $cache);

        return parent::exec($server, $db, $cache);
    }

    /**
     * @inheritdoc
     */
    protected function updateObject($obj, $factory, $response, $db, $cache, $server)
    {
        if ( !empty($_REQUEST['email']) ) {
            $obj->setEmail($_REQUEST['email']);
        }
        if ( !empty($_REQUEST['nick']) ) {
            $obj->setNick($_REQUEST['nick']);
        }
        if ( !empty($_REQUEST['password']) ) {
            $obj->setPassword($_REQUEST['password']);
        }

        parent::updateObject($obj, $factory, $response, $db, $cache, $server);

        if( $response->getStatus() == 409 ) {
            $response->setBody(array('error'=>'E-mail taken by another user'));
        }
    }

    /**
     * @inheritDoc
     */
    protected function createNewObject($userFactory, $response, $db, $cache, $server)
    {
        try {

            // Create user
            $newUser = $userFactory->createUser(
                $_REQUEST['email'],
                $_REQUEST['nick'],
                $_REQUEST['password']
            );

            $newUser->meta()->set('created', time());

            // Add meta data
            if ( isset($_REQUEST['meta']) && is_array($_REQUEST['meta']) ) {
                foreach ($_REQUEST['meta'] as $name => $val) {
                    if ( $name != 'admin' ) { // can only be granted admin privileges
                        $newUser->meta()->set($name, $val);
                    }
                }
            }
            $userFactory->update($newUser);

            $response->setStatus(201);
            $response->setBody( $this->objectToArray($newUser, $server, $db, $cache) );

        } catch (DuplicationException $e) {
            $response->setStatus(409);
            $response->setBody(array('error' => 'E-mail taken by another user'));
        }
    }

    /**
     * @inheritDoc
     */
    public function requiredArgs()
    {
        if( $this->request->getMethod() == 'POST' && $this->requestedObject() === false ) {
            // Args required when wanting to create a new user
            return array(
                'email',
                'nick',
                'password'
            );
        }

        return array();
    }

    /**
     * @inheritdoc
     */
    protected function objectToArray($object, $server, $db, $cache)
    {
        return $server->applyFilter('user.array', $object->toArray(), $db, $cache);
    }

    /**
     * @param \Fridge\DBAL\Connection\ConnectionInterface $db
     * @param \Rocker\Cache\CacheInterface $cache
     * @return \Rocker\Object\AbstractObjectFactory|UserFactory
     */
    public function createFactory($db, $cache)
    {
        return new UserFactory($db, $cache);
    }

    /**
     * @inheritDoc
     */
    public function allowedMethods()
    {
        return array('GET', 'HEAD', 'POST', 'DELETE');
    }

    /**
     * @inheritDoc
     */
    public function requiresAuth()
    {
        if( $this->request->getMethod() == 'POST' || $this->request->getMethod() == 'DELETE' ) {
            return $this->requestedObject() !== false; // false meaning we want to create a new user
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    protected function requestedObject()
    {
        if( $this->requestedObject === null ) {
            $this->requestedObject = current( array_slice(explode('/', $this->request->getPath()), -1));
            if( !is_numeric($this->requestedObject) && filter_var($this->requestedObject, FILTER_VALIDATE_EMAIL) === false ) {
                $this->requestedObject = false;
            }
        }
        return $this->requestedObject;
    }
}