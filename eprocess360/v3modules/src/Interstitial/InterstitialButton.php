<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 3/9/16
 * Time: 9:03 AM
 */

namespace eprocess360\v3modules\Interstitial;

/**
 * Class InterstitialButton
 * @package eprocess360\v3modules\Interstitial
 */
class InterstitialButton
{
    public $text;
    public $link;
    public $color;
    public $id;
    protected $trigger;

    /**
     * @param $text
     * @param $link
     * @param $color
     * @param $id
     * @param string $trigger
     * @return InterstitialButton
     */
    public static function build($text, $link, $color, $id, $trigger = '')
    {
        $button = new self;
        $button->text = $text;
        $button->link = $link;
        $button->color = $color;
        $button->id = $id;
        $button->trigger = $trigger;

        return $button;
    }

    public function getTrigger()
    {
        return $this->trigger;
    }

}