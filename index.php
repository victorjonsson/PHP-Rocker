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

// Output the auto-generated documentation
$docsURI = $config['application.path'] == '/' ? '/docs':'/';
$server->get($docsURI, function() use($config, $server) {

    $converter = new \Rocker\Utils\XML\ArrayConverter();
    $generator = new \Rocker\REST\Documentation\Generator();

    // Generate documentation and convert it to an array
    $documentationXML = $generator->generateDocumentation($config['application.operations']);

    // Add $documentation to current scope, later used in the template
    $documentation = $converter->convertXMLToArray($documentationXML);

    // Generate the documentation website
    $template = $generator->getExampleTemplatePath();
    require $template;
});


// Run forrest run
$server->run();