<?php
/**
 * Created by PhpStorm.
 * User: kira
 * Date: 8/5/2015
 * Time: 10:54 AM
 */
namespace App\Basic;
use eprocess360\v3controllers\Group\Model\Groups;
use eprocess360\v3core\Controller\Children;
use eprocess360\v3core\Controller\Dashboard;
use eprocess360\v3core\Controller\Dashboard\Buttons;
use eprocess360\v3core\Controller\Module;
use eprocess360\v3core\Controller\Persistent;
use eprocess360\v3core\Controller\Project;
use eprocess360\v3core\Controller\Rules;
use eprocess360\v3core\Controller\Trigger\InterfaceTriggers;
use eprocess360\v3core\Controller\Warden;
use eprocess360\v3core\Controller\Warden\Privilege;
use eprocess360\v3core\Controller\Warden\Role;
use eprocess360\v3core\Controller\Roles;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\State\State;
use eprocess360\v3core\Controller\TriggerRegistrar;
use eprocess360\v3core\Controller\Triggers;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry\Bits;
use eprocess360\v3core\Keydict\Entry\Fullname;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\PhoneNumber;
use eprocess360\v3core\Keydict\Entry\String;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model\Projects;
use eprocess360\v3core\Request\Request;
use eprocess360\v3modules\Form\Form;
use eprocess360\v3modules\Inspection\Inspection;
use eprocess360\v3modules\ModuleRule\Model\ModuleRules;
use eprocess360\v3modules\ModuleRule\ModuleRule;
use eprocess360\v3modules\FolderRoot\FolderRoot;
use eprocess360\v3core\View\StandardView;
use eprocess360\v3core\View\Column;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3modules\RouteTree\RouteTree;
use eprocess360\v3modules\Toolbar\Toolbar;
use Exception;
/**
 * Class Basic
 * @package App\Basic
 */
