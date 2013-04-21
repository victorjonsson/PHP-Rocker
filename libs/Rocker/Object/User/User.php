<?php
namespace Rocker\Object\User;

use Rocker\Object\PlainObject;
use Rocker\Utils\Security\Utils;


/**
 * Object representing a user
 *
 * @package Rocker\Object\User
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class User extends PlainObject implements UserInterface {

    /**
     * @inheritDoc
     */
    function getNick()
    {
        return $this->meta()->get('nick');
    }

    /**
     * @inheritDoc
     */
    function setNick($nick)
    {
        $this->meta()->set('nick', $nick);
    }

    /**
     * @inheritDoc
     */
    function setPassword($pass)
    {
        $this->meta()->set('password', Utils::toCryptedString($pass));
    }

    /**
     * @inheritDoc
     */
    function hasPassword($pass)
    {
        return Utils::validateCryptedString($pass, $this->meta()->get('password'));
    }

    /**
     * @inheritDoc
     */
    function getEmail()
    {
        return $this->getName();
    }

    /**
     * @inheritDoc
     */
    function setEmail($email)
    {
        $this->setName($email);
    }

    /**
     * @inheritDoc
     */
    function isAdmin()
    {
        return $this->meta()->get('admin') == 1;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        $meta = $this->meta()->toArray();
        $nick = $meta['nick'];
        unset($meta['password']);
        unset($meta['nick']);
        return array(
            'id' => (int)$this->getId(),
            'email' => $this->getEmail(),
            'nick' => $nick,
            'meta' => $meta
        );
    }
}