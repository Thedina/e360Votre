<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 1/26/16
 * Time: 3:42 PM
 */

namespace eprocess360\v3core\Controller\Dashboard;


/**
 * Class DashBlock
 * @package eprocess360\v3core\Controller\Dashboard
 */
class DashBlock {
    public $title;
    public $width;
    public $widthLength;
    public $template;

    /**
     * @param \eprocess360\v3core\Controller\Controller $controller
     * @return $this
     */
    public function from(\eprocess360\v3core\Controller\Controller $controller)
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function hide()
    {
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     * @return DashBlock
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getWidth()
    {
        return $this->title;
    }

    /**
     * @param boolean $width
     * @return DashBlock
     */
    public function setWidth($width)
    {
        $this->width = $width ? "col-sm-12" : "col-sm-6";
        $this->widthLength = $width ? 100 : 50;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param $template
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @param $title
     * @param null $template
     * @param bool|true $width
     * @return static
     */
    public static function build($title, $template = NULL, $width = true)
    {
        $block = new static;
        $block->setTitle($title);
        $block->setWidth($width);
        if($template !== NULL)
            $block->setTemplate($template);
        return $block;
    }
}
