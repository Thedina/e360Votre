<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/19/2015
 * Time: 1:55 PM
 */

namespace eprocess360\v3modules\RouteTree;
use eprocess360\v3core\Controller\Children;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Module;
use eprocess360\v3core\Controller\Persistent;
use eprocess360\v3core\Controller\Project;
use eprocess360\v3core\Controller\ProjectToolbar;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\Rules;
use eprocess360\v3core\Controller\Trigger\InterfaceTriggers;
use eprocess360\v3core\Controller\Triggers;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry\BitString;
use eprocess360\v3core\Keydict\Entry\Bits;
use eprocess360\v3core\Keydict\Entry\Flag;
use eprocess360\v3core\Keydict\Entry\KeydictName;
use eprocess360\v3core\Keydict\Field;
use eprocess360\v3modules\RouteTree\Model\RouteOptions;
use eprocess360\v3core\DB;
use eprocess360\v3core\Controller\Warden\Privilege;
use Exception;


/**
 * Class RouteTree
 * @package eprocess360\v3core
 */
class RouteTree extends Controller implements InterfaceTriggers
{
    use Router, Module, Persistent, Triggers, Rules, ProjectToolbar;
    /** @var  Bits This is where the keydict data will ultimately be stored, but RouteTree does not determine save. */
    protected $bits;
    /** @var  string[] This is the flags that the RouteTree will need to add to the DynamicFlags when awoken. */
    protected $configured_flags;
    /** @var  int The configuration to use that belongs to Controller */
    protected $idroute;
    /** @var  bool Whether or not RouteTree is valid */
    protected $valid;
    /** @var  BitString The idRoutOption from the database stored as bits based on position */
    protected $selectedOptions;
    /** @var  Keydict Hold the loaded route and current state */
    protected $keydictData;
    /** @var  string The location of the RouteTree option preset values */
    protected $preset;
    /** @var  bool This module modifies the keydict */
    protected $modifiesKeydict = true;
    protected $handler;
    private $routeTreeSaved;


    /**
     * Used as a fail-safe in Controllers to make sure that their dependencies and initializations are met; If not, exception is thrown.
     */
    public function dependencyCheck()
    {
        if($this->bits === NULL)
            throw new Exception("Route Tree Module does not have Flags set, please bindFlags in the initialization function.");
        if($this->selectedOptions === NULL)
            throw new Exception("Route Tree Module does not have Selected Options set, please bindSelectedOptions in the initialization function.");
    }


    /*********************************************   #ROUTING#  **********************************************/


    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     */
    public function routes()
    {
        $this->routes->map('GET', '/?[i:id]?', function ($id = false) {
            $this->getRouteTreeAPI($id);
        });

        $this->routes->map('POST', '/?', function () {
            $this->updateRouteTreeAPI();
        });

    }

    /**
     * API Function to get and build a RouteTree
     *
     * @param $id
     * @throws \Exception
     *
     * @Required_Privilege: Read
     */
    public function getRouteTreeAPI($id)
    {
        $this->verifyPrivilege(Privilege::READ);

        $this->loadRouteTree();

        $this->standardResponse($this);
    }

    /**
     * API Function to update the RouteTree with the specified Nodes.
     *
     * @Required_Privilege: Write
     * @Triggers: onUpdate
     */
    public function updateRouteTreeAPI()
    {
        $this->verifyPrivilege(Privilege::WRITE);

        $this->acceptPost();

        $this->setSaved();

        $this->standardResponse($this);

        $this->trigger('onUpdate');
    }


    /**********************************************   #HELPER#  **********************************************/


    /**
     * Helper function that sets the Response's template, data, response code, and errors.
     *
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
        $response->setTemplate('RouteTree.main.html.twig', 'server');

        $response->setResponse($responseData, $responseCode, false);
        if($error)
            $response->setErrorResponse(new \Exception($error));
    }


    /**
     * Sets the RouteTree as Saved/Not Saved.
     *
     * @param bool|true $value
     */
    private function setSaved($value = true)
    {
        $this->routeTreeSaved = $value;
    }

