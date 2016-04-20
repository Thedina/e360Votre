<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 3/2/16
 * Time: 3:13 PM
 */
namespace eprocess360\v3modules\Inspection\Model;
use eprocess360\v3core\Keydict\Entry\Bit;
use eprocess360\v3core\Keydict\Entry\Bits8;
use eprocess360\v3core\Keydict\Entry\Datetime;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;
/**
 * Class Inspections
 * @package eprocess360\v3modules\Inspection\Model
 */
class Inspections extends Model
{
    /**
     * @return $this
     * @throws \Exception
     * @throws \eprocess360\v3core\Keydict\Exception\KeydictException
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idInspection', 'Inspection ID'),
            IdInteger::build('idController', 'Inspection Controller ID'),
            IdInteger::build('idProject', 'Project ID'),
            FixedString128::build('title', 'Inspection Title'),
            FixedString128::build('description', 'Inspection Description'),
            Datetime::build('dateCreated', 'Date Created'),
            Datetime::build('dateDue', 'Date Due'),
            Datetime::build('dateCompleted', 'Date Completed'),
            Bits8::make('status',
                Bit::build(0, 'isComplete', 'Complete Inspection'),
                Bit::build(1, 'isActive', 'Active Inspection')
            )
        )->setName('Inspections')->setLabel('Inspections');
    }
    /**
     * @param $idController
     * @return array
     */
    public static function getInspections($idController)
    {
        return [];
    }
    /**
     * @param $idController
     * @param $idProject
     * @param $title
     * @param $description
     * @return array
     */
    public static function createInspection($idController, $idProject, $title, $description)
    {
        return [];
    }
}