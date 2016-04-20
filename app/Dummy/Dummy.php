<?php
/**
 * Created by PhpStorm.
 * User: kira
 * Date: 8/5/2015
 * Time: 10:54 AM
 */

namespace App\Dummy;

use eprocess360\v3controllers\Group\Model\Groups;
use eprocess360\v3controllers\IncrementGenerator\Model\IncrementGenerators;
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
use eprocess360\v3core\DB;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry\BitString;
use eprocess360\v3core\Keydict\Entry\Bits;
use eprocess360\v3core\Keydict\Entry\Datetime;
use eprocess360\v3core\Keydict\Entry\Email;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\FixedString64;
use eprocess360\v3core\Keydict\Entry\Fullname;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\Integer;
use eprocess360\v3core\Keydict\Entry\MoneyVal;
use eprocess360\v3core\Keydict\Entry\PhoneNumber;
use eprocess360\v3core\Keydict\Entry\ProjectName;
use eprocess360\v3core\Keydict\Entry\String;
use eprocess360\v3core\Keydict\Entry\String256;
use eprocess360\v3core\Keydict\Entry\Text;
use eprocess360\v3core\Keydict\Entry\TinyInteger;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model\Projects;
use eprocess360\v3core\Request\Request;
use eprocess360\v3modules\Fee\Fee;
use eprocess360\v3modules\Form\Form;
use eprocess360\v3modules\Interstitial\Interstitial;
use eprocess360\v3modules\ModuleRule\Model\ModuleRules;
use eprocess360\v3modules\ModuleRule\ModuleRule;
use eprocess360\v3modules\ProjectUser\ProjectUser;
use eprocess360\v3modules\Quotes\Quotes;
use eprocess360\v3modules\Review\Model\Reviews;
use eprocess360\v3modules\RouteTree\Model\RouteTrees;
use eprocess360\v3modules\RouteTree\RouteTree;
use eprocess360\v3modules\FolderRoot\FolderRoot;
use eprocess360\v3modules\Submittal\Submittal;
use eprocess360\v3modules\Review\Review;
use eprocess360\v3modules\Mail\Mail;
use eprocess360\v3core\View\StandardView;
use eprocess360\v3core\View\Column;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3modules\Toolbar\Toolbar;
use Exception;

/**
 * Class Dummy
 * @package App\Dummy
 */
