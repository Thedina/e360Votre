<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 12/2/2015
 * Time: 10:20 AM
 */

namespace eprocess360\v3core\Controller;


use AltoRouter;
use eprocess360\v3core\Request\Request;
use eprocess360\v3modules\Task\Model\Tasks;

/**
 * Class Router
 * @package eprocess360\v3core\Controller
 */
trait Router
{
    /** @var AltoRouter */
    protected $routes;
    private $routesInit = false;

    /**
     * Ready function for the Router Trait
     */
    public function traitRouterReady()
    {
        // nothing right now
    }

    /**
     * Prepares the Routes before the routes() functionality.
     * @return $this
     */
    public function routesInit()
    {
        /** @var Controller|Router $this */
        if ($this->routesInit) return $this;
        $path = $this->getPath(false);
        $this->routes = new AltoRouter([], ($path?'/':'').$path);
        $this->routeDependencies();
        /** @var Router $this */
        $this->routes();
        $this->routesInit = true;
        return $this;
    }

    /**
     * Defines the altoRouter Route Mappings.
     * // TODO Every Controller needs Routes() so if there is a Controller that does not use the trait Router, the controller will break. As such move in dependent Traits into the Controller base class
     */
    public function routes()
    {

    }

    /**
     * Things that will be required to make the render and data proper, ie. loading twig templates, setting meta, etc.
     * routesInit() will provide the static address.
     */
    public function routeDependencies()
    {

    }

    /**
     * Adds a route mapping to this controller's routes.
     * @param $method
     * @param $route
     * @param $target
     * @param null $name
     * @throws \Exception
     */
    public function addRoute($method, $route, $target, $name = null)
    {
        $this->routes->map($method, $route, $target, $name);
    }

    /**
     * Routes the Request. This function also adds a lot of Meta pertaining to this Controller...
     * It also does Children Routing, in which case if the Request can be passsed along to a child controller, it is.
     * @param Request $request
     * @return $this
     * @throws ControllerException
     * @throws \Exception
     */
    public function route(Request $request)
    {
        global $pool;
        $this->routesInit();
        /** @var Controller|Router|Children $this */
        /** @var Controller|ResponseHandler|Children $responseHandler */
        $responseHandler = $this->getClosest('ResponseHandler');
        $static = $this->getStaticPath();
        $meta = [
            'siteUrl'=>$pool->SysVar->siteUrl(),
            'apiPrefix'=>'/api/v'.API_VERSION,
            'static'=>$pool->SysVar->siteUrl().$static,
            'name'=>$this->getName(),
            'description'=>$this->getDescription(),
            'api'=>$this->getPath(),
            'path'=>$pool->SysVar->siteUrl().$this->getPath(),
            'apiPath'=>$pool->SysVar->siteUrl().'/api/v'.API_VERSION.$this->getPath(),
            'permissions'=>$this->getControllerPermissions()
        ];
        $parent = $this->getParent();
        if($parent !== NULL) {
            $meta['parentAPIPath'] = $pool->SysVar->siteUrl().'/api/v'.API_VERSION.$parent->getPath();
        }
        if($pool->User->isIdentified())
            $permissions = $pool->User;
        $responseHandler->addResponseMeta($this->getClass(), $meta);
        $responseHandler->addTwigPath(APP_PATH.$static.'/twig');
        $responseHandler->setResponseEnvelope(1);
        $responseHandler->setRequest($request);

        // HACK HACK HACK HACK TODO please kill me please (add num tasks to meta)
        if($pool->User->isIdentified()){
            $curMeta = $responseHandler->getResponseMeta();
            if(!isset($curMeta['taskCount'])) {
                $taskInfo = Tasks::getCount();
                $responseHandler->addResponseMeta('taskCount', $taskInfo['taskCount']);
            }
        }

        if (Request::get()->isAuthRequired()) {
            $responseHandler->setTemplate('SystemController.forbidden.html.twig');
            $responseHandler->setResponse(['errors'=>[['message'=>"You don't have permission to access this page."]]], 403);
            $responseHandler->addResponseMeta('ref',['href'=>$this->getPath()]);
            return $this;
        }

        $requestPath = Request::get()->getRequestPath();

        //TODO update Eula POST so that it doesn't send to /accept, but instead just to /eula. when that is done, remove the extra boolean statement here.
        if($pool->User->isIdentified() && !$pool->User->hasAcceptedEULA() && $requestPath != '/eula' && $requestPath != '/eula/accept' && $requestPath != '/logout'){
            header('Location: ' . $pool->SysVar->get('siteUrl') . '/eula');
            die();
        }

        if($string = rtrim($requestPath, '/'))
            $requestPath = $string;


        if ($this->uses('Children')) {
            // Children can catch and Route themselves by matching the first element from getPath()
            $getPath = $this->getPath();
            $position = 0;
            if (strpos($requestPath, $getPath) !== false)
                $position = strlen($getPath);
            $upperpath = substr($requestPath, $position);
            if ($upperpath[0] === '/')
                $upperpath = substr($upperpath, 1);
            $path = strtolower($upperpath);
            if (strpos($path, '/') !== false)
                $path = substr($path, 0, strpos($path, '/'));
            else if (strpos($path, '?') !== false)
                $path = substr($path, 0, strpos($path, '?'));
            if (is_numeric($path)) {
                $this->setObjectId((int)$path);
                $upperpath = substr($upperpath, strlen($path));
                if ($upperpath[0] === '/')
                    $upperpath = substr($upperpath, 1);
                $path = strtolower($upperpath);
                if (strpos($path, '/') !== false)
                    $path = substr($path, 0, strpos($path, '/'));
                else if (strpos($path, '?') !== false)
                    $path = substr($path, 0, strpos($path, '?'));
            }
            if (isset($this->children[$path])) {
                /** @var Controller $child */
                $child = $this->getChild($path);
                $child->ready()->run();
                return $this;
            }
        }

        try {
            // match current request url
            $match = $this->routes->match($requestPath, Request::get()->getRequestMethod());
            // call closure or throw error
            if( $match && is_callable( $match['target'] ) ) {
                call_user_func_array( $match['target'], $match['params'] );
            } elseif ($requestPath == rtrim(Request::get()->getRequestPath(), '/')) {
                throw new ControllerException("Route {$requestPath} unavailable.");
            }
        } catch (\Exception $e) {
            // something in the routing failed
            /** @var ResponseHandler $responseHandler */
            $responseHandler->setErrorResponse($e);
        }
        return $this;
    }
}