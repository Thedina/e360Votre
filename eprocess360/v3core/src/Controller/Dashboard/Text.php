<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 1/26/16
 * Time: 3:42 PM
 */

namespace eprocess360\v3core\Controller\Dashboard;


/**
 * Class Text
 * @package eprocess360\v3core\Controller\Dashboard
 */
class Text extends DashBlock {
    protected $text;

    /**
     * @return mixed
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param mixed $text
     * @return Text
     */
    public function setText($text)
    {
        $this->text = $text;
        return $this;
    }
}