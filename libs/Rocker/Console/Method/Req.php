<?php
namespace Rocker\Console\Method;


/**
 * Console method used to do a request to remote Rocker server
 *
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class Req implements MethodInterface {

    /**
     * Output info about how to use this method
     */
    public function help()
    {
        $_ = function($str) { \cli\line($str); };
        $_('%_Method - req%n');
        $_('---------------------------------');
        $_('Method used for requesting a remote server');
        $_("  $ rocker req -p system/version");
        $_("  $ rocker req -p system/version --v    (include heades in response)");
        $_("  $ rocker req -p cache/clear -X POST   (Send a POST request)");
        $_("  $ rocker req -p admin -X POST -d 'admin=0&user=239'");
    }

    /**
     * @param array $args
     * @param array $flags
     */
    public function call($args, $flags)
    {
        $client = Server::loadClient($args);
        if( empty($args['-p']) ) {
            \cli\err('Parameter -p is required (path to request)');
        }

        $response = $client->request(
                    isset($args['-X']) ? $args['-X']:'GET',
                    trim($args['-p'], '/'),
                    isset($args['-d']) ? parse_str($args['-d']):array(),
                    true
                );

        \cli\line('%_Status:%n '.$response->status);
        if( in_array('--v', $flags) ) {
            \cli\line('%_Headers:%n ');
            foreach($response->headers as $name => $val) {
                \cli\line("   $name: $val ");
            }
        }
        \cli\line('%_Body:%n '.PHP_EOL.print_r($response->body,true));
        //var_dump($response);
    }
}
