<?php
namespace Rocker\REST;

use Fridge\DBAL\Exception\Exception;
use Guzzle\Http\Client as HttpClient;
use Guzzle\Http\EntityBody;
use Rocker\Object\DuplicationException;
use Rocker\Object\SearchResult;
use Rocker\Server;
use Rocker\Utils\Security\RC4Cipher;


/**
 * Rocker Rest Server client. Makes it possible to communicate
 * with a remote Rocker server
 *
 * @package Rocker\REST
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license GPL2 (http://www.gnu.org/licenses/gpl-2.0.html)
 */
class Client extends HttpClient implements ClientInterface {

    /**
     * @var string
     */
    private $auth;

    /**
     * Returns object with properties int:status object:body
     * @param string $method
     * @param string $path
     * @param array $query
     * @param bool $doAuth
     * @throws \Exception
     * @return \stdClass
     */
    public function request($method, $path, $query=array(), $doAuth=false)
    {

        $this->userAgent = 'Rocker REST Client v'.Server::VERSION;
        $method = strtolower($method);
        $request = $this->initiateRequest($method, $path, $query);

        if( $doAuth ) {
            $this->addAuthHeader($request);
        }

        try {
            $resp = $request->send();
        } catch(\Guzzle\Http\Exception\ClientErrorResponseException $e) {
            $resp = $e->getResponse();
            if( $resp->getStatusCode() == 401 && !$doAuth && !empty($this->user) ) {
                trigger_error('Doing unauthenticated requests to an URI that requires authentication ('.$path.')');
                return $this->request($method, $path, $query, true);
            }
        }

        if( $resp->getStatusCode() == 400 ) {
            throw new \InvalidArgumentException((string)$resp->getBody());
        }

        if( $resp->getStatusCode() == 204 ) {
            return (object)array(
                    'status' => 204,
                    'body' => array()
                );
        }

        if( strpos($resp->getContentType(), 'json') === false ) {
            throw new \Exception('Server responded with unexpected content type ('.$resp->getContentType().')');
        }

        $str = (string)$resp->getBody();
        $body = json_decode($str);

        return (object)array(
            'status' => $resp->getStatusCode(),
            'body' => $body
        );
    }

    /**
     * @param $method
     * @param $path
     * @param $query
     * @return \Guzzle\Http\Message\RequestInterface
     */
    private function initiateRequest($method, $path, $query)
    {
        /* @var \Guzzle\Http\Message\RequestInterface $request */
        if ( $method == 'post' ) {
            $request = $this->$method($path, array(), $query);
            return $request;
        } else {
            $request = $this->$method($path);
            return $request;
        }
    }

    /**
     * @param $request
     */
    private function addAuthHeader($request)
    {
        $request->addHeader('Authorization', $this->auth);
    }

    public function setAuth($user, $pass, $secret = false) {
        if( $secret ) {
            $this->auth = 'RC4 '.base64_encode(RC4Cipher::encrypt($secret, $user . ':' . $pass));
        } else {
            $this->auth = 'Basic '.base64_encode($user . ':' . $pass);
        }
    }

    public function setAuthString($str) {
        $this->auth = $str;
    }

    private function checkAuth() {
        if( empty($this->auth) ) {
            throw new \Exception('Calling this operation requires that you set auth credentials');
        }
    }

    public function createUser($nick, $email, $pass, array $meta)
    {
        $response = $this->request('post', 'user', array(
                    'nick' => $nick,
                    'email' => $email,
                    'password' => $pass,
                    'meta' => $meta
                ));

        if( $response->status == 409 ) {
            throw new DuplicationException('User already exists');
        }

        return $response->body;
    }

    public function updateUser($user, $nick, $email, $pass, array $meta)
    {
        $response = $this->request('post', 'user/'.$user, array(
                'nick' => $nick,
                'email' => $email,
                'password' => $pass,
                'meta' => $meta
            ), true);

        if( $response->status == 409 ) {
            throw new DuplicationException('User already exists');
        }

        return $response->body;
    }

    /**
     * @param string|int $id E-mail or user id
     * @param string $pass
     */
    public function changePassword($id, $pass) {
        $this->checkAuth();
        $resp = $this->request('post', 'user/'.$id, array('password'=>$pass));
    }

    /**
     * @param string|int $id E-mail or id number of user
     * @throws \Exception
     */
    public function deleteUser($id) {
        $this->checkAuth();
        $response = $this->request('delete', 'user/'.$id, array(), true);
        if( $response->status < 200 || $response->status > 299 ) {
            throw new \Exception(implode(',', $response->body));
        }
    }

    /**
     * Get Rocker version on remote server
     */
    public function serverVersion() {
        $response = $this->request('get', 'system/version');
        if( $response->status == 200 ) {
            return $response->body->version;
        } else {
            throw new Exception('Server responded with status '.$response->status);
        }
    }

    /**
     * @param string $object
     * @param array $search
     * @param int $offset
     * @param int $limit
     * @throws \Exception
     * @return \Rocker\Object\SearchResult|\stdClass[]
     */
    public function search($object, $search, $offset=0, $limit=50) {
        if( empty($search) ) {
            $object .= '?q=all';
        }
        else {
            $query = '';
            foreach($search as $key => $val) {
                $query .= 'q['.urldecode($key).']='.urlencode($val).'&';
            }
            if( $query == 'q[]=&' ) {
                $object .= '?q=all';
            } else {
                $object .= '?'.rtrim($query,'&');
            }
        }

        $object .= '&offset='.$offset.'&limit='.$limit;
        $response = $this->request('get', $object);

        if( $response->status == 200 ) {
            $result = new SearchResult($offset, $limit);
            $result->setQuery($response->body->query);
            $result->setNumMatching( $response->body->matching );
            $result->setObjects($response->body->objects);
            return $result;
       } else {
            throw new \Exception('Server responded with status '.$response->status);
        }
    }

    public function loadUser($arg) {
        $response = $this->request('get', 'user/'.$arg, array());
        if($response->status == 200) {
            return $response->body;
        } else {
            return false;
        }
    }

    public function user() {
        $response = $this->request('get', 'auth', array(), true);
        if( $response->status == 200 ) {
            return $response->body;
        }
        return false;
    }
}
