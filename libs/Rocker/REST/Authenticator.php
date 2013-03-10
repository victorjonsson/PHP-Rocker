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
 * @license GPL2 (http://www.gnu.org/licenses/gpl-2.0.html)
 */
class Authenticator implements \Rocker\Rest\AuthenticatorInterface {

    /**
     * @var \Rocker\Object\User\UserFactory
     */
    private $userFactory;

    /**
     * @param \Rocker\Object\User\UserFactory|null $uf
     */
    public function __construct(UserFactory $uf = null)
    {
        $this->userFactory = $uf;
    }

    /**
     * @param \Rocker\Server $server
     * @param \Fridge\DBAL\Connection\ConnectionInterface $db
     * @param \Rocker\Cache\CacheInterface $cache
     * @return null|\Rocker\Object\User\UserInterface
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
                    $user = $this->$authFunc($authData[1]);
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
     * @param $key
     * @return \Rocker\Object\User\UserInterface|null
     */
    public function rc4Auth($data, $key)
    {
        $parts = explode(':', RC4Cipher::decrypt($key, $data));
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
    public function basicAuth($data)
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