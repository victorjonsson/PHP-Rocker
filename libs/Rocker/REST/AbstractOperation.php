<?php
namespace Rocker\REST;

use Aws\CloudFront\Exception\Exception;
use Rocker\Object\User\UserFactory;
use Rocker\Object\User\UserInterface;
use Slim\Http\Request;
use Slim\Slim;


/**
 * Base class that can be extended by classes that serves as API operations
 *
 * @package PHP-Rocker
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
abstract class AbstractOperation implements OperationInterface {

    /**
     * @var null|string|bool
     */
    private $requestedObject;

    /**
     * @var \Slim\Http\Request
     */
    protected $request;

    /**
     * @var \Rocker\Object\User\UserInterface
     */
    protected $user;


    /**
     * @var string
     */
    private $path;

    /**
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @inheritDoc
     */
    public function allowedMethods()
    {
        return array('GET', 'HEAD');
    }

    /**
     * @inheritDoc
     */
    public function requiredArgs()
    {
        return array();
    }

    /**
     * @inheritDoc
     */
    public function requiresAuth()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function requiresAdminAuth()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function setAuthenticatedUser($user)
    {
        $this->user = $user;
    }

    /**
     * @inheritDoc
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * When having configured a wildcard route for an operation this function
     * may be used to retrieve requested object (eg. 'users/*' => 'SomeOperation')
     * @return string
     */
    protected function requestedObject()
    {
        if( $this->requestedObject === null ) {
            $requestPath = $this->request->getPath();
            if( basename(dirname($requestPath)) != basename($this->path) ) {
                $this->requestedObject = false;
            } else {
                $this->requestedObject = current( array_slice(explode('/', $requestPath), -1));
            }
        }
        return $this->requestedObject;
    }
}