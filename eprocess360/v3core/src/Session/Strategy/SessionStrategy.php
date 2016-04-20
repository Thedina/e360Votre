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
 * Interface SessionStrategy
 */
interface SessionStrategy
{
    /**
     * @param Session $session
     * @return mixed
     */
    public static function onSessionFailure(Session $session);
}