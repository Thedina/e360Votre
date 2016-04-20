<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 12/9/15
 * Time: 9:38 AM
 */

namespace eprocess360\v3controllers\Logout;


use eprocess360\v3core\Controller\Auth;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\User;

/**
 * Class Logout
 * @package eprocess360\v3controllers\Logout
 */
class Logout extends Controller
{
    use Router, Auth;


    /*********************************************   #ROUTING#  **********************************************/


    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     */
    public function routes()
    {
        $this->routes->map('GET', '', function () {
            $this->getLogoutAPI();
        });
    }

    public function getLogoutAPI()
    {
        global $pool;

        $pool->Session->kill();
        $pool->add(User::getEmpty(), 'User');

        $response = $this->getResponseHandler();
        $response->setTemplate('Logout.main.html.twig');
    }

}