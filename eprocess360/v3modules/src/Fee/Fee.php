<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 2/1/16
 * Time: 8:49 AM
 */

namespace eprocess360\v3modules\Fee;


use eprocess360\v3controllers\IncrementGenerator\Model\IncrementGenerators;
use eprocess360\v3core\Controller\Children;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Dashboard;
use eprocess360\v3core\Controller\DashboardToolbar;
use eprocess360\v3core\Controller\Module;
use eprocess360\v3core\Controller\Persistent;
use eprocess360\v3core\Controller\Project;
use eprocess360\v3core\Controller\ProjectToolbar;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\Rules;
use eprocess360\v3core\Controller\Trigger\InterfaceTriggers;
use eprocess360\v3core\Controller\Triggers;
use eprocess360\v3core\Controller\Warden;
use eprocess360\v3core\Controller\Warden\Privilege;
use eprocess360\v3core\Keydict\Entry\Datetime;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Request\Request;
use eprocess360\v3core\View\Column;
use eprocess360\v3core\View\StandardView;
use eprocess360\v3modules\Fee\Model\FeeMatrices;
use eprocess360\v3modules\Fee\Model\Fees;
use eprocess360\v3modules\Fee\Model\FeeTagCategories;
use eprocess360\v3modules\Fee\Model\FeeTags;
use eprocess360\v3modules\Fee\Model\FeeTemplateFeeTag;
use eprocess360\v3modules\Fee\Model\FeeTemplates;
use eprocess360\v3modules\Fee\Model\FeeTypes;
use eprocess360\v3modules\Fee\Model\Receipts;
use eprocess360\v3modules\ModuleRule\ModuleRule;
use eprocess360\v3modules\Toolbar\Toolbar;
use Exception;

/**
 * Class Fee
 * @package eprocess360\v3modules\Fee
 */
class Fee extends Controller implements InterfaceTriggers
{
    use Router, Module, Persistent, Triggers, Children, Rules, ProjectToolbar, DashboardToolbar, Dashboard;
    /** @var  ModuleRule */
    private $moduleRule;
    private $feeTemplates;
    private $feeTags;
    private $scheduledFeeTags;
    private $feeTypes;
    private $feeTagCategories;
    private $objectType;
    private $messages = [200 => false,
        404 => "404 Not Found - Resource not found",
        403 => "Permissions are not sufficient."];
    private $textContact = null;
    private $redirect;

    /**
     * Used as a fail-safe in Controllers to make sure that their dependencies and initializations are met; If not, exception is thrown.
     */
    public function dependencyCheck()
    {
        if($this->moduleRule === NULL)
            throw new Exception("Review Module does not have a ModuleRule set, please bindModuleRule in the initialization function.");
    }


    /*********************************************   #ROUTING#  **********************************************/


    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     */
    public function routes()
    {
        if($this->getParent()->hasObjectId()) {

            $this->routes->map('GET', '', function () {
                $this->getProjectFeesAPI();
            });
            $this->routes->map('POST', '', function () {
                $this->createProjectFeeAPI();
            });
            $this->routes->map('GET', '/[i:idFee]?', function ($idFee) {
                $this->getProjectFeeAPI($idFee);
            });
            $this->routes->map('PUT', '/[i:idFee]', function ($idFee) {
                $this->editProjectFeeAPI($idFee);
            });
            $this->routes->map('DELETE', '/[i:idFee]', function ($idFee) {
                $this->deleteProjectFeeAPI($idFee);
            });

            $this->routes->map('GET', '/receipts', function () {
                $this->getProjectReceiptsAPI();
            });
            $this->routes->map('POST', '/receipts', function () {
                $this->createProjectReceiptAPI();
            });
            $this->routes->map('GET', '/receipts/[i:idReceipt]?', function ($idReceipt) {
                $this->getProjectReceiptAPI($idReceipt);
            });
            $this->routes->map('PUT', '/receipts/[i:idReceipt]', function ($idReceipt) {
                $this->editProjectReceiptAPI($idReceipt);
            });
            $this->routes->map('DELETE', '/receipts/[i:idReceipt]', function ($idReceipt) {
                $this->deleteProjectReceiptAPI($idReceipt);
            });

            $this->routes->map('POST', '/schedule', function () {
                $this->editFeeScheduleTagAPI();
            });

            $this->routes->map('POST', '/preview', function () {
                $this->recalculatePreviewAPI();
            });
            $this->routes->map('POST', '/process', function () {
                $this->processProjectFeeAPI();
            });
            $this->routes->map('GET', '/templates/find', function () {
                $this->findFeeTemplateAPI();
            });
            $this->routes->map('GET', '/receipts/generate', function () {
                $this->generateReceiptNumberAPI();
            });

        }
        else {
            $this->routes->map('GET', '', function () {
                $this->getFeeTemplatesBatchAPI();
            });
            $this->routes->map('GET', '/new', function () {
                $this->getBlankFeeTemplatesBatchAPI();
            });
            $this->routes->map('POST', '', function () {
                $this->createFeeTemplateBatchAPI();
            });
            $this->routes->map('GET', '/[i:idFeeTemplate]', function ($idFeeTemplate) {
                $this->getFeeTemplateBatchAPI($idFeeTemplate);
            });
            $this->routes->map('PUT', '/[i:idFeeTemplate]', function ($idFeeTemplate) {
                $this->editFeeTemplateBatchAPI($idFeeTemplate);
            });
            $this->routes->map('DELETE', '/[i:idFeeTemplate]', function ($idFeeTemplate) {
                $this->deleteFeeTemplateBatchAPI($idFeeTemplate);
            });

            $this->routes->map('GET', '/templates', function () {
                $this->getFeeTemplatesAPI();
            });
            $this->routes->map('POST', '/templates', function () {
                $this->createFeeTemplateAPI();
            });
            $this->routes->map('GET', '/templates/[i:idFeeTemplate]', function ($idFeeTemplate) {
                $this->getFeeTemplateAPI($idFeeTemplate);
            });
            $this->routes->map('PUT', '/templates/[i:idFeeTemplate]', function ($idFeeTemplate) {
                $this->editFeeTemplateAPI($idFeeTemplate);
            });
            $this->routes->map('DELETE', '/templates/[i:idFeeTemplate]', function ($idFeeTemplate) {
                $this->deleteFeeTemplateAPI($idFeeTemplate);
            });


            $this->routes->map('GET', '/templates/[i:idFeeTemplate]/tags', function ($idFeeTemplate) {
                $this->getFeeTemplateFeeTagsAPI($idFeeTemplate);
            });
            $this->routes->map('POST', '/templates/[i:idFeeTemplate]/tags', function () {
                $this->createFeeTemplateFeeTagAPI();
            });
            $this->routes->map('DELETE', '/templates/[i:idFeeTemplate]/tags/[i:idFeeTag]', function ($idFeeTemplate, $idFeeTag) {
                $this->deleteFeeTemplateFeeTagAPI($idFeeTemplate, $idFeeTag);
            });


            $this->routes->map('GET', '/tags', function () {
                $this->getFeeTagsAPI();
            });
            $this->routes->map('POST', '/tags', function () {
                $this->createFeeTagAPI();
            });
            $this->routes->map('GET', '/tags/[i:idFeeTag]', function ($idFeeTag) {
                $this->getFeeTagAPI($idFeeTag);
            });
            $this->routes->map('PUT', '/tags/[i:idFeeTag]', function ($idFeeTag) {
                $this->editFeeTagAPI($idFeeTag);
            });
            $this->routes->map('DELETE', '/tags/[i:idFeeTag]', function ($idFeeTag) {
                $this->deleteFeeTagAPI($idFeeTag);
            });


            $this->routes->map('GET', '/templates/[i:idFeeTemplate]/matrices', function ($idFeeTemplate) {
                $this->getFeeMatricesAPI($idFeeTemplate);
            });
            $this->routes->map('GET', '/templates/[i:idFeeTemplate]/matrices/test', function ($idFeeTemplate) {
                $this->getFeeMatricesTestAPI($idFeeTemplate);
            });
            $this->routes->map('POST', '/templates/[i:idFeeTemplate]/matrices', function () {
                $this->createFeeMatrixAPI();
            });
            $this->routes->map('GET', '/templates/[i:idFeeTemplate]/matrices/[i:idFeeMatrix]', function ($idFeeTemplate, $idFeeMatrix) {
                $this->getFeeMatrixAPI($idFeeTemplate, $idFeeMatrix);
            });
            $this->routes->map('PUT', '/templates/[i:idFeeTemplate]/matrices/[i:idFeeMatrix]', function ($idFeeTemplate ,$idFeeMatrix) {
                $this->editFeeMatrixAPI($idFeeTemplate, $idFeeMatrix);
            });
            $this->routes->map('DELETE', '/templates/[i:idFeeTemplate]/matrices/[i:idFeeMatrix]', function ($idFeeTemplate, $idFeeMatrix) {
                $this->deleteFeeMatrixAPI($idFeeTemplate, $idFeeMatrix);
            });


            $this->routes->map('GET', '/types', function () {
                $this->getFeeTypesAPI();
            });
            $this->routes->map('POST', '/types', function () {
                $this->createFeeTypeAPI();
            });
            $this->routes->map('GET', '/types/[i:idFeeType]', function ($idFeeType) {
                $this->getFeeTypeAPI($idFeeType);
            });
            $this->routes->map('PUT', '/types/[i:idFeeType]', function ($idFeeType) {
                $this->editFeeTypeAPI($idFeeType);
            });
            $this->routes->map('DELETE', '/types/[i:idFeeType]', function ($idFeeType) {
                $this->deleteFeeTypeAPI($idFeeType);
            });

            $this->routes->map('GET', '/categories', function () {
                $this->getFeeTagCategoriesAPI();
            });
            $this->routes->map('POST', '/categories', function () {
                $this->createFeeTagCategoryAPI();
            });
            $this->routes->map('GET', '/categories/[i:idFeeTagCategory]', function ($idFeeTagCategory) {
                $this->getFeeTagCategoryAPI($idFeeTagCategory);
            });
            $this->routes->map('PUT', '/categories/[i:idFeeTagCategory]', function ($idFeeTagCategory) {
                $this->editFeeTagCategoryAPI($idFeeTagCategory);
            });
            $this->routes->map('DELETE', '/categories/[i:idFeeTagCategory]', function ($idFeeTagCategory) {
                $this->deleteFeeTagCategoryAPI($idFeeTagCategory);
            });


            $this->routes->map('GET', '/tags/find', function () {
                $this->findFeeTagAPI();
            });
            $this->routes->map('GET|POST|PATCH|PUT|DELETE', '/rules/[*:trailing]?', function () {
                $this->feeRulesAPI();
            });
        }
    }

