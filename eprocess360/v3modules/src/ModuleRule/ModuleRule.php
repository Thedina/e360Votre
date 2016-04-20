<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 2/1/16
 * Time: 2:40 PM
 */

namespace eprocess360\v3modules\ModuleRule;

use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Module;
use eprocess360\v3core\Controller\Persistent;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\Trigger\InterfaceTriggers;
use eprocess360\v3core\Controller\Triggers;
use eprocess360\v3core\Controller\Warden\Privilege;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry;
use eprocess360\v3core\Logger;
use eprocess360\v3core\Request\Request;
use eprocess360\v3modules\ModuleRule\Model\ModuleRules;


/**
 * Class ModuleRule
 * @package eprocess360\v3modules\ModuleRule
 */
class ModuleRule extends Controller implements InterfaceTriggers
{
    use Router, Module, Persistent, Triggers;
    private $conditionOptions;
    private $keydictVariables;
    private $moduleRules;
    private $ruleControllerId;
    private $objectActions;


    /*********************************************   #ROUTING#  **********************************************/


    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     */
    public function routes()
    {
        $this->routes->map('GET', '', function () {
            $this->getModuleRulesAPI();
        });
        $this->routes->map('POST', '', function () {
            $this->createModuleRuleAPI();
        });
//        $this->routes->map('GET', '/[i:idModuleRule]', function ($idModuleRule) {
//            $this->getModuleRuleAPI($idModuleRule);
//        });
        $this->routes->map('PUT', '/[i:idModuleRule]', function ($idModuleRule) {
            $this->editModuleRuleAPI($idModuleRule);
        });
        $this->routes->map('DELETE', '/[i:idModuleRule]', function ($idModuleRule) {
            $this->deleteModuleRuleAPI($idModuleRule);
        });
    }


    /**
     * API Function to get ModuleRules
     * @Required_Privilege: Admin
     */
    public function getModuleRulesAPI()
    {
        $this->verifyPrivilege(Privilege::ADMIN);

        $data = $this->getModuleRules($this->getParent()->getId());

        $this->standardResponse($data);
    }

    /**
     * API Function to create a ModuleRule given idReviewType, conditions, actionTarget
     * @Required_Privilege: Admin
     */
    public function createModuleRuleAPI()
    {
        $this->verifyPrivilege(Privilege::ADMIN);

        $data = Request::get()->getRequestBody();
        $idObjectAction = isset($data['idObjectAction'])? $data['idObjectAction']:null;
        $objectActionTitle = isset($data['objectActionTitle'])? $data['objectActionTitle']:null;
        $conditions = isset($data['conditions'])? $data['conditions']:null;
        $actionType = isset($data['actionType'])? $data['actionType']:null;

        $expression = null;
        if(is_array($conditions)) {
            $expression = implode(', ', array_map(function ($singleExpression) {
                if($singleExpression['conjunction']==="N/A")
                    $singleExpression['conjunction'] = "";
                return implode(" ", $singleExpression);
            }, $conditions));
        }
        else
            $expression = $conditions;

        $data =  ModuleRules::create($this->getParent()->getId(), $idObjectAction, $objectActionTitle, $expression, $actionType);

        $this->standardResponse($data);
    }


//    /**
//     * API Function to get ModuleRules
//     * @param $idModuleRule
//     * @Required_Privilege: Admin
//     */
//    public function getModuleRuleAPI($idModuleRule)
//    {
//        $this->verifyPrivilege(Privilege::ADMIN);
//
//        $data = $this->getModuleRules($idModuleRule);
//
//        $this->standardResponse($data, 'moduleRule');
//    }


