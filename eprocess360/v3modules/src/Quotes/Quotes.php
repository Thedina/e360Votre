<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 10/27/2015
 * Time: 10:48 AM
 */

namespace eprocess360\v3modules\Quotes;

use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Module;
use eprocess360\v3core\Controller\Persistent;
use eprocess360\v3core\Controller\ResponseHandler;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\Rules;
use eprocess360\v3core\Controller\Triggers;
use eprocess360\v3core\Controller\Warden\Privilege;
use eprocess360\v3core\Formula\Formula;

/**
 * Class Quotes
 * This is a Dummy module for testing purposes
 * @package eprocess360\v3modules\Roles
 */
class Quotes extends Controller
{
    use Router, Module, Persistent, Triggers, Rules;
    public function routes()
    {
        $this->routes->map('GET', '/?[i:id]?', function ($id = false) {

            /*
             * This is what the M of MVC will be.
             */
            global $pool;
//            $meta = [
//                'path'=>$this->getPath(),
//                'apiPath'=>$this->getApiPath(),
//                'title'=>$this->getDescription(),
//                'directory'=>$this->getStaticPath(),
//                'siteUrl'=>$pool->SysVar->siteUrl()
//            ];

            $response = $this->getResponseHandler();
//            $response->addResponseMeta('path',$this->getPath());
//            $response->addResponseMeta('apiPath',$this->getApiPath());
//            $response->addResponseMeta('title',$this->getDescription());
//            $response->addResponseMeta('directory',$this->getStaticPath());
//            $response->addResponseMeta('siteUrl',$pool->SysVar->siteUrl());


            $formula = new Formula();
            $formula->addKeydict('project', $this->getClosest('Project')->getKeydict());
            $formula->setEnvironmentVariable('idProject', $this->getClosest('Project')->getObjectId());
            $formula->setEnvironmentVariable('baseFormula', $formula);
            echo $out = $formula->parse('100 + Lilypads(0)');
            die();
            $response->setTemplate('module.quotes.main.html', 'client', $this);
            $response->setResponse([
                'quote'=>$this->getQuote($id),
                'putput'=>$out
            ]);
        });
    }

    public function getQuote($number = false)
    {
        $quotes = [];
        include APP_PATH . $this->getStaticPath() . '/presets/quotes.php';
        srand ((double) microtime() * 1000000);
        if (!$number)
            $number = rand(0,count($quotes)-1);
        return [
            'number'=>$number,
            'body'=>$quotes[$number]
        ];
    }
}