<?php
namespace Rocker\API;

use Fridge\DBAL\Connection\ConnectionInterface;
use Rocker\Cache\CacheInterface;
use Rocker\Object\User\UserFactory;
use Rocker\REST\AbstractOperation;
use Rocker\REST\OperationResponse;
use Rocker\Server;
use Slim\Http\Request;
use Slim\Slim;

/**
 * Operation that can be used to add or remove admin privileges
 * from a user account
 *
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class AdminPrivilegeOperation extends AbstractOperation {

    /**
     * @inheritdoc
     */
    public function exec(Server $server, ConnectionInterface $db, CacheInterface $cache)
    {
        $userFactory = new UserFactory($db, $cache);
        $user = $userFactory->loadUser($_REQUEST['user']);
        $response = new OperationResponse();

        if( !$user ) {
            $response->setStatus(400);
            $response->setBody(array('error' => 'Argument "user" is referring to a user that does not exist'));
        }
        elseif( $this->user->isEqual($user) ) {
            $response->setStatus(400);
            $response->setBody(array('error' => 'A user can not change admin privileges for its own user account'));
        }
        else {
            $userFactory->setAdminPrivileges($user, $_REQUEST['admin'] == '1');
            $response->setStatus(204);
        }

        return $response;
    }

    /**
     * @inheritDoc
     */
    public function allowedMethods()
    {
        return array('POST');
    }

    /**
     * @inheritDoc
     */
    public function requiredArgs()
    {
        return array(
            'user', // id or email
            'admin' // 1 or 0
        );
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
    public function requiresAdminAuth()
    {
        return true;
    }
}