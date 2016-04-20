<?php
/**
 * Created by PhpStorm.
 * User: Sharon_2
 * Date: 3/9/2016
 * Time: 3:44 PM
 */

use eprocess360\v3controllers\Group\Model\Groups;
use eprocess360\v3controllers\Group\Model\GroupUsers;
use eprocess360\v3core\DB;
use eprocess360\v3core\Form;
use eprocess360\v3core\Keydict\Entry\Email;
use eprocess360\v3core\Keydict\Entry\Password;
use eprocess360\v3core\Keydict\Entry\PhoneNumber;
use eprocess360\v3core\Keydict\Entry\String;
use eprocess360\v3core\Keydict\Entry\String64;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\User;


$users = [
    "Theodore Roosevelt",
    "Sybille Hectell",
    "Constance Gordon-Cummings",
    "Clare Hodges",
    "Tredwell Moore",
    "Tom Frost",
    "Galen Clark",
    "Ansel Adams",
    "Mabel Sweet",
    "Montgomery Macomb",
    "John Muir",
    "Thomas Ayres",
    "Enid Michael",
    "Mary Winslow",
    "Julia Parker",
    "Sally Dutcher",
    "Frederick Olmsted",
    "Joseph Walker",
    "Stella Sweet",
    "James Savage",
    "James Hutchings",
    "George Anderson",
    "John Conness",
    "Lafayette Bunnell",
    "Charles Weed",
    "Allexey Schmidt",
    "Royal Robbins",
    "Carleton Watkins",
    "Bev Johnson",
    "Thomas King",
    "Maybel Davis",
    "Jesse Fremont",
    "Bridget Degnan",
    "Abram Wood",
    "Lucy Telles",
    "Maggie Howard",
    "Kitty Tatch",
    "Bertha Sweet",
    "Jennie Curry",
    "Liz Robbins",
    "William Abrams",
    "Katherine Hazelston"
];

for ($i = 0; $i < sizeof($users); $i++) {
    $name = explode(" ", $users[$i]);
    $user['firstName'] = $name[0];
    $user['lastName'] = $name[1];
    $users[$i] = $user;
}

for ($i = 0; $i < sizeof($users); $i++) {
    $domain = "@cityofyosemite.ca.gov";
    $initial = $users[$i]['firstName'][0];
    $email = $initial . $users[$i]['lastName'] . $domain;
    $users[$i]['email'] = strtolower($email);
}

$password = "Yosemite2016";
$phone = "2016031417";

$keydict = \eprocess360\v3core\Model\Users::keydict();

function cleanSleep($string) {
    $value = DB::cleanse((string)$string);
    return "{$value}";
}

global $pool;

$idGroups = [
    'Land Use Reviewer' => $pool->SysVar->get("LandUseReviewersGroupId"),
    'Structural Reviewers' => $pool->SysVar->get("StructuralReviewersGroupId"),
    'Building Reviewers' => $pool->SysVar->get("BuildingReviewersGroupId"),
    'Health Reviewers' => $pool->SysVar->get("HealthReviewersGroupId"),
    'Grading Reviewers' => $pool->SysVar->get("GradingReviewersGroupId"),
    'Permit Techs' => $pool->SysVar->get("PermitTechsGroupId")
];

$idRoles = [0, 1, 2, 2, 2];

$idGroupRoles = [];

foreach ($idGroups as $groupTitle => $groupID) {
    foreach ($idRoles as $role) {
        $roleData = Groups::getRoles($groupID)[$role];
        $roleTitle = $roleData['title'];
        $roleID = $roleData['idRole'];
        $idGroupRoles[] = [
            'idGroup' => $groupID,
            'groupTitle' => $groupTitle,
            'idRole' => $roleID,
            'roleTitle' => $roleTitle];
    }
}

$j = 0;

$userGroups = [];

foreach ($users as $user) {

    $newUser = User::register(
        cleanSleep($user['firstName']),
        cleanSleep($user['lastName']),
        cleanSleep($user['email']),
        cleanSleep($password),
        $phone = (string)((int)$phone+1)
    );

    global $pool;
    $idGroup = $pool->SysVar->get("PublicGroupId");
    //Every Group is created with 3 basic Roles for that Group. To create a member, we need the third role.
    $idRole = Groups::getRoles($idGroup)[2]['idRole'];
    $isActive = true;

    GroupUsers::create($newUser->getIdUser(), $idRole, $idGroup, $isActive);

    if ($j < sizeof($idGroupRoles)) {
        $reviewerGroup = $pool->SysVar->get("ReviewersGroupId");
        $reviewerRole = Groups::getRoles($reviewerGroup)[2]['idRole'];
        GroupUsers::create($newUser->getIdUser(), $reviewerRole, $reviewerGroup, $isActive);
        GroupUsers::create($newUser->getIdUser(), $idGroupRoles[$j]['idRole'], $idGroupRoles[$j]['idGroup'], $isActive);

//        $userGroups[] = $user['firstName'] . ' ' . $user['lastName'] . "\n" . $user['email'] . "\n" . $idGroupRoles[$j]['groupTitle'] . ' - ' . $idGroupRoles[$j]['roleTitle'] . "\n\n";

        $j++;
    }
//    else {
//        $userGroups[] = $user['firstName'] . " " . $user['lastName'] . "\n" . $user['email'] . "\n" . "Regular User" . "\n\n";
//    }
}

die();

//$userGroups = implode("", $userGroups);
//
//var_dump($userGroups);