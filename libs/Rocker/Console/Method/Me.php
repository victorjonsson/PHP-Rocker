<?php
namespace Rocker\Console\Method;


use Rocker\REST\Client;

class Me {

    public function help() {
        $_ = function($str) { \cli\line($str); };
        $_('%_Method - me%n');
        $_('---------------------------------');
        $_('Used to get information about the user that is used by this console program when requesting server');
        $_("  $ rocker me");
    }

    public function call($args, $flags) {
        $client = Server::loadClient($args);
        $user = $client->user();
        if( $user )
            Users::displayUser( $user );
        else
            \cli\line('%rCould not authenticate...%n');
    }
}
