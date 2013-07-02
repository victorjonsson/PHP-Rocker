<?php
namespace Rocker\REST;


/**
 * Class that represents a response from an API operation. This class
 * is used to setup the response to the client
 *
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class OperationResponse {

    /**
     * @var array
     */
    private $methods = array('GET','HEAD', 'POST', 'PUT','DELETE');

    /**
     * @var int
     */
    private $status;

    /**
     * @var array
     */
    private $headers= array();

    /**
     * @var array|\Traversable|\DOMDocument
     */
    private $body = array();

    /**
     * @param int $status
     * @param array $body
     */
    public function __construct($status=200, $body=array())
    {
        $this->status = $status;
        $this->body = $body;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * @param string $name
     * @param string $val
     */
    public function addHeader($name, $val)
    {
        $this->headers[$name] = $val;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Setting this variable to an array or object implementing \Traversable
     * will always be safe. If you on the other hand want to return a \DOMDocument
     * you should first check the configuration parameter "application.output"
     *
     * @example
     * <code>
     *  <?php
     *  class MyOperation extends AbstractOperation {
     *
     *      function exec(Server $server, ConnectionInterface $db, CacheInterface $cache) {
     *          // do some computations...
     *          if( $server->config('application.output') == 'xml' ) {
     *              // Return DOMDocument
     *              $body = new DOMDocument();
     *              ...
     *          } else {
     *              $body = array();
     *              ...
     *          }
     *
     *          return new OperationResponse(200, $body);
     *      }
     *  }
     * </code>
     *
     * @param array|\Traversable|\DOMDocument $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * @return array|\Traversable|\DOMDocument
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param array $methods
     */
    public function setMethods(array $methods)
    {
        $this->methods = $methods;
    }

    /**
     * @return array
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @param $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }
}