    /**
     * API Function that gets all Fees for this Project.
     * @param bool|false $recalculate
     * @throws \eprocess360\v3core\Controller\ControllerException
     * @Required_Privilege: Read
     */
    public function getProjectFeesAPI($recalculate = false)
    {
        global $pool;
        $this->verifyPrivilege(Privilege::READ);

        $data = $_GET;
        $projectData = $this->getProjectData();
        $idProject = $this->getParent()->getObjectId();

        if((isset($data['recalculate']) && $data['recalculate'] === 'true') || $recalculate){
            $this->verifyPrivilege(Privilege::WRITE);

            Fees::recalculateFees($idProject, $projectData);
        }
        /** @var Warden $parent */
        $parent = $this->getParent();
        $depositsOnly = !$parent->hasPrivilege(Privilege::WRITE, $this, null, $pool->User->getIdUser()) && !$projectData['paidDeposits'];
        $data = Fees::getFees($idProject, $projectData, true, false, false, $depositsOnly);

        $this->standardResponse($data, 'fee');
    }

    /**
     * API Function that Creates a Fee for this Project.
     * @Required_Privilege: Create
     */
    public function createProjectFeeAPI()
    {
        $this->verifyPrivilege(Privilege::CREATE);

        $data = Request::get()->getRequestBody();

        $idFeeTemplate = isset($data['idFeeTemplate'])? $data['idFeeTemplate']:null;
        $total = isset($data['total'])? $data['total']:null;
        $feeUnitPrice = isset($data['feeUnitPrice'])? $data['feeUnitPrice']:null;
        $quantity = isset($data['quantity'])? $data['quantity']:null;
        $notes = isset($data['notes'])? $data['notes']:null;
        $totalOverride = isset($data['overrides']['totalOverride'])? $data['overrides']['totalOverride']:null;
        $unitPriceOverride = isset($data['overrides']['unitPriceOverride'])? $data['overrides']['unitPriceOverride']:null;
        $variables = isset($data['variables'])? $data['variables']:null;

        if($variables)
            $this->editProjectVariables($variables);

        $idProject = $this->getParent()->getObjectId();
        $projectData = $this->getProjectData();

        $data =  Fees::create($idFeeTemplate, $idProject, $total, $feeUnitPrice, $quantity, $notes, $totalOverride, $unitPriceOverride, $projectData);

        $this->standardResponse($data, 'fee');
    }

    /**
     * API Function that gets a specified Fee.
     * @param $idFee
     * @Required_Privilege: Read
     */
    public function getProjectFeeAPI($idFee)
    {
        $this->verifyPrivilege(Privilege::READ);

        $projectData = $this->getProjectData();

        $data = Fees::getFee($idFee, $projectData);

        $this->standardResponse($data, 'fee');
    }

    /**
     * API Function edits the specified Fee.
     * @param $idFee
     * @Required_Privilege: Write
     */
    public function editProjectFeeAPI($idFee)
    {
        $this->verifyPrivilege(Privilege::WRITE);

        $data = Request::get()->getRequestBody();

        $idFeeTemplate = isset($data['idFeeTemplate'])? $data['idFeeTemplate']:null;
        $total = isset($data['total'])? $data['total']:null;
        $feeUnitPrice = isset($data['feeUnitPrice'])? $data['feeUnitPrice']:null;
        $quantity = isset($data['quantity'])? $data['quantity']:null;
        $notes = isset($data['notes'])? $data['notes']:null;
        $totalOverride = isset($data['overrides']['totalOverride'])? $data['overrides']['totalOverride']:null;
        $unitPriceOverride = isset($data['overrides']['unitPriceOverride'])? $data['overrides']['unitPriceOverride']:null;
        $variables = isset($data['variables'])? $data['variables']:null;

        if($variables)
            $this->editProjectVariables($variables);

        $projectData = $this->getProjectData();

        $data = Fees::editFee($idFee, $idFeeTemplate, $total, $feeUnitPrice, $quantity, $notes, $totalOverride, $unitPriceOverride, $projectData);

        $this->standardResponse($data, 'fee');
    }

    /**
     * API Function that deletes a specified Fee.
     * @param $idFee
     * @Required_Privilege: Delete
     */
    public function deleteProjectFeeAPI($idFee)
    {
        $this->verifyPrivilege(Privilege::DELETE);

        $data = Fees::deleteFee($idFee);

        $this->standardResponse($data, 'fee');
    }

    /**
     * API Function that gets all Receipts for this Project.
     * @Required_Privilege: Read
     */
    public function getProjectReceiptsAPI()
    {
        $this->verifyPrivilege(Privilege::READ);

        $data = Receipts::getReceipts($this->getParent()->getObjectId());

        $this->standardResponse($data, 'receipt');
    }

    /**
     * API Function that Creates a Fee for this Project.
     * @param $privilegeChecked
     * @Required_Privilege: Create
     */
    public function createProjectReceiptAPI($privilegeChecked = false)
    {
        if(!$privilegeChecked)
            $this->verifyPrivilege(Privilege::CREATE);

        $data = Request::get()->getRequestBody();

        $idProject = $this->getParent()->getObjectId();
        $datePaid = Datetime::timestamps();
        $isVoid = false;

        $receiptNumber = isset($data['receiptNumber'])? $data['receiptNumber']:null;
        $feePayments = isset($data['feePayments'])? $data['feePayments']:null;
        $userName = isset($data['userName'])? $data['userName']:null;
        $idUser = isset($data['idUser'])? $data['idUser']:null;
        $paymentMethod = isset($data['paymentMethod'])? $data['paymentMethod']:null;
        $receiptNotes = isset($data['receiptNotes'])? $data['receiptNotes']:null;

        if($paymentMethod == 'Deposit')
            $data = Receipts::depositPayment($this->getParent()->getObjectId(), $feePayments);
        else
            $data =  Receipts::create($idProject, $receiptNumber, $feePayments, $userName, $idUser, $paymentMethod, $receiptNotes, $datePaid, $isVoid);

        $this->trigger('onPayment');

        $this->standardResponse($data, 'receipt');
    }

    /**
     * API Function that gets a specified Receipt.
     * @param $idReceipt
     * @Required_Privilege: Read
     */
    public function getProjectReceiptAPI($idReceipt)
    {
        $this->verifyPrivilege(Privilege::READ);

        $data = Receipts::getReceipt($idReceipt);

        $this->standardResponse($data, 'receipt');
    }

    /**
     * API Function edits the specified Receipt.
     * @param $idReceipt
     * @Required_Privilege: Write
     */
    public function editProjectReceiptAPI($idReceipt)
    {
        $this->verifyPrivilege(Privilege::WRITE);

        $data = Request::get()->getRequestBody();

        $isVoid = isset($data['status']['isVoid'])? $data['status']['isVoid']:null;

        $data = Receipts::editReceipt($idReceipt, $isVoid);

        $this->standardResponse($data, 'receipt');
    }

