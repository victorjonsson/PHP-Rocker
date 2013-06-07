<?php
namespace Rocker\REST;

use Guzzle\Http\Message\Response;


/**
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class ClientException extends \Exception {

    const ERR_UNEXPECTED_CONTENT_TYPE = 12009;

    /**
     * @var Response
     */
    private $response;

    /**
     * @param Response $response
     * @param int $code
     * @param string $message
     * @param null $previous
     */
    function __construct($response, $code=0, $message='Remote server gave an error response', $previous=null)
    {
        $this->response = $response;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return Response
     */
    public function response()
    {
        return $this->response;
    }
}