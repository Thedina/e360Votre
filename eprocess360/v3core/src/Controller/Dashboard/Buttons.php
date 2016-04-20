<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 1/26/16
 * Time: 3:41 PM
 */

namespace eprocess360\v3core\Controller\Dashboard;


/**
 * Class Buttons
 * @package eprocess360\v3core\Controller\Dashboard
 */
class Buttons extends DashBlock
{
    public $buttons = [];
    public $template = "Block.Buttons.html.twig";

    /**
     * @param Button[] ...$buttons
     */
    public function addButton(Button ...$buttons)
    {
        foreach($buttons as $button)
            $this->buttons[] = $button;
    }

}