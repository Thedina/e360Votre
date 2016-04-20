<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 12/9/15
 * Time: 8:34 AM
 */

namespace eprocess360\v3controllers\Site404Handler;


use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Router;

/**
 * Class Site404Handler
 * @package eprocess360\v3controllers\Site404Handler
 */
class Site404Handler extends Controller
{
    use Router;


    /*********************************************   #ROUTING#  **********************************************/


    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     */
    public function routes()
    {
        $this->routes->map('GET', '', function () {
            $this->get404API();
        });
    }

    public function get404API()
    {
        echo '<h2>404 Not Found</h2>This page doesn\'t exist.';
        die();
    }
}