class Basic extends Controller implements InterfaceTriggers
{
    use Router, Project, Persistent, Children, Triggers, TriggerRegistrar, Roles, Warden, Rules, Dashboard;
    /** @var string The toolbar template to use. */
    protected $toolbarTemplate = 'project.toolbar.html.twig';
    public $toolbar;
    /**
     * The main init function for the controller.  Put global triggers here.
     */
    public function run()
    {

        if($this->hasObjectId()) {
            $this->addProjectStates(
                State::build('New Project', 1, '/form'), // add Applicant
                State::build('Form Pending', 2, '/form'),
                State::build('Form Complete', 3, '/form'),
                State::build('Project Complete', 4, '/form')
            );
            $this->buildRoles();
            $this->buildChildren();
            $this->buildStateTriggers();
            $this->buildPrivileges();
            $this->stateRun();
            $this->buildToolbar();
        }
        else{
            $this->buildChildren();
        }
        global $twig_loader;
        $twig_loader->addPath(APP_PATH.'/app/Dummy/static/twig');

        parent::run();
    }
    public function buildRoles()
    {
        $this->addRoles(
            Role::build(1, 'Viewer'),
            Role::build(2, 'Applicant'),
            Role::build(3, 'Reviewer'),
            Role::build(4, 'Inspector'),
            Role::build(5, 'Permit Tech')
        );
    }
    public function buildChildren()
    {
        //If certain Modules DEPEND on other Modules, their Dependencies Must be built Before they are.
        $this->addController(Form::build('form', $this)->setDescription('Application Form'));
        $this->addController(FolderRoot::build('files', $this)->setDescription('FolderRoot'));
        $this->addController(ModuleRule::build('rules', $this)->setDescription('Rules'));
        $this->addController(Inspection::build('inspections', $this)->setDescription('Inspections'));
    }
    public function buildStateTriggers()
    {
        /**
         * The Form Pending state needs to be redirected to ONLY when the State is entered
         */
        $this->state('Form Pending')->onEnter(function () {
            $this->route(Request::get()->setRequestPath($this->getPath().$this->state('Form Pending')->getHome())->setRequestMethod('GET'));
        });
    }
    public function buildPrivileges()
    {
        /** @var Form $form */
        $form = $this->getChild('form', false);
        $form->addRules(Privilege::READ, ['Viewer', 'Applicant'])
            ->addRules(Privilege::WRITE, ['Applicant'], ['Form Pending'])
            ->addRules(Privilege::WRITE, ['Permit Tech']);
    }
    public function buildToolbar()
    {
        /** @var Toolbar $toolbar */
        $toolbar = Toolbar::build()->setName('toolbar')->setDescription('Toolbar');
        $this->toolbar = $toolbar;
        $toolbar->setClosure(function () use ($toolbar) {
            $basePath = $this->getPath();
            $active = $this->findNextChild();
            if(!$active) {
                Request::get()->setRequestPath($this->getPath() . $this->state($this->getProjectData()->state->get())->getHome());
                $active = $this->findNextChild();
            }
            foreach($this->children as $child) {
                /** @var Module|Controller $child */
                $child->createModuleToolbar($toolbar, $child->getName() == $active);
            }
            $toolbar->setToolbarHome($basePath, !$active);
            $toolbar->setToolbarTitle($this->projectData->title);
            $toolbar->setToolbarDescription($this->projectData->description);
            if($this->keydict->applicantName->get())
                $toolbar->addToolbarBlock($this->keydict->applicantName->getLabel(), $this->keydict->applicantName);
            if($this->keydict->phoneNumber->get())
                $toolbar->addToolbarBlock($this->keydict->phoneNumber->getLabel(), $this->keydict->phoneNumber);
            if($this->keydict->favoriteFood->get())
                $toolbar->addToolbarBlock($this->keydict->favoriteFood->getLabel(), $this->keydict->favoriteFood);
            $toolbar->setToolbarProgress($this->getProjectState()->getName(), 0, $basePath.$this->getProjectState()->getHome(), "view");
            $toolbar->setToolbarTwig('toolbar.html.twig');
        });
    }
    /**
     * @return Keydict
     * @throws \Exception
     */
    public function keydict()
    {
        return Table::build(
            IdInteger::build('idProject', 'Project ID')->joinsOn(Projects::keydict())->setMeta('foreignPrimaryKey'),
            Fullname::build('applicantName', 'Name', '')->setRequired(),
            String::build('favoriteFood', 'Favorite Food', ''),
            PhoneNumber::build('phoneNumber', 'Phone Number')
        );
    }
    /**
     * Helper function that finds the child the request is going to.
     * @return string
     */
    private function findNextChild()
    {
        $requestPath = Request::get()->getRequestPath();
        if($string = rtrim($requestPath, '/'))
            $requestPath = $string;
        // Children can catch and Route themselves by matching the first element from getPath()
        $getPath = $this->getPath();
        $position = 0;
        if (strpos($requestPath, $getPath) !== false)
            $position = strlen($getPath);
        $upperpath = substr($requestPath, $position);
        if ($upperpath[0] === '/')
            $upperpath = substr($upperpath, 1);
        $path = strtolower($upperpath);
        if (strpos($path, '/') !== false)
            $path = substr($path, 0, strpos($path, '/'));
        else if (strpos($path, '?') !== false)
            $path = substr($path, 0, strpos($path, '?'));
        if (is_numeric($path)) {
            $this->setObjectId((int)$path);
            $upperpath = substr($upperpath, strlen($path));
            if ($upperpath[0] === '/')
                $upperpath = substr($upperpath, 1);
            $path = strtolower($upperpath);
            if (strpos($path, '/') !== false)
                $path = substr($path, 0, strpos($path, '/'));
            else if (strpos($path, '?') !== false)
                $path = substr($path, 0, strpos($path, '?'));
        }
        return $path;
    }
    /*********************************************   #ROUTING#  **********************************************/
    public function routes()
    {
        $this->routes->map('GET', '', function () {

            if($this->hasObjectId()) {
                //If no prior redirection, go to current state's home.
                $this->route(Request::get()->setRequestPath($this->getPath() . $this->state($this->getProjectData()->state->get())->getHome()));
            }
            else
                $this->buildSettings();
        });
    }

    public function buildSettings()
    {
        $this->verifyPrivilege(Privilege::ADMIN);
        $buttons = Buttons::build('Dummy Workflow');

        foreach($this->children as $child) {
            /** @var Module|Controller|Dashboard $child */
            if($child->uses("Dashboard")) {
                $buttons->addButton($child->getDashButton(true));
            }
        }

        $this->addDashBlocks($buttons);

        $this->buildDashboard();
    }


    /*********************************************   #WORKFLOW#  *********************************************/


