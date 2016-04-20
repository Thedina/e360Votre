<?php
/**
 * Created by PhpStorm.
 * User: kiranebilak
 * Date: 3/3/16
 * Time: 3:24 PM
 */

namespace eprocess360\v3controllers\Group\Model\Traits;


trait GroupProjects {

    /**
     * Insert a DB entry for this FeeReceipt
     */
    public function insert()
    {
        $this->data->insert();
    }

    /**
     * @param int $idGroup
     * @param int $idController
     * @return GroupProjects
     */
    public static function make($idGroup = 0, $idController = 0)
    {

        $rowData = [
            'idGroup' => $idGroup,
            'idController' => $idController];

        return self::GroupProjectConstruct($rowData);
    }


    /**
     * @param array $rowData
     * @return GroupProjects
     */
    public static function GroupProjectConstruct($rowData = [])
    {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);
        return $instance;
    }


    /**
     * @param $idGroup
     * @param $idController
     * @return array
     */
    public static function create($idGroup, $idController)
    {
        $f = static::make($idGroup, $idController);

        $f->insert();

        $result = $f->data->toArray();

        return $result;
    }

    /**
     * @param $idGroupProject
     * @return bool
     * @throws \Exception
     */
    public static function deleteFeeTemplateFeeTag($idGroupProject)
    {
        $groupProject = self::sqlFetch($idGroupProject);

        self::deleteById($groupProject->idGroupProject->get());

        return true;
    }

}