    /**
     * API Function to edit a ModuleRule. specifically: idReviewType, conditions, request
     * @param $idModuleRule
     * @Required_Privilege: Admin
     */
    public function editModuleRuleAPI($idModuleRule)
    {
        $this->verifyPrivilege(Privilege::ADMIN);

        $data = Request::get()->getRequestBody();

        $idObjectAction = isset($data['idObjectAction'])? $data['idObjectAction']:null;
        $objectActionTitle = isset($data['objectActionTitle'])? $data['objectActionTitle']:null;
        $conditions = isset($data['conditions'])? $data['conditions']:null;
        $actionType = isset($data['actionType'])? $data['actionType']:null;
        $order = isset($data['order'])? $data['order']:null;

        $expression = null;
        if(is_array($conditions)) {
            $expression = implode(',', array_map(function ($singleExpression) {
                if($singleExpression['conjunction']==="N/A")
                    $singleExpression['conjunction'] = "";
                return implode(" ", $singleExpression);
            }, $conditions));
        }
        else
            $expression = $conditions;

        $data = ModuleRules::editModuleRule($idModuleRule, $idObjectAction, $objectActionTitle, $order, $expression, $actionType);

        $this->standardResponse($data);
    }

    /**
     * API Function to delete a specified ModuleRule.
     * @param $idModuleRule
     * @Required_Privilege: Admin
     */
    public function deleteModuleRuleAPI($idModuleRule)
    {
        $this->verifyPrivilege(Privilege::ADMIN);

        $data = ModuleRules::deleteModuleRule($idModuleRule);

        $this->standardResponse($data);
    }

    /**********************************************   #HELPER#  **********************************************/


    /**
     * Helper function that sets the Response's template, data, response code, and errors.
     * @param array $data
     * @param int $responseCode
     * @param bool|false $error
     */
    private function standardResponse($data = [], $responseCode = 200, $error = false)
    {
        $responseData = [
            'error' => $error,
            'data' => $data
        ];

        $response = $this->getResponseHandler();
        $response->setTemplate('modulerules.settings.html.twig', 'server');
        $response->setTemplate('settings.modulerules.handlebars.html', 'client', $this);

        $response->extendResponseMeta('ModuleRule', ['conditionOptions'=>$this->conditionOptions]);
        $response->extendResponseMeta('ModuleRule', ['objectActions'=>$this->objectActions]);

        $response->setResponse($responseData, $responseCode, false);
        if($error)
            $response->setErrorResponse(new \Exception($error));
    }

    /**
     * @param $moduleRules
     * @return array
     */
    private function evaluateModuleRules($moduleRules)
    {
        $actions = [];
        foreach($moduleRules as $moduleRule) {
            $action = $this->evaluateModuleRule($moduleRule);
            if($action)
                $actions[] = $action;
        }

        $resultActions = [];
        foreach($actions as $key=>$action){
            $actionType = $action['actionType'];
            $idObjectAction = $action['idObjectAction'];
            if($actionType == "Add" && !isset($resultActions[$idObjectAction]))
                $resultActions[$idObjectAction] = $action;
            else if($actionType == "Remove" && isset($resultActions[$idObjectAction]))
                unset($resultActions[$idObjectAction]);
        }

        return $resultActions;
    }

    /**
     * @param $moduleRule
     * @return array
     */
    private function evaluateModuleRule($moduleRule)
    {
        $optionVariables = $this->keydictVariables;
        $result = false;
        $conditions = $moduleRule['conditions'];
        foreach($conditions as $condition){
            /**
             * Get Variable Value
             * Get Value Value
             * Case through Comparators and do the corresponding comparrison, record the results.
             * Case through the Conjunction, compare result with the recursive(?) call of evaluate on the next ModuleRule.
             */
            $conjunction = $condition['conjunction'];
            $comparator = $condition['comparator'];
            $variable = $condition['variable'];
            $value = $condition['value'];
            $validatedVariable = NULL;

            $fields = explode(".",$variable);
            /** @var Keydict $currentKeydict */
            $currentKeydict = $optionVariables;
            foreach($fields as $field){
                if($currentKeydict->__isset($field)) {
                    /** @var Entry $temp */
                    $temp = $currentKeydict->getField($field);
                    if ($temp->isContainer())
                        $currentKeydict = $temp;
                    else
                        $validatedVariable = $temp;
                }
                else
                    break;
            }

            if($validatedVariable) {

                //validate the Value to the Variable (keydict) to make sure it's a valid value
                /** @var Entry $validatedVariable */
                $realValue = $validatedVariable->validate($value);
                $realVariableValue = $validatedVariable->validate($validatedVariable->get());

                switch ($comparator) {
                    case '==':
                        $result = $realVariableValue == $realValue;
                        break;
                    case '!=':
                        $result = $realVariableValue != $realValue;
                        break;
                    case '>=':
                        $result = $realVariableValue >= $realValue;
                        break;
                    case '<=':
                        $result = $realVariableValue <= $realValue;
                        break;
                    case '>':
                        $result = $realVariableValue > $realValue;
                        break;
                    case '<':
                        $result = $realVariableValue < $realValue;
                        break;
                }
            }
            if($conjunction === "AND") {
                if (!$result)
                    break;
                else
                    $result = false;
            }
            elseif($conjunction === "OR") {
                if ($result)
                    break;
            }
            else
                break;
        }
        $result = $result?['actionType'=>$moduleRule['actionType'],
                        'idObjectAction'=>$moduleRule['idObjectAction'],
                        'objectActionTitle'=>$moduleRule['objectActionTitle']]:[];

        return $result;
    }

