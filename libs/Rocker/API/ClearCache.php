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
 * Makes it possible for administrators to clear application cache
 *
 * @package Rocker\API
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class ClearCache extends AbstractOperation {

    /**
     * Execute the operation and return response to client
     * @param \Slim\Slim $app
     * @param \Fridge\DBAL\Connection\ConnectionInterface $db
     * @param \Rocker\Cache\CacheInterface $cache
     * @return \Rocker\REST\OperationResponse
     */
    public function exec(Slim $app, ConnectionInterface $db, CacheInterface $cache)
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