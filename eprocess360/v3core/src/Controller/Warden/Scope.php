<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 12/18/2015
 * Time: 12:16 PM
 */

namespace eprocess360\v3core\Controller\Warden;


class Scope
{
    private $idController = null;
    private $idLocalRole = [];
    private $idProject = null;
    private $global = false;

    /**
     * @return mixed
     */
    public function getIdController()
    {
        return $this->idController;
    }

    /**
     * @param mixed $idController
     */
    public function setIdController($idController)
    {
        $this->idController = $idController;
    }

    /**
     * @return array
     */
    public function getIdLocalRole()
    {
        return $this->idLocalRole;
    }

    /**
     * @param array $idLocalRole
     */
    public function setIdLocalRole($idLocalRole)
    {
        $this->idLocalRole = $idLocalRole;
    }

    /**
     * @return mixed
     */
    public function getIdProject()
    {
        return $this->idProject;
    }

    /**
     * @param mixed $idProject
     */
    public function setIdProject($idProject)
    {
        $this->idProject = $idProject;
    }

    public function isGlobal()
    {
        return $this->global;
    }

    public function hasIdLocalRole()
    {
        return sizeof($this->idLocalRole);
    }


}