    /**
     * Called when the project is begin run for the first time and/or without a State
     */
    public function stateNull()
    {
        $this->setProjectState('New Project');
    }
    /**
     * Pass through State
     * Functions:
     *  Grant Applicant to current user
     *  State to Form Pending
     */
    public function stateNewProject()
    {
        $this->grant($this->role('Applicant'));
        $this->setProjectState('Form Pending');
    }
    /**
     * Waiting State
     * Functions:
     *  If the Form was Saved, set State to Form Complete.  Since the Form will issue onUpdate AFTER states are run,
     *  this is a delayed function.  This is the second function that will be applied to the onUpdate execution stack.
     */
    public function stateFormPending()
    {
        /** @var Controller|Form $form */
        $form = $this->getChild('form');
        $form->onUpdate(function () use ($form) {
            if ($form->isSaved()) {
                $this->setProjectState('Form Complete');
            }
        });
    }
    /**
     * Pass through State
     * Functions:
     *  State to Routing Pending
     *  Push user to next home
     */
    public function stateFormComplete()
    {
        $this->setProjectState('Project Complete');
    }
    public function stateProjectComplete()
    {
    }
    /******************************************   #INITIALIZATION#  ******************************************/
    /**
     * @param Keydict\Table $table
     * @return StandardView
     */
    public function view($result)
    {
        $table = Table::build(
            IdInteger::build('idProject', 'Project ID')->joinsOn(Projects::keydict())->setMeta('foreignPrimaryKey'),
            Fullname::build('applicantName', 'Name', '')->setRequired(),
            String::build('favoriteFood', 'Favorite Food', ''),
            PhoneNumber::build('phoneNumber', 'Phone Number')
        );
        $table->setName('Projects');

        /** @var StandardView $test Assign the namespace and keydict to the view */
        $view = StandardView::build($this->getClass().'.All', $this->getClass(), $table, $result);
        $view->add(
            Column::import($table->idProject)->setEnabled(false)->setSort(true),
            Column::import($table->applicantName)->filterBySearch()->setSort(false)->setIsLink(true),
            Column::import($table->favoriteFood)->setSort(false),
            Column::import($table->phoneNumber)->setSort(false)
        );

        return $view;
    }
    /**
     * @param Form $obj
     */
    public function initForm(Form $obj)
    {
        $keydict = $this->getKeydict();
        $projectKeydict= $this->getProjectData();
        $obj->accepts(
            $projectKeydict->title,
            $projectKeydict->description,
            $keydict->applicantName,
            $keydict->favoriteFood,
            $keydict->phoneNumber
        );
    }
    /**
     * @param FolderRoot $obj
     */
    public function initFiles(FolderRoot $obj)
    {
        $acceptedFileTypes = ["pdf", "txt"];
        $maxFileSize = 52428800;
        $minFileSize = 1;
        $fileCategories = ["Transmittal Letter","Plans"];
        /**
         * If for some reason you need Files that have difference Accpetence Parameters , you can add their category here:
         * Example: $specialCategories = [
        "Plans"           => ['acceptedFileTypes' =>["pdf"],'fileMaxSize' =>1000,'fileMinSize' => 1],
        "That One Thing"  => ['acceptedFileTypes' =>["pdf", "txt"],'fileMaxSize' =>10000,'fileMinSize' => 1]
        ];
         */
        $specialCategories = [];
        $obj->fileAcceptParameters ($acceptedFileTypes, $maxFileSize, $minFileSize, $fileCategories, $specialCategories);
    }
    /**
     * @param ModuleRule $obj
     * @throws Exception
     */
    public function initRules(ModuleRule $obj)
    {
        /** @var RouteTree $routing */
        $routing = $this->getChild('routing');
        $routing->loadRouteTree();
        $routing->updateFlags();
        $options = $this->getKeydict();
        $variables = $options;
        $comparators = ['==', '!=', '>=', '<=', '>', '<'];
        $values = ['true', 'false'];
        $conjunctions = ['AND', 'OR'];
        $obj->bindConditionOptions($variables, $comparators, $values, $conjunctions);
    }
    /**
     * @param Inspection $obj
     */
    public function initInspections(Inspection $obj)
    {
    }
    /********************************************   #COMMISSION#  ********************************************/
    /**
     * Basic Controller's commisioning functioning that initially builds it's children, which activates their individual commissioning functions.
     */
    public function commission()
    {
        $this->buildChildren();
    }

    /**
     * @param Inspection $obj
     */
    public function commissionInspections(Inspection $obj)
    {
    }
    /*********************************************   #TRIGGERS#  *********************************************/
    /**
     * The Functions within this section are called from the Init function if the Controller uses Trigger.
     */
    /**
     * Trigger Handler function for the Form module.
     * @param Form $obj
     */
    public function triggerForm(Form $obj)
    {
        $obj->onUpdate(function() use ($obj) {
            $this->save();
            $this->getProjectData()->update();
            $obj->getKeydict()->setSaved(true);
        });
    }
    /**
     * Trigger Handler function for the FolderRoot module.
     * @param FolderRoot $obj
     */
    public function triggerFiles(FolderRoot $obj)
    {
    }
}