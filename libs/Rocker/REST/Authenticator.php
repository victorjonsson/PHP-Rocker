<?php
namespace Rocker\REST;

use Fridge\DBAL\Connection\ConnectionInterface;
use Rocker\Server;
use Rocker\Cache\CacheInterface;
use Rocker\Object\User\UserFactory;
use Rocker\Object\User\UserInterface;
use Rocker\Utils\Security\RC4Cipher;


/**
 * Class that can authenticate clients
 *
 * @package Rocker\REST
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class Authenticator implements \Rocker\Rest\AuthenticatorInterface {

    /**
     * @var \Rocker\Object\User\UserFactory
     */
    protected $userFactory;

    /**
     * @param \Rocker\Object\User\UserFactory $uf
     */
    public function __construct(UserFactory $uf = null)
    {
        $this->userFactory = $uf;
    }

    /**
     * @inheritDoc
     */
    public function auth(Server $server, ConnectionInterface $db, CacheInterface $cache)
    {
        if( $this->userFactory === null ) {
            $this->userFactory = new UserFactory($db, $cache);
        }

        $user = null;
        $auth = $this->getAuthHeader(); // $server->request()->headers('Authorization');

        if( $auth ) {
            $authData = explode(' ', $auth);
            if( count($authData) == 2 ) {
                $authFunc = trim(strtolower($authData[0])).'Auth';
                if( method_exists($this, $authFunc) ) {
                    $user = $this->$authFunc($authData[1], $server);
                }
            }
        }

        return $user;
    }

    /**
     * @return bool
     */
    private function getAuthHeader()
    {
        $headers = function_exists('getallheaders') ? getallheaders() : $_SERVER; // support both nginx and apache
        foreach ($headers as $name => $value) {
            if ($name == 'HTTP_AUTHORIZATION' || $name == 'Authorization')
                return $value;
        }
        return false;
    }

    /**
     * @param $data
     * @param Server $server
     * @return \Rocker\Object\User\UserInterface|null
     */
    public function rc4Auth($data, $server)
    {
        $conf = $server->config('application.auth');
        $parts = explode(':', RC4Cipher::decrypt($conf['secret'], base64_decode($data)));
        if( count($parts) == 2 && !is_numeric($parts[0])) { // don't allow to login using user id
            $user = $this->userFactory->load($parts[0]);
            if( $user !== null && $user->hasPassword($parts[1]) ) {
                return $user;
            }
        }

        return null;
    }

    /**
     * @param $data
     * @return \Rocker\Object\User\UserInterface|null
     */
    public function basicAuth($data, $server)
    {
        $parts = explode(':', base64_decode($data));
        if( count($parts) == 2 && !is_numeric($parts[0]) ) { // don't allow to login using user id
            $user = $this->userFactory->load($parts[0]);
            if( $user !== null && $user->hasPassword($parts[1]) ) {
                return $user;
            }
        }

        return null;
    }
}