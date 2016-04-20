<?php
/**
 * Created by PhpStorm.
 * User: Jacob
 * Date: 11/13/2015
 * Time: 4:50 PM
 */

namespace eprocess360\v3modules\Submittal;


use eprocess360\v3core\Controller\Children;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Dashboard;
use eprocess360\v3core\Controller\Module;
use eprocess360\v3core\Controller\Persistent;
use eprocess360\v3core\Controller\ProjectToolbar;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\Rules;
use eprocess360\v3core\Controller\Trigger\InterfaceTriggers;
use eprocess360\v3core\Controller\Triggers;
use eprocess360\v3core\Files\Folder;
use eprocess360\v3core\Keydict\Entry\Datetime;
use eprocess360\v3core\Request\Request;
use eprocess360\v3modules\Review\Model\Reviews;
use eprocess360\v3modules\Submittal\Model;
use eprocess360\v3core\Controller\Warden\Privilege;
use eprocess360\v3modules\FolderRoot\FolderRoot;
use eprocess360\v3core\Model\Files;
use eprocess360\v3modules\Submittal\Model\Submittals;
use eprocess360\v3modules\Review\Review;
use Exception;

/**
 * Class SubmittalPhase
 * @package eprocess360\v3modules\Submittal
 */
class Submittal extends Controller implements InterfaceTriggers
{
    use Router, Module, Persistent, Children, Triggers, Rules, ProjectToolbar;
    /** @var FolderRoot*/
    private $folderRoot;
    private $rootDepth;
    /** @var Review*/
    private $reviews;
    private $modulesByDepth;
    protected $staticPath;
    private $redirect;
    private $messages = [200 => false,
        404 => "404 Not Found - Resource not found",
        403 => "Permissions are not sufficient."];


    /**
     * Used as a fail-safe in Controllers to make sure that their dependencies and initializations are met; If not, exception is thrown.
     */
    public function dependencyCheck()
    {
        if(!$this->folderRoot)
            throw new Exception("Submittal Module does not have a FolderRoot set, please bindFolderRoot in the initialization function.");
        if(!$this->reviews)
            throw new Exception("Submittal Module does not have a Review Controller set, please bindReview in the initialization function.");
        if(!$this->rootDepth)
            throw new Exception("Submittal Module does not have a Root Depth set, please bindRootDepth in the initialization function.");
        if(!$this->modulesByDepth)
            throw new Exception("Submittal Module does not have its Submittal Controllers set, please bindModuleByDepth in the initialization function.");

        $this->folderRoot->dependencyCheck();
        $this->reviews->dependencyCheck();
    }


    /*********************************************   #ROUTING#  **********************************************/


    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     */
    public function routes()
    {
        $this->routes->map('GET', '', function () {
            $this->getSubmittalPhasesAPI();
        });
        $this->routes->map('GET', '/[i:idSubmittalPhase]', function ($idSubmittalPhase) {
            $this->getSubmittalPhaseByIdAPI($idSubmittalPhase);
        });
        $this->routes->map('POST', '', function () {
            $this->createSubmittalPhaseAPI();
        });
        $this->routes->map('PUT', '/[i:idSubmittalPhase]', function ($idSubmittalPhase) {
            $this->editSubmittalPhaseAPI($idSubmittalPhase);
        });
        $this->routes->map('DELETE', '/[i:idSubmittalPhase]', function ($idSubmittalPhase) {
            $this->deleteSubmittalPhaseAPI($idSubmittalPhase);
        });
        $this->routes->map('GET', '/[i:idSubmittalPhase]?/submittals', function ($idSubmittalPhase = -1) {
            $this->getSubmittalsAPI($idSubmittalPhase);
        });
        $this->routes->map('GET', '/[i:idSubmittalPhase]?/submittals/[i:idSubmittal]', function ($idSubmittalPhase = -1, $idSubmittal) {
            $this->getSubmittalByIdAPI($idSubmittalPhase, $idSubmittal);
        });
        $this->routes->map('POST', '/[i:idSubmittalPhase]?/submittals', function ($idSubmittalPhase = -1) {
            $this->createSubmittalAPI();
        });
        $this->routes->map('PUT', '/[i:idSubmittalPhase]?/submittals/[i:idSubmittal]', function ($idSubmittalPhase = -1, $idSubmittal) {
            $this->editSubmittalAPI($idSubmittalPhase, $idSubmittal);
        });
        $this->routes->map('DELETE', '/[i:idSubmittalPhase]?/submittals/[i:idSubmittal]', function ($idSubmittalPhase = -1, $idSubmittal) {
            $this->deleteSubmittalAPI($idSubmittalPhase, $idSubmittal);
        });
        $this->routes->map('GET|POST|PATCH|PUT|DELETE', '/[i:idSubmittalPhase]?/submittals/[i:idSubmittal]/files/[*:trailing]?', function ($idSubmittalPhase, $idSubmittal) {
            $this->submittalFilesAPI($idSubmittal);
        });
        $this->routes->map('GET|POST|PATCH|PUT|DELETE', '/[i:idSubmittalPhase]?/submittals/[i:idSubmittal]/reviews/[*:trailing]?', function ($idSubmittalPhase, $idSubmittal) {
            $this->submittalReviewsAPI($idSubmittal);
        });
    }

