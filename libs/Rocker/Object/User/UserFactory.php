<?php
namespace Rocker\Object\User;

use Rocker\Object\AbstractObjectFactory;
use Rocker\Object\ObjectInterface;


/**
 * Factory class that creates users
 *
 * @package Rocker\Object\User
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class UserFactory extends AbstractObjectFactory {

    /**
     * @return string
     */
    function objectTypeName()
    {
        return 'user';
    }

    /**
     * @return string
     */
    function objectClassName()
    {
        return '\\Rocker\\Object\\User\\User';
    }

    /**
     * @param string|int $id E-mail or user id
     * @return \Rocker\Object\User\UserInterface
     */
    function load($id)
    {
        return parent::load($id);
    }

    /**
     * @inheritDoc
     * @return \Rocker\Object\User\UserInterface[]|\Rocker\Object\SearchResult
     */
    function search($search=null, $offset = 0, $limit = 50, $sortBy='id', $sortOrder='DESC')
    {
        return parent::search($search, $offset, $limit, $sortBy, $sortOrder);
    }

    /**
     * @inheritDoc
     * @return \Rocker\Object\User\UserInterface[]|\Rocker\Object\SearchResult
     */
    function metaSearch(array $where, $offset=0, $limit=50, $idOrder='DESC')
    {
        return parent::metaSearch($where, $offset, $limit, $idOrder);
    }

    /**
     * @param string $email
     * @param string $nick
     * @param string $password
     * @throws \InvalidArgumentException
     * @return \Rocker\Object\User\UserInterface
     */
    public function createUser($email, $nick, $password)
    {
        if( filter_var($email, FILTER_VALIDATE_EMAIL) === false ) {
            throw new \InvalidArgumentException('No valid e-mail given');
        }

        /* @var \Rocker\Object\User\UserInterface $user */
        $user = parent::create($email);
        $user->setNick($nick);
        $user->setPassword($password);
        $this->update($user);
        return $user;
    }

    /**
     * @var bool
     */
    protected static $changeAdminPrivByCode = false;

    /**
     * @param \Rocker\Object\User\UserInterface $user
     * @param bool $toggle
     */
    public function setAdminPrivileges(UserInterface $user, $toggle)
    {
        self::$changeAdminPrivByCode = true;
        $user->meta()->set('admin', $toggle ? 1:0);
        $this->update($user);
        self::$changeAdminPrivByCode = false;
    }

    /**
     * @param \Rocker\Object\ObjectInterface $user
     * @throws \InvalidArgumentException
     */
    public function update(ObjectInterface $user)
    {
        if( filter_var($user->getName(), FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('Not a valid e-mail ');
        }

        if( !self::$changeAdminPrivByCode ) {
            foreach($user->meta()->getUpdatedValues() as $name => $val) {
                if( $name == 'admin' ) {
                    throw new \InvalidArgumentException('Admin privileges can only be set by calling UserFactory::setAdminPrivileges');
                }
            }
        }
        parent::update($user);
    }
}