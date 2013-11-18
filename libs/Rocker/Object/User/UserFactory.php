<?php
namespace Rocker\Object\User;

use Rocker\Object\AbstractObjectFactory;


/**
 * Factory class that creates users
 *
 * @package rocker/server
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
        return parent::loadObject($id);
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
     * @param bool $add_created_timestamp
     * @throws \InvalidArgumentException
     * @return \Rocker\Object\User\UserInterface
     */
    public function createUser($email, $nick, $password, $add_created_timestamp=true)
    {
        if( filter_var($email, FILTER_VALIDATE_EMAIL) === false ) {
            throw new \InvalidArgumentException('No valid e-mail given');
        }

        /* @var \Rocker\Object\User\UserInterface $user */
        $user = parent::createObject($email);
        $user->setNick($nick);
        $user->setPassword($password);
        if( $add_created_timestamp ) {
            $user->meta()->set('created', time());
        }
        $this->updateObject($user);
        return $user;
    }

    /**
     * @param $name
     * @see UserFactory::createUser()
     * @throws \Exception
     */
    public function create($name)
    {
        throw new \Exception('Method not allowed, use UserFactory::createUser instead');
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
        $this->updateObject($user);
        self::$changeAdminPrivByCode = false;
    }

    /**
     * @param UserInterface $user
     * @throws \InvalidArgumentException
     */
    public function update($user)
    {
        if( filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('Not a valid e-mail');
        }

        if( !self::$changeAdminPrivByCode ) {
            foreach($user->meta()->getUpdatedValues() as $name => $val) {
                if( $name == 'admin' ) {
                    throw new \InvalidArgumentException('Admin privileges can only be set by calling UserFactory::setAdminPrivileges');
                }
            }
        }

        $this->updateObject($user);
    }

    /**
     * @param UserInterface $user
     */
    public function delete($user)
    {
        $this->deleteObject($user);
    }
}