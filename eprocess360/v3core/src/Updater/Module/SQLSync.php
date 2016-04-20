<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 7/9/2015
 * Time: 10:51 AM
 */

namespace eprocess360\v3core\Updater\Module;
use eprocess360\v3core\Updater;
use eprocess360\v3core\Updater\Exceptions\ModuleNotReadyException;
use eprocess360\v3core\Updater\HttpAccept;
use eprocess360\v3core\Updater\Module\SQLSync\Exception\InvalidAttachmentException;
use eprocess360\v3core\Updater\Module\SQLSync\Exception\InvalidRequestException;
use eprocess360\v3core\Updater\Request;
use eprocess360\v3core\Updater\Response;

/**
 * @property string index_table Location to store the SQLSync Indexes
 * @property bool delete_on_column_missing_in_base
 * @property string tablename_cleanse
 * @property string column_ignoreif
 */
class SQLSync implements Updater\InterfaceModule
{

    protected $module_space;

    public function __construct(Updater $updater)
    {
        $this->updater = $updater;
        $this->module_name = 'SQL Sync';
        $this->module_space = 'sqlsync';
    }

    public function getModuleSpace()
    {
        return $this->module_space;
    }

    public function hasMenu()
    {
        return true;
    }

    public function getMenuOptions()
    {
        return [
            'label'=>   'SQL Sync',
            'items'=>   [
                [
                    'method'=>  'Sync Structure',
                    'label'=>   '/sync-structure'
                ]
            ]
        ];
    }

    public function htmlMethods()
    {
        global $twig;
        $html_methods = [
            ''=>function() {
                // display SQLSync status
            },
            '/rebuild'=>function() {
                // rebuild index
            },
            '/sync-structure' => function() use ($twig) {
                if (isset($_POST['confirm'])) {
                    if ($this->updater->getIsMaster()) throw new \Exception("Master cannot sync with itself.");
                    $this->indexLocalDatabase();
                    $attachment = $this->serializeLocalDatabase();
                    $request = Updater\Request::create($this, '/diff-structure');
                    $request->storeAttachment($attachment);
                    $response = $this->updater->sendRequestFacade($request);
                    return $twig->render('updater.request.view.html.twig', [
                        'menus'=>$this->updater->getMenu(),
                        'request'=>$response
                    ]);
                }
                return $twig->render('updater.request.new.html.twig', [
                    'menus'=>$this->updater->getMenu(),
                    'request'=>[
                        'module'=>$this,
                        'function'=>'/diff-structure',
                        'summary'=>'Synchronize this server\'s structure with the master'
                    ]
                ]);
            }
        ];

        return $html_methods;
    }

    public function hasJsonMethod($function)
    {
        $methods = $this->jsonMethods();
        return isset($methods[$function]);
    }

    public function jsonMethods()
    {
        $updater = Updater::getInstance();
        $methods = [
            '/new' => function () {

            },
            '/diff-structure' => function ($args) {
                /*  /updater/api/sqlsync/diff-structure
                 *  Request to perform a diff against the current system with the
                 *  associated package, to make the associated package like the
                 *  current system.  Request is expected to contain an attachment.
                 *
                 * This function can only activate by a local next() call.
                 */
                try {
                    $request = new Request($args->idrequest);
                    if (!$request->isValid()) throw new InvalidRequestException("Request invalid.");
                    if (!$request->hasAttachment()) throw new InvalidAttachmentException("Attachment is missing when required by function.");
                    $origin_attachment = $request->getAttachment();
                    $origin_mode = $origin_attachment['mode'];
                    $diff = $this->diffSerializations($this->serializeLocalDatabase(), $origin_attachment);
                    switch ($origin_mode) {
                        case 'diff-only':


                            break;
                        default:
                    }
                } catch (\Exception $e) {
                    if (isset($request) && $request->is_valid) {
                        $request->update(Request::STATUS_FAILED);
                    }
                    return Updater::JSON_ERROR($e);
                }
            },
            '/do-sync-structure' => function () {
                /*
                 * Using the request attachment, change the structure of the local database
                 */
                try {
                    $httpAccept = HttpAccept::fromPhpInput();
                    $httpAccept->validateRequest();
                    $request = Request::store($httpAccept);
                    if (!$request->hasAttachment()) {
                        throw new InvalidAttachmentException("Attachment is missing when required by function.");
                    }

                    $response = new Response($httpAccept);
                    $response->addReceipt($request->update(Request::STATUS_COMPLETE));
                    $response->sign();
                    $package = $response->getPackage();
                    return json_encode($package);
                } catch (\Exception $e) {
                    if (isset($request) && $request->is_valid) {
                        $request->update(Request::STATUS_FAILED);
                    }
                    return Updater::JSON_ERROR($e);
                }
            }
        ];
        return $methods;
    }

