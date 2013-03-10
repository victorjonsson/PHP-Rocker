<?php
namespace Rocker\Object\User;

use Rocker\Object\PlainObject;
use Rocker\Utils\Security\Utils;


/**
 * Object representing a user
 *
 * @package Rocker\Object\User
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license GPL2 (http://www.gnu.org/licenses/gpl-2.0.html)
 */
class User extends PlainObject implements UserInterface {

    function getNick()
    {
        return $this->meta()->get('nick');
    }

    function setNick($nick)
    {
        $this->meta()->set('nick', $nick);
    }

    function setPassword($pass)
    {
        $this->meta()->set('password', Utils::toCryptedString($pass));
    }

    function hasPassword($pass)
    {
        return Utils::validateCryptedString($pass, $this->meta()->get('password'));
    }

    function getEmail()
    {
        return $this->getName();
    }

    function setEmail($email)
    {
        $this->setName($email);
    }

    function isAdmin()
    {
        return $this->meta()->get('admin') == 1;
    }

    public function toArray() {
        $meta = $this->meta()->toArray();
        $nick = $meta['nick'];
        unset($meta['password']);
        unset($meta['nick']);
        return array(
            'id' => $this->getId(),
            'email' => $this->getEmail(),
            'nick' => $nick,
            'meta' => $meta
        );
    }
}