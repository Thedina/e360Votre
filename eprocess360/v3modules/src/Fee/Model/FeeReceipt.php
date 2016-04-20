<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 2/2/16
 * Time: 12:18 PM
 */

namespace eprocess360\v3modules\Fee\Model;
use eprocess360\v3core\Keydict\Entry\MoneyVal;
use eprocess360\v3core\Keydict\Entry\FloatNumber;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;


/**
 * Class FeeReceipt
 * @package eprocess360\v3modules\Fee\Model
 */
class FeeReceipt extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idFeeReceipt', 'FeeReceipt ID'),
            IdInteger::build('idReceipt', 'Receipt ID'),
            IdInteger::build('idFee', 'Fee ID'),
            MoneyVal::build('paid', 'Amount Paid on this Fee for this Receipt'),
            IdInteger::build('idDeposit', 'Fee Deposit ID')
        )->setName('FeeReceipt')->setLabel('FeeReceipt');
    }

    /**
     * @param int $idReceipt
     * @param int $idFee
     * @param int $paid
     * @param int $idDeposit
     * @return FeeReceipt
     */
    public static function make($idReceipt = 0, $idFee = 0, $paid = 0, $idDeposit = 0)
    {

        $rowData = [
            'idReceipt' => $idReceipt,
            'idFee' => $idFee,
            'paid' => $paid,
            'idDeposit' => $idDeposit];

        return self::FeeReceiptConstruct($rowData);
    }


    /**
     * @param array $rowData
     * @return FeeReceipt
     */
    public static function FeeReceiptConstruct($rowData = [])
    {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);
        return $instance;
    }


    /**
     * @param $idReceipt
     * @param $idFee
     * @param $paid
     * @return array
     */
    public static function create($idReceipt, $idFee, $paid, $idDeposit = 0)
    {
        $f = static::make($idReceipt, $idFee, $paid, $idDeposit);

        $f->insert();

        $result = $f->data->toArray();

        return $result;
    }

    /**
     * @param $idFeeReceipt
     * @return bool
     * @throws \Exception
     */
    public static function deleteFeeTemplateFeeTag($idFeeReceipt)
    {
        $fee = self::sqlFetch($idFeeReceipt);

        self::deleteById($fee->idFeeReceipt->get());

        return true;
    }
}