    /**
     * API Function that deletes a specified Receipt.
     * @param $idReceipt
     * @Required_Privilege: Delete
     */
    public function deleteProjectReceiptAPI($idReceipt)
    {
        $this->verifyPrivilege(Privilege::DELETE);

        $data = Receipts::deleteReceipt($idReceipt);

        $this->standardResponse($data, 'receipt');
    }

    /**
     * API Function edits the Project idFeeTag, used as the Code Year.
     * @Required_Privilege: Write
     */
    public function editFeeScheduleTagAPI()
    {
        $this->verifyPrivilege(Privilege::WRITE);

        $data = Request::get()->getRequestBody();

        $idFeeTag = isset($data['idFeeTag'])? $data['idFeeTag']:null;

        /** @var Project|Controller $parent */
        $parent = $this->getParent();
        if($idFeeTag) {
            $this->editFeeScheduleTag($idFeeTag);
            $parent->getKeydict()->idFeeTag->set($idFeeTag);
            $parent->save();
        }

        $this->getProjectFeesAPI(true);
    }

    /**
     * API Function edits the Project idFeeTag, used as the Code Year.
     * @Required_Privilege: Write
     */
    public function recalculatePreviewAPI()
    {
        $this->verifyPrivilege(Privilege::WRITE);

        $data = Request::get()->getRequestBody();

        $idFeeTemplate = isset($data['idFeeTemplate']) ? $data['idFeeTemplate'] : null;
        $total = isset($data['total']) ? $data['total'] : null;
        $feeUnitPrice = isset($data['feeUnitPrice']) ? $data['feeUnitPrice'] : null;
        $quantity = isset($data['quantity']) ? $data['quantity'] : null;
        $totalOverride = isset($data['overrides']['totalOverride']) ? $data['overrides']['totalOverride'] : null;
        $unitPriceOverride = isset($data['overrides']['unitPriceOverride']) ? $data['overrides']['unitPriceOverride'] : null;
        $variables = isset($data['variables']) ? $data['variables'] : null;

        $projectData = $this->getProjectData();
        foreach($variables as $key=>$value) {
            $projectData[$key] = $value;
            $projectData['keydict']->$key->set($value);
        }
        if($totalOverride)
            $data = ['total'=>(float)$total];
        else
            $data = ['total'=>(float)FeeTemplates::processFee(NULL, $projectData, $idFeeTemplate, $feeUnitPrice, $unitPriceOverride, $quantity, $this->getParent()->getObjectId())];

        $this->standardResponse($data, 'fee');
    }

    /**
     * API Function edits the Project idFeeTag, used as the Code Year.
     * @Required_Privilege: READ
     */
    public function processProjectFeeAPI()
    {
        $this->verifyPrivilege(Privilege::READ);

        $this->createProjectReceiptAPI(true);
    }

    /**
     * API Function edits the Project idFeeTag, used as the Code Year.
     * @Required_Privilege: Write
     */
    public function findFeeTemplateAPI()
    {
        $data = $_GET;

        $idFeeSchedule = $this->getProjectData()['idFeeTag'];

        $found = [];
        if (isset($data['title'])) {
            $text = ltrim($data['title']);
            if (strlen($text)) {
                $found = FeeTemplates::findFeeTemplate($text, $this->getId(), $idFeeSchedule, $this->getProjectData());
            }
        }

        $data = $found;

        $this->standardResponse($data, 'feeTemplate');
    }

    /**
     * API Function edits the Project idFeeTag, used as the Code Year.
     * @Required_Privilege: Write
     */
    public function generateReceiptNumberAPI()
    {
        $data = IncrementGenerators::incrementByKey('receipts');

        $this->standardResponse($data, 'receipts');
    }

    /**
     * API Function that gets all FeeTemplates for this Controller.
     * @Required_Privilege: Read
     */
    public function getFeeTemplatesBatchAPI()
    {
        $this->verifyPrivilege(Privilege::READ);

        $result = FeeTemplates::getFeeTemplates($this->getId(), true);

        $table = $result['keydict'];

        $stateFilter = [['option' => 'Active', 'sql' => "FeeTemplates.status_0 & 0b01"],
            ['option' => 'Inactive', 'sql' => "NOT FeeTemplates.status_0 & 0b01"]];

        $types = FeeTypes::getFeeTypes($this->getId());
        $feeTypeFilter = [];
        $found = [];
        foreach($types as $type){
            if(!isset($found[$type['feeTypeTitle']])) {
                $feeTypeFilter[] = ['option' => $type['feeTypeTitle'], 'sql' => "FeeTypes.feeTypeTitle = '{$type['feeTypeTitle']}'"];
                $found[$type['feeTypeTitle']] = true;
            }
        }

        $view = StandardView::build('Templates.All', 'Fee Templates', $result['keydict'], $result);
        $view->add(
            Column::import($table->idFeeTemplate)->setEnabled(false),
            Column::import($table->title)->filterBySearch()->bucketBy()->setSort(true, "FeeTemplates.title")->setIsLink(true),
            Column::import($table->feeTypeTitle)->filterByValue($feeTypeFilter)->bucketBy()->setSort(false, "FeeTypes.feeTypeTitle"),
            Column::import($table->status->isActive)->filterByValue($stateFilter)->setSort(false, "FeeTemplates.status_0 & 0b01")->setTemplate('mvCustomColumnStatusActive'),
            Column::import($table->dateCreated)->setEnabled(false)->bucketBy(),
            Column::import($table->feeSchedule)->setEnabled(false)->bucketBy("DESC")->setSort(false, "FeeTags.feeTagValue")
        );

        $view->response($this);
        $data = $view->json(false);

        $this->standardResponse($data, 'feeTemplate');
    }

    function getBlankFeeTemplatesBatchAPI() {
        $this->standardResponse((object)NULL, 'feeTemplate');
    }

    /**
     * API Function that Creates a FeeTemplate for this Controller.
     * @Required_Privilege: Create
     */
    public function createFeeTemplateBatchAPI()
    {
        //TODO in the case that a Fee Template is created with the same name and feeSchedule, set old one to inactive and what not.
        $this->verifyPrivilege(Privilege::CREATE);

        $data = Request::get()->getRequestBody();

        $idController = $this->getId();

        $feeTags = isset($data['feeTags'])? $data['feeTags'] : [];
        $feeMatrices = isset($data['feeMatrices'])? $data['feeMatrices']:[];

        $idFeeType = isset($data['idFeeType'])? $data['idFeeType']:null;
        $idFeeSchedule = isset($data['idFeeSchedule']) ? $data['idFeeSchedule'] : null;
        $title = isset($data['title'])? $data['title']:null;
        $description = isset($data['description'])? $data['description']:null;
        $minimumValue = isset($data['minimumValue'])? $data['minimumValue']:null;
        $fixedAmount = isset($data['fixedAmount'])? $data['fixedAmount']:null;
        $matrixFormula = isset($data['matrixFormula'])? $data['matrixFormula']:null;
        $formula = isset($data['formula'])? $data['formula']:null;
        $calculationMethod = isset($data['calculationMethod'])? $data['calculationMethod']:null;
        $isActive = isset($data['status']['isActive'])? $data['status']['isActive']:null;

        $data =  FeeTemplates::create($idController, $idFeeType, $title, $description, $minimumValue, $fixedAmount,
            $matrixFormula, $formula, $calculationMethod, $isActive);

        // Schedule tag
        if($idFeeSchedule) {
            FeeTemplateFeeTag::create($data['idFeeTemplate'], $idFeeSchedule);
        }
        else {
            throw new \Exception("createFeeTemplateBatchAPI(): No fee schedule ID provided");
        }

        $data = FeeTemplates::getFeeTemplate($data['idFeeTemplate']);

        $idFeeTemplate = isset($data['idFeeTemplate'])? $data['idFeeTemplate']:null;

        //TODO Optimize the creates into batch creates.
        // Non-schedule fee tags
        foreach($feeTags as $feeTag) {
            $idFeeTag = isset($feeTag['idFeeTag']) ? $feeTag['idFeeTag'] : null;
            FeeTemplateFeeTag::create($idFeeTemplate, $idFeeTag);
        }

        $data['feeTags'] = FeeTemplateFeeTag::getFeeTemplateFeeTags($idFeeTemplate);

        foreach($feeMatrices as $feeMatrix) {
            $startingValue = isset($feeMatrix['startingValue'])? $feeMatrix['startingValue']:null;
            $order = isset($feeMatrix['order'])? $feeMatrix['order']:null;
            $baseFee = isset($feeMatrix['baseFee'])? $feeMatrix['baseFee']:null;
            $increment = isset($feeMatrix['increment'])? $feeMatrix['increment']:null;
            $incrementFee = isset($feeMatrix['incrementFee'])? $feeMatrix['incrementFee']:null;
            FeeMatrices::create($idFeeTemplate, $startingValue, $order, $baseFee, $increment, $incrementFee);
        }

        $data['feeMatrices'] = FeeMatrices::getFeeMatrices($idFeeTemplate);

        if($isActive){
            FeeTemplates::checkOneActive($idFeeTemplate, $idController, $title, $idFeeSchedule);
        }

        $this->standardResponse($data, 'feeTemplate');
    }

