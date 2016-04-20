<?php
/**
 * Created by PhpStorm.
 * User: kiranebilak
 * Date: 2/18/16
 * Time: 12:17 PM
 */

namespace eprocess360\v3core\Book;


class Entry
{
    protected $name;

    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Entry
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
}