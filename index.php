<?php
/**
 * PHP Rocker
 * ---------------------------------
 *
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */


// Composer class loader
require __DIR__.'/vendor/autoload.php';

// Load config
$config = require __DIR__.'/config.php';

// Initiate server
$server = new \Rocker\Server($config);

// Welcome page
if( $config['application.path'] != '/' ) {
    $server->get('/', function() use($config, $server) {

        $apiURL = $server->request()->getHost() .
            $server->request()->getPath() .
            trim($config['application.path'],'/').
            '/operations';

        printf('<h1>Rocker Rest Server v%s</h1>
        <p>Take a look at available operations at <a href="http://%s">http://%s</a></p>',
            \Rocker\Server::VERSION,
            $apiURL,
            $apiURL
        );

    });
}

// Run forrest run
$server->run();