    /**
     * API Function that gets a specified FeeTemplate.
     * @param $idFeeTemplate
     * @Required_Privilege: Read
     */
    public function getFeeTemplateBatchAPI($idFeeTemplate)
    {
        $this->verifyPrivilege(Privilege::READ);

        $data = FeeTemplates::getFeeTemplate($idFeeTemplate);
        $data['feeMatrices'] = FeeMatrices::getFeeMatrices($idFeeTemplate);
        $data['feeTags'] = FeeTemplateFeeTag::getFeeTemplateFeeTags($idFeeTemplate);

        $this->standardResponse($data, 'feeTemplate');
    }

    /**
     * API Function edits the specified FeeTemplate.
     * @param $idFeeTemplate
     * @Required_Privilege: Write
     */
    public function editFeeTemplateBatchAPI($idFeeTemplate)
    {
        $this->verifyPrivilege(Privilege::WRITE);

        $data = Request::get()->getRequestBody();

        $idController = $this->getId();

        $feeTags = isset($data['feeTags'])? $data['feeTags']:[];
        $feeMatrices = isset($data['feeMatrices'])? $data['feeMatrices']:[];

        $idFeeType = isset($data['idFeeType'])? $data['idFeeType']:null;
        $title = isset($data['title'])? $data['title']:null;
        $description = isset($data['description'])? $data['description']:null;
        $minimumValue = isset($data['minimumValue'])? $data['minimumValue']:null;
        $fixedAmount = isset($data['fixedAmount'])? $data['fixedAmount']:null;
        $matrixFormula = isset($data['matrixFormula'])? $data['matrixFormula']:null;
        $formula = isset($data['formula'])? $data['formula']:null;
        $calculationMethod = isset($data['calculationMethod'])? $data['calculationMethod']:null;
        $isActive = isset($data['status']['isActive'])? $data['status']['isActive']:null;
        $idFeeSchedule = isset($data['idFeeSchedule'])? $data['idFeeSchedule']:null;

        if($idFeeSchedule)
            FeeTemplateFeeTag::switchFeeSchedule($idFeeTemplate, $idFeeSchedule);
        FeeTemplates::editFeeTemplate($idFeeTemplate, $idFeeType, $title, $description, $minimumValue, $fixedAmount,
            $matrixFormula, $formula, $calculationMethod, $isActive);

        $data = FeeTemplates::getFeeTemplate($idFeeTemplate);

        //TODO Optimize the creates into batch creates.
        FeeTemplateFeeTag::deleteFeeTemplateFeeTagByIdFeeTemplate($idFeeTemplate);
        foreach ($feeTags as $feeTag) {
            $idFeeTag = isset($feeTag['idFeeTag']) ? $feeTag['idFeeTag'] : null;
            FeeTemplateFeeTag::create($idFeeTemplate, $idFeeTag);
        }

        $data['feeTags'] = FeeTemplateFeeTag::getFeeTemplateFeeTags($idFeeTemplate);

        if(isset($calculationMethod) && $calculationMethod['isMatrix']) {
            FeeMatrices::deleteFeeMatrixByIdFeeTemplate($idFeeTemplate);
            foreach ($feeMatrices as $feeMatrix) {
                $startingValue = isset($feeMatrix['startingValue']) ? $feeMatrix['startingValue'] : null;
                $order = isset($feeMatrix['order']) ? $feeMatrix['order'] : null;
                $baseFee = isset($feeMatrix['baseFee']) ? $feeMatrix['baseFee'] : null;
                $increment = isset($feeMatrix['increment']) ? $feeMatrix['increment'] : null;
                $incrementFee = isset($feeMatrix['incrementFee']) ? $feeMatrix['incrementFee'] : null;
                FeeMatrices::create($idFeeTemplate, $startingValue, $order, $baseFee, $increment, $incrementFee);
            }
        }

        if($isActive){
            FeeTemplates::checkOneActive($idFeeTemplate, $idController, $title, $idFeeSchedule);
        }

        $data['feeMatrices'] = FeeMatrices::getFeeMatrices($idFeeTemplate);

        $this->standardResponse($data, 'feeTemplate');
    }

    /**
     * API Function that deletes a specified FeeTemplate.
     * @param $idFeeTemplate
     * @Required_Privilege: Delete
     */
    public function deleteFeeTemplateBatchAPI($idFeeTemplate)
    {
        $this->verifyPrivilege(Privilege::DELETE);

        $data = FeeTemplates::deleteFeeTemplate($idFeeTemplate);

        $this->standardResponse($data, 'feeTemplate');
    }

    /**
     * API Function that gets all FeeTemplates for this Controller.
     * @Required_Privilege: Read
     */
    public function getFeeTemplatesAPI()
    {
        $this->verifyPrivilege(Privilege::READ);

        $data = FeeTemplates::getFeeTemplates($this->getId());

        $this->standardResponse($data, 'feeTemplate');
    }

    /**
     * API Function that Creates a FeeTemplate for this Controller.
     * @Required_Privilege: Create
     */
    public function createFeeTemplateAPI()
    {
        $this->verifyPrivilege(Privilege::CREATE);

        $data = Request::get()->getRequestBody();

        $idController = $this->getId();

        $idFeeType = isset($data['idFeeType'])? $data['idFeeType']:null;
        $title = isset($data['title'])? $data['title']:null;
        $description = isset($data['description'])? $data['description']:null;
        $minimumValue = isset($data['minimumValue'])? $data['minimumValue']:null;
        $fixedAmount = isset($data['fixedAmount'])? $data['fixedAmount']:null;
        $matrixFormula = isset($data['matrixFormula'])? $data['matrixFormula']:null;
        $formula = isset($data['formula'])? $data['formula']:null;
        $calculationMethod = isset($data['calculationMethod'])? $data['calculationMethod']:null;
        $isActive = isset($data['status']['isActive'])? $data['status']['isActive']:null;

        $data =  FeeTemplates::create($idController, $idFeeType, $title, $description, $minimumValue, $fixedAmount,
            $matrixFormula, $formula, $calculationMethod, $isActive);

        $this->standardResponse($data, 'feeTemplate');
    }

    /**
     * API Function that gets a specified FeeTemplate.
     * @param $idFeeTemplate
     * @Required_Privilege: Read
     */
    public function getFeeTemplateAPI($idFeeTemplate)
    {
        $this->verifyPrivilege(Privilege::READ);

        $data = FeeTemplates::getFeeTemplate($idFeeTemplate);

        $this->standardResponse($data, 'feeTemplate');
    }

    /**
     * API Function edits the specified FeeTemplate.
     * @param $idFeeTemplate
     * @Required_Privilege: Write
     */
    public function editFeeTemplateAPI($idFeeTemplate)
    {
        $this->verifyPrivilege(Privilege::WRITE);

        $data = Request::get()->getRequestBody();

        $idFeeType = isset($data['idFeeType'])? $data['idFeeType']:null;
        $title = isset($data['title'])? $data['title']:null;
        $description = isset($data['description'])? $data['description']:null;
        $minimumValue = isset($data['minimumValue'])? $data['minimumValue']:null;
        $fixedAmount = isset($data['fixedAmount'])? $data['fixedAmount']:null;
        $matrixFormula = isset($data['matrixFormula'])? $data['matrixFormula']:null;
        $formula = isset($data['formula'])? $data['formula']:null;
        $calculationMethod = isset($data['calculationMethod'])? $data['calculationMethod']:null;
        $isActive = isset($data['status']['isActive'])? $data['status']['isActive']:null;


        $data = FeeTemplates::editFeeTemplate($idFeeTemplate, $idFeeType, $title, $description, $minimumValue, $fixedAmount,
            $matrixFormula, $formula, $calculationMethod, $isActive);

        $this->standardResponse($data, 'feeTemplate');
    }

    /**
     * API Function that deletes a specified FeeTemplate.
     * @param $idFeeTemplate
     * @Required_Privilege: Delete
     */
    public function deleteFeeTemplateAPI($idFeeTemplate)
    {
        $this->verifyPrivilege(Privilege::DELETE);

        $data = FeeTemplates::deleteFeeTemplate($idFeeTemplate);

        $this->standardResponse($data, 'feeTemplate');
    }

    /**
     * API Function that gets all Fee Tags for the specified Fee Template.
     * @param $idFeeTemplate
     * @Required_Privilege: Read
     */
    public function getFeeTemplateFeeTagsAPI($idFeeTemplate)
    {
        $this->verifyPrivilege(Privilege::READ);

        $data = FeeTemplateFeeTag::getFeeTemplateFeeTags($idFeeTemplate);

        $this->standardResponse($data, 'feeTemplate_FeeTag');
    }

