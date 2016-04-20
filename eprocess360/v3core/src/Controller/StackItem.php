<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/7/2015
 * Time: 5:45 PM
 */

namespace eprocess360\v3core\Controller;

/**
 * Class StackItem
 * A simple wrapper for items that need to go on the Controller Stack.  This Class would be a good place to tap into the
 * exec() stream and log it into the database if needed.
 * @package eprocess360\v3core\Controller
 */
class StackItem
{
    protected $closure;

    /**
     * @param \Closure $closure
     * @param mixed ...$parameters
     */
    public function __construct(\Closure $closure, ...$parameters)
    {
        $this->closure = $closure;
        $this->parameters = $parameters;
    }

    /**
     * @return mixed
     */
    public function exec()
    {
        $closure = $this->closure;
        return $closure(... $this->parameters);
    }

    /**
     * @return \Closure
     */
    public function getClosure()
    {
        return $this->closure;
    }

    /**
     * @param \Closure $closure
     */
    public function setClosure($closure)
    {
        $this->closure = $closure;
    }
}