    /**
     * API function that returns all readable SubmittalPhases in a tree like structure from the highest
     * readable depth for the user. [[Keydict, Children],[Keydict, Children],...]
     * @Required_Privilege: Read
     */
    public function getSubmittalPhasesAPI()
    {
        $allowedDepth = (int)$this->verifyHighestPhaseRole(Privilege::READ);

        $data = Model\SubmittalPhases::allReadableSubmittalPhases($this, $allowedDepth);

        $this->standardResponse($data);
    }

    /**
     * API function that gets the stated SubmittalPhase and it's children in a Tree like structure.
     * If the given phase is a 0 level phase, it instead returns the submittals along with the Phase.
     * @param $idSubmittalPhase
     * @Required_Privilege: Read
     */
    public function getSubmittalPhaseByIdAPI($idSubmittalPhase)
    {
        if (Model\SubmittalPhases::isBaseDepth($idSubmittalPhase)) {
            self::getSubmittalsAPI($idSubmittalPhase);
        } else {
            $parent = Model\SubmittalPhases::sqlFetch($idSubmittalPhase);
            $depth = (int)$parent->depth->get();
            $this->verifyPrivilegeByDepth(Privilege::READ, $depth);

            $data = Model\SubmittalPhases::getById($this, $idSubmittalPhase);

            $this->standardResponse($data);
        }
    }

    /**
     * API Function that creates a new SubmittalPhase
     * @Required_Privilege: Create
     */
    public function createSubmittalPhaseAPI()
    {
        $data = Request::get()->getRequestBody();
        $title = $data['title'] ? $data['title'] : "New Phase";
        $description = $data['description'] ? $data['description']: "Description.";
        $idParent = $data['idParent'];
        $status = true;

        if($idParent) {
            $parent = Model\SubmittalPhases::sqlFetch($idParent);
            $this->verifyPrivilegeByDepth(Privilege::CREATE, $parent->depth->get());
        }
        else
            $this->verifyPrivilegeByDepth(Privilege::CREATE, $this->rootDepth);

        $data = Model\SubmittalPhases::create($this, $title, $description, $idParent, $status);

        $this->standardResponse($data);
    }

    /**
     * API Call to edit a given SubmittalPhase. Can only edit Title and Description.
     * @param $idSubmittalPhase
     * @Required_Privilege: Write
     */
    public function editSubmittalPhaseAPI($idSubmittalPhase)
    {
        $phase = Model\SubmittalPhases::sqlFetch($idSubmittalPhase);
        $depth = (int)$phase->depth->get();
        $this->verifyPrivilegeByDepth(Privilege::WRITE, $depth);

        $data = Request::get()->getRequestBody();
        $title = $data['title'] ? $data['title'] : null;
        $description = $data['description'] ? $data['title'] : null;

        if($title !== null)
            $phase->title->set($title);
        if($description !== null)
            $phase->description->set($description);

        $phase->update();
        $data = $phase->toArray();
        Model\SubmittalPhases::createLinks($data,$this);
        $this->standardResponse($data);
    }

    /**
     * API Call to Delete a given SubmittalPhase and all of it's children SubmittalPhases/Submittals/Folders/Files.
     * @param $idSubmittalPhase
     * @Required_Privilege: Delete
     */
    public function deleteSubmittalPhaseAPI($idSubmittalPhase)
    {
        $this->verifyPrivilege(Privilege::DELETE);

        $data = Model\SubmittalPhases::deletePhase($this, $idSubmittalPhase);

        $this->trigger('onSubmittalPhaseDelete', $idSubmittalPhase);

        $this->standardResponse($data);
    }

