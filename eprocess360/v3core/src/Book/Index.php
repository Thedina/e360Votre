<?php
/**
 * Created by PhpStorm.
 * User: kiranebilak
 * Date: 2/18/16
 * Time: 12:37 PM
 */

namespace eprocess360\v3core\Book;


class Index
{
    private $keys;
    private $primaryKey;
    private $increment;
    private $unique;
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

    public static function primary(...$keys)
    {
        $index = new self(...$keys);
        $index->setPrimaryKey();
        $index->increment();
        return $index;
    }

    public static function unique(...$keys)
    {
        $index = new self(...$keys);
        $index->setUnique();
        return $index;
    }

    public static function plain(...$keys)
    {
        $index = new self(...$keys);
        return $index;
    }

    /**
     * @return mixed
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * @param bool $primaryKey
     * @return $this
     */
    public function setPrimaryKey($primaryKey = true)
    {
        $this->primaryKey = $primaryKey;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIncrement()
    {
        return $this->increment;
    }

    /**
     * @param bool $increment
     * @return $this
     */
    public function increment($increment = true)
    {
        $this->increment = $increment;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUnique()
    {
        return $this->unique;
    }

    /**
     * @param bool $unique
     * @return $this
     */
    public function setUnique($unique = true)
    {
        $this->unique = $unique;
        return $this;
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