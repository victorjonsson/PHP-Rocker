<?php
namespace Rocker\Console\Method;


/**
 * Console method used to show info about the user that the console
 * uses when authenticating against the remote server
 *
 * @package Rocker\Console\Method
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class Me {

    /**
     * Output infor about how to use this method
     */
    public function help()
    {
        $_ = function($str) { \cli\line($str); };
        $_('%_Method - me%n');
        $_('---------------------------------');
        $_('Used to get information about the user that is used by this console program when requesting the server');
        $_("  $ rocker me");
    }

    /**
     * @param array $args
     * @param array $flags
     */
    public function call($args, $flags)
    {
        $client = Server::loadClient($args);
        $user = $client->user();
        if( $user )
            Users::displayUser( $user );
        else
            \cli\line('%rCould not authenticate...%n');
    }
}
