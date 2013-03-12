<?php

require_once __DIR__.'/CommonTestCase.php';

class TestObjectFactory extends CommonTestCase {

    /**
     * @var \Rocker\Object\User\UserFactory
     */
    private static $f;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$f = new \Rocker\Object\User\UserFactory(self::$db, new \Rocker\Cache\TempMemoryCache());
        self::$f->install();
    }

    function testCreateUser() {
        $user = self::$f->createUser('asdf@asdf.se', 'Özill', '12345678');
        $this->assertEquals('asdf@asdf.se', $user->getEmail());
        $this->assertEquals(1, $user->getId());
        $this->assertTrue($user->hasPassword('12345678'));
        $this->assertEquals('Özill', $user->getNick());

        // Load user
        $user = self::$f->load(1);
        $this->assertEquals('asdf@asdf.se', $user->getEmail());
        $this->assertEquals(1, $user->getId());
        $this->assertTrue($user->hasPassword('12345678'));
        $this->assertEquals('Özill', $user->getNick());


        // Load user by email
        $user = self::$f->load('asdf@asdf.se');
        $this->assertEquals('asdf@asdf.se', $user->getEmail());
        $this->assertEquals(1, $user->getId());
        $this->assertTrue($user->hasPassword('12345678'));
        $this->assertEquals('Özill', $user->getNick());

        // Load unknown user
        $this->assertNull(self::$f->load(991));
    }

    function updateUser() {
        $user = self::$f->createUser('test@test.se', 'Nick', '123456');
        $userId = $user->getId();
        $user->setEmail('blaha@blaha.com');
        $user->setNick('Bjarne');
        $user->meta()->set('something', 'text');
        self::$f->update($user);

        $user = self::$f->load($userId);
        $this->assertEquals('blaha@blaha.com', $user->getEmail());
        $this->assertEquals($userId, $user->getId());
        $this->assertTrue($user->hasPassword('123456'));
        $this->assertEquals('Bjarne', $user->getNick());
        $this->assertEquals('text', $user->meta()->get('something'));
    }

    function testDeleteUser() {
        self::$db->exec('TRUNCATE '.self::$db->getParameter('prefix').'user');
        self::$db->exec('TRUNCATE '.self::$db->getParameter('prefix').'meta_user');
        // empty out cache
        self::$f = new \Rocker\Object\User\UserFactory(self::$db, new \Rocker\Cache\TempMemoryCache());

        $user = self::$f->createUser('testing@test.se', 'Nick', '123456');
        $userId = $user->getId();

        self::$f->delete($user);

        $this->assertNull(self::$f->load($userId));
        $this->assertNull(self::$f->load('testing@test.se'));

        $query = self::$db->query('SELECT * FROM '.self::$db->getParameter('prefix').'meta_user');
        $queryObjectTable = self::$db->query('SELECT * FROM '.self::$db->getParameter('prefix').'user');

        $this->assertEquals(0, $query->rowCount());
        $this->assertEquals(0, $queryObjectTable->rowCount());
    }

    /**
     * @expectedException \Rocker\Object\DuplicationException
     */
    public function testNameCollision() {
        $userA = self::$f->createUser('userA@user.com', 'User', '');
        $userB = self::$f->createUser('userB@user.com', 'User', '');

        $userB->setEmail('userA@user.com');
        self::$f->update($userB);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAdminFailure() {
        $user = self::$f->createUser('some@user.com', 'User', '');
        $user->meta()->set('admin', 1);
        self::$f->update($user);
    }

    public function testCreateAdmin() {
        $user = self::$f->createUser('some-other@user.dk', 'A user', '');
        $this->assertFalse($user->isAdmin());

        self::$f->setAdminPrivileges($user, true);
        $this->assertTrue($user->isAdmin());

        // reload user
        $user = self::$f->load('some-other@user.dk');
        $this->assertTrue($user->isAdmin());

        self::$f->setAdminPrivileges($user, false);
        $this->assertFalse($user->isAdmin());

        $user = self::$f->load('some-other@user.dk');
        $this->assertFalse($user->isAdmin());
    }

    /**
     * @expectedException \Rocker\Object\DuplicationException
     */
    public function testNameCollisionWhenCreating() {
        self::$f->createUser('userC@user.com', 'User', '');
        self::$f->createUser('userC@user.com', 'Other user', '');
    }


    public function testSearch() {

        // Clear database
        self::$db->exec('TRUNCATE '.self::$db->getParameter('prefix').'user');
        self::$db->exec('TRUNCATE '.self::$db->getParameter('prefix').'meta_user');
        // empty out cache
        self::$f = new \Rocker\Object\User\UserFactory(self::$db, new \Rocker\Cache\TempMemoryCache());

        self::$f->createUser('jonny@website.se', 'Jonny', '');
        self::$f->createUser('benny@website.se', 'Benny', '');
        self::$f->createUser('sonny@website.se', 'Sonny', '');

        $result = self::$f->search();
        $this->assertEquals(3, $result->getNumMatching());
        $ids = array(3,2,1);
        $names = array('Sonny', 'Benny', 'Jonny');
        $test = array('ids'=>array(), 'names' => array());
        foreach($result as $user) {
            $test['ids'][] = $user->getId();
            $test['names'][] = $user->getNick();
        }
        $this->assertEquals($ids, $test['ids']);
        $this->assertEquals($names, $test['names']);

        // reversed sorting and limit
        $result = self::$f->search(null, 0, 2, 'id', 'ASC');
        $this->assertEquals(3, $result->getNumMatching());
        $ids = array(1, 2);
        $names = array('Jonny', 'Benny');
        $test = array('ids'=>array(), 'names' => array());
        foreach($result as $user) {
            $test['ids'][] = $user->getId();
            $test['names'][] = $user->getNick();
        }
        $this->assertEquals($ids, $test['ids']);
        $this->assertEquals($names, $test['names']);

        // Sort by e-mail
        $result = self::$f->search(null, 0, 2, 'name', 'ASC');
        $this->assertEquals(3, $result->getNumMatching());
        $ids = array(2, 1);
        $names = array('Benny', 'Jonny');
        $test = array('ids'=>array(), 'names' => array());
        foreach($result->getObjects() as $user) {
            $test['ids'][] = $user->getId();
            $test['names'][] = $user->getNick();
        }
        $this->assertEquals($ids, $test['ids']);
        $this->assertEquals($names, $test['names']);


        // Text search and offset
        $result = self::$f->search('nny', 1);
        $this->assertEquals(3, $result->getNumMatching());
        $ids = array(2,1);
        $names = array('Benny', 'Jonny');
        $test = array('ids'=>array(), 'names' => array());
        foreach($result->getObjects() as $user) {
            $test['ids'][] = $user->getId();
            $test['names'][] = $user->getNick();
        }
        $this->assertEquals($ids, $test['ids']);
        $this->assertEquals($names, $test['names']);

    }


    public function testMetaSearchWithSpan() {
        self::$db->exec('TRUNCATE '.self::$db->getParameter('prefix').'user');
        self::$db->exec('TRUNCATE '.self::$db->getParameter('prefix').'meta_user');
        // empty out cache
        self::$f = new \Rocker\Object\User\UserFactory(self::$db, new \Rocker\Cache\TempMemoryCache());

        $u = self::$f->createUser('user@user.com', 'Nick 1', '');
        $u->meta()->set('time', 1000);
        self::$f->update($u);

        $u = self::$f->createUser('user2@user.com', 'Nick 2', '');
        $u->meta()->set('time', 500);
        self::$f->update($u);

       # $result = self::$f->metaSearch(array('time>'=>499));
       # $this->assertEquals(1, $result->getNumMatching());

        $result = self::$f->metaSearch(array('time>'=>9));
        $this->assertEquals(2, $result->getNumMatching());
    }

    public function testMetaSearch() {
        self::$db->exec('TRUNCATE '.self::$db->getParameter('prefix').'user');
        self::$db->exec('TRUNCATE '.self::$db->getParameter('prefix').'meta_user');
        // empty out cache
        self::$f = new \Rocker\Object\User\UserFactory(self::$db, new \Rocker\Cache\TempMemoryCache());

        $user = self::$f->createUser('user@user.com', 'Diana', '');
        $user->meta()->set('type', 'judge');
        $user->meta()->set('country', 'Sweden');
        $user->meta()->set('family', 'Sven,Mia,Anders');
        self::$f->update($user);
        $user = self::$f->createUser('user2@user.com', 'Olga', '');
        $user->meta()->set('type', 'judge');
        $user->meta()->set('country', 'Russia');
        $user->meta()->set('family', 'Sergei,Vladimir,Natja');
        self::$f->update($user);
        self::$f->update($user);
        $user = self::$f->createUser('user3@user.com', 'Thomas', '');
        $user->meta()->set('type', 'judge');
        $user->meta()->set('country', 'Belgium');
        $user->meta()->set('family', 'Kokomir');
        self::$f->update($user);

        $search = array(
            'type' => 'judge',
            array('AND' =>  array('country'=> array('Sweden', 'Belgium')))
        );

        $res = self::$f->metaSearch($search);

        $search = array(
            'type' => 'judge',
            array('AND' =>  array('country'=> array('Sweden', 'Belgium'))),
            array('AND' =>  array('family'=> '*koko*'))
        );

        $res2 = self::$f->metaSearch($search);


        $search = array(
            'type' => 'judge',
            array('AND' =>  array('country'=> array('Sweden', '*elgi*')))
        );

        $res3 = self::$f->metaSearch($search);

        $search = array(
            'type' => '*udg*'
        );

        $res4 = self::$f->metaSearch($search);


        $this->assertEquals(2, $res->getNumMatching());
        $this->assertEquals(3, $res4->getNumMatching());
        $this->assertEquals(2, $res3->getNumMatching());
        $this->assertEquals(1, $res2->getNumMatching());
        $this->assertEquals('user3@user.com', $res2[0]->getEmail());

        $this->assertEquals(3, self::$f->metaSearch(array('type' => 'judge'))->getNumMatching());
        $this->assertEquals(1, count(self::$f->metaSearch(array('type' => 'judge'), 0, 1)->getObjects()) );
        $this->assertEquals(1, self::$f->metaSearch(array('nick' => '*thom*'))->getNumMatching());

        $this->assertEquals(2, self::$f->metaSearch(array('country'=> array('Sweden', 'Belgium')))->getNumMatching());
        $this->assertEquals(2, self::$f->metaSearch(array('country'=> array('Sweden', '*Belg*')))->getNumMatching());
    }

    public static function tearDownAfterClass() {
        self::$db->exec('DROP TABLE '.self::$db->getParameter('prefix').'user');
        self::$db->exec('DROP TABLE '.self::$db->getParameter('prefix').'meta_user');
    }
}