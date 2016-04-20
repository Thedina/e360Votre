<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 12/8/2015
 * Time: 8:12 AM
 */

namespace eprocess360\v3core\Controller;
use eprocess360\v3core\Request\Request;
use eprocess360\v3core\Session;
use eprocess360\v3core\Updater\Response;


/**
 * Class Auth
 * @package eprocess360\v3core\Controller
 *
 * Protects an endpoint or Controller with authentication by requiring user credentials
 */
trait Auth
{
    /**
     * Ready function for Auth, checks User is identified. If not, setAuthRequired(true), which then sends the user to a forbidden page during route().
     */
    public function traitAuthReady()
    {
        $this->authInit();
        global $pool;
        if (!$pool->User->isIdentified()) {
            /** @var Controller $this */
            Request::get()->setAuthRequired(true);
        }
    }

    /**
     * Init Function for Auth, which gets the current session and session User and adds them to the global Pool.
     */
    public function authInit()
    {
        global $pool;
        $session = Session::getInstance();
        $pool->add($session, 'Session');
        $pool->add($session->user, 'User');
    }
}