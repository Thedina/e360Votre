<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 2/5/16
 * Time: 11:24 AM
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

/**
 * Class FeeTagCategories
 * @package eprocess360\v3modules\Fee\Model
 */
class FeeTagCategories extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idFeeTagCategory', 'Fee Tag Category ID'),
            FixedString128::build('title', 'Title')->setRequired(),
            IdInteger::build('idController', 'Fee Controller ID'),
            Bits8::make('status',//TODO Change Status to Flags, because isFeeSchedule is not a status
                Bit::build(0, 'isFeeSchedule',"Is Fee Schedule")
            )
        )->setName('FeeTagCategories')->setLabel('FeeTagCategories');
    }

    /**
     * @param int $idController
     * @param string $title
     * @param array $status
     * @return FeeTypes
     */
    public static function make($idController = 0, $title = '', $status = ['isFeeSchedule' => false])
    {
        $rowData = [
            'idController' => $idController,
            'title' => $title,
            'status' => $status];

        return self::FeeTagCategoryConstruct($rowData);
    }


    /**
     * @param array $rowData
     * @return FeeTypes
     */
    public static function FeeTagCategoryConstruct($rowData = [])
    {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);
        return $instance;
    }


    /**
     * @param $idController
     * @param $title
     * @param $isFeeSchedule
     * @return array
     */
    public static function create($idController, $title, $isFeeSchedule)
    {
        $status = ['isFeeSchedule' => $isFeeSchedule];

        $f = static::make($idController, $title, $status);

        $f->insert();

        $result = $f->data->toArray();

        return $result;
    }


    /**
     * @param $idController
     * @param bool|false $multiView
     * @return array|null
     * @throws \Exception
     */
    public static function getFeeTagCategories($idController, $noFeeSchedule = false, $multiView = false)
    {
        $noFeeScheduleString = $noFeeSchedule? "AND NOT FeeTagCategories.status_0 & 0b01" : "";
        $sql = "SELECT *
                FROM FeeTagCategories
                WHERE
                  FeeTagCategories.idController = {$idController}
                  {$noFeeScheduleString}
                  ORDER BY FeeTagCategories.title ASC";

        $keydict = self::keydict();

        if($multiView){
            $select = "*";
            $where = "FeeTagCategories.idController = {$idController}";
            $result = ['keydict'=>$keydict, 'select'=>$select,'join'=>NULL, 'where'=>$where];
            return $result;
        }

        $feeTagCategories = DB::sql($sql);

        foreach ($feeTagCategories as &$feeTagCategory) {
            $feeTagCategory = $keydict->wakeup($feeTagCategory)->toArray();
        }

        return $feeTagCategories;
    }

    /**
     * @param $idFeeTagCategory
     * @return array
     * @throws \Exception
     */
    public static function getFeeTagCategory($idFeeTagCategory)
    {
        return self::sqlFetch($idFeeTagCategory)->toArray();
    }


    /**
     * @param $idFeeTagCategory
     * @return bool
     * @throws \Exception
     */
    public static function deleteFeeTagCategory($idFeeTagCategory)
    {
        $feeTagCategory = self::sqlFetch($idFeeTagCategory);

        self::deleteById($feeTagCategory->idFeeTagCategory->get());

        return true;
    }


    /**
     * @param $idFeeTagCategory
     * @param $title
     * @param $isFeeSchedule
     * @return array
     * @throws \Exception
     * @throws \eprocess360\v3core\Keydict\Exception\InvalidValueException
     * @throws \eprocess360\v3core\Keydict\Exception\KeydictException
     */
    public static function editFeeTagCategory($idFeeTagCategory, $title, $isFeeSchedule)
    {
        $feeTagCategory = self::sqlFetch($idFeeTagCategory);

        if($title !== NULL)
            $feeTagCategory->title->set($title);
        if($isFeeSchedule !== NULL)
            $feeTagCategory->status->isFeeSchedule->set($isFeeSchedule);

        $feeTagCategory->update();

        return $feeTagCategory->toArray();
    }
}