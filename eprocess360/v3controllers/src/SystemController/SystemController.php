<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 12/2/2015
 * Time: 10:12 AM
 */

namespace eprocess360\v3controllers\SystemController;
use eprocess360\v3controllers\Dashboard\Dashboard;
use eprocess360\v3controllers\Download\Download;
use eprocess360\v3controllers\EULA\EULA;
use eprocess360\v3controllers\Group\Group;
use eprocess360\v3controllers\IncrementGenerator\IncrementGenerator;
use eprocess360\v3controllers\Login\Login;
use eprocess360\v3controllers\Logout\Logout;
use eprocess360\v3controllers\MigrationsController\MigrationsController;
use eprocess360\v3controllers\Profile\Profile;
use eprocess360\v3controllers\Project\Project;
use eprocess360\v3controllers\Register\Register;
use eprocess360\v3controllers\Scheduler\Scheduler;
use eprocess360\v3controllers\Site404Handler\Site404Handler;
use eprocess360\v3controllers\Test\Test;
use eprocess360\v3controllers\Users\Users;
use eprocess360\v3core\Controller\Children;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\ControllerException;
use eprocess360\v3core\Controller\Persistent;
use eprocess360\v3core\Controller\ResponseHandler;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\TriggerRegistrar;
use eprocess360\v3core\Controller\Triggers;
use eprocess360\v3core\Request\Request;
use eprocess360\v3controllers\Controller\Controller as Control;
use eprocess360\v3modules\Task\Task;

use eprocess360\v3controllers\Inspection\Inspection;
use eprocess360\v3controllers\Inspector\Inspector;



/**
 * The main application bootstrapper.  This class should be extended and provided with the top level Controllers.
 * Responsible for initial Request acceptance.
 * Class SystemController
 * @package eprocess360\v3core\Controller
 */
class SystemController extends Controller
{
    use Router, ResponseHandler, Persistent, Children, Triggers, TriggerRegistrar;

    protected $childrenBuilt;


    /*********************************************   #ROUTING#  **********************************************/


    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     */
    public function routes()
    {
        $this->routes->map('GET', '/?', function () {
            $this->getDefault();
        });
    }

    public function run()
    {
        if(!$this->getChildrenBuilt())
            $this->buildChildren();

        parent::run();
    }

    public function buildChildren()
    {
        /**********************    NO AUTH    **********************/
        $this->addController(Scheduler::build()->setName('scheduler'));
        $this->addController(Login::build()->setName('login'));
        $this->addController(Register::build()->setName('register'));
        $this->addController(Download::build()->setName('download'));
        $this->addController(Site404Handler::build()->setName('404'));

        /**********************     AUTH     **********************/
        $this->addController(EULA::build()->setName('eula')->setDescription("EULA"));
        $this->addController(Logout::build()->setName('logout')->setDescription("Logout"));
        $this->addController(Profile::build()->setName('profile')->setDescription("My Profile"));
        $this->addController(Users::build()->setName('users')->setDescription("Users"));
        $this->addController(Dashboard::build()->setName('dashboard')->setDescription("Dashboard"));
        $this->addController(Project::build()->setName('projects')->setDescription("Projects"));
        $this->addController(Test::build()->setName('test')->setDescription("Testing"));
        $this->addController(Control::build()->setName('controllers')->setDescription("Workflows"));
        $this->addController(IncrementGenerator::build()->setName('increments')->setDescription("Increment Generator"));
        $this->addController(MigrationsController::build()->setName('migrations')->setDescription
        ('Migration Manager'));
        
//        $this->addController(Inspection::build()->setName('inspection')->setDescription("Inspection"));
        $this->addController(Inspector::build()->setName('inspector')->setDescription("Inspector"));

        $this->setChildrenBuilt(true);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return '';
    }

    /**
     * @return mixed
     */
    public function getChildrenBuilt()
    {
        return $this->childrenBuilt;
    }

    /**
     * @param boolean $childrenBuilt
     */
    public function setChildrenBuilt($childrenBuilt)
    {
        $this->childrenBuilt = $childrenBuilt;
    }

    /**
     * @throws \Exception
     */
    public function getDefault()
    {
        global $pool;
        /** @var ResponseHandler|Router|Controller $controller */
        $controller = $this;

        if (!$pool->User->isIdentified()) {
            $controller->route(Request::get()->setRequestPath('/login'));
        }
        else{
            header('Location: ' . $pool->SysVar->get('siteUrl') . '/eula');
            die();
        }

    }


}