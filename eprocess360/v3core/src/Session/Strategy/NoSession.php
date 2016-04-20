<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 7/20/2015
 * Time: 9:58 PM
 */

namespace eprocess360\v3core\Session\Strategy;
use eprocess360\v3core\Session;


/**
 * Class NoSession
 * @package kirakero\doujinpressv2\Session\Strategy
 */
class NoSession implements SessionStrategy
{
    /**
     * @param Session $session
     * @return Session
     */
    public static function onSessionFailure(Session $session)
    {
        Session::cleanSessionCookies();
        return $session;
    }
}