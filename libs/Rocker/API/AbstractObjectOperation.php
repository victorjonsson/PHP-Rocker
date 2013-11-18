<?php
namespace Rocker\API;

use Fridge\DBAL\Connection\ConnectionInterface;
use Rocker\Cache\CacheInterface;
use Rocker\Object\AbstractObjectFactory;
use Rocker\Object\DuplicationException;
use Rocker\Object\FactoryInterface;
use Rocker\Object\ObjectInterface;
use Rocker\Object\SearchResult;
use Rocker\REST\AbstractOperation;
use Rocker\REST\OperationResponse;
use Rocker\Server;
use Slim\Http\Request;

/**
 * API Operation used to manage generic objects
 *
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
abstract class AbstractObjectOperation extends AbstractOperation {

    const SEARCH_QUERY_ARG = 'q';

    /**
     * @var array
     */
    protected $conf = array(
            'meta_limit' => 25,
            'meta_max_size' => 1048576,
            'authenticated_search' => false
        );

    /**
     * @inheritdoc
     */
    public function exec(Server $server, ConnectionInterface $db, CacheInterface $cache)
    {
        $factory = $this->createFactory($db, $cache);
        $method = $this->request->getMethod();
        $requestedObj = $this->requestedObject() ? $factory->load( $this->requestedObject() ) : null;
        $response = new OperationResponse();

        // Trigger event
        $server->triggerEvent(strtolower($method).'.object', $db, $cache);

        // Create object
        if( $method == 'POST' && $this->requestedObject() === false ) {
            $this->createNewObject($factory, $response, $db, $cache, $server);
        }
        else {

            // object not found
            if( empty($requestedObj) && empty($_GET[self::SEARCH_QUERY_ARG]) ) {
                $response->setStatus(404);
                $response->setBody(array('error'=>'object not found -> '.$_SERVER['QUERY_STRING']));
            }
            else {

                // Update object
                if( $method == 'POST' ) {
                    $this->updateObject($requestedObj, $factory, $response, $db, $cache, $server);
                }

                // Delete object
                elseif( $method == 'DELETE' ) {
                    $factory->delete($requestedObj);
                    $response->setStatus(204);
                }

                else {

                    // Search for object
                    if( isset($_REQUEST[self::SEARCH_QUERY_ARG]) ) {

                        /* @var SearchResult $result */
                        $search = $this->searchObjects($factory, $db, $cache, $server);
                        list($offset, $limit, $query, $result, $objects) = $search;

                        $response->setBody(array(
                                'query' => $query,
                                'matching' => $result->getNumMatching(),
                                'offset' => $offset,
                                'limit' => $limit,
                                'objects' => $objects
                            ));
                    }

                    // Get the requested object
                    else {
                        $response->setBody( $this->objectToArray($requestedObj, $server, $db, $cache) );
                    }
                }
            }
        }

        return $response;
    }

    /**
     * @param array|null $conf
     */
    protected function setConfig($conf)
    {
        if( !empty($conf) ) {
            $this->conf = array_merge($this->conf, $conf);
        }
    }

    /**
     * @inheritdoc
     */
    protected function searchObjects($factory, $db, $cache, $server)
    {
        $offset = !empty($_REQUEST['offset']) ? abs((int)$_REQUEST['offset']) : 0;
        $limit = !empty($_REQUEST['limit']) ? abs((int)$_REQUEST['limit']) : 50;
        $order = !empty($_REQUEST['order']) && $_REQUEST['order'] == 'ASC' ? 'ASC' : 'DESC';
        $query = array();
        if ( is_array($_REQUEST[self::SEARCH_QUERY_ARG]) ) {
            foreach ($_REQUEST[self::SEARCH_QUERY_ARG] as $key => $val) {
                $key = urldecode($key);
                $values = explode('|', urldecode($val));
                if ( empty($query) ) {
                    $query[$key] = $values;
                } else {
                    $not = substr($key, -1) == '!' ? '!' : '';
                    if ( $not )
                        $key = str_replace('!', '', $key);

                    $query[] = array('AND' . $not => array($key => $values));
                }
            }

            $result = $factory->metaSearch($query, $offset, $limit, $order);

        } else {
            $result = $factory->search(null, $offset, $limit, 'id', $order);
        }

        $objects = array();
        foreach ($result as $obj) {
            $objects[] = $this->objectToArray($obj, $server, $db, $cache);
        }
        return array($offset, $limit, $query, $result, $objects);
    }

    /**
     * @param $db
     * @param $cache
     * @return \Rocker\Object\FactoryInterface
     */
    abstract function createFactory($db, $cache);

    /**
     * @param \Rocker\Object\ObjectInterface $obj
     * @param \Rocker\Object\AbstractObjectFactory $factory
     * @param OperationResponse $response
     * @param ConnectionInterface $db
     * @param CacheInterface $cache
     * @param \Rocker\Server $server
     */
    protected function updateObject($obj, $factory, $response, $db, $cache, $server)
    {
        if ( isset($_REQUEST['name']) ) {
            $obj->setName($_REQUEST['name']);
        }

        if ( isset($_REQUEST['meta']) && is_array($_REQUEST['meta']) ) {

            $result = $this->addMetaFromRequestToObject($obj);
            if( $result !== null ) {
                // Something not okay with the meta values
                $response->setStatus($result[0]);
                $response->setBody($result[1]);
                return;
            }
        }

        try {
            $factory->update($obj);
            $response->setBody( $this->objectToArray($obj, $server, $db, $cache) );
        } catch( DuplicationException $e ) {
            $response->setStatus(409);
            $response->setBody(array('error'=>$e->getMessage()));
        }
    }

    /**
     * Add meta data from request to object and check that no meta
     * entry is too large and that we're not exceeding the maximum
     * allowed number of meta entries per object
     *
     * @param ObjectInterface $obj
     * @return array|null
     */
    protected function addMetaFromRequestToObject($obj)
    {
        foreach ($_REQUEST['meta'] as $name => $val) {
            if( $val == 'null' )
                $obj->meta()->delete($name);
            elseif($val == 'true' || $val == 'false')
                $obj->meta()->set($name, $val == 'true');
            else {
                if( strlen($val) > $this->conf['meta_max_size'] ) {
                    return array(413, array('error'=>'Meta entry "'.$name.'" exceeded allowed max size of '.$this->conf['meta_max_size'].' bytes'));
                }
                $obj->meta()->set($name, $val);
            }
        }

        // Check that we don't exceed the maximum number of meta entries allowed
        if( $this->conf['meta_limit'] < $obj->meta()->count() ) {
            return array(403, array('error'=>'This object has exceeded the maximum number of meta entries allowed'));
        }

        return null;
    }

    /**
     * @param AbstractObjectFactory $factory
     * @param OperationResponse $response
     * @param ConnectionInterface $db
     * @param CacheInterface $cache
     * @param Server $server
     * @return void
     */
    abstract protected function createNewObject($factory, $response, $db, $cache, $server);

    /**
     * @inheritDoc
     */
    public function requiredArgs()
    {
        if( $this->request->getMethod() == 'POST' && $this->requestedObject() === false ) {
            // Args required when wanting to create a new object
            return array(
                'name'
            );
        }

        return array();
    }

    /**
     * @param ObjectInterface $object
     * @param \Rocker\Server $server
     * @param ConnectionInterface $db
     * @param CacheInterface $cache
     * @return mixed
     */
    protected function objectToArray($object, $server, $db, $cache)
    {
        return $server->applyFilter('object.array', $object->toArray(), $db, $cache);
    }

    /**
     * @inheritDoc
     */
    public function allowedMethods()
    {
        return array('GET', 'HEAD', 'POST', 'DELETE');
    }

    /**
     * @inheritDoc
     */
    public function requiresAuth()
    {
        return $this->request->getMethod() == 'GET' && isset($_GET[self::SEARCH_QUERY_ARG]) && $this->conf['authenticated_search'];
    }
}