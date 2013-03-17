<?php
namespace Rocker\API;

use Fridge\DBAL\Connection\ConnectionInterface;
use Rocker\Cache\CacheInterface;
use Rocker\Object\AbstractObjectFactory;
use Rocker\Object\FactoryInterface;
use Rocker\Object\SearchResult;
use Rocker\REST\AbstractOperation;
use Rocker\REST\OperationResponse;
use Rocker\Server;
use Slim\Http\Request;

/**
 * API Operation used to manage generic objects
 *
 * @package Rocker\API
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
            $this->createNewObject($factory, $response);
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
                    $this->updateObject($requestedObj, $factory, $response);
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
                        list($offset, $limit, $query, $result, $objects) = $this->searchObjects($factory);

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
                        $response->setBody($requestedObj->toArray());
                    }
                }
            }
        }

        return $response;
    }

    /**
     * @param FactoryInterface $factory
     * @return array
     */
    protected function searchObjects($factory)
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
            $objects[] = $obj->toArray();
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
     */
    protected function updateObject($obj, $factory, $response)
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
        $response->setBody($obj->toArray());
    }

    /**
     * @param $factory
     * @param OperationResponse $response
     */
    abstract protected function createNewObject($factory, $response);

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