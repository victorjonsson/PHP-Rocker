<?php
namespace Rocker\API;

use Fridge\DBAL\Connection\ConnectionInterface;
use Rocker\Cache\CacheInterface;
use Rocker\Object\User\UserFactory;
use Rocker\REST\AbstractOperation;
use Rocker\REST\OperationResponse;
use Slim\Http\Request;
use Slim\Slim;

/**
 * Operation that can be used to add or remove admin privileges
 * from a user account
 *
 * @package Rocker\API
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license GPL2 (http://www.gnu.org/licenses/gpl-2.0.html)
 */
class AdminPrivilegeOperation extends AbstractOperation {

    /**
     * Execute the operation and return response to client
     * @param \Slim\Slim $app
     * @param \Fridge\DBAL\Connection\ConnectionInterface $db
     * @param \Rocker\Cache\CacheInterface $cache
     * @return \Rocker\REST\OperationResponse
     */
    public function exec(Slim $app, ConnectionInterface $db, CacheInterface $cache)
    {
        $userFactory = new UserFactory($db, $cache);
        $user = $userFactory->load($_REQUEST['user']);
        $response = new OperationResponse();

        if( !$user ) {
            $response->setStatus(400);
            $response->setBody(array('error' => 'Argument "user" is referring to a user that does not exist'));
        }
        elseif( $user->getId() == $this->user->getId() ) {
            $response->setStatus(400);
            $response->setBody(array('error' => 'A user can not change admin privileges for its own user account'));
        }
        else {
            $userFactory->setAdminPrivileges($user, $_REQUEST['admin'] == '1');
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
            'admin' // 1:0
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