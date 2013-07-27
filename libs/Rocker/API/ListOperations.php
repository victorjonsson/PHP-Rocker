<?php
namespace Rocker\API;

use Fridge\DBAL\Connection\ConnectionInterface;
use Rocker\REST\AbstractOperation;
use Rocker\Cache\CacheInterface;
use Rocker\REST\OperationResponse;
use Rocker\Server;

/**
 * API Operation that returns info about all available operations
 *
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class ListOperations extends AbstractOperation {

    /**
     * @inheritdoc
     */
    public function exec(Server $server, ConnectionInterface $db, CacheInterface $cache)
    {
        $operations = array();
        foreach($server->config('application.operations') as $path => $op) {

            /* @var \Rocker\REST\OperationInterface $operation */
            $operation = new $op(null);

            $operations[] = array(
                'class' => $op,
                'methods' => implode(',', $operation->allowedMethods()),
                'path' => $path
            );
        }

        return new OperationResponse(200, $operations);
    }

}