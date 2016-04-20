<?php

namespace eprocess360\v3core\Keydict\Entry;
use eprocess360\v3core\Keydict\Entry;
use eprocess360\v3core\DB;


class IntList extends Entry
{
    public function __construct($name, $label, $default = NULL) {
        parent::__construct($name, $label, $default);
        $this->specification = [
            'type'=>DB::TEXT
        ];
    }

    public function wakeup($value) {
        $this->value = explode(',', $value);
        return $this;
    }

    public function sleep($depth = 0) {
        return implode(',', array_map(function($val) {return (int)$val;}, $this->value));
    }
}