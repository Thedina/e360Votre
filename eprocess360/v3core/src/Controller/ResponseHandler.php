<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 12/2/2015
 * Time: 10:41 AM
 */

namespace eprocess360\v3core\Controller;
use eprocess360\v3core\Request\Request;
use eprocess360\v3modules\Toolbar\Toolbar;

/**
 * Response handler.  Any Controller that has this trait will be able to handle the response back to the Client
 * Class Responses
 * @package eprocess360\v3core\Controller
 */
trait ResponseHandler
{
    protected $responseExpires;
    protected $responseCode;
    protected $responseResult;
    protected $responseTemplate = ['server'=>['SystemController.hb.base.html.twig'],'client'=>[]];
    protected $responseMeta = [];
    protected $responseType;
    protected $responseEnvelope;
    protected $responseError = [];
    protected $responseRequest;

    /**
     * Sets the Response type, such as 'html' or 'json'
     * @param $responseType
     */
    public function setResponseType($responseType)
    {
        $this->responseType = $responseType;
    }

    /**
     * Sets the Reponse Request.
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->responseRequest = $request;
        $this->setResponseType($request->getRequestExpectedResponseType());
    }

    /**
     * Set the Model (data) component of the response
     * @param array|object $response
     * @param int $status
     * @param bool $envelope whether to put the data in an envelope
     * @return $this
     */
    public function setResponse($response, $status = 200, $envelope = false)
    {
        // html, json okay
        $this->responseResult = $response;
        $this->responseEnvelope = $envelope;
        $this->responseCode = $status;
        return $this;
    }

    /**
     * Adds a key->entry to the response Meta.
     * @param string $namespace
     * @param array $meta
     */
    public function addResponseMeta($namespace, $meta)
    {
        $this->responseMeta = array_merge([$namespace=>$meta], $this->responseMeta);
    }

    /**
     * Adds new data to an existing meta namespace
     * @param $namespace
     * @param $meta
     */
    public function extendResponseMeta($namespace, $meta) {
        $this->responseMeta[$namespace] = array_merge($this->responseMeta[$namespace], $meta);
    }

    /**
     * Adds a twig path to the global twig_loader.
     * @param $twigPath
     * @throws \Twig_Error_Loader
     */
    public function addTwigPath($twigPath)
    {
        if (file_exists($twigPath)) {
            global $twig_loader;
            $twig_loader->addPath($twigPath);
        }
    }

    /**
     * Gets the Response Meta
     * @return array
     */
    public function getResponseMeta()
    {
        return $this->responseMeta;
    }

    /**
     * Sets an error response with a given exception.
     * @param \Exception $exception
     * @return $this
     */
    public function setErrorResponse(\Exception $exception)
    {
        $this->responseError[] = $exception;
        return $this;
    }

    /**
     * The template to use when responding to HTML requests.  There are two levels of templates: server and client.
     * Only one template for each may be specified.  Automatic templates will be applied when possible.
     * @param $template
     * @param bool $mode
     * @param $handler
     * @return $this
     */
    public function setTemplate($template, $mode = false, $handler = false)
    {
        /** @var ResponseHandler $this */
        if(!$handler){
            $handler = $this;
        }

        if (!$mode) {
            // We can recognize a twig template because it has twig in the name.
            if (is_string($template) && strpos($template, 'twig')) {
                $mode = 'server';
            } else {
                $mode = 'client';
            }
        }
        if ($mode === 'client') {
            if (is_string($template)) {
                $template = [$template];
            }
            foreach ($template as &$t) {
                if(strpos($t, APP_PATH) !== 0) {
                    // client-side templates require the full path to be available to the Controller
                    /** @var Controller $this */
                    $t = APP_PATH . $handler->getStaticPath() . '/handlebars/' . $t;
                }
            }
            $this->responseTemplate[$mode] = array_merge($this->responseTemplate[$mode], $template);
        }
        else {
            $this->responseTemplate[$mode] = $template;
        }
        return $this;
    }