    public function ready()
    {
        if (!isset($this->index_table)) {
            throw new ModuleNotReadyException("Setting 'index_table' does not exist.");
        }

        $sql = "DESCRIBE `{$this->index_table}`";
        $results = sql($sql);
        if (!$results) {
            throw new ModuleNotReadyException("Table `{$this->index_table}` does not exist.");
        }
    }

    public function register($settings_array)
    {
        $save = [];
        $defaults = $this->defaultSettings();
        foreach ($settings_array as $key=>$value) {
            if (isset($defaults[$key])) {
                $save[$key] = $value;
            }
        }
        if (sizeof($save)==sizeof($settings_array)) {
            $this->updater->saveModuleSettings($this, $settings_array);
        } else {
            throw new \Exception("Failed to save settings: Invalid signature.");
        }
    }

    public function defaultSettings() {
        return [
            'index_table' => 'updater_index',
            'tablename_cleanse' => '/[^a-z0-9_]/',
            'column_ignoreif' => '/_[0-9]+$/',
            'delete_on_column_missing_in_base' => true
        ];
    }

    public function getModuleSubSpace()
    {
        return '/sqlsync';
    }

    public function unregister()
    {
        $this->updater->removeModuleSettings($this);
    }

    public function __get($key)
    {
        if ($this->updater->issetModuleSetting($this, $key)) {
            return $this->updater->getModuleSetting($this, $key);
        }
        throw new \Exception("Key {$this->getModuleSpace()}\\{$key} does not exist.");
    }

    public function __isset($key)
    {
        if ($this->updater->issetModuleSetting($this, $key)) {
            return true;
        }
        return false;
    }

    public function dropTables()
    {
        sql("DROP TABLE IF EXISTS `{$this->updater->local_db}`.`{$this->index_table}`");
    }

    public function createTables()
    {
        sql($sql = "CREATE TABLE `{$this->updater->local_db}`.`{$this->index_table}` (
          `tablename` VARCHAR(64) NOT NULL,
          `isindex` TINYINT(1) UNSIGNED DEFAULT 0,
          `hash` VARCHAR(32) NOT NULL,
          `lastsync` TIMESTAMP,
          `lastindex` TIMESTAMP,
          `mode` TINYINT(1) UNSIGNED DEFAULT 0,
          PRIMARY KEY (tablename, isindex)
        ) CHAR SET utf8");
    }

    public function hasManualSettings()
    {
        return true;
    }

    public function availableSettings()
    {
        return [
            'index_table' => 'Index Table',
            'tablename_cleanse' => 'Table Name RegEx',
            'column_ignoreif' => 'Ignore Columns If',
            'delete_on_column_missing_in_base' => 'Delete if Missing on Master'
        ];
    }

    public function getModuleName()
    {
        return $this->module_name;
    }

    private function indexLocalDatabase()
    {
        // Do db existence checks beforehand!
        $results = sql("SHOW TABLE STATUS FROM `{$this->updater->local_db}`");
        if (sizeof($results)) {
            foreach ($results as $table) {
                if (!$this->filterIgnoreTable($table['Name']))
                    $this->indexLocalTable($table['Name']);
            }
            return true;
        }
        return false;
    }

    private function hashDescribeTable($tablename)
    {
        $tablename = preg_replace($this->tablename_cleanse,'',$tablename);
        $results = sql("DESCRIBE `{$this->updater->local_db}`.`{$tablename}`");
        $results = $this->filterIgnoreColumnsFromResults($results);
        return $this->hashDescribeTableFromResults($results);
    }