    /**
     * API function to get all Submittals under a depth 0 SubmittalPhase.
     * @param $idSubmittalPhase
     * @Required_Privilege: Read
     */
    public function getSubmittalsAPI($idSubmittalPhase)
    {
        $this->verifyPrivilegeByDepth(Privilege::READ, 0);

        $data = Model\Submittals::getByPhaseId($this, $idSubmittalPhase);

        $this->standardResponse($data);
    }

    /**
     * API call to get a particular Submittal. Currently no front end exists for this so it instead calls the submittal's phase.
     * @param $idSubmittalPhase
     * @param $idSubmittal
     * @Required_Privilege: Read
     */
    public function getSubmittalByIdAPI($idSubmittalPhase, $idSubmittal)
    {
        $idSubmittalPhase = Submittals::sqlFetch($idSubmittal)->idSubmittalPhase->get();
        self::getSubmittalsAPI($idSubmittalPhase);
//        $this->verifyPrivilegeByDepth(Privilege::READ, 0);
//        $data = Model\Submittals::getById($this, $idSubmittal);
//        $this->standardResponse($data);
    }

    /**
     * API call to create a Submittal on a depth 0 phase.
     * @param $idSubmittalPhase
     * @Required_Privilege: Create
     */
    public function createSubmittalAPI()
    {
        //TODO Assert that this phase is infact a 0 depth phase.
        $this->verifyPrivilegeByDepth(Privilege::CREATE, 0);

        $data = Request::get()->getRequestBody();

        $idSubmittalPhase = isset($data['idSubmittalPhase'])? $data['idSubmittalPhase']:null;
        $title = isset($data['title'])? $data['title']:null;
        $description = isset($data['description'])? $data['description']:null;

        $data = Model\Submittals::create($this, $idSubmittalPhase, $title, $description);

        $this->trigger('onSubmittalCreate');

        $this->standardResponse($data);
    }

    /**
     * API call to Edit a given Submittal. Can only edit isComplete.
     * @param $idSubmittalPhase
     * @param $idSubmittal
     * @Required_Privilege: Write
     * @Triggers: onSubmittalComplete
     */
    public function editSubmittalAPI($idSubmittalPhase, $idSubmittal)
    {
        $this->verifyPrivilegeByDepth(Privilege::WRITE, 0);

        $submittal = Model\Submittals::sqlFetch($idSubmittal);

        $oldIsComplete = $submittal->status->isComplete->get();

        if($oldIsComplete)
            $this->verifyPrivilegeByDepth(Privilege::ADMIN, 0);

        $data = Request::get()->getRequestBody();
        $title = isset($data['title'])? $data['title']:null;
        $description = isset($data['description'])? $data['description']:null;
        $isComplete = $data['status']['isComplete'];

        if($title !== null)
            $submittal->title->set($title);
        if($description !== null)
            $submittal->description->set($description);

        if($isComplete !== null) {
            $submittal->status->isComplete->set($isComplete);
            if($isComplete && !$oldIsComplete)
                $submittal->dateCompleted->set(Datetime::timestamps());
        }

        $submittal->update();

        if ($isComplete && !$oldIsComplete) {
            $this->trigger('onSubmittalComplete', $idSubmittalPhase, $idSubmittal);
        }

        $data = Model\Submittals::getById($this, $idSubmittal);

        $this->standardResponse($data);
    }

    /**
     * Api call to Delete a given Submittal, including it's Folder/Files.
     * @param $idSubmittalPhase
     * @param $idSubmittal
     * @Required_Privilege: Delete
     */
    public function deleteSubmittalAPI($idSubmittalPhase, $idSubmittal)
    {
        $this->verifyPrivilegeByDepth(Privilege::DELETE, 0);

        $data = Model\Submittals::deleteSubmittal($this, $idSubmittal);

        $this->standardResponse($data);
    }

