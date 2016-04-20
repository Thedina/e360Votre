<?php

namespace eprocess360\v3core\Keydict\Entry;
use eprocess360\v3core\Keydict\Entry;
use eprocess360\v3core\DB;

class Datetime extends Entry
{
    /**
     * @param string $name
     * @param string $label
     * @param null $default
     */
    public function __construct($name, $label, $default = null)
    {
        parent::__construct($name, $label, $default);
        $this->specification = [
            'type'=>DB::DATETIME
        ];
    }

    public static function validate($value)
    {
        if($value != NULL) {
            $value = date('Y-m-d H:i:s', strtotime($value));
        }
        return parent::validate($value);
    }

    public static function hasValidate() {
        return true;
    }

    public function sleep($depth = 0) {
        return ($this->value == NULL ? NULL : (string)$this->value);
    }

    public function cleanSleep($depth = 0) {
        return ($this->value == NULL ? "NULL" : "'".DB::cleanse((string)$this->value)."'");
    }

    public static function timestamps() {
        return date(Datetime::format());
    }

    public static function format() {
        return 'Y-m-d H:i:s';
    }

}