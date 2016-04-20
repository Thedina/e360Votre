<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 1/26/16
 * Time: 9:21 AM
 */

namespace eprocess360\v3core\Controller;


use eprocess360\v3modules\Toolbar\Toolbar;

/**
 * Class DashboardToolbar
 * @package eprocess360\v3core\Controller
 */
trait DashboardToolbar
{
    /**
     * @param Toolbar $toolbar
     * @param bool|true $isActive
     * @param bool|true $isAvailable
     */
    public function buildLinks(Toolbar $toolbar, $isActive = true, $isAvailable = true)
    {
        /** @var Controller $this */
        $title = $this->getDescription()?:$this->getStaticClass();
        $toolbar->addToolbarLink($title, $this->getPath(true, false), $isActive, $isAvailable);
    }

    /**
     * @param Toolbar $toolbar
     * @param Controller $child
     * @return bool
     */
    public function buildToolbarChildren(Toolbar $toolbar, Controller $child)
    {
        return false;
    }
}