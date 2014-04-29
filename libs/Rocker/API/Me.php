<?php
namespace Rocker\API;

use Fridge\DBAL\Connection\ConnectionInterface;
use Rocker\REST\AbstractOperation;
use Rocker\Cache\CacheInterface;
use Rocker\REST\OperationResponse;
use Rocker\Server;
use Slim\Slim;

/**
 * Returns the data of the user that authenticates the request
 *
 * <code>curl -u admin@website.com http://api.website.com/api/%path%</code>
 *
 * @link https://github.com/victorjonsson/PHP-Rocker/wiki/API-reference#get-user
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class Me extends AbstractOperation {

    /**
     * @inheritdoc
     */
    public function exec(Server $server, ConnectionInterface $db, CacheInterface $cache)
    {
        $userData = $server->applyFilter('user.array', $this->user->toArray(), $db, $cache);
        return new OperationResponse(200, $userData);
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
        return array('GET');
    }
}