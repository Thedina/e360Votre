<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 1/20/16
 * Time: 9:25 AM
 */

namespace eprocess360\v3modules\Toolbar;

use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\DashboardToolbar;
use eprocess360\v3core\Controller\Module;
use eprocess360\v3core\Controller\Persistent;
use eprocess360\v3core\Controller\Project;
use eprocess360\v3core\Controller\ResponseHandler;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\Rules;
use eprocess360\v3core\Controller\Triggers;
use eprocess360\v3core\Keydict\Entry;

/**
 * Class Toolbar
 * @package eprocess360\v3modules\Toolbar
 */
class Toolbar extends Controller
{
    //Persistent, Module
    use Router, Triggers, Rules;

    private $toolbarTitle;
    private $toolbarDescription;
    private $toolbarProgress = [];
    private $toolbarBlocks = [];
    private $toolbarHome;
    private $toolbarLinks = [];
    private $toolbarMore = [];
    private $toolbarTwig;
    private $closure;

    /**
     *
     */
    public function getToolbar()
    {
        //update isActive, update Progress bar.
        //foreach($toolbarLinks as $link)
        $toolbarTitle = $this->toolbarTitle === NULL || is_string($this->toolbarTitle)
            ?$this->toolbarTitle:$this->toolbarTitle->get();

        $toolbarDescription = $this->toolbarDescription === NULL  || is_string($this->toolbarDescription)
            ?$this->toolbarDescription:$this->toolbarDescription->get();

        $blocks = $this->toolbarBlocks;
        foreach($blocks as &$block)
            $block['value'] = $block['value'] === NULL || is_string($block['value'])
                ?$block['value']:$block['value']->get();

        return ['title'=> $toolbarTitle,
            'description'=> $toolbarDescription,
            'progress'=> $this->toolbarProgress,
            'blocks'=> $blocks,
            'home'=> $this->toolbarHome,
            'links'=> $this->toolbarLinks,
            'more'=> $this->toolbarMore,
            'twig'=> $this->toolbarTwig];
    }

    /**
     * @param string|Entry $title
     */
    public function setToolbarTitle($title)
    {
        $this->toolbarTitle = $title;
    }

    /**
     * @param string|Entry $description
     */
    public function setToolbarDescription($description)
    {
        $this->toolbarDescription = $description;
    }

    /**
     * @param $path
     * @param bool|false $active
     */
    public function setToolbarHome($path, $active = false)
    {
        $home = ['link'=>$path,'active'=>$active];

        $this->toolbarHome = $home;
    }

    /**
     * @param $title
     * @param $url
     * @param $isActive
     */
    public function addToolbarLink($title, $url, $isActive, $isAvailable)
    {
        $this->toolbarLinks[] = ['title'=>$title, 'url'=>$url, 'active'=>$isActive, 'available'=>$isAvailable];
    }

    /**
     * @param $title
     * @param $url
     * @param $isActive
     */
    public function addToolbarMore($title, $url, $isActive)
    {
        $this->toolbarMore[] = ['title'=>$title, 'url'=>$url, 'active'=>$isActive];
    }

    /**
     * @param null $title
     * @param null $value
     * @param null $linkUrl
     * @param null $linkTitle
     */
    public function setToolbarProgress($title = NULL, $value = NULL, $linkUrl = NULL, $linkTitle = NULL)
    {
        if($title !== NULL)
            $this->toolbarProgress['title'] = $title;
        if($value !== NULL)
            $this->toolbarProgress['value'] = $value;
        if($linkUrl !== NULL && $linkTitle !== NULL)
            $this->toolbarProgress['link'] = [ 'url'=> $linkUrl, 'title'=> $linkTitle];
    }

    /**
     * @param string $key
     * @param string|Entry $value
     */
    public function addToolbarBlock($key, $value)
    {
        $this->toolbarBlocks[] = ['key'=>$key, 'value'=>$value];
    }

    /**
     * @param mixed $toolbarTwig
     */
    public function setToolbarTwig($toolbarTwig)
    {
        $this->toolbarTwig = $toolbarTwig;
    }

    /**
     * @return mixed
     */
    public function getClosure()
    {
        return $this->closure;
    }

    /**
     * @return Toolbar
     */
    public function execClosure()
    {
        $closure = $this->closure;
        $closure();
        return $this;
    }

    /**
     * @param mixed $closure
     */
    public function setClosure($closure)
    {
        $this->closure = $closure;
    }

    /**
     * @param Controller $controller
     * @return null
     */
    public static function buildDashboardBar(Controller $controller)
    {
        global $pool;
        /** @var Toolbar $toolbar */
        $toolbar = Toolbar::build()->setName('toolbar')->setDescription('Toolbar');

        /** @var Controller|DashboardToolbar $parent */
        $parent = $controller->getParent();
        
        if($parent && $parent->uses('DashboardToolbar') && $parent->buildToolbarChildren($toolbar, $controller)){

        }
        else {
            if ($parent)
                $toolbar->buildParentLinks($parent);

            if ($controller->getStaticClass() !== 'Dashboard') {
                $title = $controller->getDescription() ?: $controller->getStaticClass();
                /** @var Module|Controller|DashboardToolbar $controller */
                if ($controller->uses('DashboardToolbar'))
                    $controller->buildLinks($toolbar, true, true);
                else
                    $toolbar->addToolbarLink($title, $controller->getPath(), true, true);
            }
        }

        $toolbar->setToolbarHome($pool->SysVar->siteUrl(), $controller->getStaticClass() === 'Dashboard');

        $toolbar->setToolbarTwig('SystemController.toolbar.html.twig');

        return $toolbar;
    }

    /**
     * @param Controller $controller
     */
    public function buildParentLinks(Controller $controller)
    {
        $isAvailable = false;
        $isActive = false;
        if($parent = $controller->getParent())
            $this->buildParentLinks($parent);
        if($controller->getPath() !== '/') {
            $title = $controller->getDescription()?:$controller->getStaticClass();
            $this->addToolbarLink($title, $controller->getPath(true, false), $isActive, $isAvailable);
        }
    }
}

