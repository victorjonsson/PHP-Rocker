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
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class ListOperations extends AbstractOperation {

    /**
     * @inheritdoc
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