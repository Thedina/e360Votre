<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 2/1/16
 * Time: 9:13 AM
 */

namespace eprocess360\v3modules\Fee\Model;


use eprocess360\v3core\DB;
use eprocess360\v3core\Keydict\Entry\Bit;
use eprocess360\v3core\Keydict\Entry\Bits8;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;
use Exception;

/**
 * Class FeeTypes
 * @package eprocess360\v3modules\Fee\Model
 */
class FeeTypes extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idFeeType', 'Fee Type ID'),
            FixedString128::build('feeTypeTitle', 'Title')->setRequired(),
            IdInteger::build('idController', 'Review Controller ID'),
            Bits8::make('feeTypeFlags',
                Bit::build(0, 'isPayable',"Is Payable"),
                Bit::build(1, 'isOpen',"Is Open"),
                Bit::build(2, 'isDeposit',"Is Deposit")
            )
        )->setName('FeeTypes')->setLabel('FeeTypes');
    }

    /**
     * @param int $idController
     * @param string $feeTypeTitle
     * @param array $feeTypeFlags
     * @return FeeTypes
     */
    public static function make($idController = 0, $feeTypeTitle = '', $feeTypeFlags = [0])
    {
        $rowData = [
            'idController' => $idController,
            'feeTypeTitle' => $feeTypeTitle,
            'feeTypeFlags' => $feeTypeFlags];

        return self::FeeTypeConstruct($rowData);
    }


    /**
     * @param array $rowData
     * @return FeeTypes
     */
    public static function FeeTypeConstruct($rowData = [])
    {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);
        return $instance;
    }


    /**
     * @param $idController
     * @param $feeTypeTitle
     * @param $isPayable
     * @param $isOpen
     * @param $isDeposit
     * @return array
     */
    public static function create($idController, $feeTypeTitle, $isPayable, $isOpen, $isDeposit)
    {
        $feeTypeFlags = ['isPayable' => $isPayable,
                'isOpen' => $isOpen,
                'isDeposit' => $isDeposit];

        $f = static::make($idController, $feeTypeTitle, $feeTypeFlags);

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
    public static function getFeeTypes($idController, $multiView = false)
    {
        $sql = "SELECT *
                FROM FeeTypes
                WHERE
                  FeeTypes.idController = {$idController}
                  ORDER BY FeeTypes.feeTypeTitle ASC";

        $keydict = self::keydict();

        if($multiView){
            $select = "*";
            $where = "FeeTypes.idController = {$idController}";
            $result = ['keydict'=>$keydict, 'select'=>$select,'join'=>NULL, 'where'=>$where];
            return $result;
        }

        $feeTypes = DB::sql($sql);

        foreach ($feeTypes as &$feeType) {
            $feeType = $keydict->wakeup($feeType)->toArray();
        }

        return $feeTypes;
    }

    /**
     * @param $idFeeType
     * @return array
     * @throws \Exception
     */
    public static function getFeeType($idFeeType)
    {
        return self::sqlFetch($idFeeType)->toArray();
    }

    /**
     * @param $idController
     * @param $feeTypeTitle
     * @return array
     * @throws \Exception
     */
    public static function getFeeTypeByTitle($idController, $feeTypeTitle)
    {
        $sql = "SELECT *
                FROM FeeTypes
                WHERE
                  FeeTypes.idController = {$idController}
                  AND FeeTypes.feeTypeTitle = '{$feeTypeTitle}'
                  ORDER BY FeeTypes.feeTypeTitle ASC";

        $feeTypes = DB::sql($sql);

        foreach ($feeTypes as &$feeType) {
            $feeType = self::keydict()->wakeup($feeType)->toArray();
            return $feeType;
        }

        throw new Exception("FeeType with the title ".$feeTypeTitle." was not found");
    }

    /**
     * @param $idFeeType
     * @return bool
     * @throws \Exception
     */
    public static function deleteFeeType($idFeeType)
    {
        $feeType = self::sqlFetch($idFeeType);

        self::deleteById($feeType->idFeeType->get());

        return true;
    }


    /**
     * @param $idFeeType
     * @param $feeTypeTitle
     * @param $isPayable
     * @param $isOpen
     * @param $isDeposit
     * @return array
     * @throws \Exception
     * @throws \eprocess360\v3core\Keydict\Exception\InvalidValueException
     * @throws \eprocess360\v3core\Keydict\Exception\KeydictException
     */
    public static function editFeeType($idFeeType, $feeTypeTitle, $isPayable, $isOpen, $isDeposit)
    {
        $feeType = self::sqlFetch($idFeeType);

        if($feeTypeTitle !== NULL)
            $feeType->feeTypeTitle->set($feeTypeTitle);
        if($isPayable !== NULL)
            $feeType->feeTypeFlags->isPayable->set($isPayable);
        if($isOpen !== NULL)
            $feeType->feeTypeFlags->isOpen->set($isOpen);
        if($isDeposit !== NULL)
            $feeType->feeTypeFlags->isDeposit->set($isDeposit);

        $feeType->update();

        return $feeType->toArray();
    }
}