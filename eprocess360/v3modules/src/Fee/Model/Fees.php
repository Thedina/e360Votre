<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 2/1/16
 * Time: 8:46 AM
 */

namespace eprocess360\v3modules\Fee\Model;


use eprocess360\v3core\DB;
use eprocess360\v3core\Keydict\Entry\Bit;
use eprocess360\v3core\Keydict\Entry\MoneyVal;
use eprocess360\v3core\Keydict\Entry\Bits8;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\FloatNumber;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\MoneyValExtended;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\String;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;
use Exception;

/**
 * Class Fees
 * @package eprocess360\v3modules\Fee\Model
 */
class Fees extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idFee', 'Fee ID'),
            IdInteger::build('idFeeTemplate', 'Fee Template ID')->joinsOn(FeeTemplates::model()),
            IdInteger::build('idProject', 'Project ID'),
            MoneyVal::build('total', 'Total Amount'),
            MoneyVal::build('feeUnitPrice', 'Unit Price ID'),
            MoneyVal::build('quantity', 'Unit Quantity'),
            FixedString128::build('notes', 'Additional Notes'),
            Bits8::make('overrides',
                Bit::build(0, 'totalOverride',"Total Price Override"),
                Bit::build(1, 'unitPriceOverride',"Unit Price Override")
            )
        )->setName('Fees')->setLabel('Fees');
    }

    /**
     * @param int $idFeeTemplate
     * @param int $idProject
     * @param int $total
     * @param int $feeUnitPrice
     * @param int $quantity
     * @param string $notes
     * @param array $overrides
     * @return Fees
     */
    public static function make($idFeeTemplate = 0, $idProject = 0, $total = 0,
                                $feeUnitPrice = 0, $quantity = 0, $notes = '', $overrides = [0])
    {

        $rowData = [
            'idFeeTemplate' => $idFeeTemplate,
            'idProject' => $idProject,
            'total' => $total,
            'feeUnitPrice' => $feeUnitPrice,
            'quantity' => $quantity,
            'notes' => $notes,
            'overrides' => $overrides];

        return self::FeeConstruct($rowData);
    }


    /**
     * @param array $rowData
     * @return Fees
     */
    public static function FeeConstruct($rowData = [])
    {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);
        return $instance;
    }


    /**
     * @param $idFeeTemplate
     * @param $idProject
     * @param $total
     * @param $feeUnitPrice
     * @param $quantity
     * @param $notes
     * @param $totalOverride
     * @param $unitPriceOverride
     * @param $projectData
     * @return array
     * @throws Exception
     */
    public static function create($idFeeTemplate, $idProject, $total, $feeUnitPrice, $quantity, $notes, $totalOverride, $unitPriceOverride, $projectData)
    {
        FeeTemplates::dependencyCheck($idFeeTemplate, $idProject);

        if(!$totalOverride){
            $total = FeeTemplates::processFee(NULL, $projectData, $idFeeTemplate, $feeUnitPrice, $unitPriceOverride, $quantity, $idProject);
        }

        $overrides = ['totalOverride' => $totalOverride,
                        'unitPriceOverride' => $unitPriceOverride];

        $f = static::make($idFeeTemplate, $idProject, $total, $feeUnitPrice, $quantity, $notes, $overrides);

        $f->insert();

        $result = $f->data->toArray();

        $feeTemplate = FeeTemplates::sqlFetch($idFeeTemplate);
        $result['feeTemplate'] = $feeTemplate->toArray();
        $formula = $feeTemplate->formula->get();
        $matrixFormula = $feeTemplate->matrixFormula->get();
        self::addVariables($result, $formula, $matrixFormula, $projectData);
        $result['balance'] = (float)$total;

        return $result;
    }

    /**
     * @param $idProject
     * @param $projectData
     * @param bool|false $addFunctions
     * @return array|null
     * @throws Exception
     */
    public static function getFees($idProject, $projectData, $addFunctions = false, $onlyDeposit = false, $onlyPayable = false, $onlyDepositsOrNotPayable = false)
    {
        $onlyDeposit = $onlyDeposit?"AND FeeTypes.feeTypeFlags_0 & 0b0100":"";

        $onlyPayable = $onlyPayable?"AND FeeTypes.feeTypeFlags_0 & 0b01":"";

        if($onlyDepositsOrNotPayable){
            $onlyDeposit = "";
            $onlyPayable = "AND (NOT FeeTypes.feeTypeFlags_0 & 0b01 OR FeeTypes.feeTypeFlags_0 & 0b0100)";
        }

        $sql = "SELECT Fees.*, FeeTemplates.*, SUM(CASE WHEN NOT Receipts.status_0 & 0b01 AND FeeReceipt.idFee = Fees.idFee THEN FeeReceipt.paid ELSE 0 END) as paid,
                  SUM(CASE WHEN FeeTypes.feeTypeFlags_0 & 0b0100 AND FeeReceipt.idDeposit AND NOT Receipts.status_0 & 0b01 THEN FeeReceipt.paid ELSE 0 END) as depositAllocated,
                  SUM(CASE WHEN FeeTypes.feeTypeFlags_0 & 0b0100 THEN 1 ELSE 0 END) as isDeposit
                FROM Fees
                  INNER JOIN FeeTemplates
                    ON FeeTemplates.idFeeTemplate = Fees.idFeeTemplate
                  INNER JOIN FeeTypes
                    ON FeeTypes.idFeeType = FeeTemplates.idFeeType
                  LEFT JOIN FeeReceipt
                    ON FeeReceipt.idFee = Fees.idFee OR FeeReceipt.idDeposit = Fees.idFee
                  LEFT JOIN Receipts
                    ON FeeReceipt.idReceipt = Receipts.idReceipt
                WHERE
                  Fees.idProject = {$idProject}
                  {$onlyPayable}
                  {$onlyDeposit}
                GROUP BY Fees.idFee";

        $fees = DB::sql($sql);
        $templateKeydict = FeeTemplates::keydict();
        $combinedTemplate = FeeTemplates::join(self::keydict());
        $feeKeydict = self::keydict()->add(FloatNumber::build('paid', 'Paid Amount'))->add(FloatNumber::build('allocatable', 'Allocatable Deposit Amount'))
            ->add(FloatNumber::build('depositAllocated', 'Deposit Allocated Amount'));

        foreach ($fees as &$fee) {
            $feeArray = $feeKeydict->wakeup($fee)->toArray();
            if((int)$fee['isDeposit'] && $feeArray['total'] == $feeArray['paid'] && $feeArray['total'])
                $feeArray['allocatable'] = $feeArray['paid'] - $feeArray['depositAllocated'];
            unset($feeArray['depositAllocated']);
            $feeArray['balance'] = $feeArray['total'] - $feeArray['paid'];
            $feeArray['feeTemplate'] = $templateKeydict->wakeup($fee)->toArray();
            if($addFunctions)
                $feeArray['functions'] = FeeTemplates::processFee($combinedTemplate->wakeup($fee)->toArray(), $projectData, 0, 0, false, 0, 0, false, true);
            self::addVariables($feeArray, $feeArray['feeTemplate']['formula'], $feeArray['feeTemplate']['matrixFormula'],$projectData);
            $fee = $feeArray;
        }

        return $fees;
    }

    /**
     * @param $idFee
     * @return $this
     * @throws \Exception
     */
    public static function getFee($idFee, $projectData)
    {
        $sql = "SELECT Fees.*, FeeTemplates.*, SUM(CASE WHEN NOT Receipts.status_0 & 0b01 AND FeeReceipt.idFee = Fees.idFee THEN FeeReceipt.paid ELSE 0 END)  as paid,
                      SUM(CASE WHEN FeeTypes.feeTypeFlags_0 & 0b0100 AND FeeReceipt.idDeposit AND NOT Receipts.status_0 & 0b01 THEN FeeReceipt.paid ELSE 0 END) as depositAllocated,
                      SUM(CASE WHEN FeeTypes.feeTypeFlags_0 & 0b0100 THEN 1 ELSE 0 END) as isDeposit
                FROM Fees
                  INNER JOIN FeeTemplates
                    ON FeeTemplates.idFeeTemplate = Fees.idFeeTemplate
                  INNER JOIN FeeTypes
                    ON FeeTypes.idFeeType = FeeTemplates.idFeeType
                  LEFT JOIN FeeReceipt
                    ON FeeReceipt.idFee = Fees.idFee OR FeeReceipt.idDeposit = Fees.idFee
                  LEFT JOIN Receipts
                    ON FeeReceipt.idReceipt = Receipts.idReceipt
                WHERE
                  Fees.idFee = {$idFee}
                GROUP BY Fees.idFee";

        $fees = DB::sql($sql);
        $feeKeydict = self::keydict()->add(FloatNumber::build('paid', 'Paid Amount'))->add(FloatNumber::build('allocatable', 'Allocatable Deposit Amount'))
            ->add(FloatNumber::build('depositAllocated', 'Deposit Allocated Amount'));
        $templateKeydict = FeeTemplates::keydict();
        $combinedTemplate = FeeTemplates::join(self::keydict());

        foreach($fees as $fee) {
            $result = $feeKeydict->wakeup($fee)->toArray();
            if((int)$fee['isDeposit'] && $result['total'] == $result['paid'] && $result['total'])
                $result['allocatable'] = $result['paid'] - $result['depositAllocated'];
            unset($result['depositAllocated']);
            $template = $templateKeydict->wakeup($fee)->toArray();
            $result['balance'] = $result['total'] - $result['paid'];
            $result['feeTemplate'] = $template;
            $feeArray['functions'] = FeeTemplates::processFee($combinedTemplate->wakeup($fee)->toArray(), $projectData, 0, 0, false, 0, 0, false, true);
            self::addVariables($result, $result['feeTemplate']['formula'], $result['feeTemplate']['matrixFormula'],$projectData);
            return $result;
        }
        throw new Exception('Fee with the ID of '.$idFee.' was not found.');
    }

    /**
     * @param $idFee
     * @return bool
     * @throws \Exception
     */
    public static function deleteFee($idFee)
    {
        $fee = self::sqlFetch($idFee);

        self::deleteById($fee->idFee->get());

        return true;
    }


    /**
     * @param $idFee
     * @param $idFeeTemplate
     * @param $total
     * @param $feeUnitPrice
     * @param $quantity
     * @param $notes
     * @param $totalOverride
     * @param $unitPriceOverride
     * @param $projectData
     * @return array
     * @throws Exception
     * @throws \eprocess360\v3core\Keydict\Exception\InvalidValueException
     * @throws \eprocess360\v3core\Keydict\Exception\KeydictException
     */
    public static function editFee($idFee, $idFeeTemplate, $total, $feeUnitPrice, $quantity, $notes, $totalOverride, $unitPriceOverride, $projectData)
    {
        $fee = self::sqlFetch($idFee);

        if($idFeeTemplate !== NULL)
            $fee->idFeeTemplate->set($idFeeTemplate);
        if($quantity !== NULL)
            $fee->quantity->set($quantity);
        if($notes !== NULL)
            $fee->notes->set($notes);
        if($totalOverride !== NULL)
            $fee->overrides->totalOverride->set($totalOverride);
        if($unitPriceOverride !== NULL)
            $fee->overrides->unitPriceOverride->set($unitPriceOverride);
        if($fee->overrides->unitPriceOverride->get())
            if($feeUnitPrice !== NULL)
                $fee->feeUnitPrice->set($feeUnitPrice);
        if($fee->overrides->totalOverride->get()) {
            if ($total !== NULL)
                $fee->total->set($total);
        }
        else
            $fee->total->set(FeeTemplates::processFee(NULL, $projectData, $fee->idFeeTemplate->get(), $fee->feeUnitPrice->get(), $fee->overrides->unitPriceOverride->get(), $fee->quantity->get(), $fee->idProject->get()));

        FeeTemplates::dependencyCheck($fee->idFeeTemplate->get(), $fee->idProject->get());

        $fee->update();

        return Fees::getFee($idFee, $projectData);
    }

    /**
     * @param $fee
     * @param $formula
     * @param $matrixFormula
     * @param $projectData
     */
    public static function addVariables(&$fee, $formula, $matrixFormula, $projectData)
    {
        $rawVariables =  array_unique(preg_split("/[^a-z]/i", $formula." ".$matrixFormula, NULL, PREG_SPLIT_NO_EMPTY));
        $variables = [];
        foreach($rawVariables as $rawVariable)
            if(isset($projectData[$rawVariable]))
                $variables[$rawVariable] = $projectData[$rawVariable];
        $fee['variables'] = $variables;
    }

    /**
     * @param $idProject
     * @param $projectData
     * @throws Exception
     */
    public static function recalculateFees($idProject, $projectData)
    {
        $sql = "SELECT Fees.*, FeeTemplates.*
                FROM Fees
                  LEFT JOIN FeeTemplates
                    ON FeeTemplates.idFeeTemplate = Fees.idFeeTemplate
                  LEFT JOIN FeeReceipt
                    ON FeeReceipt.idFee = Fees.idFee
                  LEFT JOIN Receipts
                    ON FeeReceipt.idReceipt = Receipts.idReceipt AND NOT Receipts.status_0 & 0b1
                WHERE
                  Fees.idProject = {$idProject}
                GROUP BY Fees.idFee";

        $fees = DB::sql($sql);
        foreach($fees as $fee){
            /** @var Fees|Table $feeKeydict */
            $feeKeydict = self::keydict()->wakeup($fee);
            $feeData = FeeTemplates::join(self::keydict())->wakeup($fee)->toArray();
            if(!$feeKeydict->overrides->totalOverride->get()){
                $total = FeeTemplates::processFee($feeData, $projectData);
                $feeKeydict->total->set($total);
                $feeKeydict->update();
            }
        }
    }

    /**
     * @param $idProject
     * @param $projectData
     * @param bool|false $onlyDeposits
     * @return bool
     */
    public static function checkIsPaid($idProject, $projectData, $onlyDeposits = false)
    {
        $fees = self::getFees($idProject, $projectData, false, $onlyDeposits, true);
        foreach($fees as $fee)
            if($fee['balance'] > 0)
                return false;
        return true;
    }

    /**
     * @param $idProject
     * @throws Exception
     */
    public static function deleteFeesByProject($idProject)
    {
        $sql = "DELETE FROM Fees WHERE Fees.idProject = {$idProject}";
        DB::sql($sql);
    }
}