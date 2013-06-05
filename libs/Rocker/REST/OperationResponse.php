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
     * @var array
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
     * @param $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * @return array
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