<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 2/1/16
 * Time: 8:48 AM
 */

namespace eprocess360\v3modules\Fee\Model;


use eprocess360\v3core\DB;
use eprocess360\v3core\Keydict\Entry\MoneyVal;
use eprocess360\v3core\Keydict\Entry\FloatNumber;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\Integer;
use eprocess360\v3core\Keydict\Entry\MoneyValExtended;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;

/**
 * Class FeeMatrices
 * @package eprocess360\v3modules\Fee\Model
 */
class FeeMatrices extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idFeeMatrix', 'Fee Matrix ID'),
            IdInteger::build('idFeeTemplate', 'Fee Template ID'),
            MoneyValExtended::build('startingValue', 'Starting Value'),
            Integer::build('order', 'Order Number'),
            MoneyValExtended::build('baseFee', 'Base Fee'),
            MoneyValExtended::build('increment', 'Increment value'),
            MoneyValExtended::build('incrementFee', 'Increment of Fee')
        )->setName('FeeMatrices')->setLabel('FeeMatrices');
    }

    /**
     * @param int $idFeeTemplate
     * @param int $startingValue
     * @param int $order
     * @param int $baseFee
     * @param int $increment
     * @param int $incrementFee
     * @return FeeMatrices
     */
    public static function make($idFeeTemplate = 0, $startingValue = 0, $order = 0,
                                $baseFee = 0, $increment = 0, $incrementFee = 0)
    {
        $rowData = [
            'idFeeTemplate' => $idFeeTemplate,
            'startingValue' => $startingValue,
            'order' => $order,
            'baseFee' => $baseFee,
            'increment' => $increment,
            'incrementFee' => $incrementFee];

        return self::FeeMatrixConstruct($rowData);
    }


    /**
     * @param array $rowData
     * @return FeeMatrices
     */
    public static function FeeMatrixConstruct($rowData = [])
    {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);
        return $instance;
    }


    /**
     * @param $idFeeTemplate
     * @param $startingValue
     * @param $order
     * @param $baseFee
     * @param $increment
     * @param $incrementFee
     * @return array
     */
    public static function create($idFeeTemplate, $startingValue, $order, $baseFee, $increment, $incrementFee)
    {
        $f = static::make($idFeeTemplate, $startingValue, $order, $baseFee, $increment, $incrementFee);

        $f->insert();

        $result = $f->data->toArray();

        return $result;
    }


    /**
     * @param $idFeeTemplate
     * @return array|null
     * @throws \Exception
     */
    public static function getFeeMatrices($idFeeTemplate)
    {
        $sql = "SELECT *
                FROM FeeMatrices
                WHERE
                  FeeMatrices.idFeeTemplate = {$idFeeTemplate}
                  ORDER BY FeeMatrices.order ASC";

        $matrices = DB::sql($sql);

        foreach ($matrices as &$matrix) {
            $matrix = self::keydict()->wakeup($matrix)->toArray();
        }

        return $matrices;
    }

    /**
     * @param $idFeeMatrix
     * @return array
     * @throws \Exception
     */
    public static function getFeeMatrix($idFeeMatrix)
    {
        return self::sqlFetch($idFeeMatrix)->toArray();
    }


    /**
     * @param $idFeeMatrix
     * @return bool
     * @throws \Exception
     */
    public static function deleteFeeMatrix($idFeeMatrix)
    {
        $matrix = self::sqlFetch($idFeeMatrix);

        self::deleteById($matrix->idFeeMatrix->get());

        return true;
    }


    /**
     * @param $idFeeMatrix
     * @param $startingValue
     * @param $order
     * @param $baseFee
     * @param $increment
     * @param $incrementFee
     * @return array
     * @throws \Exception
     * @throws \eprocess360\v3core\Keydict\Exception\InvalidValueException
     * @throws \eprocess360\v3core\Keydict\Exception\KeydictException
     */
    public static function editFeeMatrix($idFeeMatrix, $startingValue, $order, $baseFee, $increment, $incrementFee)
    {
        $feeMatrix = self::sqlFetch($idFeeMatrix);

        if($startingValue !== NULL)
            $feeMatrix->idReviewType->set($startingValue);
        if($order !== NULL)
            $feeMatrix->order->set($order);
        if($baseFee !== NULL)
            $feeMatrix->baseFee->set($baseFee);
        if($increment !== NULL)
            $feeMatrix->increment->set($increment);
        if($incrementFee !== NULL)
            $feeMatrix->incrementFee->set($incrementFee);

        $feeMatrix->update();

        return $feeMatrix->toArray();
    }

    /**
     * @param $idFeeTemplate
     * @param $value
     * @return float
     */
    public static function solveMatrix($idFeeTemplate, $value)
    {
        $feeMatrices = self::getFeeMatrices($idFeeTemplate);
        $total = 0.0;

        foreach($feeMatrices as $key => $matrix){
            if($matrix['startingValue'] <= $value && (!isset($feeMatrices[$key+1]) || $feeMatrices[$key+1]['startingValue'] > $value)){
                $total = $matrix['baseFee'] + ceil(((float)$value - $matrix['startingValue'])/$matrix['increment'])*$matrix['incrementFee'];
                break;
            }
        }

        return $total;

    }

    /**
     * @param $idFeeTemplate
     * @return bool
     * @throws \Exception
     */
    public static function deleteFeeMatrixByIdFeeTemplate($idFeeTemplate)
    {
        $sql = "DELETE
                FROM FeeMatrices
                WHERE
                  FeeMatrices.idFeeTemplate = {$idFeeTemplate}";

        //TODO Note that this function does not use deleteById, as such when ever a delete overhaul occurs, take not of this function.
        DB::sql($sql);

        return true;
    }

}