    /**
     * Passer Function that continues to the FolderRoot Module
     * @param $idSubmittal
     * @throws Exception
     * @throws \eprocess360\v3core\Controller\ControllerException
     * @Required_Privilege: GET: Read, POST/PUT/PATCH: Write, DELETE: Delete
     */
    public function submittalFilesAPI($idSubmittal)
    {
        //TODO VERIFY that the File is within this Submittal, for all file functions
        $type = Request::get()->getRequestMethod();
        switch ($type) {
            case 'GET':
                $this->verifyPrivilegeByDepth(Privilege::READ, 0);
                break;
            case 'POST':
            case 'PUT':
            case 'PATCH':
                $this->verifyPrivilegeByDepth(Privilege::WRITE, 0);
                if(Model\Submittals::isComplete($idSubmittal))
                    throw new Exception("Unable to edit a file off a completed submittal.");
                break;
            case 'DELETE':
                $this->verifyPrivilegeByDepth(Privilege::DELETE, 0);
                if(Model\Submittals::isComplete($idSubmittal))
                    throw new Exception("Unable to delete a file off a completed submittal.");
                break;
        }

        if($this->folderRoot){
            /** @var FolderRoot $child */
            $child= $this->folderRoot;
            $this->setObjectId($idSubmittal);
            $this->setName($this->getName()."/submittals");
            $this->addController($child);

            $data = Submittals::sqlFetch($idSubmittal);
            $idFolder = $data->idFolder->get();
            $child->setVerified(true);
            $child->attachFolder(Folder::getByID($idFolder));

            $child->ready()->run();
        }
        else
            throw new Exception("No Folder Root configured for this Module.");
    }

    /**
     * Passer Function that continues to the Reviews Module
     * @param $idSubmittal
     * @throws Exception
     */
    public function submittalReviewsAPI($idSubmittal)
    {
        if($this->reviews){
            /** @var Review $child */
            $child= $this->reviews;
            $this->setObjectId($idSubmittal);
            $this->setName($this->getName()."/submittals");
            $submittal = Submittals::sqlFetch($idSubmittal);
            $url = $this->getPath();
            $objectIdFolder = $submittal->idFolder->get();
            $objectTitle = "#".$submittal->sequenceNumber->get()." ".$submittal->title->get();
            $objectIsComplete = $submittal->status->isComplete->get();
            $child->identify($this, $idSubmittal, $url, $objectTitle, $objectIdFolder, $objectIsComplete);
            $this->addController($child);
            $child->ready()->run();
        }
        else
            throw new Exception("No Reviews configured for this Module.");
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
        if($error == false)
            $error = $this->messages[$responseCode];

        $responseData = [
            'data' => $data
        ];

        if($this->redirect)
            $responseData['redirect'] = $this->redirect;

        $response = $this->getResponseHandler();

        /** @var FolderRoot $folderRoot */
        $folderRoot = $this->folderRoot;
        $response->addResponseMeta('fileCategories',$folderRoot->getFileCategories());

        $response->setTemplate('submittals.base.html.twig', 'server');
        $response->setTemplate('module.submittals.handlebars.html', 'client', $this);
        $response->setTemplate(APP_PATH.'/eprocess360/v3controllers/src/SystemController/static/handlebars/global.files.handlebars.html', 'client', $this);

        $response->setResponse($responseData, $responseCode, false);
        if($error)
            $response->setErrorResponse(new Exception($error));
    }

    /**
     * The User will always be responsible for having the proper credentials to activate a phase of the Module.
     * @param $access
     * @throws \Exception
     */
    private function verifyPrivilegeByDepth($access, $depth)
    {
        /**
         * @var Submittal $module
         */
        $module = $this->modulesByDepth[$depth];

        $module->verifyPrivilege($access);
    }

    /**
     * Determines the highest level of Phase a User has access to. If none, throws an exception.
     * @param $access
     * @return mixed
     * @throws Exception
     */
    private function verifyHighestPhaseRole($access) {
        $depth = $this->getRootDepth();

        $verified = false;

        while($depth >= 0 && !$verified) {
            try {
                $this->verifyPrivilegeByDepth($access, $depth);
                $verified = true;
            }
            catch (\Exception $e){
                --$depth;
                if($depth < 0)
                    throw $e;
            }
        }
        return $depth;
    }


    /*********************************************   #WORKFLOW#  *********************************************/


    /**
     * Workflow function to get the highest level Phase Depth of this Submittal Controller.
     * @return mixed
     */
    public function getRootDepth()
    {
        return $this->rootDepth;
    }

    /**
     * Returns the bond FolderRoot Module.
     * @return FolderRoot
     */
    public function getFolderRoot(){
        return $this->folderRoot;
    }

    /**
     * Returns the bond Review Module.
     * @return Review
     */
    public function getReviews() {
        return $this->reviews;
    }

