<?php
/**
 * Created by PhpStorm.
 * User: kira
 * Date: 8/5/2015
 * Time: 10:43 AM
 */

namespace eprocess360\v3core;


use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Project;
use eprocess360\v3core\Keydict\Entry\Integer;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model\Controllers;

/**
 * Class Projects
 * @package eprocess360\v3core
 * @deprecated Needs to be moved to Projects
 */
class Projects
{

    /**
     * Returns the Projects that a User has access to
     * @param bool|false $superAdmin
     * @return array
     * @throws \Exception
     */
    public static function getMyProjects($superAdmin = false)
    {
        global $pool;
        $keydict = Model\Projects::keydict();

        $select = "Projects.*";
        $join = "LEFT JOIN Projects ON Projects.idProject = UserRoles.idProject
                LEFT JOIN Tasks ON Projects.idProject = Tasks.idProject";
        $where = $superAdmin? '' : "UserRoles.idUser = {$pool->User->getIdUser()} AND UserRoles.idProject > 0";
        $from = "UserRoles";
        $group = "UserRoles.idProject";

        $result = ['keydict'=>$keydict, 'select'=>$select,'join'=>$join, 'where'=>$where, "from"=> $from, 'group'=>$group];
        return $result;
    }

    /**
     * @param $idController
     * @param bool|false $superAdmin
     * @return array
     * @throws \Exception
     */
    public static function getMyProjectsByControllerId($idController, $superAdmin = false)
    {
        global $pool;
        $controller = Controllers::sqlFetch($idController);
        $class = $controller->class->get();

        /** @var Controller|Project $controller */
        $tableName = Project::constructTableName($class, $idController);

        $keydict = Project::getProjectControllerByIdController($idController)->keydict();

        $select = "{$tableName}.*, Projects.title, Projects.description, Projects.state";
        $join = "INNER JOIN Projects ON Projects.idProject = UserRoles.idProject
                LEFT JOIN Tasks ON Projects.idProject = Tasks.idProject
                LEFT JOIN {$tableName} ON {$tableName}.idProject = Projects.idProject";
        $where = $superAdmin ? '' : "UserRoles.idUser = {$pool->User->getIdUser()} AND UserRoles.idProject > 0";
        $from = "UserRoles";
        $group = "UserRoles.idProject";

        $result = ['keydict'=>$keydict, 'table'=>$tableName, 'select'=>$select,'join'=>$join, 'where'=>$where, "from"=> $from, 'group'=>$group];
        return $result;
    }

}