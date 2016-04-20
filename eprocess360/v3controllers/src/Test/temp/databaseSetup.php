<?php
namespace eprocess360\v3core;

use eprocess360\v3core\Model\Controllers;
use eprocess360\v3core\Keydict\Entry\Bits;

$id = \App\Dummy\Dummy::register('Building Permit', "Process to attain a Building Permit");
$result = Controllers::sqlFetch($id);
$result->status->isActive->set(true);
$result->update();
\App\Dummy\Dummy::createProject($id);

echo "New Workflow Controller and Project Created.";
die();

//$preset = json_decode(file_get_contents(APP_PATH."/eprocess360/v3core/src/Controller/Dummy/BuildingPermitRouteTree.json"),true);
//RouteTree::loadPreset(1, $preset);
//RouteTree::rebuildConfiguredFlags(1);