class Dummy extends Controller implements InterfaceTriggers
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
            $phaseId = $this->keydict->gotoPhaseId->get();
            if ($phaseId == 0) {
                $phaseId = '';
            }

            $this->addProjectStates(
                State::build('New Project', 1, '/form'), // add Applicant
                State::build('Form Pending', 2,  '/form'),
                State::build('Form Complete', 3,  '/routing'),
                State::build('Routing Pending', 4,  '/routing'),
                State::build('Routing Complete', 5,  '/routing'),
                State::build('Submittal Setup', 6,  '/phases'),
                State::build('Submittal Pending', 7,  '/phases/' . $phaseId),
                State::build('Submittal Complete', 8,  '/phases/' . $phaseId),
                State::build('Acceptance Pending', 9,  '/interstitial'),
                State::build('Acceptance Complete', 10,  '/interstitial'),
                State::build('Fee Deposit Pending', 11,  '/fees'),
                State::build('Fee Deposit Complete', 12,  '/fees'),
                State::build('Reviewers Pending', 13,  '/phases/' . $phaseId),
                State::build('Reviewers Complete', 14,  '/phases/' . $phaseId),
                State::build('Under Review', 15,  '/phases/' . $phaseId),
                State::build('Fee Payment Pending', 16,  '/fees'),
                State::build('Fee Payment Complete', 17,  '/fees'),
                State::build('Project Complete', 18,  '/interstitial')
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
        //TODO Refactor Module Build
        //If certain Modules DEPEND on other Modules, their Dependencies Must be built Before they are.
        $this->addController(Form::build('form', $this)->setDescription('Application Form'));
        $this->addController(RouteTree::build('routing', $this)->setDescription('Routing'));
        $this->addController(Mail::build('mail', $this)->setDescription('Mail'));
        $this->addController(Quotes::build('quotes', $this)->setDescription('Quote of the Microsecond'));
        $this->addController(FolderRoot::build('files', $this)->setDescription('FolderRoot'));
        $this->addController(ModuleRule::build('rules', $this)->setDescription('Rules'));
        $this->addController(Review::build('reviews', $this)->setDescription('Reviews'));
        $this->addController(Submittal::build('phases', $this)->setDescription('Submittals'));
        $this->addController(ProjectUser::build('users', $this)->setDescription('Project Users'));
        $this->addController(Fee::build('fees', $this)->setDescription('Fees'));
        $this->addController(Interstitial::build('interstitial', $this)->setDescription('Interstitial'));
    }

    public function buildStateTriggers()
    {
        /**
         * The Form Pending state needs to be redirected to ONLY when the State is entered
         */
        $this->state('Form Pending')->onEnter(function () {
            $this->route(Request::get()->setRequestPath($this->getPath().$this->state('Form Pending')->getHome())->setRequestMethod('GET'));
        });
        $this->state('Routing Pending')->onEnter(function () {
            $this->route(Request::get()->setRequestPath($this->getPath().$this->state('Routing Pending')->getHome())->setRequestMethod('GET'));
        });
        $this->state('Submittal Pending')->onEnter(function () {
            $this->route(Request::get()->setRequestPath($this->getPath().'/interstitial')->setRequestMethod('GET'));
        });
        $this->state('Fee Deposit Pending')->onEnter(function () {
            $this->route(Request::get()->setRequestPath($this->getPath().$this->state('Fee Deposit Pending')->getHome())->setRequestMethod('GET'));
        });
        $this->state('Reviewers Pending')->onEnter(function () {
            $this->route(Request::get()->setRequestPath($this->getPath().'/interstitial')->setRequestMethod('GET'));
        });
        $this->state('Fee Payment Pending')->onEnter(function () {
            /** @var Controller|Fee $fees */
            $fees = $this->getChild('fees');

            if($fees->checkFeesPaid()) {
                $this->getKeydict()->paidFees->set(1);
                $this->save();
                $this->setProjectState('Fee Payment Complete');
            }
        });
        $this->state('Fee Payment Complete')->onEnter(function () {
            $this->route(Request::get()->setRequestPath($this->getPath().'/interstitial')->setRequestMethod('GET'));
        });
        $this->state('Project Complete')->onEnter(function () {
            $this->route(Request::get()->setRequestPath($this->getPath().'/interstitial')->setRequestMethod('GET'));
        });
    }

    public function buildPrivileges()
    {
        /**
         *         $this->addRoles(
        Role::build(1, 'Viewer'),
        Role::build(2, 'Applicant'),
        Role::build(3, 'Reviewer'),
        Role::build(4, 'Inspector'),
        Role::build(5, 'Permit Tech')
         *
         *
         *         $this->addController(Form::build('form', $this)->setDescription('Application Form'));
        $this->addController(RouteTree::build('routing', $this)->setDescription('Routing'));
        $this->addController(Mail::build('mail', $this)->setDescription('Mail'));
        $this->addController(Quotes::build('quotes', $this)->setDescription('Quote of the Microsecond'));
        $this->addController(FolderRoot::build('files', $this)->setDescription('FolderRoot'));
        $this->addController(ModuleRule::build('rules', $this)->setDescription('Rules'));
        $this->addController(Review::build('reviews', $this)->setDescription('Reviews'));
        $this->addController(Submittal::build('phases', $this)->setDescription('Submittals'));
        $this->addController(ProjectUser::build('users', $this)->setDescription('Project Users'));
        $this->addController(Fee::build('fees', $this)->setDescription('Fees'));
        $this->addController(Interstitial::build('interstitial', $this)->setDescription('Interstitial'));
        );
         */

        $this->addRules(Privilege::ROLE_ADMIN, ['Permit Tech']);

        /** @var Form $form */
        $form = $this->getChild('form', false);
        $form->addRules(Privilege::READ, ['Viewer', 'Applicant', 'Reviewer'])
            ->addRules(Privilege::WRITE, ['Applicant'], ['Form Pending'])
            ->addRules(Privilege::ROLE_ADMIN, ['Permit Tech']);

        /** @var RouteTree $routing */
        $routing = $this->getChild('routing', false);
        $routing->addRules(Privilege::READ, ['Viewer', 'Applicant', 'Reviewer'])
            ->addRules(Privilege::WRITE, ['Applicant'], ['Routing Pending'])
            ->addRules(Privilege::ROLE_ADMIN, ['Permit Tech']);

        /** @var Submittal $phases */
        $phases = $this->getChild('phases', false);
        $phases->addRules(Privilege::READ, ['Viewer', 'Applicant', 'Reviewer'])
            ->addRules(Privilege::WRITE, ['Applicant'], ['Submittal Pending', 'Under Review'])
            ->addRules(Privilege::ROLE_ADMIN, ['Permit Tech']);

        /** @var Review $review */
        $review = $this->getChild('reviews', false);
        $review->addRules(Privilege::READ, ['Viewer', 'Applicant', 'Reviewer'])
            ->addRules(Privilege::ROLE_WRITE, ['Reviewer'], ['Under Review'])
            ->addRules(Privilege::ROLE_ADMIN, ['Permit Tech']);

        /** @var Interstitial $interstitial */
        $interstitial = $this->getChild('interstitial', false);
        $interstitial->addRules(Privilege::ROLE_WRITE, ['Reviewer'], ['Under Review'])
            ->addRules(Privilege::ROLE_ADMIN, ['Permit Tech']);

        /** @var Fee $fees */
        $fees = $this->getChild('fees', false);
        $fees->addRules(Privilege::READ, ['Viewer', 'Applicant', 'Reviewer'])
            ->addRules(Privilege::ROLE_ADMIN, ['Permit Tech']);

        /** @var ProjectUser $users */
        $users = $this->getChild('users', false);
        $users->addRules(Privilege::READ, ['Viewer', 'Applicant', 'Reviewer','Inspector'])
            ->addRules(Privilege::ROLE_ADMIN, ['Permit Tech']);
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

            $stateId = $this->getProjectState()->getidState();

            $children = [];
            if($this->hasPrivilege(Privilege::ADMIN)){
                $children = $this->children;
            }
            else {
                $stateToolbars = [$this->getProjectStateByName('New Project')->getidState() => $this->getChild('form'),
                    $this->getProjectStateByName('Routing Pending')->getidState() => $this->getChild('routing'),
                    $this->getProjectStateByName('Submittal Setup')->getidState() => $this->getChild('phases'),
                    $this->getProjectStateByName('Fee Deposit Pending')->getidState() => $this->getChild('fees')];

                foreach ($stateToolbars as $k => $v) {
                    if ($k <= $stateId) {
                        $children[] = $v;
                    }
                }
            }

            foreach ($children as $child) {
                /** @var Module|Controller $child */
                $child->createModuleToolbar($toolbar, $child->getName() == $active);
            }

            /** @var ProjectUser $users */
            $users = $this->children['users'];

            if($this->hasPrivilege(Privilege::READ, $users)) {
                $toolbar->addToolbarMore($users->getDescription(), $users->getPath(), $users->getName() == $active);
            }

            $toolbar->setToolbarHome($basePath, !$active);
            $toolbar->setToolbarTitle($this->projectData->title);

            $toolbar->setToolbarDescription($this->projectData->description);

            if($this->keydict->permitNumber->get())
                $toolbar->addToolbarBlock($this->keydict->permitNumber->getLabel(), $this->keydict->permitNumber);
            if($this->keydict->parcelNumber->get())
                $toolbar->addToolbarBlock($this->keydict->parcelNumber->getLabel(), $this->keydict->parcelNumber);


            if ($this->getProjectState()->getName() == 'Under Review') {
                $reviewsComplete = $this->keydict->reviewsComplete->get();
                $reviews = $this->keydict->reviews->get();
                $reviewsPercent = ($reviews > 0 ? $reviewsComplete/$reviews * 100 : 0);

                $toolbar->setToolbarProgress($this->getProjectState()->getName() . " {$reviewsComplete}/{$reviews} ", $reviewsPercent, $basePath.$this->getProjectState()->getHome(), "view");
            } else {
                $toolbar->setToolbarProgress($this->getProjectState()->getName(), 0, $basePath, "view");
            }

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
            // Applicant Information
            IdInteger::build('idProject', 'Project ID')->joinsOn(Projects::keydict())->setMeta('foreignPrimaryKey'),
            Fullname::build('applicantName', 'Name', '')->setRequired(),
            String256::build('contactAddress', 'Address', '')->setRequired(),
            PhoneNumber::build('phoneNumber', 'Phone Number', '')->setRequired(),
            Email::build('contactEmail', 'Contact Email', '')->setRequired(),

            // Project Information
            String256::build('siteAddress', 'Site Address', '')->setRequired(),
            ProjectName::build('projectName', 'Project Name', ''),
            MoneyVal::build('projectValuation', 'Declared Valuation', ''),
            Text::build('detailedDescription', 'Detailed Description of Work', '')->setRequired(),

            // Property Owner Information
            Fullname::build('propertyOwnerName', 'Name', '')->setRequired(),
            String256::build('propertyOwnerAddress', 'Address', '')->setRequired(),
            PhoneNumber::build('propertyOwnerPhone', 'Phone Number', '')->setRequired(),
            Email::build('propertyOwnerEmail', 'Email Address', '')->setRequired(),

            // Contractor Information
            String256::build('contractorName', 'Company Name', '')->setRequired(),
            String256::build('contractorAddress', 'Address', '')->setRequired(),
            PhoneNumber::build('contractorPhone', 'Phone Number', '')->setRequired(),
            Email::build('contractorEmail', 'Email Address', '')->setRequired(),
            String256::build('contractorLicenseNo', 'License #', '')->setRequired(),


            Bits::make(     // Will be modified later with the settings from RouteTree init
                'flags',
                Keydict\Entry\Bit::build(0, 'isResidential', 'Is Residential?')
            ),


            BitString::build('selected', 'Bits for selected options (non-searchable)'),
            Keydict\Entry\Integer::build('activeSubRevCycles', 'Active Submittal Review Cycles'),
            IdInteger::build('gotoPhaseId', 'Go to Submittal Phase Id'),
            Integer::build('reviewsComplete', 'Go to Submittal Phase Id'),
            Integer::build('reviews', 'Go to Submittal Phase Id'),
            IdInteger::build('idFeeTag', 'Project Code Year Tag'),
            Integer::build('paidDeposits', 'Has Paid Deposits'),
            Integer::build('paidFees', 'Has Paid Fees'),
            String256::build('permitNumber', 'Permit Number'),
            String256::build('parcelNumber', 'Parcel Number'),

            // Fee Fixtures
            TinyInteger::build('fixtureCircuits', 'Electrical: New Circuits'),
            TinyInteger::build('fixtureFurnaces', 'Furnaces'),
            TinyInteger::build('fixtureAirConditioners', 'Air Conditioners'),
            TinyInteger::build('fixtureAirCleaners', 'Air Cleaners'),
            TinyInteger::build('fixtureHumidifiers', 'Humidifiers'),
            TinyInteger::build('fixtureWaterHeaters', 'Water Heater'),
            TinyInteger::build('fixtureWaterSofteners', 'Water Softeners'),
            TinyInteger::build('fixtureBoilers', 'Boilers'),

            // Fee Services
            TinyInteger::build('electricalNew', 'New Electrical Services'),
            TinyInteger::build('electricalUpgrade', 'Upgrade Electrical Services'),
            TinyInteger::build('electricalTemporary', 'Temporary Electrical Services'),
            TinyInteger::build('signs', 'New Signs')
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

        global $pool;
        $idGroup = $pool->SysVar->get("PermitTechsGroupId");
        Groups::addGroupRole($idGroup, null, $this->getObjectId(), $this->role('Permit Tech')->getId());

        /** @var Fee $fees */
        $fees = $this->getChild('fees');
        $tag = $fees->getFeeTagByTitle('2016');
        $this->keydict->idFeeTag->set($tag['idFeeTag']);
        $this->save();
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
        $this->keydict->parcelNumber->set("8235".sprintf("%08d",rand(0,99999999)));
        $this->save();

        $this->setProjectState('Routing Pending');
    }

    /**
     * Waiting State
     * Functions:
     *  If the RouteTree was Saved, set State to Routing Complete. Similar to Form Pending
     */
    public function stateRoutingPending()
    {
        /** @var Controller|RouteTree $routeTree */
        $routeTree = $this->getChild('routing');
        $routeTree->onUpdate(function () use ($routeTree) {
            if ($routeTree->isSaved()) {
                $this->setProjectState('Routing Complete');
            }
        });
    }

    /**
     * Pass through State
     * Functions:
     *  State to Submittal Setup
     */
    public function stateRoutingComplete()
    {
        /** @var Interstitial $interstitial */
        $interstitial = $this->getChild('interstitial');

        $this->buildInterstitial($interstitial);
        $this->setProjectState('Submittal Setup');
    }

    /**
     * Pass through State
     * Functions:
     *  Create the root phase
     *  State to Submittal Pending
     */
    public function stateSubmittalSetup()
    {
        if(!$this->keydict->gotoPhaseId->get()) {
            // create first submittal phase here
            /** @var Submittal $phases */
            $phases = $this->getChild('phases');
            //TODO CREATE SUBMITTALS PHASES And Submittal, depending on '????'
            $phase1 = $phases->createPhase("Project Submittals", "All Conditions For Approval", 0, true);
            $phase2 = $phases->createPhase("Main Phase", "Required for Building Permit", $phase1['idSubmittalPhase'], true);
            $phase3 = $phases->createPhase("Submittals", "Primary Submittal Package", $phase2['idSubmittalPhase'], true);

            $phases->createSubmittal($phase3['idSubmittalPhase']);

            $this->keydict->gotoPhaseId->set($phase3['idSubmittalPhase']);

            $this->save();
        }

        $this->setProjectState('Submittal Pending');
    }

    /**
     * Waiting State
     * Functions:
     *  If the Submittal was marked Complete, proceed
     */
    public function stateSubmittalPending()
    {
        /** @var Controller|Submittal $submittal */
        $submittal = $this->getChild('phases');
        $submittal->onSubmittalComplete(function ($idSubmittalPhase, $idSubmittal) use ($submittal) {
            global $pool;
            $submittal->setRedirect($this->getPath() . '/interstitial');
            $this->setProjectState('Submittal Complete');
        });

        $submittal->onSubmittalPhaseDelete(function ($idSubmittalPhase) {
            if ($this->keydict->gotoPhaseId->get() == $idSubmittalPhase) {
                $this->keydict->gotoPhaseId->set(0);
                $this->save();
            }
        });
    }

    /**
     * Pass through State
     * Functions:
     *  State to Reviewers Pending
     */
    public function stateSubmittalComplete()
    {
        /** @var Fee $fees */
        $fees = $this->getChild('fees');
        $fees->createProjectFee();
        $fees->recalculateProjectFees();

        $this->setProjectState('Acceptance Pending');
    }

    /**
     *
     */
    public function stateAcceptancePending()
    {
        /** @var Interstitial $interstitial */
        $interstitial = $this->getChild('interstitial');

        $this->buildInterstitial($interstitial);
        $this->onAccept(function () {
            $this->setProjectState('Acceptance Complete');
        });

        $this->onReject(function () {
            if($goToPhase = $this->getKeydict()->gotoPhaseId->get()) {
                /** @var Controller|Submittal $submittal */
                $submittal = $this->getChild('phases');
                $submittal->openTopSubmittal($goToPhase);
            }
            /** @var Controller|Fee $fees */
            $fees = $this->getChild('fees');
            $fees->deleteProjectFees();
            $this->setProjectState('Form Pending');
            $this->route(Request::get()->setRequestPath($this->getPath() . $this->state('Form Pending')->getHome())->setRequestMethod('GET'));
        });
    }

    /**
     *
     */
    public function stateAcceptanceComplete()
    {
        /** @var Controller|Fee $fees */
        $fees = $this->getChild('fees');

        if($fees->checkDepositsPaid()) {
            $this->getKeydict()->paidDeposits->set(1);
            $this->save();
            $this->setProjectState('Fee Deposit Complete');
        }
        else {
            $this->setProjectState('Fee Deposit Pending');
        }
    }

    /**
     *
     */
    public function stateFeeDepositPending()
    {
        /** @var Controller|Fee $fees */
        $fees = $this->getChild('fees');

        $fees->onPayment(function () use ($fees){
            if($fees->checkDepositsPaid()) {
                $this->getKeydict()->paidDeposits->set(1);
                $this->save();
                $fees->setRedirect($this->getPath() . '/interstitial');
            }
        });

        if($fees->checkDepositsPaid()) {
            $this->setProjectState('Fee Deposit Complete');
        }
    }

    /**
     *
     */
    public function stateFeeDepositComplete()
    {
        $this->getKeydict()->permitNumber->set(date('y')."-".IncrementGenerators::incrementByKey('permitNumber'));
        $this->save();
        /** @var Interstitial $interstitial */
        $interstitial = $this->getChild('interstitial');

        $this->buildInterstitial($interstitial);
        $this->setProjectState('Reviewers Pending');
    }

    /**
     *
     * Functions:
     *
     */
    public function stateReviewersPending()
    {
        global $pool;

        /** @var Controller|Submittal $submittal */
        $submittal = $this->getChild('phases');
        $idSubmittal = end($submittal->getSubmittals($this->keydict->gotoPhaseId->get())['submittals'])['idSubmittal'];

        //$submittal->setRedirect($pool->SysVar->get('siteUrl') . '/dashboard');
        $review = $submittal->getReview($idSubmittal);
        //TODO Replace the call to this function with a workflow specific one
        if(!Reviews::getReviews($review)['reviews']) {
            $days = 7;
            $dateDue = date("Y-m-d H:i:s", strtotime("+" . $days . " days", strtotime(DateTime::timestamps())));
            $review->createProjectReview($dateDue);
        }

        // for all Complete Submittals that have no Reviewer state (inc. Reviews that are empty auto-assign reviewers)
        global $pool;
        $idGroup = $pool->SysVar->get("ReviewersGroupId");
        Groups::addGroupRole($idGroup, null, $this->getObjectId(), $this->role('Reviewer')->getId());

        if(!Reviews::getReviews($review)['reviews'])
            $this->setProjectState('Fee Payment Pending');
        else
            $this->setProjectState('Reviewers Complete');
    }

    /**
     * Pass through State
     * Functions:
     *  State to Reviewers Pending
     */
    public function stateReviewersComplete()
    {
        $this->setProjectState('Under Review');
    }

    /**
     * Waiting State
     * Functions:
     *
     */
    public function stateUnderReview()
    {
        /** @var Controller|Submittal $submittal */
        $submittal = $this->getChild('phases');

        $submittal->onSubmittalComplete(function ($idSubmittalPhase, $idSubmittal) use ($submittal) {
            $review = $submittal->getReview($idSubmittal);
            //TODO Replace the call to this function with a workflow specific one
            if(!Reviews::getReviews($review)['reviews']) {
                $days = 7;
                $dateDue = date("Y-m-d H:i:s", strtotime("+" . $days . " days", strtotime(DateTime::timestamps())));
                $review->createProjectReview($dateDue);
            }
            global $pool;
            $submittal->setRedirect($this->getPath() . '/interstitial');
        });

        $submittal->onSubmittalPhaseDelete(function ($idSubmittalPhase) {
            if ($this->keydict->gotoPhaseId->get() == $idSubmittalPhase) {
                $this->keydict->gotoPhaseId->set(0);
                $this->save();
            }
        });

        $submittal->onReviewAllAccept(function ($idSubmittalPhase, $idSubmittal) use ($submittal) {
            $this->stateRun(); // rerun State
        });

        // when all reviews for a given submittal are complete (but not accepted)
        $submittal->onReviewAllComplete(function ($idSubmittalPhase, $idSubmittal) use ($submittal) {
//            if ($submittal->submittalPhaseIsComplete($idSubmittalPhase)) {
                $submittal->createSubmittal($idSubmittalPhase);
//            }
        });

        if ($this->getKeydict()->activeSubRevCycles->get()== 0) {
            // There are no active Submittal/Review Cycles, but we are in a place where we know there was at least one
            // created.
            $this->setProjectState('Fee Payment Pending');
        }
    }

    /**
     * Pass through State
     * Functions:
     *  State to Submittal Setup
     */
    public function stateFeePaymentPending()
    {
        /** @var Controller|Fee $fees */
        $fees = $this->getChild('fees');

        $fees->onPayment(function () use ($fees){
            if($fees->checkFeesPaid()) {
                $this->getKeydict()->paidFees->set(1);
                $this->save();
                $fees->setRedirect($this->getPath() . '/interstitial');
                $this->setProjectState('Fee Payment Complete');
            }
        });
    }

    /**
     * Pass through State
     * Functions:
     *  State to Submittal Setup
     */
    public function stateFeePaymentComplete()
    {
        $this->setProjectState('Project Complete');
    }

    public function stateProjectComplete()
    {
        /** @var Controller|Submittal $submittal */
        $submittal = $this->getChild('phases');

        $submittal->onReviewReopen(function () {
            $this->setProjectState('Under Review');
        });

        $submittal->onSubmittalPhaseDelete(function ($idSubmittalPhase) {
            if ($this->keydict->gotoPhaseId->get() == $idSubmittalPhase) {
                $this->keydict->gotoPhaseId->set(0);
                $this->save();
            }
        });

        global $pool;
        $idGroup = $pool->SysVar->get("ReviewersGroupId");
        Groups::deleteGroupRole($idGroup, null, $this->getObjectId(), $this->role('Reviewer')->getId());
    }

    /**
     * @throws Exception
     */
    private function buildInterstitial(Interstitial $interstitial)
    {
        global $pool;
        $interstitial->setIsSet(true);
        $siteUrl = $pool->SysVar->get("siteUrl");
        $state = $this->getProjectState()->getName();
        $interstitial->buttonsReset();
        switch($state) {
            case 'Routing Complete':
                $description = "<p>Your application is almost complete. The next step is to upload your electronic documents. The following files are required:</p>
<ul>
<li>Transmittal Letter (PDF, DOC)</li>
<li>Project Plans (PDF)</li>
</ul>
<p>Additional documents may be required depending on your project type.</p>
<div class='alert alert-info'>If you are not ready to upload documents at this time, you may safely close your browser and resume this process at any time.  You will be able to select this application from your dashboard.</div>
";
                $interstitial->setBody($description);
                $interstitial->setTitle("Prepare Submittal");
                $interstitial->addButton("Proceed to Submittal",$siteUrl.$this->getPath(),"primary");
                break;
            case 'Acceptance Pending':
                if($this->hasPrivilege(Privilege::ADMIN, $this->getChild('interstitial'))) {
                    $description = "When you complete your preliminary review of this application, please choose to Accept or Reject this application. Accepting this application will allow the application process to proceed. Rejecting this application will notify the applicant to resubmit their application.";
                    $interstitial->setBody($description);
                    $interstitial->setTitle("Application Acceptance");
                    $interstitial->addButton("Reject", $siteUrl . $this->getPath() . $this->state('Acceptance Pending')->getHome(), "default", "onReject");
                    $interstitial->addButton("Accept", $siteUrl . $this->getPath() . $this->state('Acceptance Pending')->getHome(), "primary", "onAccept");
                }
                else {
                    $description = "You have completed the application.  It will now be reviewed by a Permit Technician and you will be notified by e-mail when it has been accepted or rejected.";
                    $interstitial->setBody($description);
                    $interstitial->setTitle("Submittal Uploaded");
                    break;
                }
                break;
            case 'Acceptance Complete':
                $description = "The application has been accepted. The applicant is now required to submit a deposit.";
                $interstitial->setBody($description);
                $interstitial->setTitle("Application Accepted");
                break;
            case 'Fee Deposit Complete':
                $interstitial->buttonsReset();
                $description = "All project deposits have been successfully paid.";
                $interstitial->setBody($description);
                $interstitial->setTitle("Project Deposits Paid");
                break;
            case 'Under Review':
                $description = "Your resubmittal has been received.  The documents will be reviewed again by Plan Checkers and you will receive an e-mail when the process is complete.";
                $interstitial->setBody($description);
                $interstitial->setTitle("Submittal Revised");
                break;
            case 'Fee Payment Complete':
                $interstitial->buttonsReset();
                $description = "All project fees have been successfully paid.";
                $interstitial->setBody($description);
                $interstitial->setTitle("Project Fees Paid");
                break;
            case 'Project Complete':
                $description = "The application process has been completed.";
                $interstitial->setBody($description);
                $interstitial->setTitle("Project Application Complete");
                $interstitial->addButton("Download Permit",$siteUrl.$this->getPath(),"primary");
                break;
        }
    }
    /******************************************   #INITIALIZATION#  ******************************************/

    /**
     * @param $result
     * @return StandardView
     */
    public function view($result)
    {
        $table =  Table::build(
        // Applicant Information
            FixedString64::build('state', 'Current Project State'),
            IdInteger::build('idProject', 'Project ID')->joinsOn(Projects::keydict())->setMeta('foreignPrimaryKey'),
            Fullname::build('applicantName', 'Applicant Name', '')->setRequired(),
            String256::build('contactAddress', 'Address', '')->setRequired(),
            PhoneNumber::build('phoneNumber', 'Phone Number', '')->setRequired(),
            Email::build('contactEmail', 'Contact Email', '')->setRequired(),

            // Project Information
            String256::build('siteAddress', 'Site Address', '')->setRequired(),
            ProjectName::build('projectName', 'Project Name', ''),
            MoneyVal::build('projectValuation', 'projectValuation', '')->setRequired(),
            Text::build('detailedDescription', 'Detailed Description of Work', '')->setRequired(),

            // Property Owner Information
            Fullname::build('propertyOwnerName', 'Name', '')->setRequired(),
            String256::build('propertyOwnerAddress', 'Address', '')->setRequired(),
            PhoneNumber::build('propertyOwnerPhone', 'Phone Number', '')->setRequired(),
            Email::build('propertyOwnerEmail', 'Email Address', '')->setRequired(),

            // Contractor Information
            String256::build('contractorName', 'Company Name', '')->setRequired(),
            String256::build('contractorAddress', 'Address', '')->setRequired(),
            PhoneNumber::build('contractorPhone', 'Phone Number', '')->setRequired(),
            Email::build('contractorEmail', 'Email Address', '')->setRequired(),
            String256::build('contractorLicenseNo', 'License #', '')->setRequired(),


            Bits::make(     // Will be modified later with the settings from RouteTree init
                'flags',
                Keydict\Entry\Bit::build(0, 'isResidential', 'Is Residential?')
            ),

            Keydict\Entry\Integer::build('activeSubRevCycles', 'Active Submittal Review Cycles'),
            IdInteger::build('gotoPhaseId', 'Go to Submittal Phase Id'),
            Integer::build('reviewsComplete', 'Go to Submittal Phase Id'),
            Integer::build('reviews', 'Go to Submittal Phase Id'),
            IdInteger::build('idFeeTag', 'Project Code Year Tag'),
            Integer::build('paidDeposits', 'Has Paid Deposits'),
            Integer::build('paidFees', 'Has Paid Fees'),
            String256::build('permitNumber', 'Permit Number'),
            String256::build('parcelNumber', 'Parcel Number'),
            FixedString128::build('title','Project Title')
        );
        $table->setName('Projects');
//
//        /** @var StandardView $test Assign the namespace and keydict to the view */
//        $view = StandardView::build('Projects.All', 'Projects', $table, $result);
//        $view->add(
//            Column::import($table->title)->filterBySearch()->setSort(true)->setIsLink(true),
//            Column::import($table->description)->setSort(false),
//            Column::import($table->state)->bucketBy("DESC")
//        );
        /** @var StandardView $test Assign the namespace and keydict to the view */
        $view = StandardView::build($this->getClass().'.All', $this->getClass(), $table, $result);
        $view->add(

            Column::import($table->idProject)->setEnabled(false)->setSort(true),
            Column::import($table->title)->filterBySearch()->setSort(false)->setIsLink(true),
            Column::import($table->siteAddress)->setSort(false),
            Column::import($table->permitNumber)->setSort(false),
            Column::import($table->applicantName)->setSort(false),
            Column::import($table->detailedDescription)->setSort(false),
            Column::import($table->state)->setSort(false)
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
            $keydict->applicantName,
            $keydict->contactAddress,
            $keydict->phoneNumber,
            $keydict->contactEmail,

            $keydict->siteAddress,
            $keydict->projectName,
            $keydict->projectValuation,
            $keydict->detailedDescription,

            $keydict->propertyOwnerName,
            $keydict->propertyOwnerAddress,
            $keydict->propertyOwnerPhone,
            $keydict->propertyOwnerEmail,

            $keydict->contractorName,
            $keydict->contractorAddress,
            $keydict->contractorPhone,
            $keydict->contractorEmail,
            $keydict->contractorLicenseNo
        );

        $obj->overrideTemplate('Dummy.Application.Form.html.twig');
    }

    /**
     * @param RouteTree $obj
     */
    public function initRouting(RouteTree $obj)
    {
        /** @var Bits $flags */
        $flags = $this->getKeydict()->flags;
        $obj->bindFlags($flags);
        /** @var BitString $selected */
        $selected = $this->getKeydict()->selected;
        $obj->bindSelectedOptions($selected);
        $obj->loadConfiguration();
    }

    /**
     * @param FolderRoot $obj
     */
    public function initFiles(FolderRoot $obj)
    {
        $acceptedFileTypes = ["pdf", "txt", "doc", "xls", "xlsx", "docx", "jpg", "png", "zip", "rar"];
        $maxFileSize = 500000000;
        $minFileSize = 1;
        $fileCategories = ["Transmittal Letter","Plans", "Comments"];
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
     * @param Submittal $obj
     */
    public function initPhases(Submittal $obj)
    {
        //TODO Since an Applicant is in the Under Review stage always after a submittal, they have the chance to update a closed Submittal (which they shouldn't)
        $rootDepth = 2;
        $depth0 = $obj;
        $depth1 = $depth0;
        $depth2 = $depth0;
        /** @var FolderRoot $folderRoot */
        $folderRoot = $this->getChild('files');
        $obj->bindFolderRoot($folderRoot);
        $obj->bindRootDepth($rootDepth);
        $obj->bindModuleByDepth([$depth0,$depth1,$depth2]);
        /** @var Review $review */
        $review = $this->getChild('reviews');
        $obj->bindReview($review);
    }

    /**
     * @param Review $obj
     */
    public function initReviews(Review $obj)
    {
        /** @var FolderRoot $folderRoot */
        $folderRoot = $this->getChild('files');
        $obj->bindFolderRoot($folderRoot);

        /** @var ModuleRule $moduleRules */
        $moduleRules = $this->getChild('rules');
        $obj->bindModuleRule($moduleRules);
    }

    /**
     * @param Mail $obj
     */
    public function initMail(Mail $obj)
    {
        /*global $pool;

        $idUser = (int)$pool->User->getIdUser();
        $firstName = $pool->User->getFirstName();*/

        $obj->useTemplate(
            'New Template',
            Keydict::build(String::build('myName', 'My Name'))
        );

        //$obj->send('New Template', [$idUser], ['myName'=>$firstName], [1]);
    }

    /**
     * @param Fee $obj
     * @throws Exception
     */
    public function initFees(Fee $obj)
    {
        /** @var ModuleRule $moduleRules */
        $moduleRules = $this->getChild('rules');
        $obj->bindModuleRule($moduleRules);
        $obj->setTextContact("To pay fees, visit the Building Department (Mon-Fri, 9am-5pm):<p><strong>Yosemite Building Department</strong><br>1100 Grove Blvd., Yosemite, CA 95389</p>");
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
     * @param Interstitial $obj
     */
    public function initInterstitial(Interstitial $obj)
    {
        if(!$obj->getisSet())
            self::buildInterstitial($obj);
    }

    /********************************************   #COMMISSION#  ********************************************/

    /**
     * Dummy Controller's commisioning functioning that initially builds it's children, which activates their individual commissioning functions.
     */
    public function commission()
    {
        $this->buildChildren();
    }

    /**
     * Create the RouteTrees required by a given Controller and load the presets
     * @param RouteTree $obj
     * @throws Keydict\Exception\InvalidValueException
     * @throws Keydict\Exception\KeydictException
     * @internal param $controller
     */
    public function commissionRouting(RouteTree $obj)
    {
        $preset = json_decode(file_get_contents(APP_PATH."/app/Dummy/preset/BuildingPermitRouteTree.json"),true);

        $routeTreeData = RouteTrees::keydict();
        $routeTreeData->idController->set($obj->getId());
        $routeTreeData->helpText->setMeta('ignore');
        $routeTreeData->configuredFlags->setMeta('ignore');
        $routeTreeData->lastBitPosition->setMeta('ignore');
        $routeTreeData->insert();
        RouteTree::loadPreset($routeTreeData->idRoute->get(), $preset);
        RouteTree::rebuildConfiguredFlags($routeTreeData->idRoute->get());
    }

    /**
     * Commissioning function for Reviews that adds the inital review types to the database.
     * @param Review $obj
     * @throws Exception
     */
    public function commissionReviews(Review $obj)
    {
        global $pool;
        $rT = $obj->bindReviewTypes(
            ["name"=>"Land Use", "idGroup"=>$pool->SysVar->get("LandUseReviewersGroupId")],
            ["name"=>"Structural", "idGroup"=>$pool->SysVar->get("StructuralReviewersGroupId")],
            ["name"=>"Building", "idGroup"=>$pool->SysVar->get("BuildingReviewersGroupId")],
            ["name"=>"Health", "idGroup"=>$pool->SysVar->get("HealthReviewersGroupId")],
            ["name"=>"Grading", "idGroup"=>$pool->SysVar->get("GradingReviewersGroupId")],
            ["name"=>"Permit Tech", "idGroup"=>$pool->SysVar->get("PermitTechsGroupId")]);
        //TODO Make a workflow function for this create.

        // Land Use
        //  Residential
        ModuleRules::create($obj->getId(), $rT[0]['idReviewType'], $rT[0]['title'], "flags.isResidential == 1 AND, flags.isNewConstruction == 1 OR, flags.isCategoryOther == 1 OR, flags.isAddition == 1 ", "Add");
        //  Commercial
        ModuleRules::create($obj->getId(), $rT[0]['idReviewType'], $rT[0]['title'], "flags.isCommercial == 1 AND, flags.isNewConstruction == 1 AND, flags.isGeneralConstruction == 1 ", "Add");
        ModuleRules::create($obj->getId(), $rT[0]['idReviewType'], $rT[0]['title'], "flags.isCommercial == 1 AND, flags.isAddition == 1 ", "Add");

        // Structural
        //  Residential
        ModuleRules::create($obj->getId(), $rT[1]['idReviewType'], $rT[1]['title'], "flags.isResidential == 1 AND, flags.isNewConstruction == 1 AND, flags.isCategoryOther == 1 OR, flags.isSingleFamily == 1 OR, flags.isMultiFamily == 1 OR, flags.isTwoFamily == 1 OR, flags.isTownhome == 1 ", "Add");
        //  Commercial
        ModuleRules::create($obj->getId(), $rT[1]['idReviewType'], $rT[1]['title'], "flags.isCommercial == 1 AND, flags.isNewConstruction == 1 AND, flags.isGeneralConstruction == 1 ", "Add");
        //  Any Case from >New Construction
        ModuleRules::create($obj->getId(), $rT[1]['idReviewType'], $rT[1]['title'], "flags.isCanopy == 1 OR, flags.isDetachedGarage == 1 ", "Add");
        //  Any Case from >Remodel
        ModuleRules::create($obj->getId(), $rT[1]['idReviewType'], $rT[1]['title'], "flags.isRoofConversion == 1 OR, flags.isDisaster == 1", "Add");
        //  Any Case from Other
        ModuleRules::create($obj->getId(), $rT[1]['idReviewType'], $rT[1]['title'], "flags.isCategoryOther == 1", "Add");

        // Building Code
        //  Residential
        ModuleRules::create($obj->getId(), $rT[2]['idReviewType'], $rT[2]['title'], "flags.isSingleFamily == 1 OR, flags.isMultiFamily == 1 OR, flags.isTwoFamily == 1 OR, flags.isTownhome == 1 ", "Add");
        ModuleRules::create($obj->getId(), $rT[2]['idReviewType'], $rT[2]['title'], "flags.isResidential == 1 AND, flags.isAccessoryStructure == 1 ", "Add");
        //  Commercial
        ModuleRules::create($obj->getId(), $rT[2]['idReviewType'], $rT[2]['title'], "flags.isCommercial == 1 AND, flags.isGeneralConstruction == 1 ", "Add");
        //  Any Addition
        ModuleRules::create($obj->getId(), $rT[2]['idReviewType'], $rT[2]['title'], "flags.isAddition == 1", "Add");
        //  Any Case from >Remodel
        ModuleRules::create($obj->getId(), $rT[2]['idReviewType'], $rT[2]['title'], "flags.isRoofConversion == 1 OR, flags.isDisaster == 1 OR, flags.isWindows == 1 OR, flags.isGeneral == 1 ", "Add");
        //  Any Other Case
        ModuleRules::create($obj->getId(), $rT[2]['idReviewType'], $rT[2]['title'], "flags.isSubCategoryOther == 1", "Add");
        ModuleRules::create($obj->getId(), $rT[2]['idReviewType'], $rT[2]['title'], "flags.isCategoryOther == 1", "Add");

        // Health
        ModuleRules::create($obj->getId(), $rT[3]['idReviewType'], $rT[3]['title'], "flags.isPoolSpa == 1 OR, flags.isWaterTank == 1", "Add");

        // Grading
        ModuleRules::create($obj->getId(), $rT[4]['idReviewType'], $rT[4]['title'], "flags.isGrading == 1 ", "Add");

        // Permit Tech
        ModuleRules::create($obj->getId(), $rT[5]['idReviewType'], $rT[5]['title'], "flags.isReRoof == 1 OR, flags.isSign == 1 ", "Add");
        ModuleRules::create($obj->getId(), $rT[5]['idReviewType'], $rT[5]['title'], "flags.isDemolition == 1 AND, flags.isGrading != 1", "Add");
        ModuleRules::create($obj->getId(), $rT[5]['idReviewType'], $rT[5]['title'], "flags.isUpgrades == 1 OR, flags.isShed == 1 OR, flags.isExteriorFinishes == 1", "Add");
        ModuleRules::create($obj->getId(), $rT[5]['idReviewType'], $rT[5]['title'], "flags.isCategoryOther == 1", "Add");
    }

    /**
     * @param Mail $obj
     * @throws Exception
     */
    public function commissionMail(Mail $obj)
    {
        $obj->registerTemplate(
            'New Template',
            'templateTest.twig'
        );
    }

    /**
     * @param Fee $obj
     */
    public function commissionFees(Fee $obj)
    {
        $feeTypeArray = $obj->bindFeeTypes(['feeTypeTitle' =>"Fees", 'isPayable' => true, 'isOpen' => true, 'isDeposit' => false],
            ['feeTypeTitle' =>"Deposits", 'isPayable' => true, 'isOpen' => true, 'isDeposit' => true],
            ['feeTypeTitle' =>"Valuations", 'isPayable' => false, 'isOpen' => false, 'isDeposit' => false]);

        $feeTagCategoryArray = $obj->bindFeeTagCategories(['title'=>'Years', 'isFeeSchedule'=>true], ['title'=>'Department', 'isFeeSchedule'=>false]);

        $feeTagArray = $obj->bindFeeTags(['feeTagValue' =>"2016", 'idFeeTagCategory' => $feeTagCategoryArray[0]['idFeeTagCategory']],
            ['feeTagValue' =>"2015", 'idFeeTagCategory' => $feeTagCategoryArray[0]['idFeeTagCategory']],
            ['feeTagValue' =>"Building", 'idFeeTagCategory' => $feeTagCategoryArray[1]['idFeeTagCategory']],
            ['feeTagValue' =>"Planning", 'idFeeTagCategory' => $feeTagCategoryArray[1]['idFeeTagCategory']],
            ['feeTagValue' =>"Fire", 'idFeeTagCategory' => $feeTagCategoryArray[1]['idFeeTagCategory']],
            ['feeTagValue' =>"Public Works", 'idFeeTagCategory' => $feeTagCategoryArray[1]['idFeeTagCategory']]
            );
//
//        $title = "Building Permit Fee";
//        $title1 = "Commercial Building Permit Fee";
//        $description = "Standard building permit fee required by the city.";
//        $description1 = "Standard fee required by the city for building a commercial structure.";
//        $minimumValue = 0;
//        $calculationMethod = ['isFixed' => true,
//            'isUnit' => false,
//            'isFormula' => false,
//            'isMatrix' => false];
//        $calculationMethod2 = ['isFixed' => true,
//            'isUnit' => true,
//            'isFormula' => true,
//            'isMatrix' => false];
//        $calculationMethod3 = ['isFixed' => true,
//            'isUnit' => true,
//            'isFormula' => false,
//            'isMatrix' => false];
//
//
//        $feeTemplate = $obj->createFeeTemplate($feeTypeArray[0]['idFeeType'], $title, $description, $minimumValue, 70.00,
//            '', 'Basis()', $calculationMethod, $feeTagArray[0]['idFeeTag'], $feeTagArray[2]['idFeeTag']);
//        $feeTemplate2 = $obj->createFeeTemplate($feeTypeArray[1]['idFeeType'], $title1, $description1, $minimumValue, 150.00,
//           '', 'Basis()', $calculationMethod, $feeTagArray[0]['idFeeTag'], $feeTagArray[2]['idFeeTag']);
//        $feeTemplate3 = $obj->createFeeTemplate($feeTypeArray[0]['idFeeType'], $title1, $description1, $minimumValue, 500.00,
//            '', 'Basis()', $calculationMethod, $feeTagArray[1]['idFeeTag'], $feeTagArray[2]['idFeeTag']);
//        $feeTemplate4 = $obj->createFeeTemplate($feeTypeArray[0]['idFeeType'], "Advanced Fee Testing", "Amazing Testing Fee", $minimumValue, 10.00,
//            '', "Basis() * Quantity() * project.reviewsComplete", $calculationMethod2, $feeTagArray[0]['idFeeTag'], $feeTagArray[2]['idFeeTag']);
//        $feeTemplate5 = $obj->createFeeTemplate($feeTypeArray[0]['idFeeType'], "Unit Fee Testing", "Unit Fee Testing Fee", $minimumValue, 15.00,
//            '', 'Basis() * Quantity()', $calculationMethod3, $feeTagArray[0]['idFeeTag'], $feeTagArray[2]['idFeeTag']);
//
//        $feeTemplates = $obj->getFeeTemplates();
//        $titleArray = [];
//        $objectActions = [];
//        foreach($feeTemplates as $feeTemplate0)
//            if($feeTemplate0['status']['isActive'])
//                $titleArray[$feeTemplate0['title']] = $feeTemplate0;
//        $i = 1;
//        foreach($titleArray as $key => $value)
//            $objectActions[$key] = ["idObjectAction"=> $i++,
//                "objectActionTitle" =>$key];
//
//        ModuleRules::create($obj->getId(), $objectActions[$feeTemplate['title']]['idObjectAction'], $feeTemplate['title'], "flags.isResidential == 1", "Add");
//        ModuleRules::create($obj->getId(), $objectActions[$feeTemplate5['title']]['idObjectAction'], $feeTemplate5['title'], "flags.isResidential == 1", "Add");
//        ModuleRules::create($obj->getId(), $objectActions[$feeTemplate4['title']]['idObjectAction'], $feeTemplate4['title'], "flags.isResidential == 1", "Add");
//        ModuleRules::create($obj->getId(), $objectActions[$feeTemplate2['title']]['idObjectAction'], $feeTemplate2['title'], "flags.isCommercial == 1 OR, flags.isCategoryOther == 1", "Add");
        $import = function ($file) {
            $handle = fopen($this->getPresetPath().'/'.$file, "r");
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    DB::sql($line);
                }
                fclose($handle);
            } else {
                // error opening the file.
            }
        };

        $files = ['master_db_FeeMatrices.sql', 'master_db_FeeTemplateFeeTag.sql', 'master_db_FeeTemplates.sql'];

        foreach ($files as $file) $import($file);

        $feeTemplates = $obj->getFeeTemplates();
        $titleArray = [];
        $objectActions = [];
        foreach($feeTemplates as $feeTemplate0)
            if($feeTemplate0['status']['isActive'])
                $titleArray[$feeTemplate0['title']] = $feeTemplate0;
        $i = 1;
        foreach($titleArray as $key => $value)
            $objectActions[$key] = $i++;

        // ModuleRules::create($obj->getId(), 'templateID', 'templateTitle', "flags.isResidential == 1", "Add");

        // Building Permit Fee
        //  New Construction, Addition, Remodel
        ModuleRules::create($obj->getId(), $objectActions["Building Permit Fee"], "Building Permit Fee", "flags.isResidential == 1 AND, flags.isRemodel == 1 OR, flags.isAddition == 1 OR, flags.isSubCategoryOther == 1 OR, flags.isNewConstruction == 1", "Add");
        ModuleRules::create($obj->getId(), $objectActions["Building Permit Fee"], "Building Permit Fee", "flags.isCommercial == 1 AND, flags.isRemodel == 1 OR, flags.isAddition == 1 OR, flags.isSubCategoryOther == 1 OR, flags.isGeneralConstruction == 1 OR, flags.isPoolSpa == 1 OR, flags.isCanopy == 1 OR, flags.isShed == 1 OR, flags.isDetachedGarage == 1", "Add");
        ModuleRules::create($obj->getId(), $objectActions["Building Permit Fee"], "Building Permit Fee", "flags.isCategoryOther == 1", "Add");

        //Plan Check Fee
        ModuleRules::create($obj->getId(), $objectActions["Plan Check Fee"], "Plan Check Fee", "flags.isResidential == 1 AND, flags.isRemodel == 1 OR, flags.isAddition == 1 OR, flags.isSubCategoryOther == 1 OR, flags.isNewConstruction == 1", "Add");
        ModuleRules::create($obj->getId(), $objectActions["Plan Check Fee"], "Plan Check Fee", "flags.isCommercial == 1 AND, flags.isRemodel == 1 OR, flags.isAddition == 1 OR, flags.isSubCategoryOther == 1 OR, flags.isGeneralConstruction == 1 OR, flags.isPoolSpa == 1 OR, flags.isCanopy == 1 OR, flags.isShed == 1 OR, flags.isDetachedGarage == 1", "Add");
        ModuleRules::create($obj->getId(), $objectActions["Plan Check Fee"], "Plan Check Fee", "flags.isCategoryOther == 1", "Add");

        //  Upgrades
        ModuleRules::create($obj->getId(), $objectActions["Electrical: New Circuits"], "Electrical: New Circuits", "flags.isAdditionalCircuit == 1", "Add");
        ModuleRules::create($obj->getId(), $objectActions["Electrical: New/Upgrade/Temporary Service"], "Electrical: New/Upgrade/Temporary Service", "flags.isElectricalNewService == 1 OR, flags.isElectricalServiceUpgrade == 1 OR, flags.isTemporaryPower == 1 ", "Add");
        ModuleRules::create($obj->getId(), $objectActions["Mechanical: Furnaces/AC/Humidifiers"], "Mechanical: Furnaces/AC/Humidifiers", "flags.isFurnace == 1 OR, flags.isAirConditioner == 1 OR, flags.isAirCleaner == 1 OR, flags.isHumidifier == 1", "Add");
        ModuleRules::create($obj->getId(), $objectActions["Plumbing: Water Heaters/Softeners/Boilers"], "Plumbing: Water Heaters/Softeners/Boilers", "flags.isWaterHeater == 1 OR, flags.isWaterSoftener == 1 OR, flags.isBoiler == 1 ", "Add");

        //  Sign
        ModuleRules::create($obj->getId(), $objectActions["Signs"], "Signs", "flags.isSign == 1", "Add");

        // Deposits
        ModuleRules::create($obj->getId(), $objectActions["Pre-Paid Plan Check Fee (Residential)"], "Pre-Paid Plan Check Fee (Residential)", "flags.isResidential == 1 AND, flags.isRemodel == 1 OR, flags.isAddition == 1 OR, flags.isSubCategoryOther == 1 OR, flags.isNewConstruction == 1", "Add");
        ModuleRules::create($obj->getId(), $objectActions["Pre-Paid Plan Check Fee (Commercial)"], "Pre-Paid Plan Check Fee (Commercial)", "flags.isCommercial == 1 AND, flags.isRemodel == 1 OR, flags.isAddition == 1 OR, flags.isSubCategoryOther == 1 OR, flags.isGeneralConstruction == 1 OR, flags.isPoolSpa == 1 OR, flags.isCanopy == 1 OR, flags.isShed == 1 OR, flags.isDetachedGarage == 1", "Add");
        ModuleRules::create($obj->getId(), $objectActions["Pre-Paid Plan Check Fee (Commercial)"], "Pre-Paid Plan Check Fee (Commercial)", "flags.isCategoryOther == 1", "Add");

        // Grading Fees
        ModuleRules::create($obj->getId(), $objectActions["Grading Permit"], "Grading Permit", "flags.isGrading == 1", "Add");
        ModuleRules::create($obj->getId(), $objectActions["Grading Plan Check"], "Grading Plan Check", "flags.isGrading == 1", "Add");

        // Demolition Fees
        ModuleRules::create($obj->getId(), $objectActions["Demolition Permit Fee (Commercial)"], "Demolition Permit Fee (Commercial)", "flags.isCommercial == 1 AND, flags.isDemolition == 1", "Add");
        ModuleRules::create($obj->getId(), $objectActions["Demolition Plan Check Fee (Commercial)"], "Demolition Plan Check Fee (Commercial)", "flags.isCommercial == 1 AND, flags.isDemolition == 1", "Add");
        ModuleRules::create($obj->getId(), $objectActions["Demolition Permit Fee (Residential)"], "Demolition Permit Fee (Residential)", "flags.isResidential == 1 AND, flags.isDemolition == 1", "Add");
        ModuleRules::create($obj->getId(), $objectActions["Demolition Plan Check Fee (Residential)"], "Demolition Plan Check Fee (Residential)", "flags.isResidential == 1 AND, flags.isDemolition == 1", "Add");

        // Valuation (essentially always added right now)
        ModuleRules::create($obj->getId(), $objectActions["Declared Valuation"], "Declared Valuation", "projectValuation > 0", "Add");
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

            $title = $obj->getKeydict()->getField('siteAddress')->getCleanValue();

            $this->getProjectData()->getField('title')->set($title);
            $this->getProjectData()->update();
            $obj->getKeydict()->setSaved(true);
        });
    }

    /**
     * Trigger Handler function for the RouteTree module.
     * @param RouteTree $obj
     */
    public function triggerRouting(RouteTree $obj)
    {
        $obj->onUpdate(function() use ($obj) {
            $this->save();

            $description = implode(", ", $obj->routeTreeSummary());

            $this->getProjectData()->getField('description')->set($description);
            $this->getProjectData()->update();
        });
    }

    /**
     * Trigger Handler function for the FolderRoot module.
     * @param FolderRoot $obj
     */
    public function triggerFiles(FolderRoot $obj)
    {

    }

    /**
     * Trigger Handler function for the Submittal module.
     * @param Submittal $obj
     */
    public function triggerPhases(Submittal $obj)
    {
        $obj->onSubmittalCreate(function () {
            $this->getKeydict()->activeSubRevCycles->set($this->getKeydict()->activeSubRevCycles->get()+1);
            $this->save();
        });

        $obj->onReviewComplete(function($review) use($obj) {
            $obj->checkReviews($review);
        });

        $obj->onReviewReopen(function($review) use($obj) {
            $obj->checkReviews($review);
        });

        $obj->onReviewCreate(function($review) use($obj) {
            $obj->checkReviews($review);
        });

        $obj->onReviewDelete(function($review) use($obj) {
            $obj->checkReviews($review);
        });

        // when all reviews for a given submittal are accepted
        $obj->onReviewAllAccept(function ($idSubmittalPhase, $idSubmittal) {
            $this->getKeydict()->activeSubRevCycles->set($this->getKeydict()->activeSubRevCycles->get()-1);
            $this->save();
        });

        // when all reviews for a given submittal are complete (but not accepted)
        $obj->onReviewAllComplete(function ($idSubmittalPhase, $idSubmittal) {
            $this->getKeydict()->activeSubRevCycles->set($this->getKeydict()->activeSubRevCycles->get()-1);
            $this->save();
        });
    }

    /**
     * Trigger Handler function for the Review module.
     * @param Review $obj
     */
    public function triggerReviews(Review $obj)
    {
        //Lets the Controller who owns this Review know that the Review is Completed.
        $obj->onReviewComplete(function() use($obj) {
            if($obj->getBaseObject()){
                $this->getKeydict()->reviewsComplete->set($this->getKeydict()->reviewsComplete->get()+1);
                $this->save();
                /** @var Triggers $target */
                $target = $obj->getBaseObject();
                $target->trigger('onReviewComplete', $obj);
            }
        });

        //Lets the Controller who owns this Review know that the Review has been Reopened.
        $obj->onReviewReopen(function() use($obj) {
            if($obj->getBaseObject()){
                $this->getKeydict()->reviewsComplete->set($this->getKeydict()->reviewsComplete->get()-1);
                $this->save();
                /** @var Triggers $target */
                $target = $obj->getBaseObject();
                $target->trigger('onReviewReopen', $obj);
            }
        });

        //Lets the Controller who owns this Review know that a Review has been Created.
        $obj->onReviewCreate(function() use($obj) {
            if($obj->getBaseObject()){
                $this->getKeydict()->reviews->set($this->getKeydict()->reviews->get()+1);
                $this->save();
                /** @var Triggers $target */
                $target = $obj->getBaseObject();
                $target->trigger('onReviewCreate', $obj);
            }
        });

        //Lets the Controller who owns this Review know that the Review is Deleted.
        $obj->onReviewDelete(function() use($obj) {
            if($obj->getBaseObject()){
                /** @var Triggers $target */
                $target = $obj->getBaseObject();
                $target->trigger('onReviewDelete', $obj);
            }
        });
    }

    /**
     * @param \Closure $closure
     * @return \eprocess360\v3core\Controller\Trigger\Trigger
     */
    public function onAccept(\Closure $closure)
    {
        /** @var Triggers|Controller|Rules $this */
        return $this->addTrigger(__FUNCTION__, $closure);
    }

    /**
     * @param \Closure $closure
     * @return \eprocess360\v3core\Controller\Trigger\Trigger
     */
    public function onReject(\Closure $closure)
    {
        /** @var Triggers|Controller|Rules $this */
        return $this->addTrigger(__FUNCTION__, $closure);
    }

}