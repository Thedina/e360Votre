<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 12/9/15
 * Time: 8:54 AM
 */

namespace eprocess360\v3controllers\EULA;


use eprocess360\v3core\Controller\Auth;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Form;

/**
 * Class EULA
 * @package eprocess360\v3controllers\EULA
 */
class EULA extends Controller
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
            $this->getEulaAPI();
        });
        $this->routes->map('POST', '/accept', function () {
            $this->postEulaAPI();
        });
    }

    /**
     * Gets the EULA. If already accepted, header to Dashboard.
     * @throws \Exception
     */
    public function getEulaAPI()
    {
        global $pool;
        if ($pool->User->isValid() && $pool->User->hasAcceptedEULA()) {
            header('Location: ' . $pool->SysVar->get('siteUrl') . '/dashboard');
            die();
        }

        $form = Form::build(0, 'eula', 'EULA')->setPublic(true);

        $response = $this->getResponseHandler();
        $response->setTemplate('EULA.main.html.twig');
        $response->setResponse(['Form'=>$form]);
    }

    /**
     * Gets the EULA. If already accepted, header to Dashboard.
     * @throws \Exception
     */
    public function postEulaAPI()
    {
        global $pool;

        if ($pool->User->isValid() && $pool->User->hasAcceptedEULA()) {
            header('Location: ' . $pool->SysVar->get('siteUrl') . '/dashboard');
            die();
        }

        $form = Form::build(0, 'eula', 'EULA')->setPublic(true);

        if ($_POST['submitform'] == 'true' && $_POST['iseula'] == 'true') {
            $pool->User->setAcceptedEULA();
            header('Location: ' . $pool->SysVar->get('siteUrl') . '/dashboard');
            die();
        }

        $response = $this->getResponseHandler();
        $response->setTemplate('EULA.main.html.twig');
        $response->setResponse(['Form'=>$form]);
    }
}