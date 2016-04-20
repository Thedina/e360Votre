<?php
/**
 * Created by PhpStorm.
 * User: kira
 * Date: 8/5/2015
 * Time: 11:30 AM
 */

namespace eprocess360\v3core\Keydict\Entry;

use eprocess360\v3core\DB;
use eprocess360\v3core\Keydict\Exception\InvalidValueException;
use Exception;

/**
 * Class Password
 * @package eprocess360\v3core\Keydict\Entry
 */
class Password extends String
{
    protected $form_type = 'password';

    /**
     * @param string $name
     * @param string $label
     * @param null $default
     */
    public function __construct($name, $label, $default)
    {
        parent::__construct($name, $label, $default);
        $this->specification = [
            'type'=>DB::VARCHAR,
            'length'=>128
        ];
    }

    /**
     * @param $value
     * @return string
     * @throws Exception
     */
    public static function validate($value)
    {
        global $pool;
        $cleanvalue = parent::validate($value);
        $password_length = strlen($cleanvalue);
        if ($password_length < $pool->SysVar->get('passwordMinLength')) {
            throw new InvalidValueException("Password is too short.", 302);
        }
        if ($password_length > $pool->SysVar->get('passwordMaxLength')) {
            throw new InvalidValueException("Password is too long.", 303);
        }
        if ($pool->SysVar->get('passwordRequiresLetter') && !preg_match('/[a-zA-Z]/', $cleanvalue)) {
            throw new InvalidValueException("Password must contain a letter.", 304);
        }
        if ($pool->SysVar->get('passwordRequiresNumber') && !preg_match('/[0-9]/', $cleanvalue)) {
            throw new InvalidValueException("Password must contain a number.", 305);
        }
        return (string)$cleanvalue;
    }

    /**
     * @return bool
     */
    public static function hasValidate()
    {
        return true;
    }

    public function setEmpty()
    {
        $this->value = '';
    }

}