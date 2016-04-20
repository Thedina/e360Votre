<?php

namespace eprocess360\v3core\Controller;
use eprocess360\v3core\Controller\Dashboard\Button;
use eprocess360\v3core\Controller\Dashboard\Buttons;
use eprocess360\v3core\Controller\Dashboard\DashBlock;
use eprocess360\v3core\Controller\Warden\Privilege;

/**
 * Class Dashboard
 * Enable a Controller to have a Dashboard
 */
trait Dashboard
{
    protected $dashboardTitle;
    protected $dashboardBlocks = [];

    protected $dashboardBlockTitle;
    protected $dashboardDescription; // extended info about the item
    protected $dashboardAutoLink = true; // enable automatic linking for this Dashboard to its parent Dashboard
    protected $dashboardIcon = 'cog'; // the icon for this Dashboard and its button
    protected $dashboardGroup = null; // how to group this item on the parent's dashboard
    protected $dashboardLink; // where the default button should link
    protected $dashboardStatic = false;

    /**
     * @param null $otherData
     */
    public function buildDashboard($otherData = NULL)
    {
        $responseData = [
            'data' => ['blocks' => $this->dashboardBlocks,
                        'title' => $this->dashboardTitle]
        ];

        if($otherData !== NULL)
            $responseData['data'] = array_merge($responseData['data'], $otherData);

        /** @var Controller|Dashboard $this */
        $response = $this->getResponseHandler();

        $response->addTwigPath(APP_PATH.'/eprocess360/v3core/src/Controller/Dashboard/static/twig');

        $response->setTemplate('dashboard.base.html.twig', 'server');

        $response->setResponse($responseData);
    }


    /**
     * @param DashBlock[] ...$blocks
     */
    public function addDashBlocks(DashBlock ...$blocks)
    {
        foreach ($blocks as $block)
            $this->dashboardBlocks[$block->getTitle()] = $block;
    }

    /**
     * Get the button to represent this object
     * @return Button|null
     */
    public function getDashButton($permission = false)
    {
        $this->dashboardInit();

        if (static::READ_ALL) $permission = true;
        /** @var \eprocess360\v3core\Controller\Controller|Dashboard|Project|\eprocess360\v3core\Controller\Warden $this */
        if (!$permission) {
            if (!$this->isDashboardAutoLink() || !$this->hasPrivilege(Privilege::READ)) return null;
        }
        return Button::build(
            $this->dashboardBlockTitle?:$this->getDescription(),
            $this->dashboardDescription?:$this->getDescription(),
            $this->dashboardIcon,
            $this->dashboardLink?:$this->getPath(), //todo modulate getPath() to getDashPath()
            $this->dashboardGroup
        );
    }

    public function traitDashboardReady()
    {
        /* @var \eprocess360\v3core\Controller\Controller|Dashboard $this */
        $this->setDashboardTitle($this->getClass()); // use the name as the basic Title
    }

    /**
     * @return mixed
     */
    public function getDashboardTitle()
    {
        return $this->dashboardTitle;
    }

    /**
     * @param mixed $dashboardTitle
     * @return Dashboard
     */
    public function setDashboardTitle($dashboardTitle)
    {
        $this->dashboardTitle = $dashboardTitle;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDashboardBlockTitle()
    {
        return $this->dashboardBlockTitle;
    }

    /**
     * @param mixed $dashboardBlockTitle
     * @return Dashboard
     */
    public function setDashboardBlockTitle($dashboardBlockTitle)
    {
        $this->dashboardBlockTitle = $dashboardBlockTitle;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDashboardDescription()
    {
        return $this->dashboardDescription;
    }

    /**
     * @param mixed $dashboardDescription
     * @return Dashboard
     */
    public function setDashboardDescription($dashboardDescription)
    {
        $this->dashboardDescription = $dashboardDescription;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isDashboardAutoLink()
    {
        return $this->dashboardAutoLink;
    }

    /**
     * @param boolean $dashboardAutoLink
     * @return Dashboard
     */
    public function setDashboardAutoLink($dashboardAutoLink)
    {
        $this->dashboardAutoLink = $dashboardAutoLink;
        return $this;
    }

    /**
     * @return string
     */
    public function getDashboardIcon()
    {
        return $this->dashboardIcon;
    }

    /**
     * @param string $dashboardIcon
     * @return Dashboard
     */
    public function setDashboardIcon($dashboardIcon)
    {
        $this->dashboardIcon = $dashboardIcon;
        return $this;
    } // allow for the overriding of default Dashboard names

    /**
     * @return mixed
     */
    public function getDashboardLink()
    {
        return $this->dashboardLink;
    }

    /**
     * @param mixed $dashboardLink
     * @return Dashboard
     */
    public function setDashboardLink($dashboardLink)
    {
        $this->dashboardLink = $dashboardLink;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isDashboardStatic()
    {
        return $this->dashboardStatic;
    }

    /**
     * @param boolean $dashboardStatic
     * @return Dashboard
     */
    public function setDashboardStatic($dashboardStatic)
    {
        $this->dashboardStatic = $dashboardStatic;
        return $this;
    }

    /**
     * @return null
     */
    public function getDashboardGroup()
    {
        return $this->dashboardGroup;
    }

    /**
     * @param null $dashboardGroup
     * @return Dashboard
     */
    public function setDashboardGroup($dashboardGroup)
    {
        $this->dashboardGroup = $dashboardGroup;
        return $this;
    } // prevent the user from customizing the dashboard

    /**
     * Do custom stuff here that will always be available, even if this is not the actual dashboard being rendered
     * (used for building breadcrumbs etc)
     */
    public function dashboardReady()
    {
        /** @var Dashboard|Controller $this */
        $this->setDashboardTitle($this->getName());
    }

    /**
     * Do all dashboard main init here - we dont need this function to run unless we are on the page in which the
     * dashboard will actually be shown
     */
    public function dashboardInit()
    {
//        /**
//         * self is already configured, we just need to define the blocks
//         * the initial order and configuration here determines the presets
//         */
//        $this->addDashBlocks(
//            Buttons::build(null)->from($this), // for default buttons
//            Text::build('How To')->setText('How to use the settings or something.'),
//            Settings::build('Settings')->from($this)->hide() // hidden by default so fancy
//        );
//        $this->setDashboardStatic(true); // prevent the user from changing the dashboard
    }
}


