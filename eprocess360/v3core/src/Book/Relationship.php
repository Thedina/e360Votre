<?php
/**
 * Created by PhpStorm.
 * User: kiranebilak
 * Date: 2/18/16
 * Time: 12:37 PM
 */

namespace eprocess360\v3core\Book;


class Relationship
{

    private $name;

    /**
     * Index constructor.
     * @param array ...$keys
     */
    protected function __construct(...$keys)
    {
        $this->keys = $keys;
        $this->name = implode('_', $keys);
    }



    /**
     * @param $name
     * @return $this
     */
    public function identifiedBy($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
}