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
 * Makes it possible for administrators to clear application cache. This operation requires that the
 * client authenticates as a user with admin privileges
 *
 * <code>curl -X POST -u admin@service.com http://service.com/api/%path%</code>
 *
 * @link https://github.com/victorjonsson/PHP-Rocker/wiki/API-reference#clear-object-cache
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class ClearCache extends AbstractOperation {

    /**
     * @inheritdoc
     */
    public function exec(Server $server, ConnectionInterface $db, CacheInterface $cache)
    {
        $cache->clear();
        return new OperationResponse(204);
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