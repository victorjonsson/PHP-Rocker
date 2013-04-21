<?php
namespace Rocker\REST;

use Fridge\DBAL\Connection\ConnectionInterface;
use Rocker\Cache\CacheInterface;
use Rocker\Server;
use Slim\Slim;


/**
 * Class that manages request to the API
 *
 * @package PHP-Rocker
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class RequestController {

    /**
     * @var Server
     */
    protected $server;

    /**
     * @var ConnectionInterface
     */
    protected $db;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @param \Rocker\Server $server
     * @param \Fridge\DBAL\Connection\ConnectionInterface $db
     * @param \Rocker\Cache\CacheInterface $cache
     */
    public function __construct(Server $server, ConnectionInterface $db=null, CacheInterface $cache=null)
    {
        $this->server = $server;
        $this->db = $db;
        $this->cache = $cache;
    }

    /**
     * @param array $path
     */
    public function handle(array $path)
    {
        $response = $this->dispatchRequest($path, $this->server);
        $this->handleResponse($response);
    }

    /**
     * @param OperationResponse $response
     */
    public function handleResponse(OperationResponse $response)
    {
        $this->server->response()->header('Content-Type', 'application/json');
        $this->server->response()->header('Access-Control-Allow-Origin', '*');
        $this->server->response()->header('Access-Control-Allow-Headers', 'Authorization,Content-Type,Content-Length');

        $this->server->response()->header('Access-Control-Allow-Methods', implode(',', $response->getMethods()));
        $this->server->response()->status($response->getStatus());
        foreach($response->getHeaders() as $name => $val) {
            $this->server->response()->header($name, $val);
        }

        $this->server->triggerEvent('output', null, null);

        // todo: support other formats
        echo json_encode($response->getBody());
    }

    /**
     * @param array $path
     * @return OperationInterface|null
     */
    private function loadOperation(array $path)
    {
        $operations = $this->server->config('application.operations');
        $wildCardIndex_a = implode('/', array_slice($path, 0, count($path)-1)).'/*';
        $wildCardIndex_b = implode('/', $path).'/*';
        $index = implode('/', $path);

        try {
            if( isset($operations[$wildCardIndex_a]) ) {
                return new $operations[$wildCardIndex_a]();
            }
            elseif( isset($operations[$wildCardIndex_b]) ) {
                return new $operations[$wildCardIndex_b]();
            }
            elseif( isset($operations[$index]) ) {
                return new $operations[$index]();
            } else {
                return null;
            }
        } catch(\Exception $e) {
            if( $this->server->config('mode') == 'development' ) {
                throw $e;
            }
            return null;
        }
    }

    /**
     * @param $method
     * @param OperationInterface $op
     * @return array
     */
    private function findMissingArgs($method, OperationInterface $op)
    {
        $missing = array();
        foreach($op->requiredArgs($method) as $name) {
            if( !isset($_REQUEST[$name]) ) {
                $missing[] = $name;
            }
        }

        return $missing;
    }

    /**
     * @param array $path
     * @param \Rocker\Server $server
     * @return OperationResponse
     */
    public function dispatchRequest(array $path, Server $server)
    {
        $op = $this->loadOperation($path);
        $method = $this->server->request()->getMethod();

        if( $op === null ) {
            $response = new OperationResponse(404);
            $response->setBody(array('error'=>'Operation not found'));
            return $response;
        }

        $op->setRequest($server->request());
        $isAuthenticated = $this->authenticate($op);

        // Handle options request
        if( $method == 'OPTIONS' ) {
            $methods = $op->allowedMethods();
            $methods[] = 'OPTIONS';
            $response = new OperationResponse();
            $response->setMethods($methods);
            return $response;
        }

        if( !in_array($method, $op->allowedMethods()) ) {
            $response = new OperationResponse(405);
            $response->setMethods($op->allowedMethods());
            $response->setBody(array(
                    'error'=>'Wrong request method, only '.implode(', ', $op->allowedMethods()).' is allowed'
                ));
        }
        elseif( $op->requiresAuth() && !$isAuthenticated ) {
            $response = new OperationResponse(401);
            if( $server->request()->headers('HTTP_X_REQUESTED_WITH') != 'xmlhttprequest' ) {
                $authConfig = $this->server->config('application.auth');
                $response->setHeaders(array('WWW-Authenticate'=> $authConfig['mechanism']));
            }
            $response->setMethods($op->allowedMethods());
        }
        elseif( $missingArgs = $this->findMissingArgs($method, $op) ) {
            $response = new OperationResponse(400);
            $response->setBody(array(
                    'error' => 'One or more required arguments is missing ('.implode(', ', $missingArgs).')'
                ));
        }
        else {
            $response = $op->exec($this->server, $this->db, $this->cache);
        }

        return $response;
    }

    /**
     * @param OperationInterface $op
     * @return bool
     */
    private function authenticate( OperationInterface $op )
    {
        /* @var AuthenticatorInterface $authenticator */
        $authConfig = $this->server->config('application.auth');
        $authenticator = new $authConfig['class']();
        $user = $authenticator->auth($this->server, $this->db, $this->cache);
        if( empty($user) || ($op->requiresAdminAuth() && !$user->isAdmin())) {
            return false;
        }
        $op->setAuthenticatedUser($user);
        return true;
    }

    /**
     * @param \Rocker\Cache\CacheInterface $cache
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param \Fridge\DBAL\Connection\ConnectionInterface $db
     */
    public function setDatabase($db)
    {
        $this->db = $db;
    }

    /**
     * @param \Rocker\Server $server
     */
    public function setServer($server)
    {
        $this->server = $server;
    }
}