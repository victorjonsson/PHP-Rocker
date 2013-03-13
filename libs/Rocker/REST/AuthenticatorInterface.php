<?php
namespace Rocker\REST;

use Fridge\DBAL\Connection\ConnectionInterface;
use Rocker\Cache\CacheInterface;
use Rocker\Object\User\UserInterface;
use Rocker\Server;


/**
 * Interface for classes that can authenticate a client
 *
 * @package Rocker\REST
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
interface AuthenticatorInterface {

    /**
     * @param \Rocker\Server $server
     * @param \Fridge\DBAL\Connection\ConnectionInterface $db
     * @param \Rocker\Cache\CacheInterface $cache
     * @return \Rocker\Object\User\UserInterface|null
     */
    public function auth(Server $server, ConnectionInterface $db, CacheInterface $cache);

}