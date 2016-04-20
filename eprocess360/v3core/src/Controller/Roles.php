<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 12/16/2015
 * Time: 12:35 PM
 */

namespace eprocess360\v3core\Controller;


use eprocess360\v3core\Controller\Warden\Role;

/**
 * Class Roles
 * @package eprocess360\v3core\Controller
 */
trait Roles
{
    /** @var Role[] */
    protected $rolesByName = [];
    /** @var Role[] */
    protected $rolesById = [];

    /**
     * Adds a Role to this controller.
     * @param Role ...$roles
     */
    public function addRoles(Role ...$roles)
    {
        /**
         * @var Role $role
         */
        foreach ($roles as $role) {
            $role->setController($this);
            $this->rolesByName[$role->getName()] = $role;
            $this->rolesById[$role->getId()] = $role;
        }
    }

    /**
     * Gets a Role on this Controller by its id.
     * @param int $id
     * @return Role
     * @throws ControllerException
     */
    public function rolesGetById($id)
    {
        if (array_key_exists($id, $this->rolesById)) {
            return $this->rolesById[$id];
        }
        throw new ControllerException("Role with ID {$id} does not exist.");
    }

    /**
     * Gets a Role on this Controller by its name.
     * @param string $name
     * @return Role
     * @throws ControllerException
     */
    public function rolesGetByName($name)
    {
        if (array_key_exists($name, $this->rolesByName)) {
            return $this->rolesByName[$name];
        }
        throw new ControllerException("Role with ID {$name} does not exist.");
    }

    /**
     * Gets a Role on this Controller by its name, calls rolesGetByName($name).
     * @param string $name
     * @return Role
     */
    public function role($name)
    {
        return $this->rolesGetByName($name);
    }

    /**
     * returns the set of Roles
     * @return Warden\Role[]
     */
    public function getRolesById()
    {
        $result = [];
        foreach($this->rolesById as $key => $value){
            $result[$key]= $value->getName();
        }
        return $result;
    }

    public function buildRoles()
    {

    }
}