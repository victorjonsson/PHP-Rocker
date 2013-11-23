<?php
namespace Rocker\REST;

use Guzzle\Common\Collection;
use Guzzle\Http\Client as HttpClient;
use Guzzle\Http\EntityBody;
use Guzzle\Http\Message\Response;
use Rocker\Object\SearchResult;
use Rocker\Server;
use Rocker\Utils\Security\RC4Cipher;
use Slim\Http\Request;


/**
 * PHP-Rocker server client. Makes it possible to communicate
 * with a remote PHP-Rocker application
 *
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class Client extends HttpClient implements ClientInterface {

    /**
     * @var Response
     */
    private $lastResponse;

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
     * @throws \InvalidArgumentException
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
            $this->lastResponse = $request->send();
        } catch(\Guzzle\Http\Exception\ClientErrorResponseException $e) {
            $this->lastResponse = $e->getResponse();
            if( $this->lastResponse->getStatusCode() == 401 && !$doAuth && !empty($this->user) ) {
                trigger_error('Doing unauthenticated requests to an URI that requires authentication ('.$path.')', E_WARNING);
                return $this->request($method, $path, $query, true);
            }
        }

        if( $this->lastResponse->getStatusCode() == 400 ) {
            throw new ClientException($this->lastResponse, 400);
        }

        if( $this->lastResponse->getStatusCode() == 204 ) {
            return (object)array(
                    'status' => 204,
                    'body' => array()
                );
        }

        if( strpos($this->lastResponse->getContentType(), 'json') === false ) {
            throw new ClientException(
                        $this->lastResponse,
                        ClientException::ERR_UNEXPECTED_CONTENT_TYPE,
                        'Server responded with unexpected content type ('.$this->lastResponse->getContentType().')'
                    );
        }

        $str = (string)$this->lastResponse->getBody();
        $body = json_decode($str);

        return (object)array(
            'status' => $this->lastResponse->getStatusCode(),
            'headers' => $this->headerCollectionToArray($this->lastResponse->getHeaders()),
            'body' => $body
        );
    }

    /**
     * @param Collection $headers
     * @return array
     */
    private function headerCollectionToArray(Collection $headers)
    {
        $headers_arr = array();
        foreach($headers->toArray() as $name => $val ) {
            $headers_arr[$name] = current($val);
        }
        return $headers_arr;
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
     * @param \Guzzle\Http\Message\Request $request
     */
    private function addAuthHeader($request)
    {
        $request->addHeader('Authorization', $this->auth);
    }

    /**
     * @inheritDoc
     */
    public function setUser($user, $pass, $secret = false)
    {
        if( $secret ) {
            $this->setAuthString('RC4 '.base64_encode(RC4Cipher::encrypt($secret, $user . ':' . $pass)));
        } else {
            $this->setAuthString('Basic '.base64_encode($user . ':' . $pass));
        }
    }

    /**
     * @inheritDoc
     */
    public function setAuthString($str)
    {
        $this->auth = $str;
    }

    /**
     * @throws \Exception
     */
    private function checkAuth()
    {
        if( empty($this->auth) ) {
            throw new \Exception('Calling this operation requires that you set auth credentials');
        }
    }

    /**
     * @inheritDoc
     */
    public function createUser($nick, $email, $pass, array $meta)
    {
        $response = $this->request('post', 'user', array(
                    'nick' => $nick,
                    'email' => $email,
                    'password' => $pass,
                    'meta' => $meta
                ));

        if( $response->status == 409 ) {
            throw new ClientException($this->lastResponse, 409, 'User already exists');
        }

        return $response->body;
    }

    /**
     * @inheritDoc
     */
    public function updateUser($user, $nick, $email, $pass, array $meta)
    {
        $response = $this->request('post', 'user/'.$user, array(
                'nick' => $nick,
                'email' => $email,
                'password' => $pass,
                'meta' => $meta
            ), true);

        if( $response->status == 409 ) {
            throw new ClientException($this->lastResponse, 409, 'User already exists');
        }

        return $response->body;
    }

    /**
     * @inheritdoc
     */
    public function getBaseURI()
    {
        return $this->getBaseUrl();
    }

    /**
     * @inheritDoc
     */
    public function deleteUser($id)
    {
        $this->checkAuth();
        $response = $this->request('delete', 'user/'.$id, array(), true);
        if( $response->status < 200 || $response->status > 299 ) {
            throw new ClientException($this->lastResponse, $response->status);
        }
    }

    /**
     * @inheritDoc
     */
    public function serverVersion()
    {
        $response = $this->request('get', 'system/version');
        if( $response->status == 200 ) {
            return $response->body->version;
        } else {
            throw new ClientException($this->lastResponse, $response->status);
        }
    }

    /**
     * @inheritDoc
     */
    public function search($object, $search, $offset=0, $limit=50)
    {
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
            throw new ClientException($this->lastResponse, $response->status);
        }
    }

    /**
     * @inheritDoc
     */
    public function loadUser($arg)
    {
        $response = $this->request('get', 'user/'.$arg, array());
        if($response->status == 200) {
            return $response->body;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function me()
    {
        $this->checkAuth();
        $response = $this->request('get', 'me', array(), true);
        if( $response->status == 200 ) {
            return $response->body;
        } else {
            throw new ClientException($this->lastResponse, $response->status);
        }
    }
}