    /**
     * For this idSubmittal, which is idObject of the review, check to see if all Reviews are Complete/Accepted.
     * @param Review $review
     */
    public function checkReviews(Review $review)
    {
        $isComplete = true;
        $isAccepted = true;
        $idSubmittal = $review->getIdObject();
        $submittal = Submittals::sqlFetch($idSubmittal);
        $hasReview = $submittal->status->hasReview->get();
        $hasReviewCheck = $hasReview;
        $idSubmittalPhase = $submittal->idSubmittalPhase->get();
        $reviews = Reviews::getReviews($this->reviews);
        foreach($reviews['reviews'] as $actualReview){
            $hasReviewCheck = true;
            if(!$actualReview['status']['isComplete'])
                $isComplete = false;
            if(!$actualReview['status']['isAccepted'])
                $isAccepted = false;
        }

        $update = false;
        if($hasReview != $hasReviewCheck) {
            $submittal->status->hasReview->set($hasReviewCheck);
            $update = true;
        }

        if ($isComplete) {
            $submittal->status->reviewsCompleted->set(true);
            $update = true;
        }

        if($isComplete && $isAccepted) {
            $submittal->status->reviewsAccepted->set(true);
            $update = true;
        }
        if($update)
            $submittal->update();

        if($isComplete && $isAccepted)
            $this->trigger('onReviewAllAccept', $idSubmittalPhase, $idSubmittal);
        else if($isComplete)
            $this->trigger('onReviewAllComplete',$idSubmittalPhase, $idSubmittal);
    }

    /**
     * Returns this Submittal's Review Module populated with this Controllers Identification data and the given Submittal ID.
     * @param $idSubmittal
     * @return Review
     * @throws Exception
     * @throws \eprocess360\v3core\Controller\ControllerException
     */
    public function getReview($idSubmittal)
    {
        if($this->reviews){
            /** @var Review $child */
            $child= $this->reviews;
            $this->setObjectId($idSubmittal);
            $this->setName($this->getName()."/submittals");
            $submittal = Submittals::sqlFetch($idSubmittal);
            $url = $this->getPath();
            $objectIdFolder = $submittal->idFolder->get();
            $objectTitle = "#".$submittal->sequenceNumber->get()." ".$submittal->title->get();
            $objectIsComplete = $submittal->status->isComplete->get();
            $child->identify($this, $idSubmittal, $url, $objectTitle, $objectIdFolder, $objectIsComplete);
            $this->addController($child);
            return $child;
        }
        else
            throw new Exception("No Reviews configured for this Module.");
    }

    /**
     * Workflow Function to create a SubmittalPhase
     * @param $title
     * @param $description
     * @param $idParent
     * @param $limitOneIncomplete
     * @return Model\SubmittalPhases
     */
    public function createPhase($title, $description, $idParent, $limitOneIncomplete)
    {
        return Model\SubmittalPhases::create($this, $title, $description, $idParent, $limitOneIncomplete);
    }

    /**
     * Workflow Function to create a Submittal
     * @param $idSubmittalPhase
     * @param null $title
     * @param null $description
     * @return array
     */
    public function createSubmittal($idSubmittalPhase, $title = NULL, $description = NULL)
    {
        $submittal = Submittals::create($this, $idSubmittalPhase, $title, $description);
        $this->trigger('onSubmittalCreate');
        return $submittal;
    }