    /**
     * Echo data back to the client.  If HTML, the response will echo a template.  Otherwise, the response is the raw
     * JSON result.
     */
    public function render()
    {
        global $twig, $pool;
        //set headers to NOT cache a page
        if ($this->responseExpires ) {
            header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
            header("Pragma: no-cache"); //HTTP 1.0
            header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        } else {
            header("Cache-Control: max-age=2592000");
        }
        $this->addResponseMeta('User',[
            'idUser'=>$pool->User->isIdentified() ? $pool->User->getIdUser() : 0,
            'name'=>$pool->User->isIdentified() ? $pool->User->getFullName() : '',
            'apiPath'=>$pool->SysVar->siteUrl().'/api/v'.API_VERSION.'/profile',
            'status'=>$pool->User->isIdentified() ? $pool->User->isMustChangePassword() : false
        ]);

        //TODO Try and do this more efficiently
        /** @var Project|Controller|Children $project */
        $project = Request::get()->getResponder()->getClosest('Project', false, false);
        if($project && $project->hasObjectId()) {
            //TODO Add $toolbar to Controller or Project
            /** @var Module|Controller|Toolbar $toolbar */
            if($toolbar = $project->toolbar) {
                $static = $toolbar->getStaticPath();
                $this->addTwigPath(APP_PATH . $static);
                $this->addResponseMeta('projtool', $toolbar->execClosure()->getToolbar());
            }
        }
        else{
            $controller = Request::get()->getResponder();
            if(!(get_class($controller->getParent())==="App\\System" && !$controller->uses('Auth'))) {
                /** @var Toolbar $toolbar */
                $toolbar = Toolbar::buildDashboardBar($controller);
                $this->addResponseMeta('dashboardBar', $toolbar->getToolbar());
            }
        }
        if (sizeof($this->responseError)) {
            /** @var \Exception $e */
            foreach ($this->responseError as $e)
                $this->responseResult['errors'][] = [
                    'message'=>$e->getMessage(),
                    'traceAsString'=>$e->getTraceAsString()
                ];
            $this->responseCode = 500;
            $this->responseTemplate = ['server'=>'SystemController.error.html.twig','client'=>[]];
        }
        http_response_code($this->responseCode);
        switch ($this->responseType) {
            case 'html':
                $response = $this->responseResult;
                $response['meta'] = $this->responseMeta;
                $pool->add($response, 'Response');
                $data = $pool->asArray();
                if (is_array($this->responseTemplate)) {
                    $template = $this->responseTemplate['server'];
                    $data['Template'] = '';
                    foreach ($this->responseTemplate['client'] as $t) {
                        // We need to load these templates so the client-side can use them
                        $data['Template'] .= file_get_contents($t).PHP_EOL;
                    }
                } else {
                    $template = $this->responseTemplate;
                }
                if(is_array($template)) {
                    $template = array_shift($template);
                }
                echo $twig->render($template, $data);
                break;
            case 'json':
                header('Content-type: application/json');
                if (is_object($this->responseResult) && $this->responseResult->getData()) {
                    $this->responseResult = $this->responseResult->getData();
                }
                if (1||$this->responseEnvelope) {
                    $this->responseResult['errors'] = isset($this->responseResult['errors']) ? $this->responseResult['errors'] : [];
                    $this->responseResult['data'] = isset($this->responseResult['data']) ? $this->responseResult['data'] : [];
                    $this->responseResult['meta'] = $this->responseMeta;
                }
                echo json_encode($this->responseResult);
                break;
        }
    }

    /**
     * Gets the Response type, such as 'html' or 'json'
     * @return mixed
     */
    public function getResponseType()
    {
        return $this->responseType;
    }

    /**
     * Gets the Response type, such as 'html' or 'json'
     * @return mixed
     */
    public function getResponseResult()
    {
        return $this->responseResult;
    }

    /**
     * Gets the Response Envelope, which determines if on render the response is wrapped into an envelope.
     * @return mixed
     */
    public function getResponseEnvelope()
    {
        return $this->responseEnvelope;
    }

    /**
     * Sets the Response Envelope, which determines if on render the response is wrapped into an envelope.
     * @param mixed $responseEnvelope
     */
    public function setResponseEnvelope($responseEnvelope)
    {
        $this->responseEnvelope = $responseEnvelope;
    }

    /**
     * Gets the Response Errors.
     * @return array
     */
    public function getResponseErrors()
    {
        return $this->responseError;
    }
}