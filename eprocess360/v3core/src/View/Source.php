<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 9/15/2015
 * Time: 12:11 PM
 */

namespace eprocess360\v3core\View;


/**
 * Class Source
 * @package eprocess360\v3core\View
 * @deprecated
 */
class Source
{
    /**
     * @return string[] The names of all the views this class supports
     */
    public function allViews()
    {
        $out = [];
        foreach (get_class_methods($this) as $method) {
            if (substr($method,0,4) == 'view') {
                $out = $method;
            }
        }
        return $out;
    }

}