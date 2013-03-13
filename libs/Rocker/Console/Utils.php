<?php
namespace Rocker\Console;


/**
 * General utility functions used when writing console programs
 *
 * @package Rocker\Console
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class Utils {

    /**
     * @param string $prompt
     * @return string
     */
    public static function promptPassword($prompt = 'Enter Password: ')
    {
        if (preg_match('/^win/i', PHP_OS)) {
            $vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
            file_put_contents(
                $vbscript, 'wscript.echo(InputBox("'
                . addslashes($prompt)
                . '", "", "password here"))');
            $command = "cscript //nologo " . escapeshellarg($vbscript);
            $password = rtrim(shell_exec($command));
            unlink($vbscript);
            return $password;
        } else {
            $command = "/usr/bin/env bash -c 'echo OK'";
            if (rtrim(shell_exec($command)) !== 'OK') {
                trigger_error("Can't invoke bash");
                return \cli\prompt($prompt, false, '');
            }
            $command = "/usr/bin/env bash -c 'read -s -p \""
                . addslashes($prompt)
                . "\" mypassword && echo \$mypassword'";
            $password = rtrim(shell_exec($command));
            echo "\n";
            return $password;
        }
    }

    /**
     * Same as \cli\prompt except that it allows empty input
     * @param string $question
     * @param bool $default
     * @param string $marker
     * @return string
     */
    public static function promptAllowingEmpty( $question, $default = false, $marker = ': ' )
    {
        if( $default && strpos( $question, '[' ) === false ) {
            $question .= ' [' . $default . ']';
        }

        while( true ) {
            \cli\Streams::out( $question . $marker );
            $line = \cli\Streams::input();
            return $line;
        }
    }

    /**
     * @param array $arguments
     * @return array
     */
    public static function parseInput($arguments)
    {
        $flags = array();
        $args = array();
        $currentArg = false;
        foreach($arguments as $argument) {
            if( $argument != 'php' && $argument != '-f' ) {
                if(strpos($argument, '-') === 0) {
                    $currentArg = $argument;
                }
                elseif($currentArg !== false) {
                    $args[$currentArg] = $argument;
                    $currentArg = false;
                }
                else {
                    $flags[] = $argument;
                }
            }
        }
        if( $currentArg ) {
            $flags[] = $currentArg;
        }

        array_splice($flags, 0, 1); // remove console name
        $method = current(array_splice($flags, 0, 1));
        return array($flags, $args, $method);
    }
}