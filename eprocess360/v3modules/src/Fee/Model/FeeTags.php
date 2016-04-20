<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 2/1/16
 * Time: 9:11 AM
 */

namespace eprocess360\v3modules\Fee\Model;


use eprocess360\v3core\DB;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;
use Exception;

/**
 * Class FeeTags
 * @package eprocess360\v3modules\Fee\Model
 */
class FeeTags extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idFeeTag', 'Fee Tag ID'),
            IdInteger::build('idController', 'Review Controller ID'),
            FixedString128::build('feeTagValue', 'Fee Tag Value')->setRequired(),
            IdInteger::build('idFeeTagCategory', 'Fee Tag Category ID')->setRequired()->joinsOn(FeeTagCategories::model())
        )->setName('FeeTags')->setLabel('FeeTags');
    }

    /**
     * @param int $idController
     * @param string $feeTagValue
     * @param int $idFeeTagCategory
     * @return FeeTags
     */
    public static function make($idController = 0, $feeTagValue = '', $idFeeTagCategory = 0)
    {

        $rowData = [
            'idController' => $idController,
            'feeTagValue' => $feeTagValue,
            'idFeeTagCategory' => $idFeeTagCategory];

        return self::FeeTagConstruct($rowData);
    }


    /**
     * @param array $rowData
     * @return FeeTags
     */
    public static function FeeTagConstruct($rowData = [])
    {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);
        return $instance;
    }


    /**
     * @param $idController
     * @param $feeTagValue
     * @param $idFeeTagCategory
     * @return array
     */
    public static function create($idController, $feeTagValue, $idFeeTagCategory)
    {
        $f = static::make($idController, $feeTagValue, $idFeeTagCategory);

        $f->insert();

        $result = $f->data->toArray();

        return self::getFeeTag($result['idFeeTag']);
    }

    /**
     * @param $idController
     * @param bool|false $notIsScheduled
     * @param bool|false $isScheduled
     * @param bool|false $multiView
     * @return array|null
     * @throws Exception
     */
    public static function getFeeTags($idController, $notIsScheduled = false, $isScheduled = false, $multiView = false)
    {
        $sql = "SELECT *
                FROM FeeTags
                LEFT JOIN FeeTagCategories
                  ON FeeTagCategories.idFeeTagCategory = FeeTags.idFeeTagCategory
                WHERE
                  FeeTags.idController = {$idController}";

        if($multiView){
            $keydict = self::keydict()->add(FixedString128::build('title', 'Categories'));
            $select = "*, FeeTagCategories.title";
            $where = "FeeTags.idController = {$idController}";
            $join  = "LEFT JOIN FeeTagCategories
                  ON FeeTagCategories.idFeeTagCategory = FeeTags.idFeeTagCategory";
            $result = ['keydict'=>$keydict, 'select'=>$select,'join'=>$join, 'where'=>$where];
            return $result;
        }

        if($notIsScheduled && !$isScheduled)
            $sql = "SELECT *
                FROM FeeTags
                LEFT JOIN FeeTagCategories
                  ON FeeTagCategories.idFeeTagCategory = FeeTags.idFeeTagCategory
                WHERE
                  FeeTags.idController = {$idController}
                  AND NOT FeeTagCategories.status_0 & 0b01";
        else if(!$notIsScheduled && $isScheduled)
            $sql = "SELECT *
                FROM FeeTags
                LEFT JOIN FeeTagCategories
                  ON FeeTagCategories.idFeeTagCategory = FeeTags.idFeeTagCategory
                WHERE
                  FeeTags.idController = {$idController}
                  AND FeeTagCategories.status_0 & 0b01";

        $keydict = FeeTagCategories::join(self::keydict());

        $tags = DB::sql($sql);
        foreach ($tags as &$tag) {
            $tag = $keydict->wakeup($tag)->toArray();
        }

        return $tags;
    }

    /**
     * @param $idFeeTag
     * @return array
     * @throws Exception
     */
    public static function getFeeTag($idFeeTag)
    {
        $sql = "SELECT *
                FROM FeeTags
                LEFT JOIN FeeTagCategories
                  ON FeeTagCategories.idFeeTagCategory = FeeTags.idFeeTagCategory
                WHERE
                  FeeTags.idFeeTag = {$idFeeTag}";

        $keydict = FeeTagCategories::join(self::keydict());

        $tags = DB::sql($sql);
        foreach ($tags as &$tag) {
            $tag = $keydict->wakeup($tag)->toArray();
            return $tag;
        }
        throw new Exception("Fee Tag with id of {$idFeeTag} was not found");
    }

    /**
     * @param $idFeeTag
     * @return bool
     * @throws \Exception
     */
    public static function deleteFeeTag($idFeeTag)
    {
        $fee = self::sqlFetch($idFeeTag);

        self::deleteById($fee->idFeeTag->get());

        return true;
    }


    /**
     * @param $idFeeTag
     * @param $feeTagValue
     * @return array
     * @throws \Exception
     * @throws \eprocess360\v3core\Keydict\Exception\InvalidValueException
     * @throws \eprocess360\v3core\Keydict\Exception\KeydictException
     */
    public static function editFeeTag($idFeeTag, $feeTagValue)
    {
        $tag = self::sqlFetch($idFeeTag);

        if($feeTagValue !== NULL)
            $tag->feeTagValue->set($feeTagValue);

        $tag->update();

        return self::getFeeTag($tag->idFeeTag->get());
    }

    /**
     * @param $idController
     * @param $title
     * @return array|null
     * @throws \Exception
     */
    public static function getFeeTagByTitle($idController, $title)
    {
        $sql = "SELECT *
                FROM FeeTags
                WHERE
                  FeeTags.idController = {$idController}
                  AND FeeTags.feeTagValue = {$title}";

        $tags = DB::sql($sql);

        foreach ($tags as $tag) {
            return self::keydict()->wakeup($tag)->toArray();
        }

        throw new Exception("Tag with Title".$title." on Controller ".$idController." was not found.");
    }

    /**
     * @param $text
     * @param $idController
     * @return array|null
     * @throws Exception
     */
    public static function findFeeTag($text, $idController)
    {
        $text = DB::cleanse($text);

        $sql = "SELECT FeeTags.*
                FROM FeeTags
                LEFT JOIN FeeTagCategories
                  ON FeeTagCategories.idFeeTagCategory = FeeTags.idFeeTagCategory
                WHERE FeeTags.idController = {$idController}
                    AND NOT FeeTagCategories.status_0 & 0b01
                    AND FeeTags.feeTagValue LIKE '%{$text}%'
                GROUP BY FeeTags.idFeeTag
                ORDER BY FeeTags.feeTagValue ASC";

        $feeTags = DB::sql($sql);
        $keydict = self::keydict();

        $result = [];
        foreach ($feeTags as &$feeTag) {
            $feeTag = $keydict->wakeup($feeTag)->toArray();
            $title = $feeTag['feeTagValue'];
            if (!isset($result[$title]))
                $result[] = $feeTag;
        }

        return $result;
    }
}