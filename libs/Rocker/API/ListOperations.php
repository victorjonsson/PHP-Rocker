<?php
namespace Rocker\API;

use Fridge\DBAL\Connection\ConnectionInterface;
use Rocker\REST\AbstractOperation;
use Rocker\Cache\CacheInterface;
use Rocker\REST\OperationResponse;

/**
 * API Operation that returns info about all available operations
 *
 * @package Rocker\API
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license GPL2 (http://www.gnu.org/licenses/gpl-2.0.html)
 */
class ListOperations extends AbstractOperation {

    /**
     * @param \Slim\Slim $app
     * @param \Fridge\DBAL\Connection\ConnectionInterface $db
     * @param \Rocker\Cache\CacheInterface $cache
     * @return \Rocker\REST\OperationResponse
     */
    public function exec(\Slim\Slim $app, ConnectionInterface $db, CacheInterface $cache)
    {
        $operations = array();
        foreach($app->config('application.operations') as $path => $op) {

            /* @var \Rocker\REST\OperationInterface $operation */
            $operation = new $op();

            $operations[$path] = array(
                'class' => $op,
                'methods' => implode(',', $operation->allowedMethods())
            );
        }

        return new OperationResponse(200, $operations);
    }

}