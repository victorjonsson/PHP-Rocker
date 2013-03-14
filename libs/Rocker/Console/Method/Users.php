<?php
namespace Rocker\Console\Method;

use Rocker\Console\Utils;
use Rocker\Object\DuplicationException;
use Rocker\Object\User\UserFactory;


/**
 * Console method used to manage users on a remote server
 *
 * @package Rocker\Console\Method
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class Users implements MethodInterface {

    /**
     * @inheritdoc
     */
    public function help()
    {
        $_ = function($str) { \cli\line($str); };
        $_('%_Method - users%n');
        $_('---------------------------------');
        $_('Search (examples):');
        $_('  Search for user with nick containing john and that comes from either Germany or France');
        $_("  $ rocker users -q 'nick=*john*&country=Sweden|Germany'");
        $_('  Search for users from Sweden with offset 10');
        $_("  $ rocker users -q 'country=Sweden' -off 10 -lim 50");
        $_('Load user (examples):');
        $_("  $ rocker users -l 2133");
        $_("  $ rocker users -l user@website.com");
        $_('Remove user (examples):');
        $_("  $ rocker users -r 2133");
        $_("  $ rocker users -r user@website.com");
        $_('Create user (examples):');
        $_("  $ rocker users -c 'John Doe'");
        $_('Update user (examples):');
        $_("  You will be prompted for property values. An empty value will not update the property.".
                "\n  Meta values should be given a query string (eg. 'some-meta=some value&other-meta=some value').".
                "\n  To remove a meta value  you set it to null (eg. 'some-meta=null')");
        $_("  $ rocker users -u john@doe.com");
        $_("  $ rocker users -u 1093");
    }

    /**
     * @inheritdoc
     */
    public function call($args, $flags)
    {
        $client = Server::loadClient($args);

        if( !$client )
            return;

        // Create user
        if( isset($args['-c']) ) {
            $email = \cli\prompt('E-mail');
            $pass = Utils::promptPassword('Password: ');
            $metaData = Utils::promptAllowingEmpty('Meta (query string)');
            $meta = array();

            foreach(explode('&', $metaData) as $data) {
                $parts = explode('=', $data);
                if( count($parts) == 2 ) {
                    $meta[trim($parts[0])] = trim($parts[1]);
                }
            }

            try {
                $user = $client->createUser($args['-c'], $email, $pass, $meta);
                self::displayUser($user);
            } catch(DuplicationException $e) {
                \cli\line('%rUser with given e-mail already exists%n');
            } catch(\InvalidArgumentException $e) {
                \cli\line('Invalid arguments, message: '.$e->getMessage());
            }
        }

        // Load user
        elseif( isset($args['-l']) ) {
            $user = $client->loadUser($args['-l']);
            if( $user ) {
                self::displayUser($user);
            }
            else {
                \cli\line('-No user found');
            }
        }

        // Delete user
        elseif( isset($args['-r']) ) {
            $user = $client->loadUser($args['-r']);
            if( $user ) {
                $yes = \cli\prompt('Do you really want to delete "'.$user->nick.'" (y/n)');
                if( $yes[0] == 'y' ) {
                    $client->deleteUser($args['-r']);
                    \cli\line('...user deleted');
                }
            } else {
                \cli\line('User not found');
            }
        }

        // Search user
        elseif( isset($args['-q']) ) {
            $limit = isset($args['-lim']) ? $args['-lim']:50;
            $offset = isset($args['-off']) ? $args['-off']:0;
            $search = array();
            foreach(explode('&', $args['-q']) as $que) {
                $parts = explode('=', $que);
                $search[$parts[0]] = $parts[1];
            }

            $result = $client->search('user', $search, $offset, $limit);

            if( in_array('-v', $flags) ) {
                print_r($result);
            }

            \cli\line('%_Matching users: '.$result->getNumMatching().'%n');
            $data = array();
            foreach($result as $obj) {
                $data[] = array($obj->id, $obj->email, $obj->nick);
            }

            $table = new \cli\Table();
            $table->setHeaders(array('ID', 'E-mail', 'Nick'));
            $table->setRows($data);
            $table->display();
        }

        // Update user
        elseif( isset($args['-u']) ) {

            $user = $client->loadUser($args['-u']);
            if( $user ) {
                self::displayUser($user);
                \cli\line('Update user "%_'.$user->nick.'%n" (leave values empty if not wanting to change a property)');

                $nick = Utils::promptAllowingEmpty('New nick');
                $email = Utils::promptAllowingEmpty('New e-mail');
                $pass = Utils::promptPassword('New password: ');
                $metaData = Utils::promptAllowingEmpty('Meta');
                $meta = array();

                foreach(explode('&', $metaData) as $data) {
                    $parts = explode('=', $data);
                    if( count($parts) == 2 ) {
                        $meta[trim($parts[0])] = trim($parts[1]);
                    }
                }

                $user = $client->updateUser($user->id, $nick, $email, $pass, $meta);
                \cli\line('%_%gUser updated%n');
                self::displayUser($user);

            } else {
                \cli\line('User not found');
            }
        }
        else {
            $this->help();
        }

    }

    /**
     * Output user info in console
     * @param \stdClass $user
     */
    public static function displayUser($user)
    {
        \cli\line('%_'.$user->nick.'%n '.$user->email.' (#'.$user->id.')');
        if( isset($user->meta) ) {
            $data = array();
            foreach($user->meta as $name=> $val) {
                if( is_bool($val) ) {
                    $val = '%Wbool('.($val ? 'true':'false').')%n';
                }
                $data[] = array($name, $val);
            }
            $table = new \cli\Table();
            $table->setHeaders(array('Meta name', 'Meta value'));
            $table->setRows($data);
            $table->display();
        }
    }

}