    /**
     * API Function that Adds a Fee Tag for the given Fee Template.
     * @Required_Privilege: Create
     */
    public function createFeeTemplateFeeTagAPI()
    {
        $this->verifyPrivilege(Privilege::CREATE);

        $data = Request::get()->getRequestBody();

        $idFeeTemplate = isset($data['$idFeeTemplate'])? $data['$idFeeTemplate']:null;
        $idFeeTag = isset($data['idFeeTag'])? $data['idFeeTag']:null;

        $data =  FeeTemplateFeeTag::create($idFeeTemplate, $idFeeTag);

        $this->standardResponse($data, 'feeTemplate_FeeTag');
    }

    /**
     * API Function that deletes a specified FeeTemplate.
     * @param $idFeeTemplate
     * @param $idFeeTag
     * @Required_Privilege: Delete
     */
    public function deleteFeeTemplateFeeTagAPI($idFeeTemplate, $idFeeTag)
    {
        $this->verifyPrivilege(Privilege::DELETE);

        $data = FeeTemplateFeeTag::deleteFeeTemplateFeeTag($idFeeTemplate, $idFeeTag);

        $this->standardResponse($data, 'feeTemplate_FeeTag');
    }

    /**
     * API Function that gets all FeeTags for this Controller.
     * @Required_Privilege: Read
     */
    public function getFeeTagsAPI()
    {
        $this->verifyPrivilege(Privilege::READ);

        $result = FeeTags::getFeeTags($this->getId(), true, true, true);

        $table = $result['keydict'];

        $categories = FeeTagCategories::getFeeTagCategories($this->getId());
        $categoryFilter = [];
        $found = [];
        foreach($categories as $category){
            if(!isset($found[$category['title']])) {
                $categoryFilter[] = ['option' => $category['title'], 'sql' => "FeeTagCategories.title = '{$category['title']}'"];
                $found[$category['title']] = true;
            }
        }

        $view = StandardView::build('FeeTags.All', 'Fee Tags', $result['keydict'], $result);
        $view->add(
            Column::import($table->idFeeTag)->setEnabled(false),
            Column::import($table->feeTagValue, "Title")->filterBySearch()->bucketBy()->setSort(true),
            Column::import($table->title, "Category")->filterByValue($categoryFilter)->bucketBy()->setSort(false, "FeeTagCategories.title"),
            Column::build("options", "Options")->setTemplate("mvCustomColumnEdit")
        );

        $view->response($this);
        $data = $view->json(false);

        $this->standardResponse($data, 'feeTag');
    }

    /**
     * API Function that Creates a FeeTag for this Controller.
     * @Required_Privilege: Create
     */
    public function createFeeTagAPI()
    {
        $this->verifyPrivilege(Privilege::CREATE);

        $data = Request::get()->getRequestBody();

        $idController = $this->getId();

        $feeTagValue = isset($data['feeTagValue'])? $data['feeTagValue']:null;
        $idFeeTagCategory = isset($data['idFeeTagCategory'])? $data['idFeeTagCategory']:null;

        $data =  FeeTags::create($idController, $feeTagValue, $idFeeTagCategory);

        $this->standardResponse($data, 'feeTag');
    }

    /**
     * API Function that gets a specified FeeTag.
     * @param $idFeeTag
     * @Required_Privilege: Read
     */
    public function getFeeTagAPI($idFeeTag)
    {
        $this->verifyPrivilege(Privilege::READ);

        $data = FeeTags::getFeeTag($idFeeTag);

        $this->standardResponse($data, 'feeTag');
    }

    /**
     * API Function edits the specified FeeTag.
     * @param $idFeeTag
     * @Required_Privilege: Write
     */
    public function editFeeTagAPI($idFeeTag)
    {
        $this->verifyPrivilege(Privilege::WRITE);

        $data = Request::get()->getRequestBody();

        $feeTagValue = isset($data['feeTagValue'])? $data['feeTagValue']:null;

        $data = FeeTags::editFeeTag($idFeeTag, $feeTagValue);

        $this->standardResponse($data, 'feeTag');
    }

    /**
     * API Function that deletes a specified FeeTag.
     * @param $idFeeTag
     * @Required_Privilege: Delete
     */
    public function deleteFeeTagAPI($idFeeTag)
    {
        $this->verifyPrivilege(Privilege::DELETE);

        $data = FeeTags::deleteFeeTag($idFeeTag);

        $this->standardResponse($data, 'feeTag');
    }

    /**
     * API Function that gets all FeeMatrices for this Controller.
     * @param $idFeeTemplate
     * @Required_Privilege: Read
     */
    public function getFeeMatricesAPI($idFeeTemplate)
    {
        $this->verifyPrivilege(Privilege::READ);

        $data = FeeMatrices::getFeeMatrices($idFeeTemplate);

        $this->standardResponse($data, 'feeMatrix');
    }

    /**
     * API Function that uses a Test value against the matricies of this idFeeTemplate.
     * @param $idFeeTemplate
     * @Required_Privilege: Read
     */
    public function getFeeMatricesTestAPI($idFeeTemplate)
    {
        $this->verifyPrivilege(Privilege::READ);

        $data = $_GET;

        $testInput = isset($data['testInput'])? $data['testInput']:null;

        $data = FeeMatrices::solveMatrix($idFeeTemplate, $testInput);

        $this->standardResponse($data, 'feeMatrix');
    }

    /**
     * API Function that Creates a FeeMatrix for this Controller.
     * @Required_Privilege: Create
     */
    public function createFeeMatrixAPI()
    {
        $this->verifyPrivilege(Privilege::CREATE);

        $data = Request::get()->getRequestBody();

        $idFeeTemplate = isset($data['idFeeTemplate'])? $data['idFeeTemplate']:null;
        $startingValue = isset($data['startingValue'])? $data['startingValue']:null;
        $order = isset($data['order'])? $data['order']:null;
        $baseFee = isset($data['baseFee'])? $data['baseFee']:null;
        $increment = isset($data['increment'])? $data['increment']:null;
        $incrementFee = isset($data['incrementFee'])? $data['incrementFee']:null;


        $data =  FeeMatrices::create($idFeeTemplate, $startingValue, $order, $baseFee, $increment, $incrementFee);

        $this->standardResponse($data, 'feeMatrix');
    }

    /**
     * API Function that gets a specified FeeMatrix.
     * @param $idFeeTemplate
     * @param $idFeeMatrix
     * @Required_Privilege: Read
     */
    public function getFeeMatrixAPI($idFeeTemplate, $idFeeMatrix)
    {
        $this->verifyPrivilege(Privilege::READ);

        $data = FeeMatrices::getFeeMatrix($idFeeMatrix);

        $this->standardResponse($data, 'feeMatrix');
    }

    /**
     * API Function edits the specified FeeMatrix.
     * @param $idFeeTemplate
     * @param $idFeeMatrix
     * @Required_Privilege: Write
     */
    public function editFeeMatrixAPI($idFeeTemplate, $idFeeMatrix)
    {
        $this->verifyPrivilege(Privilege::WRITE);

        $data = Request::get()->getRequestBody();

        $startingValue = isset($data['startingValue'])? $data['startingValue']:null;
        $order = isset($data['order'])? $data['order']:null;
        $baseFee = isset($data['baseFee'])? $data['baseFee']:null;
        $increment = isset($data['increment'])? $data['increment']:null;
        $incrementFee = isset($data['incrementFee'])? $data['incrementFee']:null;

        $data = FeeMatrices::editFeeMatrix($idFeeMatrix, $startingValue, $order, $baseFee, $increment, $incrementFee);

        $this->standardResponse($data, 'feeMatrix');
    }

    /**
     * API Function that deletes a specified FeeMatrix.
     * @param $idFeeTemplate
     * @param $idFeeMatrix
     * @Required_Privilege: Delete
     */
    public function deleteFeeMatrixAPI($idFeeTemplate, $idFeeMatrix)
    {
        $this->verifyPrivilege(Privilege::DELETE);

        $data = FeeMatrices::deleteFeeMatrix($idFeeMatrix);

        $this->standardResponse($data, 'feeMatrix');
    }

