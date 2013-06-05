<?php
namespace Rocker\Utils;


/**
 * Class that manages runtime errors and exceptions
 *
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class ErrorHandler {

    /**
     * @var string
     */
    private $mode;

    /**
     * @param string $mode
     */
    public function __construct($mode='development')
    {
        $this->mode = $mode;
    }

    /**
     * @var bool
     */
    private static $initiated = false;

    /**
     * Setup handlers for errors and exception. Error will after this
     * automatically be transformed into thrown exceptions
     * @param array $config
     */
    public static function init($config)
    {
        if( !self::$initiated ) {
            self::$initiated = true;
            $handle = new self($config['mode']);
            set_error_handler(array($handle, 'error'));
            set_exception_handler(array($handle, 'exception'));
        }
    }

    /**
     * @param $number
     * @param $mess
     * @param $file
     * @param $line
     * @return bool
     * @throws \ErrorException
     */
    public function error($number, $mess, $file, $line)
    {
        // Don't care about minor errors unless we're in dev environment
        if( $this->mode != 'development' && in_array($number, array(E_STRICT, E_DEPRECATED, E_NOTICE, E_USER_NOTICE, E_USER_DEPRECATED))) {
            return true;
        }
        throw new \ErrorException($mess, $number, $number, $file, $line);
    }

    /**
     * @param \Exception $e
     * @param bool $terminate
     */
    public function exception(\Exception $e, $terminate = true)
    {
        $html_message = '';
        $id = hash('md4', $e->getMessage().$e->getFile().$e->getLine());

        if( !($e instanceof \ErrorException) ) {
            $html_message = '<strong>- Uncaught exception: </strong> '.get_class($e)."<br />\n";
        }

        $html_message .= '<strong>- Message:</strong> '.$e->getMessage();

        if( $this->mode == 'development' ) {
            $html_message .= "<br />\n<strong>- File:</strong> ".$e->getFile().
                "<br />\n<strong>- Line:</strong> ".$e->getLine().
                "<br />\n<strong>- Code:</strong> ".$e->getCode();
        }

        $html_message .="<br />\n<strong>- ID:</strong> $id";

        $log_body = trim(strip_tags($html_message));

        // If we're in production/test the html message will not
        // contain info about the file where the error appeared
        // therefor we add it to error log here
        if( $this->mode != 'development' ) {
            $log_body .= "\n- File: ".$e->getFile().
                "\n- Line: ".$e->getLine().
                "\n- Code: ".$e->getCode();
        }

        $log_message = '### Error '.( empty($_SERVER['REQUEST_URI']) ? '':$_SERVER['REQUEST_URI']).
            ( empty($_SERVER['REQUEST_METHOD']) ? '':' ('.$_SERVER['REQUEST_METHOD'].')').
            ( empty($_SERVER['REMOTE_ADDR']) ? '':' '.$_SERVER['REMOTE_ADDR']).
            "\n".$log_body.
            "\n".$e->getTraceAsString();

        if($this->mode == 'development') {
            $html_message .= "\n<p>".nl2br($e->getTraceAsString())."</p>\n";
        } else {
            $html_message .= "\n<p><em>Stack trace and full error message available in the error log</em></p>\n";
        }

        $html_message = '<h2>PHP Error</h2>' . $html_message;

        error_log($log_message);

        if( $terminate ) {
            if( !headers_sent() )
                header('HTTP/1.1 500 Internal Server Error');
            echo $html_message;
            die;
        }
    }

    /**
     * Send info about exception to error log
     * @param \Exception $e
     */
    public static function log(\Exception $e)
    {
        $handle = new self();
        $handle->exception($e, false);
    }
}