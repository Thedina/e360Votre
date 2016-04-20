<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 3/8/16
 * Time: 11:23 AM
 */

namespace eprocess360\v3modules\Interstitial;


use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Module;
use eprocess360\v3core\Controller\Persistent;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\Rules;
use eprocess360\v3core\Controller\Triggers;

/**
 * Class Interstitial
 * @package eprocess360\v3controllers\Interstitial
 */
class Interstitial extends Controller
{
    use Router, Module, Persistent, Triggers, Rules;

    public $title;
    public $body;
    public $buttons;
    private $isSet;


    /*********************************************   #ROUTING#  **********************************************/


    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     * @throws \Exception
     */
    public function routes()
    {
        $this->routes->map('GET', '', function () {
            $this->getInterstitialAPI();
        });
        $this->routes->map('GET', '/[i:idButton]', function ($idButton) {
            $this->getInterstitialButtonAPI($idButton);
        });
    }

    /**
     * API Function to get render the Interstitial.
     */
    public function getInterstitialAPI()
    {
        $this->standardResponse($this);
    }

    /**
     * API Function to get render the Interstitial.
     */
    public function getInterstitialButtonAPI($idButton)
    {
        if(!isset($this->buttons[$idButton]))
            $this->standardResponse($this);
        else {
            /** @var InterstitialButton $button */
            $button = $this->buttons[$idButton];

            /** @var Controller|Triggers $parent */
            $parent = $this->getParent();

            $parent->trigger($button->getTrigger());
        }
    }

    /**********************************************   #HELPER#  **********************************************/


    /**
     * Helper function that sets the Response's template, data, response code, and errors.
     * @param array $data
     * @param int $responseCode
     * @param bool|false $error
     */
    private function standardResponse($data = [], $responseCode = 200, $error = false)
    {
        $responseData = [
            'error' => $error,
            'data' => $data
        ];

        $response = $this->getResponseHandler();
        $response->setTemplate('interstitial.base.html.twig', 'server');

        $response->setResponse($responseData, $responseCode, false);
        if($error)
            $response->setErrorResponse(new \Exception($error));
    }


    /*********************************************   #WORKFLOW#  *********************************************/


    /**
     * @param $title
     * @return $this
     */
    public function setTitle($title){
        $this->title = $title;
        return $this;
    }

    /**
     * @param $body
     * @return $this
     */
    public function setBody($body){
        $this->body = $body;
        return $this;
    }

    /**
     * @param $text
     * @param $link
     * @param $color
     * @param string $trigger
     * @return $this
     */
    public function addButton($text, $link, $color, $trigger = '')
    {
        $id = sizeof($this->buttons)+1;
        if($trigger){
            $link = $this->getPath()."/".$id;
        }
        $this->buttons[$id] = InterstitialButton::build($text, $link, $color, $id, $trigger);
        return $this;
    }

    /**
     * @return $this
     */
    public function buttonsReset()
    {
        $this->buttons  = [];
        return $this;
    }

    public function setisSet($boolean){
        $this->isSet = $boolean;
    }

    public function getisSet(){
        return $this->isSet;
    }


    /******************************************   #INITIALIZATION#  ******************************************/





    /*********************************************   #TRIGGERS#  *********************************************/



}