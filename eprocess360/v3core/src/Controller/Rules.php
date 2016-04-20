<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 12/15/2015
 * Time: 2:25 PM
 */

namespace eprocess360\v3core\Controller;

/**
 * Class AccessRules
 * @package eprocess360\v3core\Controller
 *
 * Allows for the mapping of Privileges, Roles, and States to this object
 */
trait Rules
{
    public $accessRules = [];
    /**
     * Given ControllerRole can grant users the given access to this MappableEventObject during the given States
     * @param int $access
     * @param string|string[] $roles
     * @param string|string[] $states
     * @return $this
     * @throws \Exception
     */
    public function addRules($access, $roles, $states = [0]) {

        if (!is_array($states)) {
            $states = [$states];
        }
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        foreach ($states as $state) {
            if (!array_key_exists($state, $this->accessRules)) {
                $this->accessRules[$state] = [];
            }
            $tempAccess = $access;
            while ($tempAccess) {
                $bit = $tempAccess & (-$tempAccess);
                if (!array_key_exists($bit, $this->accessRules[$state])) {
                    $this->accessRules[$state][$bit] = [];
                }
                foreach ($this->accessRules[$state][$bit] as $key=>$existingRole) {
                    foreach ($roles as $newRole) {
                        if ($existingRole == $newRole) {
                            unset($this->accessRules[$state][$bit][$key]);
                        }
                    }
                }
                $this->accessRules[$state][$bit] = array_merge($this->accessRules[$state][$bit], $roles);
                $tempAccess = $tempAccess & ($tempAccess-1);
            }

        }

        return $this;
    }

    /**
     * Compiles a role list from the accessRules for the given access mode and controller state.  If a Controller is
     * provided, the Roles will be translated back to the idLocalRole (typically what will happen).
     * @param int $filterAccess
     * @param string $filterState
     * @param Controller|Roles $controller
     * @return int[]
     */
    public function getRules($filterAccess = -1, $filterState = null, Controller $controller = null)
    {
        $out = [];
        foreach ($this->accessRules as $keyRuleState=>$ruleState) {
            if ($filterState === null || $filterState === $keyRuleState || $keyRuleState === 0) {
                foreach ($ruleState as $keyRuleAccess => $ruleAccess) {
                    if ($filterAccess === $keyRuleAccess) {
                        foreach ($ruleAccess as $controllerRole) {
                            $out[] = $controller ? $controller->rolesGetByName($controllerRole)->getId():$controllerRole;
                        }
                    }
                }
            }
        }
        return $out;
    }

    /**
     * Given a set of User Roles, returns permission flags with avaiable permissions
     * @param null $filterState
     * @param Controller|Roles $controller
     * @return int
     */
    public function getRuleFlags($roles, $filterState = null, Controller $controller = null)
    {
        $flags = 0;
        foreach ($this->accessRules as $keyRuleState=>$ruleState) {
            if ($filterState === null || $filterState === $keyRuleState || $keyRuleState === 0) {
                foreach ($ruleState as $keyRuleAccess => $ruleAccess) {
                    foreach ($ruleAccess as $controllerRole) {
                        $id = $controller ? $controller->rolesGetByName($controllerRole)->getId():$controllerRole;
                        if(isset($roles[$id]))
                            $flags = $flags | $keyRuleAccess;
                    }
                }
            }
        }
        return $flags;
    }

    /**
     * Gets the states in which a rule is in effect for.
     * @return array
     */
    public function rulesGetStates()
    {
        $states = array_keys($this->accessRules);
        return $states;
    }
}