    /**
     * Sets whether or not this RouteTree is valid for use.
     *
     * @param mixed $valid
     */
    private function setValid($valid)
    {
        $this->valid = $valid;
    }

    /**
     * Prepares the RouteTree's DynamicFlags.  These Flags are controller/configuration specific.
     *
     * @return $this
     * @throws \Exception
     */
    private function loadConfiguredFlags()
    {
        if (!is_array($this->configured_flags)) {
            throw new \Exception("Cannot load Configured Flags since the array isn't set.");
        }

        foreach ($this->bits->getKeydict()->allFields(Field::TYPE_ANY, true) as $field) {
            if (!in_array($field->getName(), $this->configured_flags, true)) {
                throw new \Exception("RouteTree requires that the configuration provides flag '{$field->getName()}' but it was not found in the configuration.");
            }
        }
        /** @var Bits $bits */
        $bits = $this->bits;
        foreach ($this->configured_flags as $bit=>$flag_name) {
            if (substr($bit,0,2)!='__') {
                $bits->addBit(Keydict\Entry\Bit::build($bit, $flag_name));
            }

        }
        $this->setValid(true);
        return $this;
    }

    /**
     * Sets the idRoute of the RouteTree
     *
     * @param $idroute
     * @return RouteTree
     */
    private function setIdRoute($idroute)
    {
        $this->idroute = $idroute;
        return $this;
    }

    /**
     * Helper function to update the RouteTree on a POST/PUT request.
     *
     * @throws \Exception
     */
    private function acceptPost()
    {
        
        $this->loadRouteTree();

        //Clear the bits so that we only have to pass checked ones, while still covering for unchecking.
        $this->selectedOptions->wakeup('');

        //Change the bits (selectedOption)
        $this->keydictData->acceptPost();

        //reset all flags (->bits) and then set them according to bits (aka selectedOption)
        $this->updateFlags();
    }


    /*********************************************   #WORKFLOW#  *********************************************/


    /**
     * Returns whether or not this RouteTree is presently valid for use.
     *
     * @return mixed
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * Load the RouteTree configuration from the RouteTrees table.  This will prepare the RouteTree with a Controller
     * specific configuration, and then call loadConfiguredFlags.
     *
     * @return $this
     * @throws \Exception
     */
    public function loadConfiguration()
    {
        if ($this->isValid()) {
            return $this;
        }
        /** @var Project|Children|Controller $projectController */
        $projectController = $this->getClosest('Project');
        $sql = "SELECT * FROM `RouteTrees` WHERE `idController` = '{$this->getId()}'";

        $results = DB::sql($sql);
        
        if (is_array($results) && sizeof($results)) {
            $this->setIdRoute($results[0]['idRoute']);
            $this->configured_flags = json_decode($results[0]['configuredFlags'], true);
            $this->loadConfiguredFlags();
            return $this;
        }
        throw new \Exception("RouteTree '{$this->getName()}' does not have flags configured for Controller #{$this->getId()}.");
    }

