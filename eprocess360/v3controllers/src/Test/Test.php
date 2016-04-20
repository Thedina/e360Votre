<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 12/11/15
 * Time: 12:27 PM
 */

namespace eprocess360\v3controllers\Test;


use eprocess360\v3core\Controller\Auth;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Router;

/**
 * Class Test
 * @package eprocess360\v3controllers\Test
 */
class Test extends Controller
{
    use Router, Auth;

    /*********************************************   #ROUTING#  **********************************************/


    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     */
    public function routes()
    {
        $this->routes->map('GET', '/databaseSetup', function () {
            require_once('eprocess360/v3controllers/src/Test/temp/databaseSetup.php');
        });
        $this->routes->map('GET', '/SubmittalsTest', function () {
            require_once('eprocess360/v3controllers/src/Test/temp/SubmittalsTest.php');
        });
        $this->routes->map('GET', '/GroupTest', function () {
            require_once('eprocess360/v3controllers/src/Test/temp/GroupTest.php');
        });
        $this->routes->map('GET', '/ReviewTaskTest', function () {
            require_once('eprocess360/v3controllers/src/Test/temp/ReviewTaskTest.php');
        });
        $this->routes->map('GET', '/emailTemplates', function() {
            require_once('eprocess360/v3controllers/src/Test/temp/emailTemplates.php');
        });
        $this->routes->map('GET', '/yosemiteUsers', function() {
            require_once('eprocess360/v3controllers/src/Test/temp/yosemiteUsers.php');
        });
    }
}