    private function hashDescribeTableIndex($tablename)
    {
        $tablename = preg_replace($this->tablename_cleanse,'',$tablename);
        $results = sql($sql = "SHOW INDEX FROM `{$this->updater->local_db}`.`{$tablename}`");
        $results = $this->filterIgnoreIndexColumnsFromResults($results);
        return $this->hashDescribeTableFromResults($results);
    }

    private function hashDescribeTableFromResults($results)
    {
        $out = [];
        if (is_array($results) && sizeof($results)) {
            foreach ($results as $result) {
                if (!preg_match($this->column_ignoreif, $result['Field'])) {
                    $out[] = implode('#', $result);
                }
            }
        }
        return md5(implode('#',$out));
    }

    private function filterIgnoreColumnsFromResults($results)
    {
        $out = [];
        if (is_array($results) && sizeof($results)) {
            foreach ($results as $result) {
                if (!preg_match($this->column_ignoreif,$result['Field'])) {
                    $out[] = $result;
                }
            }
        }
        return $out;
    }

    private function filterIgnoreIndexColumnsFromResults($results)
    {
        if (is_array($results) && sizeof($results)) {
            foreach ($results as $key=>$result) {
                unset($results[$key]['Collation'], $results[$key]['Cardinality'], $results[$key]['Sub_part'], $results[$key]['Packed'], $results[$key]['Null'], $results[$key]['Comment'], $results[$key]['Index_comment']);
            }
        }

        return $results;
    }

    /** todo update for new module system
     * @param string $tablename
     * @return bool
     */
    private function filterIgnoreTable($tablename)
    {
        if ($tablename == $this->updater->cfg_auth_table || $tablename == $this->index_table) {
            return true;
        }
        return false;
    }

