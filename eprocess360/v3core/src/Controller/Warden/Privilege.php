<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 12/18/2015
 * Time: 12:11 PM
 */

namespace eprocess360\v3core\Controller\Warden;


class Privilege
{
    const ROLE_READ = 1;        // Role to use for Read Only users;         Read
    const ROLE_WRITE = 3;       // Role to use for Write users;             Read,Write
    const ROLE_CREATE = 5;      // Role to use for Creators (ie Applicants);Read,Create
    const ROLE_DELETE = 9;      // Role to use for deletion;                Read,Delete
    const ROLE_ADMIN = 31;      // Role to use for admin;                   Read,Write,Create,Delete,Admin

    const READ = 1;
    const WRITE = 2;
    const CREATE = 4;
    const DELETE = 8;
    const ADMIN = 16;

    private $value = 0;

    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * @param int $value
     */
    public function set($value)
    {
        $this->value = $value;
    }


}