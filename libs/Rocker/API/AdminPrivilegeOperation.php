<?php
namespace Rocker\API;

use Fridge\DBAL\Connection\ConnectionInterface;
use Rocker\Cache\CacheInterface;
use Rocker\Object\User\UserFactory;
use Rocker\REST\AbstractOperation;
use Rocker\REST\OperationResponse;
use Rocker\Server;


/**
 * Operation that is used to add or remove admin privileges from a user account.
 *
 * Turn admin privileges on:
 * <code>curl -X POST -u admin@service.com http://service.com/api/%path% -d 'user=12&admin=1'</code>
 *
 * Turn admin privileges on:
 * <code>curl -X POST -u admin@service.com http://service.com/api/%path% -d 'user=12&admin=0'</code>
 *
 * Both of these requests requires that the client authenticates as a user that has
 * admin privileges. The user can not remove admin privileges from his/hers own account.
 *
 * This operation returns http status <em>204</em> upon success.
 *
 * @link https://github.com/victorjonsson/PHP-Rocker/wiki/API-reference#granting-admin-privileges
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
        $user = $userFactory->load($_REQUEST['user']);
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