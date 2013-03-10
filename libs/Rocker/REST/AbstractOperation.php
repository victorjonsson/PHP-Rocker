<?php
namespace Rocker\REST;

use Rocker\Object\User\UserFactory;
use Rocker\Object\User\UserInterface;
use Slim\Http\Request;
use Slim\Slim;


/**
 * Base class that can be extended by classes that serves as API operations
 *
 * @package Rocker\REST
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license GPL2 (http://www.gnu.org/licenses/gpl-2.0.html)
 */
abstract class AbstractOperation implements OperationInterface {

    /**
     * @var \Slim\Http\Request
     */
    protected $request;

    /**
     * @var \Rocker\Object\User\UserInterface
     */
    protected $user;

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
}