    /**
     * API Function that gets all FeeTypes for this Controller.
     * @Required_Privilege: Read
     */
    public function getFeeTypesAPI()
    {
        $this->verifyPrivilege(Privilege::READ);

        $result = FeeTypes::getFeeTypes($this->getId(), true);
        $table = $result['keydict'];

        $isPayableFilter = [['option' => 'Is Payable', 'sql' => "FeeTypes.feeTypeFlags_0 & 0b01"],
            ['option' => 'Not Payable', 'sql' => "NOT FeeTypes.feeTypeFlags_0 & 0b01"]];

        $isOpenFilter = [['option' => 'Is Shown Open', 'sql' => "FeeTypes.feeTypeFlags_0 & 0b010"],
            ['option' => 'Not Shown Open', 'sql' => "NOT FeeTypes.feeTypeFlags_0 & 0b010"]];

        $isDepositFilter = [['option' => 'Is Deposit', 'sql' => "FeeTypes.feeTypeFlags_0 & 0b0100"],
            ['option' => 'Not Deposit', 'sql' => "NOT FeeTypes.feeTypeFlags_0 & 0b0100"]];

        $view = StandardView::build('Categories.All', 'Fee Tag Categories', $result['keydict'], $result);
        $view->add(
            Column::import($table->idFeeType)->setEnabled(false),
            Column::import($table->feeTypeTitle)->filterBySearch()->bucketBy()->setSort(true),
            Column::import($table->feeTypeFlags->isPayable)->filterByValue($isPayableFilter)->setSort(false, "FeeTypes.feeTypeFlags_0 & 0b01"),
            Column::import($table->feeTypeFlags->isOpen)->filterByValue($isOpenFilter)->setSort(false, "FeeTypes.feeTypeFlags_0 & 0b010"),
            Column::import($table->feeTypeFlags->isDeposit)->filterByValue($isDepositFilter)->setSort(false, "FeeTypes.feeTypeFlags_0 & 0b0100"),
            Column::build("options", "Options")->setTemplate('mvCustomColumnEdit')
        );

        $view->response($this);
        $data = $view->json(false);

        $this->standardResponse($data, 'feeType');
    }

    /**
     * API Function that Creates a FeeType for this Controller.
     * @Required_Privilege: Create
     */
    public function createFeeTypeAPI()
    {
        $this->verifyPrivilege(Privilege::CREATE);

        $data = Request::get()->getRequestBody();

        $idController = $this->getId();

        $feeTypeTitle = isset($data['feeTypeTitle'])? $data['feeTypeTitle']:null;
        $isPayable = isset($data['feeTypeFlags']['isPayable'])? $data['feeTypeFlags']['isPayable']:null;
        $isOpen = isset($data['feeTypeFlags']['isOpen'])? $data['feeTypeFlags']['isOpen']:null;
        $isDeposit = isset($data['feeTypeFlags']['isDeposit'])? $data['feeTypeFlags']['isDeposit']:null;

        $data =  FeeTypes::create($idController, $feeTypeTitle, $isPayable, $isOpen, $isDeposit);

        $this->standardResponse($data, 'feeType');
    }

    /**
     * API Function that gets a specified FeeType.
     * @param $idFeeType
     * @Required_Privilege: Read
     */
    public function getFeeTypeAPI($idFeeType)
    {
        $this->verifyPrivilege(Privilege::READ);

        $data = FeeTypes::getFeeType($idFeeType);

        $this->standardResponse($data, 'feeType');
    }

    /**
     * API Function edits the specified FeeType.
     * @param $idFeeType
     * @Required_Privilege: Write
     */
    public function editFeeTypeAPI($idFeeType)
    {
        $this->verifyPrivilege(Privilege::WRITE);

        $data = Request::get()->getRequestBody();

        $feeTypeTitle = isset($data['feeTypeTitle'])? $data['feeTypeTitle']:null;
        $isPayable = isset($data['feeTypeFlags']['isPayable'])? $data['feeTypeFlags']['isPayable']:null;
        $isOpen = isset($data['feeTypeFlags']['isOpen'])? $data['feeTypeFlags']['isOpen']:null;
        $isDeposit = isset($data['feeTypeFlags']['isDeposit'])? $data['feeTypeFlags']['isDeposit']:null;

        $data = FeeTypes::editFeeType($idFeeType, $feeTypeTitle, $isPayable, $isOpen, $isDeposit);

        $this->standardResponse($data, 'feeType');
    }

    /**
     * API Function that deletes a specified FeeType.
     * @param $idFeeType
     * @Required_Privilege: Delete
     */
    public function deleteFeeTypeAPI($idFeeType)
    {
        $this->verifyPrivilege(Privilege::DELETE);

        $data = FeeTypes::deleteFeeType($idFeeType);

        $this->standardResponse($data, 'feeType');
    }


    /**
     * API Function that gets all FeeTagCategories for this Controller.
     * @Required_Privilege: Read
     */
    public function getFeeTagCategoriesAPI()
    {
        $this->verifyPrivilege(Privilege::READ);

        $result = FeeTagCategories::getFeeTagCategories($this->getId(), false, true);
        $table = $result['keydict'];

        $feeScheduleFilter = [['option' => 'Is Fee Schedule', 'sql' => "FeeTagCategories.status_0 & 0b01"],
                        ['option' => 'Not Fee Schedule', 'sql' => "NOT FeeTagCategories.status_0 & 0b01"]];

        $view = StandardView::build('Categories.All', 'Fee Tag Categories', $result['keydict'], $result);
        $view->add(
            Column::import($table->idFeeTagCategory)->setEnabled(false)->bucketBy(),
            Column::import($table->title)->filterBySearch()->bucketBy()->setSort(true),
            Column::import($table->status->isFeeSchedule)->filterByValue($feeScheduleFilter)->setSort(false, "FeeTagCategories.status_0 & 0b01"),
            Column::build("options", "Options")->setTemplate("mvCustomColumnEdit")
        );

        $view->response($this);
        $data = $view->json(false);

        $this->standardResponse($data, 'feeTagCategory');
    }

    /**
     * API Function that Creates a FeeTagCategory for this Controller.
     * @Required_Privilege: Create
     */
    public function createFeeTagCategoryAPI()
    {
        $this->verifyPrivilege(Privilege::CREATE);

        $data = Request::get()->getRequestBody();

        $idController = $this->getId();

        $title = isset($data['title'])? $data['title']:null;
        $isFeeSchedule = isset($data['status']['isFeeSchedule'])? $data['status']['isFeeSchedule']:null;

        $data =  FeeTagCategories::create($idController, $title, $isFeeSchedule);

        $this->standardResponse($data, 'feeTagCategory');
    }

    /**
     * API Function that gets a specified FeeTagCategory.
     * @param $idFeeTagCategory
     * @Required_Privilege: Read
     */
    public function getFeeTagCategoryAPI($idFeeTagCategory)
    {
        $this->verifyPrivilege(Privilege::READ);

        $data = FeeTagCategories::getFeeTagCategories($idFeeTagCategory, true);

        $this->standardResponse($data, 'feeTagCategory');
    }

    /**
     * API Function edits the specified FeeTagCategory.
     * @param $idFeeTagCategory
     * @Required_Privilege: Write
     */
    public function editFeeTagCategoryAPI($idFeeTagCategory)
    {
        $this->verifyPrivilege(Privilege::WRITE);

        $data = Request::get()->getRequestBody();

        $title = isset($data['title'])? $data['title']:null;
        $isFeeSchedule = isset($data['status']['isFeeSchedule'])? $data['status']['isFeeSchedule']:null;

        $data = FeeTagCategories::editFeeTagCategory($idFeeTagCategory, $title, $isFeeSchedule);

        $this->standardResponse($data, 'feeTagCategory');
    }

    /**
     * API Function that deletes a specified FeeTagCategory.
     * @param $idFeeTagCategory
     * @Required_Privilege: Delete
     */
    public function deleteFeeTagCategoryAPI($idFeeTagCategory)
    {
        $this->verifyPrivilege(Privilege::DELETE);

        $data = FeeTagCategories::deleteFeeTagCategory($idFeeTagCategory);

        $this->standardResponse($data, 'feeTagCategory');
    }

    /**
     * API Function edits the Project idFeeTag, used as the Code Year.
     * @Required_Privilege: Write
     */
    public function findFeeTagAPI()
    {
        $data = $_GET;

        $found = [];
        if (isset($data['value'])) {
            $text = ltrim($data['value']);
            if (strlen($text)) {
                $found = FeeTags::findFeeTag($text, $this->getId());
            }
        }

        $data = $found;

        $this->standardResponse($data, 'feeTag');
    }

