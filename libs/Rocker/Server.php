<?php
namespace Rocker;

use Rocker\Cache\CacheInterface;
use Rocker\Object\DB;
use Rocker\REST\OperationResponse;
use Rocker\REST\RequestController;
use Rocker\Utils\ErrorHandler;


/**
 * Rocker server application
 *
 * @package Rocker
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class Server extends \Slim\Slim  {

    /**
     * @const Current version of Rocker
     */
    const VERSION = '1.1.8';

    /**
     * @var array
     */
    private $boundEventListeners = array('filter'=>array(), 'event'=>array());

    /**
     * @var bool
     */
    private $closeDBConnOnDestruct = true;

    /**
     * @param array $config
     * @param bool $initErrorHandler
     */
    function __construct(array $config, $initErrorHandler=true)
    {
        // Initiate error handler
        if( $initErrorHandler ) {
            ErrorHandler::init($config);
        }

        parent::__construct($config);

        // Base path of the API requests
        $basePath = trim($this->settings['application.path']);
        if( $basePath != '/' ){
            $basePath = '/'.trim($basePath, '/').'/';
        }

        // Setup dynamic routing
        $this->map($basePath.':args+', array($this, 'handleAPIRequest'))->via('GET', 'POST', 'HEAD', 'PUT', 'DELETE', 'OPTIONS');


        // Bind events defined in config
        if( !empty($config['application.events']) ) {
            foreach($config['application.events'] as $arr) {
                $event = key($arr);
                $func = current($arr);
                $this->bind($event, $func);
            }
        }

        // Add filters defined in config
        if( !empty($config['application.filters']) ) {
            foreach( $config['application.filters'] as $arr) {
                $filter = key($arr);
                $func = current($arr);
                $this->bind($filter, $func, 'filter');
            }
        }
    }

    /**
     * Closes database connection
     * @see Server::closeDBConnOnDestruct()
     */
    public function __destruct()
    {
        if( $this->closeDBConnOnDestruct && DB::isInitiated() ) {
            DB::instance()->close();
        }
    }

    /**
     * @param bool $toggle
     */
    public function closeDBConnOnDestruct($toggle)
    {
        $this->closeDBConnOnDestruct = (bool)$toggle;
    }

    /**
     * Handles request and echos response to client
     * @param array $path
     * @param null|RequestController $controller
     */
    public function handleAPIRequest($path, $controller=null)
    {
        try {

            $db = \Rocker\Object\DB::instance($this->config('application.db'));
            $cache = \Rocker\Cache\Cache::instance($this->config('application.cache'));

            if( $controller === null ) {
                $controller = new \Rocker\REST\RequestController($this, $db, $cache);
            } else {
                $controller->setDatabase($db);
                $controller->setCache($cache);
            }

            $controller->handle($path);

        } catch(\InvalidArgumentException $e) {

            $response = new OperationResponse(400, array('error'=>$e->getMessage()));
            $controller = new RequestController($this, null, null);
            $controller->handleResponse( $response );

        } catch(\Exception $e) {

            ErrorHandler::log($e);

            $mess = array('message'=>$e->getMessage());
            if( $this->config('mode') == 'development' ) {
                $mess['trace'] = $e->getTraceAsString();
            }
            $response = new OperationResponse(500, $mess);
            $controller = new RequestController($this, null, null);
            $controller->handleResponse( $response );
        }
    }

    /**
     * This function is overridden to prevent slim from adding
     * its own exception handler (PrettyExceptions, rockers exception handler
     * is pretty enough). If updating to a newer version of slim make sure you
     * take a look in case this function have changed
     */
    public function run()
    {
        //Invoke middleware and application stack
        $this->middleware[0]->call();

        //Fetch status, header, and body
        list($status, $header, $body) = $this->response->finalize();

        //Send headers
        if (headers_sent() === false) {
            //Send status
            if (strpos(PHP_SAPI, 'cgi') === 0) {
                header(sprintf('Status: %s', \Slim\Http\Response::getMessageForCode($status)));
            } else {
                header(sprintf('HTTP/%s %s', $this->config('http.version'), \Slim\Http\Response::getMessageForCode($status)));
            }

            //Send headers
            foreach ($header as $name => $value) {
                $hValues = explode("\n", $value);
                foreach ($hValues as $hVal) {
                    header("$name: $hVal", false);
                }
            }
        }

        // Send body
        echo $body;
    }

    /**
     * Redirects client to current page
     */
    public function reload()
    {
        $this->redirect($this->request()->getPath());
        die;
    }

    /**
     * @param string $event
     * @param \Closure $func
     * @param string $type
     */
    public function bind($event, $func, $type='event')
    {
        $this->boundEventListeners[$type][$event][] = $func;
    }

    /**
     * @param string $event
     * @param \Fridge\DBAL\Connection\ConnectionInterface $db
     * @param \Rocker\Cache\CacheInterface $cache
     */
    public function triggerEvent($event, $db, $cache)
    {
        if( isset($this->boundEventListeners['event'][$event]) ) {
            foreach($this->boundEventListeners['event'][$event] as $func) {
                call_user_func($func, $this, $db, $cache);
            }
        }
    }

    /**
     * @param string $event
     * @param mixed $content
     * @param $db
     * @param $cache
     * @return mixed
     */
    public function applyFilter($event, $content, $db, $cache)
    {
        if( isset($this->boundEventListeners['filter'][$event]) ) {
            foreach($this->boundEventListeners['filter'][$event] as $func) {
                $content = call_user_func($func, $this, $content, $db, $cache);
            }
        }

        return $content;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->settings;
    }
}