    private function indexLocalTable($tablename)
    {
        $hash = $this->hashDescribeTable($tablename);
        $hash_index = $this->hashDescribeTableIndex($tablename);
        $result = sql("INSERT INTO `{$this->updater->local_db}`.`{$this->index_table}` (`tablename`, `isindex`, `hash`, `lastindex`, `lastsync`)
                VALUES ('{$tablename}', 0,'{$hash}', CURRENT_TIMESTAMP(), NULL)
                ON DUPLICATE KEY UPDATE `tablename` = '{$tablename}', `isindex` = 0, `hash` = '{$hash}', `lastindex` = CURRENT_TIMESTAMP(), `lastsync` = `lastsync`") &&
            sql("INSERT INTO `{$this->updater->local_db}`.`{$this->index_table}` (`tablename`, `isindex`, `hash`, `lastindex`, `lastsync`)
                VALUES ('{$tablename}', 1, '{$hash_index}', CURRENT_TIMESTAMP(), NULL)
                ON DUPLICATE KEY UPDATE `tablename` = '{$tablename}', `isindex` = 1, `hash` = '{$hash_index}', `lastindex` = CURRENT_TIMESTAMP(), `lastsync` = `lastsync`");
        if (!$result) {
            throw new \Exception("Failed to execute SQL.");
        } else {
            return true;
        }
    }

    private function serializeLocalDatabase($asarray = false)
    {
        // Do db existence checks beforehand!
        $results = sql("SHOW TABLE STATUS FROM `{$this->updater->local_db}`");
        if (sizeof($results)) {
            $out = [];
            $i = 0;
            foreach ($results as $table) {
                if (!$this->filterIgnoreTable($table['Name'])) {
                    $i++;
                    $out[$table['Name']] = $this->serializeLocalTable($table['Name'], true);
                }
            }
            $results = [
                'tables'=>$out,
                'table_count'=>$i
            ];
            if ($asarray) {
                return $results;
            }
            return json_encode($results);
        }
        throw new \Exception('Could not fetch table information');
    }

    private function serializeLocalTable($tablename, $asarray = false)
    {
        $tablename = preg_replace($this->tablename_cleanse,'',$tablename);

        $results = sql($sql = "DESCRIBE `{$this->updater->local_db}`.`{$tablename}`");
        if ($results === false || sizeof($results) == 0) {
            throw new \Exception("Could not fetch table information using: {$sql}");
        }
        $results = $this->filterIgnoreColumnsFromResults($results);
        $hash = $this->hashDescribeTableFromResults($results);
        if (!$current_results = sql($sql = "SELECT * FROM `{$this->updater->local_db}`.`{$this->index_table}` WHERE `tablename` = '{$tablename}' AND `isindex` = 0")) {
            throw new \Exception("Failed to get current table information for {$tablename} using: {$sql}\r\n");
        }
        $current = _f($current_results);


        $results_index = sql($sql = "SHOW INDEX FROM `{$this->updater->local_db}`.`{$tablename}`");
        if ($results_index === false) {
            throw new \Exception("Could not fetch table index information using: {$sql}");
        }
        $results_index = $this->filterIgnoreIndexColumnsFromResults($results_index);
        $hash_index = $this->hashDescribeTableFromResults($results_index);
        $current_index = _f(sql("SELECT * FROM `{$this->updater->local_db}`.`{$this->index_table}` WHERE `tablename` = '{$tablename}' AND `isindex` = 1"));


        $results = [
            'tablename'=>$current['tablename'],
            'real_hash'=>$hash,
            'stored_hash'=>$current['hash'],
            'real_hash_index'=>$hash_index,
            'stored_hash_index'=>$current_index['hash'],
            'lastsync'=>$current['lastsync'],
            'lastindex'=>$current['lastindex'],
            'mode'=>$current['mode'],
            'columns'=>$results,
            'indexes'=>$results_index
        ];
        if ($asarray) {
            return $results;
        }
        return json_encode($results);
    }

    private function diffSerializations($base, $delta)
    {

        $base_tables = $base['tables'];
        $delta_tables = $delta['tables'];
        $missing_from_base = [];
        $missing_from_delta = [];
        $diff_from_base = [];
        foreach ($base_tables as $key=>$base_table) {
            if (isset($delta_tables[$key])) {
                $table_diff = $base_tables[$key]['real_hash'] == $delta_tables[$key]['real_hash'];
                $index_diff = $base_tables[$key]['real_hash_index'] == $delta_tables[$key]['stored_hash_index'];
                if ($table_diff && $index_diff) {
                    // table is the same according to hashes
                } else {
                    $diff_from_base[$key] = $this->diffTables($base_tables[$key],$delta_tables[$key]);
                }
            } else {
                $missing_from_delta[$key] = $base_table;
            }
        }
        return $diff_from_base;
    }


    /**
     * @param $base_table
     * @param $delta_table
     * @param array $options
     */
    private function syncTables($base_table, $delta_table, $options = [])
    {
        /** todo fix temp patch */
        $options = ['delete_on_column_missing_in_base'=>$this->delete_on_column_missing_in_base, $options];
        $diff = $this->diffSerializations($base_table, $delta_table);
        // var_dump($diff);
        $sql = [];
        foreach ($diff as $tablename=>$table) {
            // process deletions
            if ($options['delete_on_column_missing_in_base']) {
                if (sizeof($table['exists_delta'])) {
                    foreach ($table['exists_delta'] as $column) {
                        $sql['DELETE'][] = "ALTER TABLE {$tablename} DROP COLUMN {$column}";
                    }
                }
            }

            $meta_builder = function ($column_position, $diff_meta_keys = false) use ($table) {
                $changes = [];
                $changes[] = strtoupper($table['base_definitions'][$column_position]['Type']);

                if (!$diff_meta_keys || isset($diff_meta_keys['Null'])) {
                    $changes[] = strtoupper($diff_meta_keys['Null']) == 'YES' ? 'NULL' : 'NOT NULL';
                }
                if (isset($diff_meta_keys['Default'])) {
                    $changes[] = "DEFAULT '{$diff_meta_keys['Default']}'";
                }
                return implode(' ', $changes);
            };

            $index_drop = function ($tablename, $table, $index_key, $index) {
                if ($index_key == 'PRIMARY') {
                    return "ALTER {$tablename} DROP PRIMARY KEY";
                }
                return "ALTER {$tablename} DROP INDEX {$index_key}";
            };

            $index_create = function ($tablename, $table, $index_key, $index) {
                $index_on = '`'.implode('`,`',$index['Column_name']).'`';
                if ($index_key == 'PRIMARY') {
                    return "ALTER {$tablename} ADD PRIMARY KEY ({$index_on})";
                }
                $unique = $index['Non_unique']? '':'UNIQUE ';
                return "ALTER {$tablename} ADD {$unique}INDEX {$index_key} ({$index_on})";
            };

            // process alter meta
            if (sizeof($table['diff_delta'])) {
                foreach ($table['diff_delta'] as $column_position=>$diff_meta_keys) {
                    $changes = $meta_builder($column_position, $diff_meta_keys);
                    $sql['ALTER'][] = "ALTER TABLE {$tablename} MODIFY COLUMN {$table['base_definitions'][$column_position]['Field']} {$changes}";
                }
            }

            // process create/move columns
            if (sizeof($table['exists'])) {
                $column_adjustment = 0;
                foreach ($table['exists'] as $base_column_position => $delta_column_position) {
                    $delta_column_position_adjusted = $column_adjustment + $delta_column_position;
//                    echo "Analyze '{$table['base_definitions'][$base_column_position]['Field']}' {$base_column_position} => '{$table['base_definitions'][$delta_column_position]['Field']}' {$delta_column_position}; adj. '{$table['base_definitions'][$delta_column_position_adjusted]['Field']}' {$delta_column_position_adjusted}\r\n";
                    if ($delta_column_position == -1) {
                        $changes = $meta_builder($base_column_position);
                        if ($base_column_position==0) {
                            $position = 'FIRST';
                        } else {
                            $column_adjustment++;
                            $column_position_before = $base_column_position - $column_adjustment;
                            $position = "AFTER `{$table['base_definitions'][$column_position_before]['Field']}`";
                        }
                        $sql['ALTER'][] = "ALTER TABLE {$tablename} ADD COLUMN {$table['base_definitions'][$base_column_position]['Field']} {$changes} {$position}";
//                        echo ".. -1\r\n";
//                        echo "result ".end($sql['ALTER'])."\r\n";
                    } elseif ($base_column_position != $delta_column_position_adjusted) {
                        $changes = $meta_builder($base_column_position, $table['diff_delta'][$base_column_position]);
                        if ($base_column_position==0) {
                            $position = 'FIRST';
                        } else {
                            //if ($base_column_position<$delta_column_position_adjusted)
                            $column_adjustment++;
                            $column_position_before = $base_column_position - 1;
                            $position = "AFTER `{$table['base_definitions'][$column_position_before]['Field']}`";
                        }
                        $sql['ALTER'][] = "ALTER TABLE {$tablename} MODIFY COLUMN {$table['base_definitions'][$base_column_position]['Field']} {$changes} {$position}";
//                        echo ".. {$base_column_position} != {$delta_column_position_adjusted}\r\n";
//                        echo "result ".end($sql['ALTER'])."\r\n";
                    }
                }
            }
            // process index adjustments
            foreach ($table['exists_delta_indexes'] as $index_key=>$index) {
                $sql['INDEX'][] = $index_drop($tablename, $table, $index_key, $index);
            }

            foreach ($table['diff_delta_indexes'] as $index_key=>$index) {
                if (isset($table['indexes_delta'][$index_key])) {
                    // only if it exists on delta
                    $sql['INDEX'][] = $index_drop($tablename, $table, $index_key, $index);
                }
                $sql['INDEX'][] = $index_create($tablename, $table, $index_key, $index);
            }

        }

        $out = [];
        if (is_array($sql)) {
            foreach ($sql as $cname=>$component) {
                $out[$cname] = [];
                if (is_array($component)) {
                    foreach ($component as &$c) {
                        $replaced = false;
                        foreach ($out[$cname] as &$c2) {
                            $l = min(strlen($c),strlen($c2));
                            if (substr($c,0,$l) == substr($c2,0,$l)) {
                                if (strlen($c) < strlen($c2)) {
                                    $c = $c2;
                                } else {
                                    $c2 = $c;
                                }
                                $replaced = true;
                                break;
                            }
                        }
                        if (!$replaced) {
                            $out[$cname][] = $c;
                        }
                    }
                }
            }
        }
    }

    private function diffTables($base_table, $delta_table)
    {

        $table_diff = $base_table['real_hash'] != $delta_table['real_hash'];
        $index_diff = $base_table['real_hash_index'] != $delta_table['stored_hash_index'];
        $prepareIndexes = function ($serialRaw) {
            $indexes = [];
            if (is_array($serialRaw) && sizeof($serialRaw)) {
                foreach ($serialRaw as $indexElement) {
                    $pos = (int)$indexElement['Seq_in_index'] - 1;
                    if ($pos==0) {
                        $indexElement['Table'] = [$indexElement['Table']];
                        $indexElement['Column_name'] = [$indexElement['Column_name']];
                        unset($indexElement['Seq_in_index']);
                        $indexes[$indexElement['Key_name']] = $indexElement;
                    } else {
                        $indexes[$indexElement['Key_name']]['Table'][] = $indexElement['Table'];
                        $indexes[$indexElement['Key_name']]['Column_name'][] = $indexElement['Column_name'];
                    }

                }
            }
            return $indexes;
        };

        $exists_definition = $base_table_columns = $base_table['columns'];
        $delta_table_columns = $delta_table['columns'];
        $diff_delta = [];
        $exists_delta = [];
        $exists = [];
        $delta_diff_indexes_additions = [];
        $delta_diff_indexes_deletions = [];

        if ($index_diff) {
            $base_table_indexes = $prepareIndexes($base_table['indexes']);
            $delta_table_indexes = $prepareIndexes($delta_table['indexes']);

            foreach ($base_table_indexes as $base_index_key=>$base_index) {
                if (isset($delta_table_indexes[$base_index_key]) && $base_index === $delta_table_indexes[$base_index_key]) {
                    // already exists in delta and is the same
                    //$update = false;
                } else {
                    $delta_diff_indexes_additions[$base_index_key] = $base_index;
                }
            }
            foreach ($delta_table_indexes as $delta_index_key=>$delta_index) {
                if (!isset($delta_diff_indexes_additions[$delta_index_key]) && !isset($base_table_indexes[$delta_index_key])) {
                    $delta_diff_indexes_deletions[$delta_index_key] = $delta_index;
                }
            }
        }

        if ($table_diff) {
            if (!$base_table_columns || !$delta_table_columns)
                throw new \Exception('Bad table input.');
            // determine existence
            foreach ($base_table_columns as $base_position=>$base_column) {
                $exists_position = -1;
                foreach ($delta_table_columns as $delta_position=>$delta_column) {
                    if ($base_column['Field'] == $delta_column['Field']) {
                        $exists_position = $delta_position;
                        foreach ($base_column as $key=>$base_column_meta) {
                            if ($base_column[$key] != $delta_column[$key]) {
                                $diff_delta[$base_position][$key] = $base_column[$key];
                            }
                        }
                        break;
                    }
                }
                $exists[$base_position] = $exists_position;

            }

            foreach ($delta_table_columns as $delta_position=>$delta_column) {
                $exists_position = -1;
                foreach ($base_table_columns as $base_position=>$base_column) {
                    if ($base_column['Field'] == $delta_column['Field']) {
                        $exists_position = 1;
                        break;
                    }
                }
                if ($exists_position == -1) {
                    $exists_delta[] = $delta_column['Field'];
                }
            }
        }

        return [
            'diff_delta' => $diff_delta,
            'exists_delta' => $exists_delta,
            'diff_delta_indexes' => $delta_diff_indexes_additions,
            'exists_delta_indexes' => $delta_diff_indexes_deletions,
            'exists' => $exists,
            'base_definitions' => $exists_definition,
            'indexes' => $base_table['indexes'],
            'indexes_delta' => $delta_table['indexes']
        ];
    }
}