    /**
     * Passer Function that continues to the ModuleRule Module
     * @throws Exception
     */
    public function feeRulesAPI()
    {
        if($this->moduleRule){
            /** @var ModuleRule $child */
            $child= $this->moduleRule;
            $feeTemplates = $this->getFeeTemplates();
            $titleArray = [];
            $objectActions = [];
            foreach($feeTemplates as $feeTemplate)
                if($feeTemplate['status']['isActive'])
                    $titleArray[$feeTemplate['title']] = $feeTemplate;
            $i = 1;
            foreach($titleArray as $key => $value)
                $objectActions[] = ["idObjectAction"=> $i++,
                    "objectActionTitle" =>$key];
            $child->bindObjectActions($objectActions);
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
     * @param string $objectType
     * @param int $responseCode
     * @param bool|false $error
     */
    private function standardResponse($data = [], $objectType = '', $responseCode = 200, $error = false)
    {
        global $pool;
        $this->objectType = $objectType;
        if($error == false)
            $error = $this->messages[$responseCode];

        $responseData = [
            'error' => $error,
            'data' => $data
        ];

        if($this->redirect)
            $responseData['redirect'] = $this->redirect;

        $response = $this->getResponseHandler();

        if($this->getParent()->hasObjectId()) {
            $response->setTemplate('fees.base.html.twig', 'server');
            $response->setTemplate('module.fees.handlebars.html', 'client', $this);
            $response->setTemplate(APP_PATH.'/eprocess360/v3controllers/src/SystemController/static/handlebars/global.multiview.handlebars.html', 'client', $this);
        }
        else {
            $response->setTemplate('fees.settings.html.twig', 'server');
            $response->setTemplate('settings.fees.handlebars.html', 'client', $this);
            $response->setTemplate(APP_PATH.'/eprocess360/v3controllers/src/SystemController/static/handlebars/global.multiview.handlebars.html', 'client', $this);
        }

        /** @var Project|Controller $parent*/
        $parent = $this->getParent();

        $response->extendResponseMeta('Fee', ['currencyOptions'=>[
            'symbol'=>'$',
            'digitSeparator'=>',',
            'decimalSeparator'=>'.'
        ]]);

        if($parent->hasObjectId()) {
            $response->extendResponseMeta('Fee', ['idFeeSchedule' => FeeTags::getFeeTag($parent->getKeydict()->idFeeTag->get())['idFeeTag']]);
            $response->extendResponseMeta('Fee', ['feeScheduleOptions' => $this->getScheduledFeeTags()]);
            $response->extendResponseMeta('Fee', ['feeTypes' => $this->getFeeTypes()]);
        }
        else{
            $response->extendResponseMeta('Fee', ['feeScheduleOptions' => $this->getScheduledFeeTags()]);
            $response->extendResponseMeta('Fee', ['feeTypeOptions' => $this->getFeeTypes()]);
            $response->extendResponseMeta('Fee', ['feeTagOptions' => $this->getFeeTags()]);
            $response->extendResponseMeta('Fee', ['feeTagCategories' => $this->getFeeTagCategories()]);
        }

        $response->extendResponseMeta('Fee', ['objectType'=>$objectType]);
        $response->extendResponseMeta('Fee', ['text'=>['contact'=>$this->getTextContact()]]);
        $response->setResponse($responseData, $responseCode, false);
        if($error)
            $response->setErrorResponse(new \Exception($error));
    }

    /**
     * @param $actions
     */
    private function feeActions($actions)
    {
        foreach($actions as $action){
            //We assume here there are only ADDs, nothing else should get to this point
            $actionType = $action['actionType'];
            $feeTemplateTitle = $action['objectActionTitle'];
            if($actionType == "Add") {
                $idFeeTemplate = $this->getProjectFeeTemplate($feeTemplateTitle);
                if($idFeeTemplate)
                    $this->createFee($idFeeTemplate);
            }
        }
    }

    /**
     * Sets the FeeTemplates usable by this Fee Controller.
     * @return $this
     */
    private function setFeeTemplates()
    {
        $baseFeeTemplates = FeeTemplates::getFeeTemplates($this->getId());

        $feeTemplates= [];
        foreach($baseFeeTemplates as $baseFeeTemplate)
            $feeTemplates[$baseFeeTemplate['idFeeTemplate']] = $baseFeeTemplate;

        $this->feeTemplates = $feeTemplates;
        return $this;
    }


    /**
     * Sets the FeeTags usable by this Fee Controller.
     * @return $this
     */
    private function setFeeTags()
    {
        $baseFeeTags = FeeTags::getFeeTags($this->getId(), true);

        $feeTags = [];
        foreach($baseFeeTags as $baseFeeTag)
            $feeTags[$baseFeeTag['idFeeTag']] = $baseFeeTag;

        $this->feeTags = $feeTags;
        return $this;
    }

    /**
     * Sets the FeeTags usable by this Fee Controller.
     * @return $this
     */
    private function setScheduledFeeTags()
    {
        $baseFeeTags = FeeTags::getFeeTags($this->getId(), false, true);

        $feeTags = [];
        foreach($baseFeeTags as $baseFeeTag)
            $feeTags[$baseFeeTag['idFeeTag']] = $baseFeeTag;

        $this->scheduledFeeTags = $feeTags;
        return $this;
    }

    /**
     * Sets the FeeTypes usable by this Fee Controller.
     * @return $this
     */
    private function setFeeTypes()
    {
        $baseFeeTypes = FeeTypes::getFeeTypes($this->getId());

        $feeTypes = [];
        foreach($baseFeeTypes as $baseFeeType)
            $feeTypes[$baseFeeType['idFeeType']] = $baseFeeType;

        $this->feeTypes = $feeTypes;
        return $this;
    }

    /**
     * Sets the FeeTypes usable by this Fee Controller.
     * @return $this
     */
    private function setFeeTagCategories()
    {
        $baseFeeTagCategories = FeeTagCategories::getFeeTagCategories($this->getId(), true);

        $feeTagCategories = [];
        foreach($baseFeeTagCategories as $baseFeeTagCategory)
            $feeTagCategories[$baseFeeTagCategory['idFeeTagCategory']] = $baseFeeTagCategory;

        $this->feeTagCategories = $feeTagCategories;
        return $this;
    }

    /**
     * Gets the project data from the Project's Keydict.
     * @return array
     * @throws Exception
     */
    private function getProjectData()
    {
        /** @var Project|Controller $parent*/
        $parent = $this->getParent();
        $fields = $parent->getKeydict()->getFields();
        $projectData = [];
        foreach($fields  as $field){
            $projectData[$field->getName()] = $field->getValue();
        }
        $projectData['keydict'] = $parent->getKeydict();
        return $projectData;
    }

    /**
     * @param Toolbar $toolbar
     * @param bool|true $isActive
     * @param bool|true $isAvailable
     */
    public function buildLinks(Toolbar $toolbar, $isActive = true, $isAvailable = true)
    {
        /** @var Controller|Fee $this */
        $title = $this->getDescription()?:$this->getStaticClass();
        $toolbar->addToolbarLink($title, $this->getPath(true, false), $this->objectType === "feeTemplate", $isAvailable);
        $toolbar->addToolbarLink("Categories", $this->getPath(true, false)."/categories", $this->objectType === "feeTagCategory", $isAvailable);
        $toolbar->addToolbarLink("Tags", $this->getPath(true, false)."/tags", $this->objectType === "feeTag", $isAvailable);
        $toolbar->addToolbarLink("Types", $this->getPath(true, false)."/types", $this->objectType === "feeType", $isAvailable);
        $toolbar->addToolbarLink("Rules", $this->getPath(true, false)."/rules", false, $isAvailable);
    }

    /**
     * @param Toolbar $toolbar
     * @param Controller $child
     * @return bool
     */
    public function buildToolbarChildren(Toolbar $toolbar, Controller $child)
    {
        $toolbar->buildParentLinks($this->getParent());
        $isAvailable = true;
        $title = $this->getDescription()?:$this->getStaticClass();
        $toolbar->addToolbarLink($title, $this->getPath(true, false), false, $isAvailable);
        $toolbar->addToolbarLink("Categories", $this->getPath(true, false)."/categories", false, $isAvailable);
        $toolbar->addToolbarLink("Tags", $this->getPath(true, false)."/tags", false, $isAvailable);
        $toolbar->addToolbarLink("Types", $this->getPath(true, false)."/types", false, $isAvailable);
        $toolbar->addToolbarLink("Rules", $this->getPath(true, false)."/rules", $child->getClass() === "ModuleRule", $isAvailable);

        return true;
    }

    /*********************************************   #WORKFLOW#  *********************************************/

    /**
     * Returns the array of Fee Templates on the Fee Controller.
     * @return array|null
     */
    public function getFeeTemplates()
    {
        if($this->feeTemplates)
            return $this->feeTemplates;
        else
            return $this->setFeeTemplates()->feeTemplates;
    }

    /**
     * Returns the array of Fee Templates on the Fee Controller.
     * @return array|null
     */
    public function getFeeTags()
    {
        if($this->feeTags)
            return $this->feeTags;
        else
            return $this->setFeeTags()->feeTags;
    }

    /**
     * Returns the array of Fee Templates on the Fee Controller.
     * @return array|null
     */
    public function getScheduledFeeTags()
    {
        if($this->scheduledFeeTags)
            return $this->scheduledFeeTags;
        else
            return $this->setScheduledFeeTags()->scheduledFeeTags;
    }

    /**
     * Returns the array of Fee Templates on the Fee Controller.
     * @return array|null
     */
    public function getFeeTypes()
    {
        if($this->feeTypes)
            return $this->feeTypes;
        else
            return $this->setFeeTypes()->feeTypes;
    }

    /**
     * Returns the array of getfeeTagCategories on the Fee Controller.
     * @return array|null
     */
    public function getFeeTagCategories()
    {
        if($this->feeTagCategories)
            return $this->feeTagCategories;
        else
            return $this->setFeeTagCategories()->feeTagCategories;
    }

    /**
     * @param $idFeeType
     * @param $title
     * @param $description
     * @param $minimumValue
     * @param $fixedAmount
     * @param $matrixFormula
     * @param $formula
     * @param $calculationMethod
     * @param ...$idFeeTags
     * @return array
     */
    public function createFeeTemplate($idFeeType, $title, $description, $minimumValue, $fixedAmount,
                                      $matrixFormula, $formula, $calculationMethod,...$idFeeTags)
    {
        $idController = $this->getId();

        $isActive = true;

        $data =  FeeTemplates::create($idController, $idFeeType, $title, $description, $minimumValue, $fixedAmount,
            $matrixFormula, $formula, $calculationMethod, $isActive);

        foreach($idFeeTags as $idFeeTag)
        FeeTemplateFeeTag::create($data['idFeeTemplate'],$idFeeTag); //TODO Optimize this Tag addition.

        return $data;
    }

    /**
     * Workflow Function that Creates a Fee.
     * @param $idFeeTemplate
     * @param $total
     * @param $feeUnitPrice
     * @return array
     * @throws \eprocess360\v3core\Controller\ControllerException
     */
    public function createFee($idFeeTemplate, $total = null, $feeUnitPrice = 0)
    {
        $idProject = $this->getParent()->getObjectId();
        $notes = '';
        $totalOverride = false;
        $unitPriceOverride = false;
        $quantity = 0;

        $projectData = $this->getProjectData();

        $data =  Fees::create($idFeeTemplate, $idProject, $total, $feeUnitPrice, $quantity, $notes, $totalOverride, $unitPriceOverride, $projectData);

        return $data;
    }

    /**
     *
     */
    public function createProjectFee()
    {
        $moduleRule = $this->moduleRule;
        $actions = $moduleRule->evaluateRulesByController($this->getId());
        $this->feeActions($actions);
    }

    /**
     * Workflow Function that gets all FeeTags for this Controller.
     */
    public function getFeeTagByTitle($title)
    {
        $data = FeeTags::getFeeTagByTitle($this->getId(), $title);

        return $data;
    }

    public function recalculateProjectFees()
    {
        Fees::recalculateFees($this->getParent()->getObjectId(), $this->getProjectData());
    }

    /**
     * For a given Title, a corresponding Fee Template that's current to the current project idFeeTag.
     * @param $feeTemplateTitle
     * @return int
     * @throws Exception
     */
    public function getProjectFeeTemplate($feeTemplateTitle)
    {
        /** @var Project|Controller $parent */
        $parent = $this->getParent();
        $idFeeTag = $parent->getKeydict()->idFeeTag->get();
        $feeTemplates = $this->getFeeTemplates();
        $idFeeTemplate = 0;
        foreach($feeTemplates as $feeTemplate)
            if($feeTemplate['title'] == $feeTemplateTitle && $feeTemplate['status']['isActive']) {
                $idFeeTemplate = $feeTemplate['idFeeTemplate'];
                foreach ($feeTemplate['tags'] as $tag)
                    if ((int)$tag == $idFeeTag)
                        break 2;
            }

        return $idFeeTemplate;
    }

    /**
     * For a given Title, a corresponding Fee Template that's current to the current project idFeeTag.
     * @param $feeTemplateTitle
     * @return int
     * @throws Exception
     */
    public static function getProjectFeeTemplateStatic($feeTemplateTitle, $idController, $idFeeTag)
    {
        $baseFeeTemplates = FeeTemplates::getFeeTemplates($idController);

        $feeTemplates= [];
        foreach($baseFeeTemplates as $baseFeeTemplate)
            $feeTemplates[$baseFeeTemplate['idFeeTemplate']] = $baseFeeTemplate;

        $idFeeTemplate = 0;
        foreach($feeTemplates as $feeTemplate)
            if($feeTemplate['title'] == $feeTemplateTitle && $feeTemplate['status']['isActive']) {
                $idFeeTemplate = $feeTemplate['idFeeTemplate'];
                foreach ($feeTemplate['tags'] as $tag)
                    if ((int)$tag == $idFeeTag)
                        break 2;
            }

        if($idFeeTemplate)
            return $feeTemplates[$idFeeTemplate];
        else
            return 0;
    }

    /**
     * @param $variables
     */
    public function editProjectVariables($variables)
    {
        /** @var Project|Controller $parent */
        $parent = $this->getParent();
        $keydict = $parent->getKeydict();

        foreach($variables as $key=>$value)
            if($field = $keydict->getField($key))
                $field->set($value);

        $parent->save();
    }

    /**
     * @param $idFeeTag
     * @throws \eprocess360\v3core\Controller\ControllerException
     * @throws \eprocess360\v3core\Keydict\Exception\InvalidValueException
     * @throws \eprocess360\v3core\Keydict\Exception\KeydictException
     */
    public function editFeeScheduleTag($idFeeTag)
    {
        $this->getProjectFeesAPI();
        $templates = $this->getFeeTemplates();
        $projectData = $this->getProjectData();
        $fees = Fees::getFees($this->getParent()->getObjectId(), $projectData);
        $feeKeydict = Fees::keydict();
        $templateNameArray = [];
        $templateIdArray = [];
        foreach($templates as $template) {
            $templateIdArray[$template['idFeeTemplate']] = $template;
            if (!isset($templateNameArray[$template['title']]) || $templateNameArray[$template['title']]['feeSchedule'] != $idFeeTag)
                $templateNameArray[$template['title']] = $template['idFeeTemplate'];
        }
        foreach($fees as $fee){
            if($templateIdArray[$fee['idFeeTemplate']]['feeSchedule'] != $idFeeTag) {
                /** @var Fees|Table $updateFee */
                $updateFee = $feeKeydict->wakeup($fee);
                if(isset($templateNameArray[$fee['feeTemplate']['title']])) {
                    $updateFee->idFeeTemplate->set($templateNameArray[$fee['feeTemplate']['title']]);
                    $updateFee->update();
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function checkDepositsPaid()
    {
        $idProject = $this->getParent()->getObjectId();

        return Fees::checkIsPaid($idProject, $this->getProjectData(), true);
    }

    /**
     * @return bool
     */
    public function checkFeesPaid()
    {
        $idProject = $this->getParent()->getObjectId();

        return Fees::checkIsPaid($idProject, $this->getProjectData());
    }

    /**
     * @return array|null
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * @param $url
     * @return $this
     */
    public function setRedirect($url)
    {
        $this->redirect = $url;
        return $this;
    }

    public function deleteProjectFees()
    {
        $idProject = $this->getParent()->getObjectId();
        Fees::deleteFeesByProject($idProject);
    }


    /******************************************   #INITIALIZATION#  ******************************************/


    /**
     * @param array $feeTypes
     * @return $this
     */
    public function bindFeeTypes(...$feeTypes)
    {
        //TODO Optimie these Inserts
        $idController = $this->getId();
        $feeTypeArray = [];
        foreach($feeTypes as $feeType) {
            $feeTypeArray[] = FeeTypes::create($idController, $feeType['feeTypeTitle'], $feeType['isPayable'], $feeType['isOpen'], $feeType['isDeposit']);
        }
        return $feeTypeArray;
    }

    /**
     * @param ...$feeTagCategories
     * @return array
     */
    public function bindFeeTagCategories(...$feeTagCategories)
    {
        //TODO Optimie these Inserts
        $idController = $this->getId();
        $feeTagCategoryArray = [];
        foreach($feeTagCategories as $feeTagCategory) {
            $feeTagCategoryArray[] = FeeTagCategories::create($idController, $feeTagCategory['title'], $feeTagCategory['isFeeSchedule']);
        }
        return $feeTagCategoryArray;
    }

    /**
     * @param array $feeTags
     * @return $this
     */
    public function bindFeeTags(...$feeTags)
    {
        //TODO Optimie these Inserts
        $idController = $this->getId();
        $feeTagArray = [];
        foreach($feeTags as $feeTag) {
            $feeTagArray[] = FeeTags::create($idController, $feeTag['feeTagValue'], $feeTag['idFeeTagCategory']);
        }
        return $feeTagArray;
    }

    /**
     * Binds corresponding ModuleRule Module to the Review.
     * @param ModuleRule $moduleRule
     * @return $this
     */
    public function bindModuleRule(ModuleRule $moduleRule)
    {
        $this->moduleRule = $moduleRule;
        return $this;
    }

    /**
     * @param $textContact
     * @return $this
     */
    public function setTextContact($textContact)
    {
        $this->textContact = $textContact;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTextContact()
    {
        return $this->textContact;
    }



    /*********************************************   #TRIGGERS#  *********************************************/


    /**
     * Trigger fired when the Form has been Updated on a successful POST/PUT request.
     * @param \Closure $closure
     * @return \eprocess360\v3core\Controller\Trigger\Trigger
     */
    public function onPayment($closure)
    {
        return $this->addTrigger(__FUNCTION__, $closure);
    }

    public function dashboardInit()
    {
        $this->setDashboardIcon('blank fa fa-usd');
    }
}