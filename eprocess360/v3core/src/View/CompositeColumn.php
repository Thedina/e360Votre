<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 9/15/2015
 * Time: 11:16 AM
 */

namespace eprocess360\v3core\View;


use eprocess360\v3core\Keydict\Field;

/**
 * Class CompositeColumn
 * @package eprocess360\v3core\View
 * @deprecated
 */
class CompositeColumn extends Column
{

    /**
     * @param $name
     * @param $label
     * @param $fields
     * @param $handler
     * @return Column
     */
    public static function build ($name, $label, \Closure $handler = null, Field ...$fields)
    {
        return new static();
    }
}