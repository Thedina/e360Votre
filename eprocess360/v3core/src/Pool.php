<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 7/21/2015
 * Time: 5:07 PM
 */

namespace eprocess360\v3core;
use Exception;


/**
 * @property  \Twig_Environment Twig
 * @property  \Closure middleware_logout
 * @property  User User
 * @property  \eprocess360\v3core\SysVar SysVar
 * @property  \eprocess360\v3core\Project Project
 * @property  (object)[] Temp
 */
class Pool
{
    private static $instance;

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return Pool The *Singleton* instance.
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function evaluate($templateString)
    {
        global $twigEnvArray, $pool;

        if (!is_object($twigEnvArray)) {
            $twigEnvArray = new \Twig_Environment(new \Twig_Loader_Array([]));
        }

        $template = $twigEnvArray->createTemplate($templateString);

        return $template->render($this->asArray());
    }

    public function asArray()
    {
        return $this->pool;
    }

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    protected function __construct()
    {
        $this->pool = [];
    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * *Singleton* instance.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Private unserialize method to prevent unserializing of the *Singleton*
     * instance.
     *
     * @return void
     */
    private function __wakeup()
    {
    }

    public function add($object, $key) {
        $this->pool[$key] = $object;
    }

    public function __isset($key) {
        return isset($this->pool[$key]);
    }

    public function __get($key) {
        if (!$this->__isset($key)) throw new Exception();
        return $this->pool[$key];
    }
}