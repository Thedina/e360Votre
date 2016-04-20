<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 1/22/16
 * Time: 8:21 AM
 */

namespace eprocess360\v3modules\Review\Model;


use eprocess360\v3core\DB;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;

/**
 * Class ReviewTypes
 * @package eprocess360\v3modules\Review\Model
 */
class ReviewTypes extends Model
{
    /**
     * @return $this
     * @throws \Exception
     * @throws \eprocess360\v3core\Keydict\Exception\KeydictException
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idReviewType', 'Review Type ID'),
            IdInteger::build('idController', 'Review Controller ID'),
            IdInteger::build('idGroup', 'Group ID'),
            FixedString128::build('title', 'Review Type Title')->setRequired()
        )->setName('ReviewTypes')->setLabel('ReviewTypes');
    }

    /**
     * @param int $idController
     * @param int $idGroup
     * @param string $title
     * @return ReviewTypes
     */
    public static function make($idController = 0, $idGroup = 0, $title = '')
    {
        $rowData = [
            'idController' => $idController,
            'idGroup' => $idGroup,
            'title' => $title];

        return self::ReviewTypeConstruct($rowData);
    }

    /**
     * @param array $rowData
     * @return ReviewTypes
     */
    public static function ReviewTypeConstruct($rowData = [])
    {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);
        return $instance;
    }

    /**
     * @param $idController
     * @param $idGroup
     * @param $title
     * @return array
     */
    public static function create($idController, $idGroup, $title) {
        $f = static::make($idController, $idGroup, $title);

        $f->insert();

        $result = $f->data->toArray();

        return $result;
    }

    /**
     * @param $idController
     * @return mixed
     */
    public static function getReviewTypes($idController)
    {
        $sql = "SELECT ReviewTypes.*, Groups.title as groupTitle
                FROM ReviewTypes
                  LEFT JOIN Groups ON Groups.idGroup = ReviewTypes.idGroup
                WHERE
                  ReviewTypes.idController = {$idController}";

        $reviewTypes = DB::sql($sql);

        $keydict = Table::build(
            PrimaryKeyInt::build('idReviewType', 'Review Type ID'),
            IdInteger::build('idController', 'Review Controller ID'),
            IdInteger::build('idGroup', 'Group ID'),
            FixedString128::build('title', 'Review Type Title'),
            FixedString128::build('groupTitle', 'Group Title')
        );

        foreach ($reviewTypes as &$reviewType) {
            $reviewType = $keydict->wakeup($reviewType)->toArray();
        }

        return $reviewTypes;
    }

    /**
     * @param $idReviewType
     * @return bool
     * @throws \Exception
     */
    public static function deleteReviewType($idReviewType)
    {
        $reviewType = self::sqlFetch($idReviewType);

        self::deleteById($idReviewType);

        return true;
    }

    /**
     * @param $idReviewType
     * @param $idGroup
     * @param $title
     * @return array
     * @throws \Exception
     * @throws \eprocess360\v3core\Keydict\Exception\InvalidValueException
     * @throws \eprocess360\v3core\Keydict\Exception\KeydictException
     */
    public static function editReviewType($idReviewType, $idGroup, $title)
    {
        $reviewType = self::sqlFetch($idReviewType);

        if($idGroup !== NULL)
            $reviewType->idGroup->set((int)$idGroup);
        if($title !== NULL)
            $reviewType->title->set($title);

        $reviewType->update();
        //TODO Optimize This Function

        $sql = "SELECT ReviewTypes.*, Groups.title as groupTitle
                FROM ReviewTypes
                  LEFT JOIN Groups ON Groups.idGroup = ReviewTypes.idGroup
                WHERE
                  ReviewTypes.idReviewType = {$idReviewType}";

        $reviewType = DB::sql($sql)[0];

        $keydict = Table::build(
            PrimaryKeyInt::build('idReviewType', 'Review Type ID'),
            IdInteger::build('idController', 'Review Controller ID'),
            IdInteger::build('idGroup', 'Group ID'),
            FixedString128::build('title', 'Review Type Title'),
            FixedString128::build('groupTitle', 'Group Title')
        );

        $reviewType = $keydict->wakeup($reviewType)->toArray();

        return $reviewType;
    }
}