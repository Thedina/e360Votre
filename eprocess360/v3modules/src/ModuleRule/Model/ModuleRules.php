<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 2/2/16
 * Time: 2:45 PM
 */

namespace eprocess360\v3modules\ModuleRule\Model;


use eprocess360\v3core\DB;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\FixedString256;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\Integer;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;

/**
 * Class ModuleRules
 * @package eprocess360\v3modules\ModuleRule\Model
 */
class ModuleRules extends Model
{
    /**
     * @return $this
     * @throws \Exception
     * @throws \eprocess360\v3core\Keydict\Exception\KeydictException
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idModuleRule', 'ModuleRule ID'),
            IdInteger::build('idController', 'Controller ID'),
            IdInteger::build('idObjectAction', 'Object Action ID')->setRequired(),
            FixedString128::build('objectActionTitle', 'Object Action Title')->setRequired(),
            Integer::build('order', 'ModuleRule Order'),
            FixedString256::build('expression', 'ModuleRule Expression')->setRequired(),
            FixedString128::build('actionType', 'Action Type')->setRequired()
        )->setName('ModuleRules')->setLabel('ModuleRules');
    }

    /**
     * @param int $idController
     * @param int $idObjectAction
     * @param int $order
     * @param string $expression
     * @param string $actionType
     * @return ModuleRules
     */
    public static function make($idController = 0, $idObjectAction = 0, $objectActionTitle = 0, $order = 0, $expression = '', $actionType = '')
    {
        $rowData = [
            'idController' => $idController,
            'idObjectAction' => $idObjectAction,
            'objectActionTitle' => $objectActionTitle,
            'order' => $order,
            'expression' => $expression,
            'actionType' => $actionType];

        return self::ModuleRuleConstruct($rowData);
    }

    /**
     * @param array $rowData
     * @return ModuleRules
     */
    public static function ModuleRuleConstruct($rowData = [])
    {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);
        return $instance;
    }

    /**
     * @param $idController
     * @param $idObjectAction
     * @param $objectActionTitle
     * @param $expression
     * @param $actionType
     * @return array
     * @throws \Exception
     */
    public static function create($idController, $idObjectAction, $objectActionTitle, $expression, $actionType) {
        $sql = "SELECT COUNT(ModuleRules.idModuleRule) as rulesCount
                FROM ModuleRules
                WHERE
                  ModuleRules.idController = {$idController}";
        $moduleRulesCount = (int)DB::sql($sql)[0]['rulesCount'];
        $order = $moduleRulesCount + 1;
        $f = static::make($idController, $idObjectAction, $objectActionTitle, $order, $expression, $actionType);

        $f->insert();

        $result = $f->data->toArray();

        return $result;
    }

    /**
     * @param $idController
     * @return array|null
     * @throws \Exception
     */
    public static function getModuleRules($idController)
    {
        $sql = "SELECT *
                FROM ModuleRules
                WHERE
                  ModuleRules.idController = {$idController}
                  ORDER BY ModuleRules.order ASC";

        $rules = DB::sql($sql);

        foreach ($rules as &$rule) {
            $rule = self::keydict()->wakeup($rule)->toArray();
        }

        return $rules;
    }

    /**
     * @param $idModuleRule
     * @return array
     * @throws \Exception
     */
    public static function getModuleRule($idModuleRule)
    {
        return self::sqlFetch($idModuleRule)->toArray();
    }

    /**
     * @param $idModuleRule
     * @return bool
     * @throws \Exception
     */
    public static function deleteModuleRule($idModuleRule)
    {
        $rule = self::sqlFetch($idModuleRule);

        self::deleteById($rule->idModuleRule->get());

        return true;
    }

    /**
     * @param $idModuleRule
     * @param $idObjectAction
     * @param $objectActionTitle
     * @param $order
     * @param $expression
     * @param $actionType
     * @return array
     * @throws \Exception
     * @throws \eprocess360\v3core\Keydict\Exception\InvalidValueException
     * @throws \eprocess360\v3core\Keydict\Exception\KeydictException
     */
    public static function editModuleRule($idModuleRule, $idObjectAction, $objectActionTitle, $order, $expression, $actionType)
    {
        $rule = self::sqlFetch($idModuleRule);

        if($idObjectAction !== NULL)
            $rule->idObjectAction->set($idObjectAction);
        if($objectActionTitle !== NULL)
            $rule->objectActionTitle->set($objectActionTitle);
        if($order !== NULL)
            $rule->order->set($order);
        if($expression !== NULL)
            $rule->expression->set($expression);
        if($actionType !== NULL)
            $rule->actionType->set($actionType);

        $rule->update();

        return $rule->toArray();
    }
}