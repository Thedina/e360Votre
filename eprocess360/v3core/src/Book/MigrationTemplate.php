<?php
/**
 * Created by PhpStorm.
 * User: kiranebilak
 * Date: 2/18/16
 * Time: 1:44 PM
 */

namespace eprocess360\v3core\Book;


class MigrationTemplate
{
    private $data;
    private $up;

    public function up(Migration $migration)
    {

    }

    public function down(Migration $migration)
    {

    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function __get($name)
    {
        return $this->data[$name];
    }

    public function getData()
    {
        return $this->data;
    }

    public function isUp()
    {
        return $this->up;
    }

    public function doUp(Migration $migration)
    {
        $migration->setCurrentOperation($this);
        $this->up($migration);
        $migration->addOperation($this);
        $this->up = true;
    }

    public function doDown(Migration $migration)
    {
        $migration->setCurrentOperation($this);
        $this->down($migration);
        // Custom rollback methods here
        $file = $this->getData(); $file = $file['file'];
        $migration->rollbackMethods($file);
        $migration->rollbackTraits($file);
        $migration->addOperation($this);
        $this->up = false;
    }
}