<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 2/16/16
 * Time: 10:38 AM
 */

namespace eprocess360\v3controllers\IncrementGenerator\Model;


use eprocess360\v3core\DB;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\Integer;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;
use Exception;

/**
 * Class IncrementGenerators
 * @package eprocess360\v3controllers\IncrementGenerator\Model
 */
class IncrementGenerators  extends Model
{
    /**
     * @return $this
     * @throws Exception
     * @throws \eprocess360\v3core\Keydict\Exception\KeydictException
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idIncrementGenerator', 'Increment Generator ID'),
            Integer::build('baseIncrement', 'Current Increment'),
            Integer::build('currentIncrement', 'Current Increment'),
            FixedString128::build('key', 'Increment Generator Key')->setRequired(),
            FixedString128::build('stringGenerator', 'sprintf String')->setRequired()
        )->setName('IncrementGenerators')->setLabel('IncrementGenerators');
    }

    /**
     * @param int $baseIncrement
     * @param int $currentIncrement
     * @param string $key
     * @param string $stringGenerator
     * @return IncrementGenerators
     */
    public static function make($baseIncrement = 0, $currentIncrement = 0, $key = '', $stringGenerator = '')
    {

        $rowData = [
            'baseIncrement' => $baseIncrement,
            'currentIncrement' => $currentIncrement,
            'key' => $key,
            'stringGenerator' => $stringGenerator];

        return self::IncrementGeneratorConstruct($rowData);
    }


    /**
     * @param array $rowData
     * @return IncrementGenerators
     */
    public static function IncrementGeneratorConstruct($rowData = [])
    {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);
        return $instance;
    }


    /**
     * @param $baseIncrement
     * @param $currentIncrement
     * @param $key
     * @param $stringGenerator
     * @return array
     */
    public static function create($baseIncrement, $currentIncrement, $key, $stringGenerator)
    {
        $f = static::make($baseIncrement, $currentIncrement, $key, $stringGenerator);

        $f->insert();

        $result = $f->data->toArray();

        return $result;
    }

    /**
     * @return array|null
     * @throws \Exception
     */
    public static function getIncrementGenerators()
    {
        $sql = "SELECT *
                FROM IncrementGenerators";

        $incrementGenerators = DB::sql($sql);

        foreach ($incrementGenerators as &$incrementGenerator) {
            $incrementGenerator = self::keydict()->wakeup($incrementGenerator)->toArray();
        }

        return $incrementGenerators;
    }

    /**
     * @param $idIncrementGenerator
     * @return array
     * @throws \Exception
     */
    public static function getIncrementGenerator($idIncrementGenerator)
    {
        return self::sqlFetch($idIncrementGenerator)->toArray();
    }

    /**
     * @param $idIncrementGenerator
     * @return bool
     * @throws \Exception
     */
    public static function deleteIncrementGenerator($idIncrementGenerator)
    {
        $incrementGenerator = self::sqlFetch($idIncrementGenerator);

        self::deleteById($incrementGenerator->idIncrementGenerator->get());

        return true;
    }


    /**
     * @param $idIncrementGenerator
     * @param $baseIncrement
     * @param $currentIncrement
     * @param $key
     * @param $stringGenerator
     * @return array
     * @throws Exception
     * @throws \eprocess360\v3core\Keydict\Exception\InvalidValueException
     * @throws \eprocess360\v3core\Keydict\Exception\KeydictException
     */
    public static function editIncrementGenerator($idIncrementGenerator, $baseIncrement, $currentIncrement, $key, $stringGenerator)
    {
        $incrementGenerator = self::sqlFetch($idIncrementGenerator);

        if($baseIncrement !== NULL)
            $incrementGenerator->baseIncrement->set($baseIncrement);
        if($currentIncrement !== NULL)
            $incrementGenerator->currentIncrement->set($currentIncrement);
        if($key !== NULL)
            $incrementGenerator->key->set($key);
        if($stringGenerator !== NULL)
            $incrementGenerator->stringGenerator->set($stringGenerator);

        $incrementGenerator->update();

        return $incrementGenerator->toArray();
    }

    /**
     * @param $key
     * @return bool
     * @throws \Exception
     */
    public static function incrementByKey($key)
    {
        $entry = FixedString128::build('key', 'Increment Generator Key');
        $key = $entry->set($key)->cleanSleep();

        $sql = "SELECT *
                FROM IncrementGenerators
                WHERE
                  IncrementGenerators.key = {$key}";

        $incrementGenerators = DB::sql($sql);

        if(!isset($incrementGenerators[0]))
            throw new Exception('Increment Generator with the key \''.$key.'.\' was not found.');

        /** @var Table|IncrementGenerators $incrementGenerator */
        $incrementGenerator = self::keydict()->wakeup($incrementGenerators[0]);

        $increment = $incrementGenerator->baseIncrement->get() + $incrementGenerator->currentIncrement->get();
        $incrementGenerator->currentIncrement->set($incrementGenerator->currentIncrement->get()+1);
        $incrementGenerator->update();

        $stringIncrement = sprintf($incrementGenerator->stringGenerator->get(), $increment);
        return $stringIncrement;
    }
}