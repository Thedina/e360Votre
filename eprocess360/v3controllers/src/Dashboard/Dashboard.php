<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 12/9/15
 * Time: 10:05 AM
 */

namespace eprocess360\v3controllers\Dashboard;


use App\System;
use eprocess360\v3controllers\Group\Group;
use eprocess360\v3controllers\Users\Users;
use eprocess360\v3core\Controller\Auth;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Dashboard\Buttons;
use eprocess360\v3core\Controller\Dashboard\DashBlock;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\Dashboard as DashboardTrait;
use eprocess360\v3controllers\Controller\Controller as ControllersView;
use eprocess360\v3modules\Task\Task;

/**
 * Class Dashboard
 * @package eprocess360\v3controllers\Dashboard
 */
class Dashboard extends Controller
{
    use Router, Auth, DashboardTrait;

    protected $controllers;
    protected $projects;


    /*********************************************   #ROUTING#  **********************************************/

    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     * @throws \Exception
     */
    public function routes()
    {
        $this->routes->map('GET', '', function () {
            $this->getDashboardAPI();
        });
    }

    /**
     * API Function to get render the Dashboard.
     */
    public function getDashboardAPI()
    {
        /** @var System $parent */
        $parent = $this->getParent();
        /** @var Group $groups */
        $groups = $parent->getChild('groups');
        /** @var Task $tasks */
        $tasks = $parent->getChild('tasks');
        /** @var ControllersView $controllers */
        $controllers = $parent->getChild('controllers');
        /** @var Users $users */
        $users = $parent->getChild('users');


        $buttons = Buttons::build('Dashboard');


        $inclean = [];
        $in = [$controllers->getDashButton(), $groups->getDashButton(), $users->getDashButton()];
        foreach ($in as $i)
            if ($i !== null)
                $inclean[] = $i;
        $buttons->addButton(...$inclean);

        $projects = DashBlock::build("Existing Project", "Block.CreateProject.html.twig", false);
        $controllers = DashBlock::build("Create Project", "Block.ExistingProject.html.twig", false);
        $this->addDashBlocks($buttons, $controllers, $projects);

        $responseData = [
            'controllers' => $this->controllers,
            'projects' => $this->projects
        ];

        $this->buildDashboard($responseData);
    }


    /*********************************************   #WORKFLOW#  *********************************************/


    /**
     * Sets the array of Project Controllers available on the Dashboard.
     * @param $controllers
     */
    public function setControllers($controllers)
    {
        $this->controllers = $controllers;
    }

    /**
     * Sets the array of Projects available on the Dashboard.
     * @param $projects
     */
    public function setProjects($projects)
    {
        $this->projects = $projects;
    }
}