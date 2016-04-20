<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 2/1/16
 * Time: 12:38 PM
 */

namespace eprocess360\v3modules\Fee\Model;


use eprocess360\v3core\DB;
use eprocess360\v3core\Formula\Formula;
use eprocess360\v3core\Keydict\Entry\Bit;
use eprocess360\v3core\Keydict\Entry\MoneyVal;
use eprocess360\v3core\Keydict\Entry\Bits8;
use eprocess360\v3core\Keydict\Entry\Datetime;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\FloatNumber;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\String;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;
use eprocess360\v3modules\Fee\Fee;
use Exception;
use jlawrence\eos\Parser;

/**
 * Class FeeTemplates
 * @package eprocess360\v3modules\Fee\Model
 */
class FeeTemplates extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idFeeTemplate', 'Fee Template ID'),
            IdInteger::build('idController', 'Fee Controller ID'),
            IdInteger::build('idFeeType', 'ID Fee Type')->setRequired()->joinsOn(FeeTypes::model()),
            FixedString128::build('title', 'Title')->setRequired(),
            FixedString128::build('description', 'Description'),
            MoneyVal::build('minimumValue', 'Minimum Value'),
            MoneyVal::build('fixedAmount', 'Fixed Amount'),
            FixedString128::build('matrixFormula', 'Matrix Variable'),
            FixedString128::build('formula', 'Formula'),
            Datetime::build('dateCreated', 'Date Created'),
            Bits8::make('calculationMethod',
                Bit::build(0, 'isFixed',"Is Fixed Fee"),
                Bit::build(1, 'isUnit',"Is Unit Fee"),
                Bit::build(2, 'isFormula',"Is Formula Fee"),
                Bit::build(3, 'isMatrix',"Is Matrix Fee")
            ),
            Bits8::make('status',
                Bit::build(0, 'isActive', "Is Active")
            )
        )->setName('FeeTemplates')->setLabel('FeeTemplates');
    }

    /**
     * @param int $idController
     * @param int $idFeeType
     * @param int $title
     * @param int $description
     * @param int $minimumValue
     * @param int $fixedAmount
     * @param int $matrixFormula
     * @param string $formula
     * @param string $dateCreated
     * @param array $calculationMethod
     * @param array $status
     * @return FeeTemplates
     */
    public static function make($idController = 0,$idFeeType = 0, $title = 0, $description = 0,
                                $minimumValue = 0,$fixedAmount = 0,
                                $matrixFormula = 0, $formula = '', $dateCreated = '',
                                $calculationMethod = [0], $status = ['isActive' => false])
    {

        $rowData = [
            'idController' => $idController,
            'idFeeType' => $idFeeType,
            'title' => $title,
            'description' => $description,
            'minimumValue' => $minimumValue,
            'fixedAmount' => $fixedAmount,
            'matrixFormula' => $matrixFormula,
            'formula' => $formula,
            'dateCreated' => $dateCreated,
            'calculationMethod' => $calculationMethod,
            'status' => $status];

        return self::FeeTemplateConstruct($rowData);
    }

    /**
     * @param array $rowData
     * @return FeeTemplates
     */
    public static function FeeTemplateConstruct($rowData = [])
    {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);
        return $instance;
    }

    /**
     * @param $idController
     * @param $idFeeType
     * @param $title
     * @param $description
     * @param $minimumValue
     * @param $fixedAmount
     * @param $matrixFormula
     * @param $formula
     * @param $calculationMethod
     * @param $isActive
     * @return array
     */
    public static function create($idController, $idFeeType, $title, $description, $minimumValue, $fixedAmount,
                                  $matrixFormula, $formula, $calculationMethod, $isActive)
    {
        if($calculationMethod['isFormula'] == null){
            //Else if not used here because isFixed could technically be corresponding with isUnit.
            if($calculationMethod['isFixed'])
                $formula = "Basis()";
            if($calculationMethod['isUnit'])
                $formula = "Quantity() * Basis()";
            if($calculationMethod['isMatrix'])
                $formula = "Matrix()";
        }

        $dateCreated = DateTime::timestamps();

        $status = ['isActive' => $isActive];

        $f = static::make($idController, $idFeeType, $title, $description, $minimumValue, $fixedAmount,
            $matrixFormula, $formula, $dateCreated, $calculationMethod, $status);

        $f->insert();

        $result = $f->data->toArray();

        return $result;
    }

    /**
     * @param $idController
     * @param bool|false $multiView
     * @return array|null
     * @throws Exception
     */
    public static function getFeeTemplates($idController, $multiView = false)
    {
        $sql = "SELECT FeeTemplates.*, GROUP_CONCAT(DISTINCT FeeTemplateFeeTag.idFeeTag SEPARATOR ', ') as tags,
                        FeeTags.idFeeTag as feeSchedule, COUNT(DISTINCT Projects.idProject) as projectCount
                FROM FeeTemplates
                LEFT JOIN FeeTemplateFeeTag
                    ON FeeTemplateFeeTag.idFeeTemplate = FeeTemplates.idFeeTemplate
                LEFT JOIN FeeTags
                    ON FeeTemplateFeeTag.idFeeTag = FeeTags.idFeeTag
                LEFT JOIN FeeTagCategories
                    ON FeeTagCategories.idFeeTagCategory = FeeTags.idFeeTagCategory
                LEFT JOIN Fees
                    ON Fees.idFeeTemplate = FeeTemplates.idFeeTemplate
                LEFT JOIN Projects
                    ON Fees.idProject = Projects.idProject
                WHERE
                  FeeTemplates.idController = {$idController}
                  AND FeeTagCategories.status_0 & 0b01
                GROUP BY FeeTemplates.idFeeTemplate
                ORDER BY FeeTemplates.title ASC";

        if($multiView){

            $keydict = self::keydict()->add(String::build('tags', 'Tags'))->add(String::build('feeSchedule', 'Fee Schedule Tag'))
                ->add(IdInteger::build('projectCount', 'Project Count'))->add(String::build('feeTypeTitle', 'Group'));

            $select = "FeeTemplates.*, FeeTypes.feeTypeTitle, GROUP_CONCAT(DISTINCT FeeTemplateFeeTag.idFeeTag SEPARATOR ', ') as tags,
                        FeeTags.feeTagValue as feeSchedule, COUNT(DISTINCT Projects.idProject) as projectCount";
            $join = "LEFT JOIN FeeTemplateFeeTag
                    ON FeeTemplateFeeTag.idFeeTemplate = FeeTemplates.idFeeTemplate
                LEFT JOIN FeeTags
                    ON FeeTemplateFeeTag.idFeeTag = FeeTags.idFeeTag
                LEFT JOIN FeeTagCategories
                    ON FeeTagCategories.idFeeTagCategory = FeeTags.idFeeTagCategory
                LEFT JOIN Fees
                    ON Fees.idFeeTemplate = FeeTemplates.idFeeTemplate
                LEFT JOIN Projects
                    ON Fees.idProject = Projects.idProject
                LEFT JOIN FeeTypes
                    ON FeeTypes.idFeeType = FeeTemplates.idFeeType";
            $where = "FeeTemplates.idController = {$idController}
                  AND FeeTagCategories.status_0 & 0b01";
            $group = "FeeTemplates.idFeeTemplate";
            $result = ['keydict'=>$keydict, 'select'=>$select,'join'=>$join, 'where'=>$where, 'group'=>$group];
            return $result;
        }

        $feeTemplates = DB::sql($sql);

        $keydict = self::keydict()->add(String::build('tags', 'Tags'))->add(IdInteger::build('feeSchedule', 'Fee Schedule Tag'))
            ->add(IdInteger::build('projectCount', 'Project Count'))->add(String::build('feeTypeTitle', 'Group'));

        foreach ($feeTemplates as &$feeTemplate) {
            $feeTemplate = $keydict->wakeup($feeTemplate)->toArray();
            $feeTemplate['tags'] = array_map('intval', explode(', ', $feeTemplate['tags']));
        }

        return $feeTemplates;
    }

    /**
     * @param $idFeeTemplate
     * @return array
     * @throws \Exception
     */
    public static function getFeeTemplate($idFeeTemplate)
    {
        $sql = "SELECT FeeTemplates.*, GROUP_CONCAT(DISTINCT FeeTemplateFeeTag.idFeeTag SEPARATOR ', ') as tags,
                        FeeTags.idFeeTag as feeSchedule, COUNT(DISTINCT Projects.idProject) as projectCount
                FROM FeeTemplates
                LEFT JOIN FeeTemplateFeeTag
                    ON FeeTemplateFeeTag.idFeeTemplate = FeeTemplates.idFeeTemplate
                LEFT JOIN FeeTags
                    ON FeeTemplateFeeTag.idFeeTag = FeeTags.idFeeTag
                LEFT JOIN FeeTagCategories
                    ON FeeTagCategories.idFeeTagCategory = FeeTags.idFeeTagCategory
                LEFT JOIN Fees
                    ON Fees.idFeeTemplate = FeeTemplates.idFeeTemplate
                LEFT JOIN Projects
                    ON Fees.idProject = Projects.idProject
                WHERE
                  FeeTemplates.idFeeTemplate = {$idFeeTemplate}
                  AND FeeTagCategories.status_0 & 0b01
                GROUP BY FeeTemplates.idFeeTemplate
                ORDER BY FeeTemplates.title ASC";

        $feeTemplates = DB::sql($sql);

        $keydict = self::keydict()->add(String::build('tags', 'Tags'))->add(IdInteger::build('feeSchedule', 'Fee Schedule Tag'))
            ->add(IdInteger::build('projectCount', 'Project Count'));
        foreach ($feeTemplates as &$feeTemplate) {
            $feeTemplate = $keydict->wakeup($feeTemplate)->toArray();
            $feeTemplate['tags'] = array_map('intval', explode(', ', $feeTemplate['tags']));

            return $feeTemplate;
        }

        throw new Exception('FeeTemplate with the ID of '.$idFeeTemplate.' was not found.');
    }


    /**
     * @param $idFeeTemplate
     * @return bool
     * @throws \Exception
     */
    public static function deleteFeeTemplate($idFeeTemplate)
    {
        $feeTemplate = self::sqlFetch($idFeeTemplate);

        FeeTemplateFeeTag::deleteFeeTemplateFeeTagByIdFeeTemplate($idFeeTemplate);
        FeeMatrices::deleteFeeMatrixByIdFeeTemplate($idFeeTemplate);

        self::deleteById($feeTemplate->idFeeTemplate->get());

        return true;
    }


    /**
     * @param $idFeeTemplate
     * @param $idFeeType
     * @param $title
     * @param $description
     * @param $minimumValue
     * @param $fixedAmount
     * @param $matrixFormula
     * @param $formula
     * @param $calculationMethod
     * @param $isActive
     * @return array
     * @throws Exception
     * @throws \eprocess360\v3core\Keydict\Exception\InvalidValueException
     * @throws \eprocess360\v3core\Keydict\Exception\KeydictException
     */
    public static function editFeeTemplate($idFeeTemplate, $idFeeType, $title, $description, $minimumValue, $fixedAmount,
                                           $matrixFormula, $formula, $calculationMethod, $isActive)
    {
        //TODO In the case that a UnitVaraible is Changed because of an edit, be sure to go through all Fees of this FeeTemplate, and if they have Unit Price Override, unset it.
        //TODO Also change the Unit Prices on corresponding Fees that have the idFeeTemplate of this.
        $feeTemplate = self::sqlFetch($idFeeTemplate);

        if($idFeeType !== NULL)
            $feeTemplate->idFeeType->set($idFeeType);
        if($title !== NULL)
            $feeTemplate->title->set($title);
        if($description !== NULL)
            $feeTemplate->description->set($description);
        if($minimumValue !== NULL)
            $feeTemplate->minimumValue->set($minimumValue);
        if($fixedAmount !== NULL)
            $feeTemplate->fixedAmount->set($fixedAmount);
        if($matrixFormula !== NULL)
            $feeTemplate->matrixFormula->set($matrixFormula);
        if($formula !== NULL)
            $feeTemplate->formula->set($formula);
        if($calculationMethod !== NULL)
            $feeTemplate->calculationMethod->set($calculationMethod);
        if($isActive !== NULL)
            $feeTemplate->status->isActive->set($isActive);

        $feeTemplate->update();

        return $feeTemplate->toArray();
    }

    /**
     * @param $feeData
     * @param $projectData
     * @param int $idFeeTemplate
     * @param int $feeUnitPrice
     * @param int $quantity
     * @param int $idProject
     * @param bool|false $getMatrix
     * @return float|mixed
     * @throws Exception
     */
    public static function processFee($feeData, $projectData, $idFeeTemplate = 0, $feeUnitPrice = 0, $unitPriceOverride = false, $quantity = 0, $idProject = 0, $getMatrix = false, $getFunctionData = false)
    {
        $projectData['projectData'] = $projectData;
        $feeTemplate = $projectData['feeTemplate'] = $feeData ? NULL : self::sqlFetch($idFeeTemplate);
        $idProject = $projectData['idProject'] = $feeData?$feeData['idProject']:$idProject;
        $idFeeTemplate = $projectData['idFeeTemplate'] = $feeData?$feeData['idFeeTemplate']:$idFeeTemplate;
        $minimumValue = $projectData['minimumValue'] = $feeData?$feeData['minimumValue']:$feeTemplate->minimumValue->get();;
        $idController = $projectData['idController'] = $feeData?$feeData['idController']:$feeTemplate->idController->get();
        $fixedAmount = $projectData['fixedAmount'] = $feeData ? ($feeData['overrides']['unitPriceOverride'] ?$feeData['feeUnitPrice']: $feeData['fixedAmount'])
            : ($unitPriceOverride ?$feeUnitPrice:$feeTemplate->fixedAmount->get());
        $matrixFormula = $projectData['matrixFormula'] = $feeData?$feeData['matrixFormula']:$feeTemplate->matrixFormula->get();
        $formula = $projectData['formula'] = $feeData?$feeData['formula']:$feeTemplate->formula->get();
        $quantity = $projectData['quantity'] = $feeData?$feeData['quantity']:$quantity;

        $projectFees  = $projectData['projectFees'] = Fees::getFees($idProject, $projectData);

        $parsingFormula = new Formula();
        $parsingFormula->addKeydict('project', $projectData['keydict']);

        foreach($projectData as $k=>$v) {
            $parsingFormula->setEnvironmentVariable($k, $v);
        }

        $parsingFormula->setEnvironmentVariable('lastResults', []);

        if ($getMatrix)
            return FeeMatrices::solveMatrix($idFeeTemplate, $parsingFormula->parse($matrixFormula));

        $total = $parsingFormula->parse($formula);

/*        //get the formula, parse out the special chars for some weird equivalent or something and continue
        $updatedFormula = $formula;
        $updatedMatrixFormula = $matrixFormula;
        $updatedFunctions = [];

        //Matrix Has to go last.
        $functions = ["Sum", "Quantity", "Basis", "Fee", "Matrix"];

        $processMatrix = false;

        foreach($functions as $function){
            preg_match_all("/".$function."\(\".*?\"\)|".$function."\(\)/", $formula." ".$matrixFormula, $matches);
            $matches = array_unique($matches[0]);

            $criteria = ["/".$function."\(\"/", "/\"\)/", "/".$function."\(\)/",
                "/".$function."\(\'/", "/\'\)/", "/".$function."\(\)/"];

            foreach($matches as $string){
                $title = preg_replace($criteria, "", $string);
                $value = 0;
                switch ($function) {
                    case "Sum":
                        $value = $title ? self::getSumByType($title, $projectFees, $idController) : 0;
                        break;
                    case "Quantity":
                        $value = $title ? self::getQuantityByTitle($title, $projectFees) : $quantity;
                        break;
                    case "Basis":
                        $value = $title ? self::getBasisByTitle($title, $projectFees) : $fixedAmount;
                        break;
                    case "Fee":
                        $value = $title ? self::getTotalByTitle($title, $projectFees) : 0;
                        break;
                    case "Matrix":
                        $processMatrix = true;
                        $value = $title ? self::getMatrixByTitle($title, $projectData, $idController, $idProject, $quantity) : 0;
                        break;
                }
                $inputTitle = self::uniqueID();
                $projectData[$inputTitle] = $value;
                $updatedFunctions[$string] = $value;
                $updatedFormula = str_replace($string, $inputTitle, $updatedFormula);
                $updatedMatrixFormula = str_replace($string, $inputTitle, $updatedMatrixFormula);
            }
        }

        if($processMatrix) {
            $value = Parser::solveIF($updatedMatrixFormula, $projectData);
            $projectData['FormulaMatrix'] = FeeMatrices::solveMatrix($idFeeTemplate, $value);
            if ($getMatrix)
                return $projectData['FormulaMatrix'];
        }

        $total = Parser::solveIF($updatedFormula, $projectData);*/

        if($minimumValue > $total)
            $total = $minimumValue;

        if($getFunctionData)
            return $parsingFormula->getEnvironmentVariables('lastResults');

        return $total;
    }

    /**
     * helper function to return a unique string
     * @return mixed
     */
    private static function uniqueID()
    {
        //Final function can't use numbers in it's variables, so we replace them. Might be better to use a SHA with only numbers or something.
        $numberArray = ['/\s+/', '/[1]/','/[2]/','/[3]/','/[4]/','/[5]/','/[6]/','/[7]/','/[8]/','/[9]/','/[0]/'];
        $replaceArray = ['', 'a','b', 'c', 'd', 'e', 'f','g','h','i','j'];

        return preg_replace($numberArray, $replaceArray, uniqid());
    }

    /**
     * @param $text
     * @param $idController
     * @return array|null
     * @throws Exception
     */
    public static function findFeeTemplate($text, $idController, $idFeeSchedule, $projectData)
    {
        $text = DB::cleanse($text);

        $sql = "SELECT FeeTemplates.*, FeeTags.idFeeTag as feeSchedule
                FROM FeeTemplates
                LEFT JOIN FeeTemplateFeeTag
                    ON FeeTemplateFeeTag.idFeeTemplate = FeeTemplates.idFeeTemplate
                LEFT JOIN FeeTags
                    ON FeeTemplateFeeTag.idFeeTag = FeeTags.idFeeTag
                LEFT JOIN FeeTagCategories
                    ON FeeTagCategories.idFeeTagCategory = FeeTags.idFeeTagCategory
                WHERE
                    FeeTemplates.idController = {$idController}
                    AND FeeTagCategories.status_0 & 0b01
                    AND FeeTemplates.status_0 & 0b01
                    AND FeeTemplates.title LIKE '%{$text}%'
                GROUP BY FeeTemplates.idFeeTemplate
                ORDER BY FeeTemplates.title ASC";

        $feeTemplates = DB::sql($sql);
        $keydict = self::keydict()->add(IdInteger::build('feeSchedule', 'Fee Schedule Tag'));

        $result = [];
        foreach ($feeTemplates as &$feeTemplate) {
            $feeTemplate = $keydict->wakeup($feeTemplate)->toArray();
            $title = $feeTemplate['title'];

            if (!isset($result[$title]) || $feeTemplate['feeSchedule'] == $idFeeSchedule)
                $result[$title] = $feeTemplate;

            Fees::addVariables($result[$title], $feeTemplate['formula'], $feeTemplate['matrixFormula'], $projectData);
        }

        return $result;
    }

    /**
     * @param $idFeeTemplate
     * @param $idProject
     * @return bool
     * @throws Exception
     */
    public static function dependencyCheck($idFeeTemplate, $idProject)
    {
        $feeTemplate = self::sqlFetch($idFeeTemplate);
        $idController = $feeTemplate->idController->get();
        $formula = $feeTemplate->formula->get();
        $matrixFormula = $feeTemplate->matrixFormula->get();

        $criteria = ["/Sum\(\".*?\"\)|Sum\(\)/", "/Quantity\(\".*?\"\)|Quantity\(\)/",
            "/Fee\(\".*?\"\)|Fee\(\)/", "/Basis\(\".*?\"\)|Basis\(\)/" ];
        $stringCriteria = "(".implode($criteria, "|").")";
        preg_match_all($stringCriteria, $formula." ".$matrixFormula, $matches);
        $criteria = ["/Sum\(\"/", "/Quantity\(\"/", "/Basis\(\"/", "/Fee\(\"/","/\"\)/",
            "/Sum\(\)/", "/Quantity\(\)/", "/Basis\(\)/", "/Fee\(\)/"];
        $dependencies = [];
        $matches = array_unique($matches);

        foreach($matches as $string){
            $title = preg_replace($criteria, "", $string);
            if($title)
                $dependencies[$title] = $title;
        }
        $sql = "SELECT DISTINCT FeeTemplates.title
                FROM FeeTemplates
                LEFT JOIN Fees
                    ON Fees.idFeeTemplate = FeeTemplates.idFeeTemplate
                WHERE
                  FeeTemplates.idController = {$idController}
                  AND Fees.idProject = {$idProject}
                GROUP BY FeeTemplates.title
                ORDER BY FeeTemplates.title ASC";


        //So given a set of Dependency Titles, determine if everything is ok. so, a call to get all fee templates that have fees in this project, and then check all their titles.

        $feeTemplates = DB::sql($sql);
        foreach($feeTemplates as $feeTemplate){
            if(isset($dependencies[$feeTemplate['title']]))
                unset($dependencies[$feeTemplate['title']]);
        }

        if($dependencies)
            throw new Exception('Dependency Fee Template '.$feeTemplate['title'].' was not found. Please assure there exists a Fee using this template within the Project before creating this Fee.');

        return true;
    }

    /**
     * @param $title
     * @param $projectFees
     * @return mixed
     * @throws Exception
     */
    public static function getBasisByTitle($title, $projectFees)
    {
        foreach($projectFees as $projectFee){
            if($projectFee['feeTemplate']['title'] === $title)
                return $projectFee['feeTemplate']['fixedAmount'];
        }
        throw new Exception('Dependency Fee Template '.$title.' was not found. Please assure there exists a Fee using this template within the Project before creating this Fee.');
    }

    /**
     * @param $title
     * @param $projectFees
     * @return mixed
     * @throws Exception
     */
    public static function getQuantityByTitle($title, $projectFees)
    {
        foreach($projectFees as $projectFee){
            if($projectFee['feeTemplate']['title'] === $title)
                return $projectFee['quantity'];
        }
        throw new Exception('Dependency Fee Template '.$title.' was not found. Please assure there exists a Fee using this template within the Project before creating this Fee.');
    }

    /**
     * @param $title
     * @param $projectFees
     * @return mixed
     * @throws Exception
     */
    public static function getTotalByTitle($title, $projectFees)
    {
        foreach($projectFees as $projectFee){
            if($projectFee['feeTemplate']['title'] === $title)
                return $projectFee['total'];
        }
        throw new Exception('Dependency Fee Template '.$title.' was not found. Please assure there exists a Fee using this template within the Project before creating this Fee.');
    }

    /**
     * @param $title
     * @param $projectFees
     * @param $idController
     * @return mixed
     * @throws Exception
     */
    public static function getSumByType($title, $projectFees, $idController)
    {

        $feeType = FeeTypes::getFeeTypeByTitle($idController, $title);

        $sum = 0.0;
        foreach($projectFees as $projectFee){
            if($projectFee['feeTemplate']['idFeeType'] === $feeType['idFeeType'])
                $sum += $projectFee['total'];
        }
        return $sum;
    }

    /**
     * @param $title
     * @param $projectData
     * @param $idController
     * @param $idProject
     * @param $quantity
     * @param null $optionalValue
     * @return float|int|mixed
     */
    public static function getMatrixByTitle($title, $projectData, $idController, $idProject, $quantity, $optionalValue = NULL)
    {
        //TODO make single quotes work as well for formulas. Also add optional input calue thing
        $feeTemplate = Fee::getProjectFeeTemplateStatic($title, $idController, $projectData['idFeeTag']);
        if(!$feeTemplate)
            return 0;
        $feeTemplate['idProject']= $idProject;
        $feeTemplate['quantity']= $quantity;
        $feeTemplate['feeUnitPrice'] = 0;
        $feeTemplate['overrides']['unitPriceOverride'] = false;
        if($optionalValue !== NULL)
            $feeTemplate['matrixFormula'] = $optionalValue;
        $feeTemplate['formula'] = "Matrix()";

        return self::processFee($feeTemplate, $projectData, 0, 0, false, 0, 0, true);
    }

    /**
     * @param $idFeeTemplate
     * @param $idController
     * @param $title
     * @param $idFeeSchedule
     * @throws Exception
     * @throws \eprocess360\v3core\Keydict\Exception\InvalidValueException
     * @throws \eprocess360\v3core\Keydict\Exception\KeydictException
     */
    public static function checkOneActive($idFeeTemplate, $idController, $title, $idFeeSchedule)
    {
        $title = DB::cleanse($title);
        $idFeeSchedule = (int)DB::cleanse($idFeeSchedule);

        $sql = "SELECT FeeTemplates.*, FeeTags.idFeeTag as feeSchedule
                FROM FeeTemplates
                LEFT JOIN FeeTemplateFeeTag
                    ON FeeTemplateFeeTag.idFeeTemplate = FeeTemplates.idFeeTemplate
                LEFT JOIN FeeTags
                    ON FeeTemplateFeeTag.idFeeTag = FeeTags.idFeeTag
                LEFT JOIN FeeTagCategories
                    ON FeeTagCategories.idFeeTagCategory = FeeTags.idFeeTagCategory
                WHERE
                  FeeTemplates.idController = {$idController}
                  AND FeeTemplates.title = '{$title}'
                  AND NOT FeeTemplates.idFeeTemplate = {$idFeeTemplate}
                  AND FeeTemplates.status_0 & 0b01
                  AND FeeTagCategories.status_0 & 0b01
                GROUP BY FeeTemplates.idFeeTemplate
                ORDER BY FeeTemplates.title ASC";

        $feeTemplates = DB::sql($sql);

        $keydict = self::keydict();
        foreach ($feeTemplates as &$feeTemplate) {
            /** @var Table $feeTemplate */
            $feeSchedule = (int)$feeTemplate['feeSchedule'];
            $feeTemplate = $keydict->wakeup($feeTemplate);
            if($feeSchedule === $idFeeSchedule){
                $feeTemplate->status->isActive->set(false);
                $feeTemplate->update();
            }
        }
    }
}