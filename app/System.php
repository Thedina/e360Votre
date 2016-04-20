<?php

namespace App;


use eprocess360\v3controllers\Dashboard\Dashboard;
use eprocess360\v3controllers\Group\Group;
use eprocess360\v3controllers\Group\Model\Groups;
use eprocess360\v3controllers\Group\Model\GroupUsers;
use eprocess360\v3controllers\IncrementGenerator\Model\IncrementGenerators;
use eprocess360\v3controllers\Migrations\MigrationsController;
use eprocess360\v3controllers\Register\Register;
use eprocess360\v3controllers\SystemController\SystemController;
use eprocess360\v3core\Controller\Project;
use eprocess360\v3core\Controller\Trigger\InterfaceTriggers;
use eprocess360\v3core\DB;
use eprocess360\v3core\Model\Controllers;
use eprocess360\v3modules\Task\Task;

/**
 * Class System
 * @package App
 */
class System extends SystemController implements InterfaceTriggers
{
    public function run()
    {
        parent::buildChildren();
        $this->addController(Group::persistentBuild('groups',$this)->setDescription("Groups"));
        $this->addController(Task::persistentBuild('tasks', $this)->setDescription("Tasks"));

        $register = $this->getChild('register');

        /** @var Register $register */
        $register->onSuccess(function () {
            global $pool;
            $idGroup = $pool->SysVar->get("PublicGroupId");
            //Every Group is created with 3 basic Roles for that Group. To create a member, we need the third role.
            $idRole = Groups::getRoles($idGroup)[2]['idRole'];
            $isActive = true;

            GroupUsers::create($pool->User->getIdUser(), $idRole, $idGroup, $isActive);
        });

        parent::run();
    }

    public function commission()
    {
        global $pool;
        $idGroups = Group::persistentBuild('groups',$this)->getId();
        $idTasks = Task::persistentBuild('tasks', $this)->getId();

        $pool->SysVar->add("idTasks",$idGroups);
        $pool->SysVar->add("idGroups",$idTasks);

        IncrementGenerators::create(1111111, 0, 'receipts', "%d");
        IncrementGenerators::create(0, 0, 'projectNumbers', "#%d");
        IncrementGenerators::create(1, 0, 'permitNumber', "%05d");

        //psuedo Group for 'Reviewers' *********************/
        $data = Groups::create("Reviewers", true);
        $idGroup = $data['idGroup'];
        $pool->SysVar->add("ReviewersGroupId",$idGroup);

        $data = Groups::create("Permit Techs", true);
        $idGroup = $data['idGroup'];
        $pool->SysVar->add("PermitTechsGroupId",$idGroup);

        $data = Groups::create("Land Use Reviewers", true);
        $idGroup = $data['idGroup'];
        $pool->SysVar->add("LandUseReviewersGroupId",$idGroup);

        $data = Groups::create("Structural Reviewers", true);
        $idGroup = $data['idGroup'];
        $pool->SysVar->add("StructuralReviewersGroupId",$idGroup);

        $data = Groups::create("Building Reviewers", true);
        $idGroup = $data['idGroup'];
        $pool->SysVar->add("BuildingReviewersGroupId",$idGroup);

        $data = Groups::create("Health Reviewers", true);
        $idGroup = $data['idGroup'];
        $pool->SysVar->add("HealthReviewersGroupId",$idGroup);

        $data = Groups::create("Grading Reviewers", true);
        $idGroup = $data['idGroup'];
        $pool->SysVar->add("GradingReviewersGroupId",$idGroup);

        $id = \App\Dummy\Dummy::register('Building Permit', "Apply for a Building Permit.  Electronic documents may be required if your project requires a review.", Controllers::STATUS_UP);
        $result = Controllers::sqlFetch($id);
        $result->status->isActive->set(true);
        $result->update();

        $id = \App\Basic\Basic::register('Basic Workflow', "Basic workflow which provides a testing environment for new implementation of Modules.", Controllers::STATUS_UP);
        $result = Controllers::sqlFetch($id);
        $result->status->isActive->set(true);
        $result->update();

        /*******************/

        $data = Groups::create("Public", true, true);
        $idGroup = $data['idGroup'];

        $pool->SysVar->add("PublicGroupId",$idGroup);
    }

    /**
     * @param Dashboard $obj
     */
    public function initDashboard(Dashboard $obj)
    {
        //TODO Formally address the getting of Projects and Controllers in the multiple ways we would want them.
        global $pool;
        $controllers = Project::getProjectControllers();
        $obj->setControllers($controllers);
        $sql = "SELECT Projects.*, COUNT(DISTINCT Tasks.idTask) as taskCount FROM UserRoles
                LEFT JOIN Projects ON Projects.idProject = UserRoles.idProject
                LEFT JOIN Tasks ON Projects.idProject = Tasks.idProject AND NOT Tasks.status_0 & 0b1
                WHERE UserRoles.idUser = {$pool->User->getIdUser()} AND UserRoles.idProject > 0
                GROUP BY UserRoles.idProject
                ORDER BY COUNT(Tasks.idTask) DESC, Projects.idProject DESC
                LIMIT 0,30";
        //TODO Query so that Count is only the non completed Tasks
        $projects = DB::sql($sql);
        $obj->setProjects($projects);
    }

}