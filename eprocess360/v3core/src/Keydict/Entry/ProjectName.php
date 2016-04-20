<?php
/**
 * Created by PhpStorm.
 * User: kira
 * Date: 8/5/2015
 * Time: 11:30 AM
 */

namespace eprocess360\v3core\Keydict\Entry;


use eprocess360\v3core\DB;

class ProjectName extends String
{

    public static function getData()
    {
        return [
            'type'=>DB::VARCHAR,
            'length'=>128
        ];
    }
}