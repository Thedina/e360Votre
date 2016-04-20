<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 2/1/16
 * Time: 8:47 AM
 */

namespace eprocess360\v3modules\Fee\Model;

use eprocess360\v3controllers\IncrementGenerator\Model\IncrementGenerators;
use eprocess360\v3core\DB;
use eprocess360\v3core\Keydict\Entry\Bit;
use eprocess360\v3core\Keydict\Entry\MoneyVal;
use eprocess360\v3core\Keydict\Entry\Bits8;
use eprocess360\v3core\Keydict\Entry\Datetime;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\FloatNumber;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\Integer;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;
use Exception;

/**
 * Class Receipts
 * @package eprocess360\v3modules\Fee\Model
 */
class Receipts extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idReceipt', 'Receipt ID'),
            IdInteger::build('idProject', 'Project ID'),
            MoneyVal::build('paid', 'Paid Amount')->setRequired(),
            Integer::build('receiptNumber', 'Receipt Number'),
            FixedString128::build('userName', 'Payee User Name'),
            IdInteger::build('idUser', 'Payee User ID'),
            FixedString128::build('paymentMethod', 'Payment Method'),
            Datetime::build('datePaid', 'Date Paid'),
            FixedString128::build('receiptNotes', 'Additional Notes'),
            Bits8::make('status',
                Bit::build(0, 'isVoid',"Is Voided")
            )
        )->setName('Receipts')->setLabel('Receipts');
    }

    /**
     * @param int $idProject
     * @param int $paid
     * @param int $receiptNumber
     * @param string $userName
     * @param int $idUser
     * @param string $paymentMethod
     * @param string $receiptNotes
     * @param string $datePaid
     * @param array $status
     * @return Receipts
     */
    public static function make($idProject = 0, $paid = 0, $receiptNumber = 0, $userName = '', $idUser = 0,
                                $paymentMethod = '', $receiptNotes = '', $datePaid = '', $status = [0])
    {

        $rowData = [
            'idProject' => $idProject,
            'paid' => $paid,
            'receiptNumber' => $receiptNumber,
            'userName' => $userName,
            'idUser' => $idUser,
            'paymentMethod' => $paymentMethod,
            'receiptNotes' => $receiptNotes,
            'datePaid' => $datePaid,
            'status' => $status];

        return self::ReceiptConstruct($rowData);
    }


    /**
     * @param array $rowData
     * @return Receipts
     */
    public static function ReceiptConstruct($rowData = [])
    {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);
        return $instance;
    }


    /**
     * @param $idProject
     * @param $feePayments
     * @param $userName
     * @param $idUser
     * @param $paymentMethod
     * @param $receiptNotes
     * @param $datePaid
     * @param $isVoid
     * @return array
     */
    public static function create($idProject, $receiptNumber, $feePayments, $userName, $idUser, $paymentMethod, $receiptNotes, $datePaid, $isVoid)
    {
        $paid = 0.0;
        foreach($feePayments as $key=>$value){
            $paid += $value;
        }
        $status = ['isVoid' => $isVoid];

        if(!$receiptNumber)
            $receiptNumber = IncrementGenerators::incrementByKey('receipts');

        $f = static::make($idProject, $paid, $receiptNumber, $userName, $idUser, $paymentMethod, $receiptNotes, $datePaid, $status);

        $f->insert();

        $result = $f->data->toArray();

        $idReceipt = $result['idReceipt'];
        foreach($feePayments as $key=>$value){
            //Get ALl Fees on this project,
            FeeReceipt::create($idReceipt,$key,$value, 0);
        }

        return $result;
    }


    /**
     * @param $idProject
     * @return array|null
     * @throws \Exception
     */
    public static function getReceipts($idProject)
    {
        $sql = "SELECT *
                FROM Receipts
                WHERE
                  Receipts.idProject = {$idProject}
                ORDER BY Receipts.datePaid ASC";

        $receipts = DB::sql($sql);

        foreach ($receipts as &$receipt) {
            $receipt = self::keydict()->wakeup($receipt)->toArray();
        }

        return $receipts;
    }

    /**
     * @param $idReceipt
     * @return array
     * @throws \Exception
     */
    public static function getReceipt($idReceipt)
    {
        return self::sqlFetch($idReceipt)->toArray();
    }


    /**
     * @param $idReceipt
     * @return bool
     * @throws \Exception
     */
    public static function deleteReceipt($idReceipt)
    {
        $reviewRule = self::sqlFetch($idReceipt);

        self::deleteById($reviewRule->idReceipt->get());

        return true;
    }


    /**
     * @param $idReceipt
     * @param $isVoid
     * @return array
     * @throws \Exception
     * @throws \eprocess360\v3core\Keydict\Exception\InvalidValueException
     * @throws \eprocess360\v3core\Keydict\Exception\KeydictException
     */
    public static function editReceipt($idReceipt, $isVoid)
    {
        $receipt = self::sqlFetch($idReceipt);

        if($isVoid !== NULL)
            $receipt->status->isVoid->set($isVoid);

        $receipt->update();

        return $receipt->toArray();
    }

    /**
     * @param $idProject
     * @param $feePayments
     * @return bool
     * @throws Exception
     */
    public static function depositPayment($idProject, $feePayments)
    {
        $sql = "SELECT Fees.idFee,
                  GROUP_CONCAT(CASE WHEN NOT Receipts.status_0 & 0b01 AND FeeReceipt.idFee = Fees.idFee THEN CONCAT(FeeReceipt.idReceipt,'#',FeeReceipt.paid) END SEPARATOR ', ') AS receipts,
                  GROUP_CONCAT(CASE WHEN FeeTypes.feeTypeFlags_0 & 0b0100 AND FeeReceipt.idDeposit AND NOT Receipts.status_0 & 0b01 THEN CONCAT(FeeReceipt.idReceipt,'#',FeeReceipt.paid) END SEPARATOR ', ') AS allocatedReceipts
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
                  AND FeeTypes.feeTypeFlags_0 & 0b0100
                GROUP BY Fees.idFee";

        $fees = DB::sql($sql);

        $allocatable = [];
        foreach ($fees as &$fee) {
            $allocatedReceipts = [];
            $receipts = [];
            $allocatedReceiptsExplode = explode(', ', $fee['allocatedReceipts']);
            $receiptsExplode = explode(', ', $fee['receipts']);

            foreach($allocatedReceiptsExplode as $allocatedReceipt) {
                if($allocatedReceipt) {
                    $parts = array_map('floatval', explode('#', $allocatedReceipt));
                    if (isset($allocatedReceipts[$parts[0]]))
                        $allocatedReceipts[$parts[0]] += $parts[1];
                    else
                        $allocatedReceipts[$parts[0]] = $parts[1];
                }
            }


            foreach($receiptsExplode as $receipt){
                if($receiptsExplode) {
                    $parts = array_map('floatval', explode('#', $receipt));
                    if (isset($receipts[$parts[0]]))
                        $receipts[$parts[0]] += $parts[1];
                    else
                        $receipts[$parts[0]] = $parts[1];
                }
            }

            foreach($receipts as $k=>$v){
                if(isset($allocatedReceipts[$k]))
                    $receipts[$k] = $receipts[$k] - $allocatedReceipts[$k];
            }

            $allocatable[(int)$fee['idFee']] = $receipts;
        }

        $totalAvailable = 0.0;
        foreach($allocatable as $fee)
            foreach($fee as $k=>$v)
                $totalAvailable += $v;

        $totalRequested = 0.0;
        foreach($feePayments as $key=>$value){
            $totalRequested += $value;
        }

        if($totalAvailable < $totalRequested)
            throw new Exception('Requested the amount of '.$totalRequested." while only ".$totalAvailable." is currently available.");

        foreach($feePayments as $key=>$value){
            $valueRemaining = $value;
            foreach($allocatable as $idFee => &$receipts) {
                foreach ($receipts as $idReceipt => &$availableValue) {
                    if ($valueRemaining <= 0)
                        break 2;
                    if ($availableValue > 0) {
                        if ($valueRemaining >= $availableValue)
                            $grantedValue = $availableValue;
                        else
                            $grantedValue = $valueRemaining;
                        FeeReceipt::create($idReceipt, $key, $grantedValue, $idFee);
                        $valueRemaining = $valueRemaining - $grantedValue;
                        $availableValue = $availableValue - $grantedValue;
                    }
                }
            }
        }

        return true;
    }
}