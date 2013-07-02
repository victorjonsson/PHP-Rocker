<?php
namespace Rocker\REST;

use Fridge\DBAL\Connection\ConnectionInterface;
use Rocker\Cache\CacheInterface;
use Rocker\Server;
use Rocker\Utils\XML\ArrayConverter;
use Rocker\Utils\XML\ArrayConverterInterface;
use Slim\Slim;


/**
 * Class that manages request to the API
 *
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class RequestController {

    /**
     * @var \Rocker\Server
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
     * @var ArrayConverterInterface
     */
    protected $arrayConverter;

    /**
     * @param \Rocker\Server $server
     * @param \Fridge\DBAL\Connection\ConnectionInterface $db
     * @param \Rocker\Cache\CacheInterface $cache
     * @param \Rocker\Utils\XML\ArrayConverterInterface $converter
     */
    public function __construct(Server $server, ConnectionInterface $db=null, CacheInterface $cache=null, ArrayConverterInterface $converter=null)
    {
        $this->server = $server;
        $this->db = $db;
        $this->cache = $cache;
        $this->arrayConverter = $converter;
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
        $this->server->response()->status($response->getStatus());
        $this->server->response()->header('Access-Control-Allow-Origin', '*');

        foreach($response->getHeaders() as $name => $val) {
            $this->server->response()->header($name, $val);
        }

        if( $this->server->config('application.output') === 'xml' ) {
            $this->outputXML( $response->getBody() );
        } else {
            $this->outputJSON( $response->getBody() );
        }
    }

    /**
     * @param string $output
     * @throws \Exception
     */
    private function outputJSON($output)
    {
        $this->server->response()->header('Content-Type', 'application/json');
        try {
            echo json_encode($output);
        } catch(\Exception $e) {
            if( $output instanceof \DOMDocument ) {
                // The operation generating the response has ignored to check application.output
                // in the server config.
                error_log($this->server->request()->getPath().' executes an operation that returns a '.
                            ' DOMDocument even though the config variable "application.output" declares '.
                            'that the API should turn a JSON formatted response. Please fix the '.
                            'operation so it sets the body of the response to an array or an ' .
                            'object implementing Traversable', E_USER_ERROR);

                echo $this->loadArrayConverter()->convertXMLToJSON($output);

            } else {
                throw $e;
            }
        }
    }

    /**
     * @return ArrayConverterInterface
     */
    private function loadArrayConverter()
    {
        return $this->arrayConverter === null ? new ArrayConverter() : $this->arrayConverter;
    }

    /**
     * @param string $output
     */
    private function outputXML($output)
    {
        $this->server->response()->header('Content-Type', 'application/xml');
        if( $output instanceof \DOMDocument ) {
            echo $output->saveXML();
        } else {
            echo $this->loadArrayConverter()->convert($output)->saveXML();
        }
    }

    /**
     * @param array $path
     * @throws \Exception
     * @return OperationInterface|null
     */
    private function loadOperation(array $path)
    {
        $operations = $this->server->config('application.operations');
        $prePath = array_slice($path, 0, count($path)-1);
        $wildCardIndex_a = implode('/', $prePath).'/*';
        $wildCardIndex_b = implode('/', $path).'/*';
        $index = implode('/', $path);

        try {
            if( isset($operations[$wildCardIndex_a]) ) {
                return new $operations[$wildCardIndex_a](implode('/', $prePath));
            }
            elseif( isset($operations[$wildCardIndex_b]) ) {
                return new $operations[$wildCardIndex_b]($index);
            }
            elseif( isset($operations[$index]) ) {
                return new $operations[$index]($index);
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

        // Could not resolve any operation, return 404
        if( $op === null ) {
            $response = new OperationResponse(404);
            $response->setBody(array('error'=>'Operation not found'));
            return $response;
        }

        $op->setRequest($server->request());
        $isAuthenticated = $this->authenticate($op);

        // Handle OPTIONS request
        if( $method == 'OPTIONS' ) {

            $response = new OperationResponse();

            // Add allowed request data
            $requestHeaders = $server->request()->headers('Access-Control-Request-Headers', false);
            $allowedHeaders = 'Authorization, Content-Type, Content-Length'. ( $requestHeaders ? ', '.$requestHeaders:'');
            $response->addHeader('Access-Control-Allow-Headers', ucwords($allowedHeaders));
            $response->addHeader('Access-Control-Allow-Methods', implode(',', $op->allowedMethods()));

            return $response;
        }

        // Wrong method!
        if( !in_array($method, $op->allowedMethods()) ) {
            $response = new OperationResponse(405);
            $response->setMethods($op->allowedMethods());
            $response->setBody(array(
                    'error'=>'Wrong request method, only '.implode(', ', $op->allowedMethods()).' is allowed'
                ));
        }

        // Not authorized!
        elseif( $op->requiresAuth() && !$isAuthenticated ) {
            $response = new OperationResponse(401);
            $with = $server->request()->headers('HTTP_X_REQUESTED_WITH');
            if( !$with )
                $with = $server->request()->headers('X_REQUESTED_WITH');
            if( !$with || strtolower($with) != 'xmlhttprequest' ) {
                $authConfig = $this->server->config('application.auth');
                $response->setHeaders(array('WWW-Authenticate'=> $authConfig['mechanism']));
            }
            $response->setMethods($op->allowedMethods());
        }

        // Missing arguments!
        elseif( $missingArgs = $this->findMissingArgs($method, $op) ) {
            $response = new OperationResponse(400);
            $response->setBody(array(
                    'error' => 'One or more required arguments is missing ('.implode(', ', $missingArgs).')'
                ));
        }

        // All is fine :)
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