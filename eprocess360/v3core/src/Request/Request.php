<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 12/2/2015
 * Time: 11:20 AM
 */

namespace eprocess360\v3core\Request;

use const eprocess360\v3core\API_PATH;
use eprocess360\v3core\Controller\Controller;

/**
 * Class Request
 * @package eprocess360\v3core\Request
 *
 * Singleton Request.
 */
class Request
{
    private static $instance;
    protected $requestBody;
    protected $requestPath;
    protected $requestExpectedResponseType = 'html';
    protected $authRequired = false;
    protected $requestHandler;
    protected $requestMethod;
    protected $responder;

    protected function __construct()
    {
        $this->setRequestMethod($_SERVER['REQUEST_METHOD']);
        $path = $this->getActualRequestPath();
        if (substr($path, 0, strlen(API_PATH)) === API_PATH) {
            $path = substr($path, strlen(API_PATH));
            $this->setRequestExpectedResponseType('json');
            $responseBody = json_decode(file_get_contents('php://input'), true);
            if($responseBody === NULL)
                $responseBody = $_REQUEST;
            $this->setRequestBody($responseBody);
        }
        $this->setRequestPath($path);
    }

    /**
     * @return Request
     */
    public static function get()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function getActualRequestPath()
    {
        return $_SERVER['REQUEST_URI']; // /something/12/something...
    }

    /**
     * @return mixed
     */
    public function getRequestBody()
    {
        return $this->requestBody;
    }

    /**
     * @param mixed $requestBody
     * @return $this
     */
    public function setRequestBody($requestBody)
    {
        $this->requestBody = $requestBody;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRequestExpectedResponseType()
    {
        return $this->requestExpectedResponseType;
    }

    /**
     * @param mixed $requestExpectedResponseType
     * @return $this
     */
    public function setRequestExpectedResponseType($requestExpectedResponseType)
    {
        $this->requestExpectedResponseType = $requestExpectedResponseType;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRequestPath()
    {
        return $this->requestPath;
    }

    /**
     * @param mixed $requestPath
     * @return $this
     */
    public function setRequestPath($requestPath)
    {
        $this->requestPath = $requestPath;
        return $this;
    }

    /**
     * Return the relative request path for a given path.
     * @param $basePath
     * @return string
     * @throws RequestException
     */
    public function forPath($basePath)
    {
        $path = $this->getRequestPath();
        if (substr($path,0,strlen($basePath)) == $basePath) {
            return substr($path,strlen($basePath));
        }
        throw new RequestException("Given path not found in request path.");
    }

    public function setAuthRequired($value)
    {
        $this->authRequired = $value;
    }

    /**
     * @return boolean
     */
    public function isAuthRequired()
    {
        return $this->authRequired;
    }

    /**
     * @return mixed
     */
    public function getRequestHandler()
    {
        return $this->requestHandler;
    }

    /**
     * @param mixed $requestHandler
     */
    public function setRequestHandler($requestHandler)
    {
        $this->requestHandler = $requestHandler;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRequestMethod()
    {
        return $this->requestMethod;
    }

    /**
     * @param mixed $requestMethod
     */
    public function setRequestMethod($requestMethod)
    {
        $this->requestMethod = $requestMethod;
        return $this;
    }

    /**
     * @param Controller $responder
     */
    public function setResponder(Controller $responder)
    {
        $this->responder = $responder;
    }

    /**
     * @return Controller
     */
    public function getResponder()
    {
        return $this->responder;
    }

}