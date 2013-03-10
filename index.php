<?php
/**
 * PHP Rocker
 * ---------------------------------
 *
 * @package Rocker
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license GPL2 (http://www.gnu.org/licenses/gpl-2.0.html)
 */


// Composer class loader
require __DIR__.'/vendor/autoload.php';

// Load config
$config = require __DIR__.'/config.php';

// Initiate server
$server = new \Rocker\Server($config);

// Welcome page
$server->get('/', function() use($config, $server) {

    $db = \Rocker\Object\DB::instance($config['application.db']);
    $uf = new \Rocker\Object\User\UserFactory($db);

    var_dump($uf->metaSearch(array(
                'aa' => 'bb',
                array('AND' => array('country'=>array('Sweden','France')))
            ))->getObjects());

});

// Run forrest run
$server->run();