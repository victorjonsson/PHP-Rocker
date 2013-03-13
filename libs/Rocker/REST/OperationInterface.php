<?php
namespace Rocker\REST;

use Fridge\DBAL\Connection\ConnectionInterface;
use Rocker\Cache\CacheInterface;
use Slim\Http\Request;
use Slim\Slim;


/**
 * Interface for classes that serves as API operations
 *
 * @package Rocker\REST
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
interface OperationInterface {

    /**
     * The request methods that is allowed to be used when calling this operation
     * @return array
     */
    public function allowedMethods();

    /**
     * The query arguments that is required when calling this operation
     * @return array
     */
    public function requiredArgs();

    /**
     * Execute the operation and return response to client
     * @param \Slim\Slim $app
     * @param \Fridge\DBAL\Connection\ConnectionInterface $db
     * @param \Rocker\Cache\CacheInterface $cache
     * @return \Rocker\REST\OperationResponse
     */
    public function exec(Slim $app, ConnectionInterface $db, CacheInterface $cache);

    /**
     * Tells whether or not the client has to be authenticated when calling this
     * operation with current request method
     * @return bool
     */
    public function requiresAuth();

    /**
     * Whether or not this operation requires that the client is
     * authenticated as an admin user
     * @return bool
     */
    public function requiresAdminAuth();

    /**
     * Set authenticated user for this request
     * @param \Rocker\Object\User\UserInterface $user
     */
    public function setAuthenticatedUser($user);

    /**
     * @param \Slim\Http\Request $request
     */
    public function setRequest(Request $request);
}