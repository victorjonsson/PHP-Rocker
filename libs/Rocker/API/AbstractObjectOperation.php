<?php
namespace Rocker\API;

use Fridge\DBAL\Connection\ConnectionInterface;
use Rocker\Cache\CacheInterface;
use Rocker\Object\AbstractObjectFactory;
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
 * @package PHP-Rocker
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
abstract class AbstractObjectOperation extends AbstractOperation {

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
            if( empty($requestedObj) && empty($_GET['q']) ) {
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
                    if( isset($_REQUEST['q']) ) {

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
     * @inheritdoc
     */
    protected function searchObjects($factory, $db, $cache, $server)
    {
        $offset = !empty($_REQUEST['offset']) ? $_REQUEST['offset'] : 0;
        $limit = !empty($_REQUEST['limit']) ? $_REQUEST['limit'] : 50;
        $order = !empty($_REQUEST['order']) && $_REQUEST['order'] == 'ASC' ? 'ASC' : 'DESC';
        $query = array();
        if ( is_array($_REQUEST['q']) ) {
            foreach ($_REQUEST['q'] as $key => $val) {
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
            foreach ($_REQUEST['meta'] as $name => $val) {
                if( $val == 'null' )
                    $obj->meta()->delete($name);
                elseif($val == 'true' || $val == 'false')
                    $obj->meta()->set($name, $val == 'true');
                else
                    $obj->meta()->set($name, $val);
            }
        }

        $factory->update($obj);
        $response->setBody( $this->objectToArray($obj, $server, $db, $cache) );
    }

    /**
     * @param AbstractObjectFactory $factory
     * @param OperationResponse $response
     * @param ConnectionInterface $db
     * @param CacheInterface $cache
     * @param Server $sever
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
        return false;
    }
}