    /***
     * Workflow function to check if Submittal is complete.
     * @param $idSubmittalPhase
     * @return boolean if submittals in submittal phase is complete
     */
    public function submittalPhaseIsComplete($idSubmittalPhase) {
        // check if submittal phase has submittals that have completed reviews
        $submittals = Submittals::getByPhaseId($this, $idSubmittalPhase);

        foreach ($submittals as $submittal) {
            if (!$submittal['status']['reviewsCompleted']) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return array|null
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * @return array|null
     */
    public function setRedirect($url)
    {
        $this->redirect = $url;
        return $this;
    }

    /**
     * @param $idSubmittalPhase
     * @return array|null
     */
    public function getSubmittals($idSubmittalPhase)
    {
        return Model\Submittals::getByPhaseId($this, $idSubmittalPhase);
    }

    /**
     * Workflow function that gets the most current submittal in a given Submittal Phase, then opens it.
     * @param $idSubmittalPhase
     */
    public function openTopSubmittal($idSubmittalPhase)
    {
        $submittal = Submittals::getTopSubmittal($idSubmittalPhase);

        $submittal->status->isComplete->set(false);
        $submittal->update();
    }

    /******************************************   #INITIALIZATION#  ******************************************/


    /**
     * Binds the FolderRoot Module used by this Submittal to store files
     * @param $folderRoot
     * @return $this
     */
    public function bindFolderRoot(FolderRoot $folderRoot)
    {
        $this->folderRoot = $folderRoot;
        return $this;
    }

    /**
     * Binds the Depth, which is the number of level of Phases that this Submittal Controller can have.
     * @param $rootDepth
     * @return $this
     */
    public function bindRootDepth($rootDepth)
    {
        $this->rootDepth = $rootDepth;
        return $this;
    }

    /**
     * Binds an array of Submittal Modules To allow different permissions by Depth. Used only for Permissions.
     * @param array $moduleComponents
     * @return $this
     */
    public function bindModuleByDepth(array $moduleComponents)
    {
        $modulesByDepth = [];
        for($i = 0; $i <= $this->rootDepth; $i++)
        {
            array_push($modulesByDepth, $moduleComponents[$i]);
        }
        $this->modulesByDepth = $modulesByDepth;
        return $this;
    }

    /**
     * Binds corresponding Review Module to the Submittal.
     * @param Review $review
     */
    public function bindReview(Review $review)
    {
        $this->reviews = $review;
        $review->bindFolderRoot($this->folderRoot);
    }


    /*********************************************   #TRIGGERS#  *********************************************/


    /**
     * Fired when a Submittal is Created.
     * @param \Closure $closure
     * @return \eprocess360\v3core\Controller\Trigger\Trigger
     */
    public function onSubmittalCreate($closure)
    {
        return $this->addTrigger(__FUNCTION__, $closure);
    }

    /**
     * Fired when a Submittal is marked as Completed from a non completed status.
     * @param \Closure $closure
     * @return \eprocess360\v3core\Controller\Trigger\Trigger
     */
    public function onSubmittalComplete(\Closure $closure)
    {
        return $this->addTrigger(__FUNCTION__, $closure);
    }

    /**
     * Fired when a Submittal Phase is deleted.
     * @param \Closure $closure
     * @return \eprocess360/v3core/Controller/Trigger/Trigger
     */
    public function onSubmittalPhaseDelete(\Closure $closure)
    {
        return $this->addTrigger(__FUNCTION__, $closure);
    }

    /**
     * Fired when a Submittal's Review is marked as Complete from a non completed status.
     * @param \Closure $closure
     * @return \eprocess360\v3core\Controller\Trigger\Trigger
     */
    public function onReviewComplete(\Closure $closure)
    {
        return $this->addTrigger(__FUNCTION__, $closure);
    }

    /**
     * Fired when a Submittal's Review is marked as Incomplete after being complete.
     * @param \Closure $closure
     * @return \eprocess360\v3core\Controller\Trigger\Trigger
     */
    public function onReviewReopen(\Closure $closure)
    {
        return $this->addTrigger(__FUNCTION__, $closure);
    }

    /**
     * Fired when a Submittal's Review is created for this object.
     * @param \Closure $closure
     * @return \eprocess360\v3core\Controller\Trigger\Trigger
     */
    public function onReviewCreate(\Closure $closure)
    {
        return $this->addTrigger(__FUNCTION__, $closure);
    }

    /**
     * Fired when a Submittal's Review is Deleted.
     * @param \Closure $closure
     * @return \eprocess360\v3core\Controller\Trigger\Trigger
     */
    public function onReviewDelete(\Closure $closure)
    {
        return $this->addTrigger(__FUNCTION__, $closure);
    }

    /**
     * Fired when All Reviews for a particular submittal are marked as Accepted and Completed.
     * @param \Closure $closure
     * @return \eprocess360\v3core\Controller\Trigger\Trigger
     */
    public function onReviewAllAccept(\Closure $closure)
    {
        return $this->addTrigger(__FUNCTION__, $closure);
    }

    /**
     * Fired when All Reviews for a particular submittal are marked as Completed, but not all are Accepted.
     * @param \Closure $closure
     * @return \eprocess360\v3core\Controller\Trigger\Trigger
     */
    public function onReviewAllComplete(\Closure $closure)
    {
        return $this->addTrigger(__FUNCTION__, $closure);
    }

}