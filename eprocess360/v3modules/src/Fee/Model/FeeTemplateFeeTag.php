<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 2/2/16
 * Time: 10:12 AM
 */

namespace eprocess360\v3modules\Fee\Model;


use eprocess360\v3core\DB;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;

/**
 * Class FeeTemplateFeeTag
 * @package eprocess360\v3modules\Fee\Model
 */
class FeeTemplateFeeTag extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idFeeTemplateFeeTag', 'FeeTemplateFeeTag ID'),
            IdInteger::build('idFeeTemplate', 'Fee Template ID'),
            IdInteger::build('idFeeTag', 'Fee Tag ID')
        )->setName('FeeTemplateFeeTag')->setLabel('FeeTemplateFeeTag');
    }

    /**
     * @param int $idFeeTemplate
     * @param int $idFeeTag
     * @return FeeTags
     */
    public static function make($idFeeTemplate = 0, $idFeeTag = 0)
    {

        $rowData = [
            'idFeeTemplate' => $idFeeTemplate,
            'idFeeTag' => $idFeeTag];

        return self::FeeTemplateFeeTagConstruct($rowData);
    }


    /**
     * @param array $rowData
     * @return FeeTags
     */
    public static function FeeTemplateFeeTagConstruct($rowData = [])
    {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);
        return $instance;
    }


    /**
     * @param $idFeeTemplate
     * @param $idFeeTag
     * @return array
     */
    public static function create($idFeeTemplate, $idFeeTag)
    {
        $f = static::make($idFeeTemplate, $idFeeTag);

        $f->insert();

        $result = $f->data->toArray();

        return $result;
    }

    /**
     * @param $idFeeTemplate
     * @return mixed
     */
    public static function getFeeTemplateFeeTags($idFeeTemplate)
    {
        $sql = "SELECT FeeTags.*
                FROM FeeTemplateFeeTag
                  LEFT JOIN FeeTags
                    ON FeeTags.idFeeTag = FeeTemplateFeeTag.idFeeTag
                  LEFT JOIN FeeTagCategories
                    ON FeeTagCategories.idFeeTagCategory = FeeTags.idFeeTagCategory
                WHERE
                  FeeTemplateFeeTag.idFeeTemplate = {$idFeeTemplate}
                  AND NOT FeeTagCategories.status_0 & 0b01";

        $tags = DB::sql($sql);

        foreach ($tags as &$tag) {
            $tag = FeeTags::keydict()->wakeup($tag)->toArray();
        }

        return $tags;
    }

    /**
     * @param $idFeeTemplate
     * @param $idFeeTag
     * @return bool
     * @throws \Exception
     */
    public static function deleteFeeTemplateFeeTag($idFeeTemplate, $idFeeTag)
    {
        $sql = "SELECT *
                FROM FeeTemplateFeeTag
                WHERE
                  FeeTemplateFeeTag.idFeeTemplate = {$idFeeTemplate}
                  AND FeeTemplateFeeTag.idFeeTag = {$idFeeTag}";

        $tags = DB::sql($sql);


        foreach ($tags as $tag) {
            if(isset($tag['idFeeTemplateFeeTag']))
                self::deleteById($tag['idFeeTemplateFeeTag']);
        }

        return true;
    }

    /**
     * @param $idFeeTemplate
     * @return bool
     * @throws \Exception
     */
    public static function deleteFeeTemplateFeeTagByIdFeeTemplate($idFeeTemplate)
    {
        $sql = "DELETE
                FROM FeeTemplateFeeTag
                WHERE
                  FeeTemplateFeeTag.idFeeTemplate = {$idFeeTemplate}
                  AND idFeeTag IN
                  ( SELECT idFeeTag
                    FROM FeeTags
                    INNER JOIN FeeTagCategories
                      ON FeeTags.idFeeTagCategory = FeeTagCategories.idFeeTagCategory
                    WHERE NOT FeeTagCategories.status_0 & 0b1
                  )";

        //TODO Note that this function does not use deleteById, as such when ever a delete overhaul occurs, take not of this function.
        DB::sql($sql);

        return true;
    }

    /**
     * @param $idFeeTemplate
     * @param $idFeeSchedule
     * @return array
     * @throws \Exception
     */
    public static function switchFeeSchedule($idFeeTemplate, $idFeeSchedule)
    {
        $sql = "DELETE
                FROM FeeTemplateFeeTag
                WHERE
                  FeeTemplateFeeTag.idFeeTemplate = {$idFeeTemplate}
                  AND idFeeTag IN
                  ( SELECT idFeeTag
                    FROM FeeTags
                    INNER JOIN FeeTagCategories
                      ON FeeTags.idFeeTagCategory = FeeTagCategories.idFeeTagCategory
                    WHERE FeeTagCategories.status_0 & 0b1
                  )";

        //TODO Note that this function does not use deleteById, as such when ever a delete overhaul occurs, take not of this function.
        DB::sql($sql);

        return self::create($idFeeTemplate, $idFeeSchedule);
    }
}