    /**
     * @param $idRoute
     * @param $array
     * @param $parent
     * @param $bitPosition
     * @param $depth
     * @return void
     * @throws Keydict\Exception\InvalidValueException
     */
    public static function loadPreset($idRoute, $array, $parent = 0, &$bitPosition = 0, $depth = 0)
    {
        if (array_key_exists('options', $array)) {
            // this is the root
            $bitPosition = 1;
            self::loadPreset($idRoute, $array['options'], 0, $bitPosition, 0);
            return;
        }
        $itemorder = 0;
        foreach ($array as $title=>$node) {
            $title = DB::cleanse($title);
            $node['helptext'] = array_key_exists('helptext', $node) ? DB::cleanse($node['helptext']) : '';
            if (array_key_exists('flags', $node)) {
                if (!is_array($node['flags'])) {
                    $node['flags'] = [$node['flags']];
                }
                foreach ($node['flags'] as &$flag) {
                    KeydictName::validate($flag);
                }

                $node['flags'] = json_encode($node['flags']);
                $node['flags'] = DB::cleanse($node['flags']);
            } else {
                $node['flags'] = '[]';
            }
            //TODO Optimize this set of inserts into a single SQL request, Nested Sets should do the trick.
            $sql = "INSERT INTO RouteOptions (parent, title, helptext, flags, idRoute, itemOrder, bitPosition, depth) VALUES (
              {$parent},'{$title}','{$node['helptext']}','{$node['flags']}',{$idRoute},{$itemorder},{$bitPosition},{$depth})";
            $iid = DB::insert($sql);
            $bitPosition++;
            $itemorder++;
            if (array_key_exists('children', $node)) {
                $newdepth = $depth + 1;
                self::loadPreset($idRoute, $node['children'], $iid, $bitPosition, $newdepth);
            }
        }
    }

    /**
     * Restores a RouteTree from the database and loads in existing bit values.
     *
     * @throws Keydict\Exception\InvalidValueException
     * @throws Keydict\Exception\KeydictException
     * @throws \Exception
     */
    public function loadRouteTree()
    {
        
        $selected = $this->getSelectedOptions();
        
        $sql = "SELECT * FROM RouteOptions WHERE idRoute = {$this->getIdRoute()} ORDER BY parent ASC, itemOrder ASC";
        $specification = RouteOptions::keydict();
        $keydict = $baseKeydict = Keydict::build(
            Keydict::build()->setName('children')
        )->setMeta('idRouteOption', 0)->setName('node0');
        $lastParent = 0;
        $results = DB::sql($sql);
        if ($results) {
            foreach ($results as $result) {
                --$result['bitPosition'];
                if ($result['parent'] != $lastParent) {
                    if ((int)$result['parent'] == 0) {
                        $keydict = $baseKeydict;
                    } else {
                        if ($find = $baseKeydict->findField('node'.$result['parent'])) {
                            $keydict = $find;
                        } else {
                            throw new \Exception('failed to find: '.'node'.$result['parent']);
                        }
                    }
                    $lastParent = $keydict->getMeta('idRouteOption');
                }
                $flagName = 'flag' . $result['idRouteOption'];
                $flagValue = $specification->flags->wakeup($result['flags'])->getValue();
                $keydict->getChildren()->add(Keydict::build(
                    $flag=Flag::build($flagName, $result['title'], $flagValue)->setMetaArray($result),
                    Keydict::build()->setName('children')
                )->setName('node' . $result['idRouteOption'])->setMeta('idRouteOption', $result['idRouteOption']));
                /** @var Flag $flag */
//                /** Bind the BitString to the Flag - This will load a value into the Flag */
//                $v = (int)$selected->getBit($result['bitPosition']);
//                if ($v)
//                    echo "binding {$flagName} {$selected->getName()}.{$result['bitPosition']} = {$v} \r\n";
                $flag->bindBitString($selected, $result['bitPosition']);
            }
        }
        $this->keydictData = $baseKeydict;
    }

    /**
     * Helper for routeTreeSummary() - check if an idRouteOption refers to a
     * leaf option
     * @param $idRouteOption
     * @param $selectedOptions
     * @return bool
     */
    private function summaryIsLeaf($idRouteOption, $selectedOptions) {
        $isLeaf = true;

        foreach ($selectedOptions as $opt) {
            if($opt['parent'] == $idRouteOption) {
                $isLeaf = false;
            }
        }

        return $isLeaf;
    }

    /**
     * Helper for routeTreeSummary() - basic recursive function to find root
     * from a given branch or lead node
     * @param $startAt
     * @param $selectedOptions
     * @return mixed
     */
    private function summaryFindRoot($startAt, $selectedOptions) {
        if($startAt['parent'] == 0) {
            return $startAt;
        }
        else {
            foreach($selectedOptions as $opt) {
                if($opt['idRouteOption'] == $startAt['parent']) {
                    return $this->summaryFindRoot($opt, $selectedOptions);
                }
            }
        }
    }

    /**
     * Get a summary of route tree selections
     * (as an array of [root title]: [leaf title])
     * @return array
     * @throws Exception
     */
    public function routeTreeSummary() {
        $out = [];

        $bitPositions = array_map(function($val) {
            return $val + 1;
        }, $this->getSelectedOptions()->getPositions());
        
        if(!empty($bitPositions)) {
            $bitPositions = implode(',', $bitPositions);

            $sql = "SELECT *
                FROM RouteOptions
                WHERE
                  idRoute = {$this->getIdRoute()}
                  AND bitPosition IN (" . $bitPositions . ")
                ORDER BY parent ASC, itemOrder ASC";

            $results = DB::sql($sql);
            
            if (!empty($results)) {
                $leaves = [];

                foreach ($results as $r) {
                    if ($this->summaryIsLeaf($r['idRouteOption'], $results)) {
                        $leaves[] = $r;
                    }
                }

                foreach ($leaves as $l) {
                    $root = $this->summaryFindRoot($l, $results);
                    if ($root['idRouteOption'] === $l['idRouteOption']) {
                        $out[] = $root['title'];
                    } else {
                        $out[] = $root['title'] . ': ' . $l['title'];
                    }
                }

            }
        }

        return $out;
    }

    /**
     * Returns the current Selected Options of the RouteTree.
     *
     * @return BitString
     * @throws \Exception
     */
    public function getSelectedOptions()
    {
        $this->loadConfiguration();
        return $this->selectedOptions;

    }

    /**
     * See if the User completed the RouteTree enough for it to be deemed complete.  Makes use of the Controller
     * specified during __construct.
     */
    public function isRouteComplete()
    {

    }

    /**
     * Returns the current id of the RouteTree
     *
     * @return mixed
     */
    public function getIdRoute()
    {
        return $this->idroute;
    }

    /**
     * Returns the Nodes, used to build the Route Tree in the front end view.
     *
     * @return array
     * @throws \Exception
     */
    public function getNodes()
    {
        if (!$this->isValid()) {
            throw new \Exception("Route Tree is not ready.");
        }
        /**
         * @param Keydict|Field $data
         * @param array $list
         * @param null $parent
         * @param int $depth
         * @throws \Exception
         */
        $getNodes = function ($data, &$list = [], $parent = null, $depth = 0) use (&$getNodes) {
            $i = false;
            /**
             * Each one of $list[$depth][$data->getName()] will become a 'block' on the interface
             */
            foreach ($data->getChildren()->allFields(Field::TYPE_ANY, true) as $node) {
                if (!$i) {
                    $n = $list[$depth][$data->getName()]['node'] = $data->getFieldByClass('Flag');
                    $list[$depth][$data->getName()]['parent'] = $parent;
                    $i = true;
                }
                $list[$depth][$data->getName()]['children'][] = $node;
                $getNodes($node, $list, (method_exists($n, 'getLabel') ? $n->getLabel() : null), $depth+1);
            }
        };
        $list = [];
        $getNodes($this->keydictData, $list);
        return $list;
    }

    /**
     * Update the flags used by the RouteTree.
     *
     * @throws \Exception
     */
    public function updateFlags()
    {
        $flagsAll = [];
        $flagsToSet = [];

        foreach ($this->keydictData->allFields(Field::TYPE_ANY) as $flag) {
            $flags = json_decode($flag->getMeta('flags'));
            foreach ($flags as $f) {
                if ($flag->get() && !in_array($f, $flagsToSet, true)) {
                    $flagsToSet[] = $f;
                }
                $flagsAll[] = $f;
            }
        }
        $flagsAll = array_unique($flagsAll);

        foreach($flagsAll as $f) {
            $this->bits->$f->set(false);
        }
        foreach($flagsToSet as $f) {
            $this->bits->$f->set(true);
        }
        
    }

    /**
     * Gets the RouteTree's preset categories.
     *
     * @return mixed
     */
    public function getPreset()
    {
        return $this->preset;
    }

    /**
     * Gets the return path to the POST/PUT.
     *
     * @return string
     */
    public function getHandler()
    {
        if (!$this->handler) {
            global $pool;
            return "{$pool->SysVar->siteUrl()}{$this->getPath()}";
        }
        return $this->handler;
    }

    /**
     * States whether the RouteTree has been saved or not.
     *
     * @return mixed
     */
    public function isSaved()
    {
        return $this->routeTreeSaved;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getRouteOptions()
    {
        //TODO Why are the flag options for route tree stored in [ ] ????
        $sql = "SELECT * FROM RouteOptions WHERE idRoute = {$this->getIdRoute()} ORDER BY parent ASC, itemOrder ASC";
        $routeOptions = DB::sql($sql);
        $result = [];
        foreach($routeOptions as $routeOption){
            $flag = $routeOption['flags'];
            if($flag != "[]")
                $result[] = "Status.".substr($routeOption['flags'],2,-2);
        }
        $result = array_merge(array_flip(array_flip($result)));


        return $result;
    }


    /******************************************   #INITIALIZATION#  ******************************************/


    /**
     * Binds the initial bit flags.
     *
     * @param Bits $dynamicFlags
     * @return $this
     */
    public function bindFlags(Bits $dynamicFlags)
    {
        $this->bits = $dynamicFlags;
        return $this;
    }

    /**
     * Binds the options that have been selected.
     *
     * @param BitString $selectedOptions
     * @return $this
     */
    public function bindSelectedOptions(BitString $selectedOptions)
    {
        $this->selectedOptions = $selectedOptions;
        return $this;
    }

    /**
     * Sets the RouteTree's preset categories.
     *
     * @param mixed $preset
     */
    public function setPreset($preset)
    {
        $this->preset = $preset;
    }

    /**
     * Sets the return path to the POST/PUT.
     * @param mixed $handler
     * @return $this
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * Should ONLY be called during Routing Commissioning.
     * Rebuilds the configuredFlags for a specific route.  The Flags is a collection of all the flags that the route can
     * set and will be used later to verify that the controller meets it's flag requirements from the RouteTree.  Also,
     * the configuredFlags defines the bit position for all the flags when they are added to the DynamicFlags Field. It
     * will not allow a new Flag to take over the bit position of another flag.
     *
     * @param $idroute
     */
    public static function rebuildConfiguredFlags($idroute)
    {
        
        
        $sql = "SELECT configuredFlags FROM RouteTrees WHERE idRoute = {$idroute}";
        $results = DB::sql($sql);
        
        echo "<pre>";
        print_r($results);
        if ($results&&sizeof($all = json_decode($results[0]['configuredFlags'], true))) {

        } else {
            $all = ['__bitLength'=>0];
        }
        $bitLength = $all['__bitLength'];
        $sql = "SELECT flags FROM RouteOptions WHERE idRoute = {$idroute}";
        $results = DB::sql($sql);

        if ($results) {

            foreach ($results as $result) {
                $flags = json_decode($result['flags'], true);
                foreach ($flags as $flag) {
                    if (!in_array($flag, $all, true)) {
                        $all[(string)++$bitLength] = $flag;
                    }
                }
            }
        }
        $all['__bitLength'] = $bitLength;
        $out = DB::cleanse(json_encode($all));
        $sql = "UPDATE RouteTrees SET configuredFlags = '{$out}', lastBitPosition = {$bitLength} WHERE idRoute = {$idroute}";
        DB::sql($sql);
    }


    /*********************************************   #TRIGGERS#  *********************************************/


    /**
     * Trigger fired when the RouteTree has been Updated on a successful POST/PUT request.
     *
     * @param \Closure $closure
     * @return \eprocess360\v3core\Controller\Trigger\Trigger
     */
    public function onUpdate($closure)
    {
        return $this->addTrigger(__FUNCTION__, $closure);
    }

}