<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 2/16/16
 * Time: 8:50 AM
 */

namespace eprocess360\v3core\Controller\Dashboard;


/**
 * Class Button
 * @package eprocess360\v3core\Controller\Dashboard
 */
class Button
{
    public $title;
    public $description;
    public $icon;
    public $link;
    public $group;

    /**
     * @param $dashboardTitle
     * @param $dashboardDescription
     * @param $dashboardIcon
     * @param $dashboardLink
     * @param $dashboardGroup
     * @return Buttons
     */
    public static function build($dashboardTitle, $dashboardDescription, $dashboardIcon, $dashboardLink, $dashboardGroup)
    {
        $button = new self;
        $button->title = $dashboardTitle;
        $button->description = $dashboardDescription;
        $button->icon = $dashboardIcon;
        $button->link = $dashboardLink;
        $button->group = $dashboardGroup;

        return $button;
    }

}