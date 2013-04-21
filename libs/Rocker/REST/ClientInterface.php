<?php
namespace Rocker\REST;


/**
 * @package PHP-Rocker
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
interface ClientInterface {

    /**
     * Returns an object with 'status' which represent http status code and 'body' holding
     * an object with the parse JSON data returned by the server
     * @param string $method
     * @param string $path
     * @param array $query
     * @param bool $doAuth
     * @return \stdClass
     */
    public function request($method, $path, $query=array(), $doAuth=false);

    /**
     * @param string $user
     * @param string $pass
     * @param bool|string $secret
     * @return void
     */
    public function setAuth($user, $pass, $secret = false);

    /**
     * @param $str - For example 'Basic ==sdfjk2n42n3lk'
     * @see ClientInterface::setAuth()
     * @return void
     */
    public function setAuthString($str);

    /**
     * @param string $object
     * @param array $search
     * @param int $offset
     * @param int $limit
     * @return \Rocker\Object\SearchResult
     */
    public function search($object, $search, $offset=0, $limit=50);

    /**
     * Get the version of the PHP Rocker framework installed on
     * the remote server
     * @return string
     */
    public function serverVersion();

    /**
     * Load user data either by e-mail or user id
     * @param string|int $arg
     * @return bool|\stdClass
     */
    public function loadUser($arg);

    /**
     * Returns data belonging to the user that is usde for authentication
     * @return \stdClass
     */
    public function me();

    /**
     * @param string|int $arg
     * @return void
     */
    public function deleteUser($arg);

    /**
     * @param string $nick
     * @param string $email
     * @param string $pass
     * @param array $meta
     * @return \stdClass
     */
    public function createUser($nick, $email, $pass, array $meta);

    /**
     * Empty values will not update a property
     * @param string|int $user Either e-mail or user ID
     * @param $nick
     * @param $email
     * @param $pass
     * @param array $meta
     * @return mixed
     */
    public function updateUser($user, $nick, $email, $pass, array $meta);

    /**
     * @return string
     */
    public function getBaseURI();
}