    /**
     * @param $array
     * @param $append
     * @return array
     */
    private function flatVariableHelper($array,$append)
    {
        $result = [];
        foreach ($array as $key => $value){
            if(is_array($value)) {
                $result = array_merge($result, $this->flatVariableHelper($value, $key));
            }
            else
                $result[] = $append?$append.".".$key:$key;
        }
        return $result;
    }

    /**
     * @param $idController
     * @return $this
     */
    private function setModuleRules($idController)
    {
        $moduleRules = ModuleRules::getModuleRules($idController);

        foreach($moduleRules as &$element){
            $conditionsString = $element['expression'];

            $lines = explode(", ",$conditionsString);
            $conditions = [];
            foreach($lines as $line) {
                $individualCondition = explode(" ", $line);
                $conditions[] = ['variable' => isset($individualCondition[0])?$individualCondition[0]:"",
                    'comparator' => isset($individualCondition[1])?$individualCondition[1]:"",
                    'value' => isset($individualCondition[2])?$individualCondition[2]:"",
                    'conjunction' => isset($individualCondition[3])?$individualCondition[3]:"N/A"];
            }
            $element['expression'] = $conditionsString;
            $element['conditions'] = $conditions;
        }

        $this->moduleRules = $moduleRules;
        $this->ruleControllerId = $idController;
        return $this;
    }
    
    
    /*********************************************   #WORKFLOW#  *********************************************/


    /**
     * @param $idController
     * @return array
     */
    public function evaluateRulesByController($idController)
    {
        $moduleRules = $this->getModuleRules($idController);
        return $this->evaluateModuleRules($moduleRules);
    }

    
    /**
     * @return mixed
     */
    public function getModuleRules($idController)
    {
        if($this->moduleRules && $this->ruleControllerId == $idController)
            return $this->moduleRules;
        else
            return $this->setModuleRules($idController)->moduleRules;
    }



    /******************************************   #INITIALIZATION#  ******************************************/

    /**
     * Binds the conditionOptions to make ModuleRules
     * @param $variables
     * @param $comparators
     * @param $values
     * @param $conjunctions
     * @return $this
     */
    public function bindConditionOptions(Keydict $variables, $comparators, $values, $conjunctions)
    {
        $flatVariable = $this->flatVariableHelper($variables->toArray(),'');
        array_unshift($flatVariable,"N/A");
        array_unshift($conjunctions,"N/A");

        $conditionOptions = ['variables' => $flatVariable,
            'comparators' => $comparators,
            'values' => $values,
            'conjunctions' => $conjunctions];
        $this->conditionOptions = $conditionOptions;
        $this->keydictVariables = $variables;
        return $this;
    }

    /**
     * @param array $objectActions
     * @return $this
     */
    public function bindObjectActions(Array $objectActions)
    {
        $this->objectActions = $objectActions;

        return $this;
    }

    /*********************************************   #TRIGGERS#  *********************************************/


    /**
     * Trigger fired when the Form has been Updated on a successful POST/PUT request.
     * @param \Closure $closure
     * @return \eprocess360\v3core\Controller\Trigger\Trigger
     */
    public function onUpdate($closure)
    {
        return $this->addTrigger(__FUNCTION__, $closure);
    }
}