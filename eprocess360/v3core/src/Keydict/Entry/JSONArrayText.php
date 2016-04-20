<?php

namespace eprocess360\v3core\Keydict\Entry;


use eprocess360\v3core\Keydict\Entry;
use eprocess360\v3core\DB;

class JSONArrayText extends Entry
{
    public function __construct($name, $label, $default = null)
    {
        parent::__construct($name, $label, $default);
        $this->specification = [
            'type'=>DB::TEXT
        ];
    }

    public function wakeup($value)
    {
        $this->value = json_decode($value, true);
        return $this;
    }

    public function sleep($depth = 0)
    {
        return json_encode($this->value);
    }

    public function cleanSleep($depth = 0)
    {
        $val = DB::cleanse(json_encode($this->value));
        return "'{$val}'";
    }

}