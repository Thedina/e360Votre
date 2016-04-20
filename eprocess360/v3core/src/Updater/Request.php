<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 7/8/2015
 * Time: 10:53 AM
 */

namespace eprocess360\v3core\Updater;
use eprocess360\v3core\Updater;
use eprocess360\v3core\Updater\Module\SQLSync\Exception\InvalidAttachmentException;
use eprocess360\v3core\Updater\Module\SQLSync\Exception\InvalidRequestException;

/**
 * @property  int request_id
 * @property Updater updater
 */
class Request extends Sendable
{
    const STATUS_NEW = 0; // exists on originating server only
    const STATUS_RECEIVED = 1; // received by target
    const STATUS_SUPERSEDED = 2; // superseded by target
    const STATUS_COMPLETE = 3; // process chain complete
    const STATUS_FAILED = 4;

    /**
     * @param int $request_id
     * @return Request
     */
    public function __construct($request_id = 0)
    {
        parent::__construct();
        $this->data = [];
        $this->is_valid = false;
        $this->receipt = false;
        $this->parent = null;
        $this->updater = Updater::getInstance();
        if ($request_id) {
            $this->load($request_id);
        }
        return $this;
    }

    private function load($request_id = 0)
    {
        if ($request_id) {
            $results = sql("SELECT *, CAST(SUBSTR(request_id, -6) AS UNSIGNED) as origin_client_id FROM `{$this->updater->local_db}`.`{$this->updater->cfg_requests_table}` WHERE `request_id` = '{$request_id}' LIMIT 1");
            if (sizeof($results)) {
                $this->data = array_shift($results);
                if ($this->hasParent()) {
                    $this->loadParent();
                }
                $this->is_valid = true;
            }
        }
    }

    public function loadParent()
    {
        $this->parent = new Request($this->getParentId());
    }

    public function loadChild()
    {
        $this->child = new Request($this->getChildId());
    }

    public function isValid()
    {
        return $this->is_valid;
    }

    public function getContents()
    {
        return $this->data;
    }

    public static function statusLabels($case = null)
    {
        $labels = [
            0=>'New',
            1=>'Request Received',
            2=>'Response Received',
            3=>'Response Processed'
        ];

        if ($case === null) {
            return $labels;
        }
        if (!isset($labels[$case])) {
            throw new \Exception("statusLabels case {$case} out of range.");
        }

        return $labels[$case];
    }

    public function getStatus()
    {
        return $this->data['status'];
    }

    protected function getReceiptHash()
    {
        //var_dump($this->updater->client->getClientAuth().$this->getStatus().$this->hasAttachment().$this->getHash().$this->getRequestId());
        return md5($this->updater->client->getClientAuth().$this->getStatus().$this->hasAttachment().$this->getHash().$this->getRequestId().Updater::PRIVATE_KEY);
    }

    public function getReceipt()
    {
        if ($this->receipt) {
            return $this->receipt;
        }
        throw new \Exception("Receipt not available.");
    }

    public function __debugInfo()
    {
        return $this->data;
    }

    public function update($status, $attachment = null)
    {
        if (!$this->is_valid) throw new \Exception("Cannot update an invalid request.");
        if ($attachment === null) $attachment = $this->hasAttachment();
        $status = (int)$status;
        if ($status > -1 && $status < 4) {
            $attachment = (int)$attachment;
            sql($sql = "UPDATE `{$this->updater->local_db}`.`{$this->updater->cfg_requests_table}` SET `status` = {$status}, `attachment` = '{$attachment}',`lastupdated` = CURRENT_TIMESTAMP() WHERE `request_id` = {$this->request_id}");
            //echo $sql;
            $this->data['attachment'] = $attachment;
            $this->data['status'] = $status;
            $this->receipt = $this->getReceiptHash();
            //echo PHP_EOL;
            return $this;
        }
        throw new \Exception("Invalid mode to updateRequest");
    }

    public function storeStatus()
    {
        if (!$this->is_valid) throw new \Exception("Cannot update an invalid request.");
        $attachment = (int)$this->hasAttachment();
        $status = (int)$this->status;
        if ($status > -1 && $status < 4) {
            sql($sql = "UPDATE `{$this->updater->local_db}`.`{$this->updater->cfg_requests_table}` SET `status` = {$status}, `attachment` = '{$attachment}',`lastupdated` = CURRENT_TIMESTAMP() WHERE `request_id` = {$this->request_id}");
            return $this;
        }
        throw new \Exception("Invalid mode to storeStatus");
    }

    public function loadRaw($result_row)
    {
        $this->data = $result_row;
        $this->is_valid = true;
        return $this;
    }

    public static function next($case)
    {
        // verify that the case actually exists or throw exception
        if (!is_array($case))
            $case = [$case];
        foreach ($case as $c)
            self::statusLabels($c);

        $updater = Updater::getInstance();
        $cases = implode(',', $case);
        $results = sql("SELECT * FROM `{$updater->local_db}`.`{$updater->cfg_requests_table}` WHERE `status` IN ({$cases}) LIMIT 1");
        $request = new Request(0);
        if (sizeof($results)) {
            return $request->loadRaw(array_shift($results));
        }
        return $request;
    }

    public function storeAttachment($raw)
    {
        if (!$this->is_valid) throw new InvalidRequestException;
        file_put_contents($this->getAttachmentFilePath(), $raw);
        $this->update($this->data['status'], 1);
        return $this;
    }

    public function moduleExists()
    {
        return isset($this->updater->modules[$this->getModuleName()]);
    }

    public function getModuleName()
    {
        return $this->data['module'];
    }

    public function getModule()
    {
        return $this->updater->modules[$this->getModuleName()];
    }

    public function functionExists()
    {
        /** @var InterfaceModule $module */
        $module = $this->getModule();
        return $module->hasJsonMethod($this->getFunctionName());
    }

    public function getFunctionName()
    {
        return $this->data['function'];
    }

    public function getParentId()
    {
        return $this->data['parent'];
    }

    public function getParentHash()
    {
        return $this->data['parent_hash'];
    }

    public function getChildId()
    {
        return $this->data['child'];
    }

    public function getChildHash()
    {
        return $this->data['child_hash'];
    }

    public function hasParent()
    {
        return $this->data['parent'] == null ? false : true;
    }

    public function hasChild()
    {
        return $this->data['child'] == null ? false : true;
    }

    public static function fromHttpAcceptBody(HttpAccept $response)
    {
        $request = new Updater\Request();
        $request->loadRaw($response->getBody('request'));
        //var_dump($request->getContents(),$response->getBody('request'),$response->getClientAuth(),$request->getStatus(),$request->hasAttachment(),$request->getHash(),$request->getRequestId());
        $md5 = md5($response->getClientAuth().$request->getStatus().$request->hasAttachment().$request->getHash().$request->getRequestId().Updater::PRIVATE_KEY);
        echo $md5 .'.'. $response->getBody('receipt')['hash'];
        if ($md5 == $response->getBody('receipt')['hash']) {
            // validated
            return $request;
        }
        throw new \Exception("Receipt does not match request.");
    }

    /**
     * Update or store a new request.  Accepts attachments.
     *
     * @param HttpAccept $http_request
     * @return Request
     * @throws InvalidRequestException
     * @throws \Exception
     */
    public static function store(HttpAccept $http_request)
    {
        $updater = Updater::getInstance();
        $http_request->validateRequest();
        $http_request->authenticate();
        if ($http_request->isAuthenticated()) {
            // see if the request already exists
            $http_request->request->loadRaw($http_request->data['request']);
            $client_id = $http_request->getClientId();
            $request_id = $http_request->request->getRequestId();
            $status = $http_request->request->getStatus();
            $sql = "SELECT * FROM `{$updater->local_db}`.`{$updater->cfg_requests_table}` WHERE `request_id` = '{$request_id}'";
            $results = sql($sql);
            if (sizeof($results)) {
                // update request
                $stored_request = new Request(0);
                $stored_request = $stored_request->loadRaw(array_shift($results));
                if ($status > (int)$stored_request->status) {
                    // update is allowed to certain parts
                    $attachment = (int)$stored_request->hasAttachment();
                    if (!$stored_request->hasAttachment() && $http_request->hasAttachment()) {
                        // attachment allowed to be added
                        $stored_request->storeAttachment($http_request->getAttachment());
                        $attachment = 1;
                    }
                    $stored_request->update($status, $attachment);
                    return $stored_request->sign();
                } else {
                    throw new InvalidRequestException;
                }
            } else {
                // new request
                $request = $http_request->request;

                if (!$request->moduleExists())
                    throw new \Exception("Module '{$request->getModuleName()}' not supported.");
                /** @var InterfaceModule $module */
                $module = $request->getModule();
                if (!$request->functionExists()) {
                    throw new \Exception("Method '{$request->getFunctionName()}' not supported by '{$module->getModuleSpace()}'.");
                }

                $attachment = 0;
                if ($http_request->hasAttachment()) {
                    // attachment allowed to be added
                    $attachment = 1;
                }
                $set_parent = false;
                if ($request->hasParent()) {
                    // This request is in response to an older request
                    $parent_request = new Request($request->getParentId());
                    if ($parent_request->is_valid && $parent_request->getHash() == $request->getParentHash()) {
                        $set_parent = true;
                    } else {
                        throw new InvalidRequestException("Unable to validate parent.");
                    }
                }
                sql($sql = "INSERT INTO `{$updater->local_db}`.`{$updater->cfg_requests_table}` (`request_id`, `client_id`, `lastupdated`, `module`, `function`, `status`, `attachment`, `hash`, `parent`) VALUES ('{$request_id}',{$client_id}, current_timestamp(), '{$request->getModuleName()}', '{$request->getFunctionName()}', '{$status}', 0, '{$request->getHash()}', NULL)");
                $received_request = new Request($request_id);

                if ($set_parent) {
                    if (isset($parent_request)) {
                        $parent_request->setChild($received_request);
                    } else {
                        throw new \Exception("Request Parent unexpectedly unavailable.");
                    }
                }
                if ($attachment == 1) {
                    //var_dump($http_request);
                    //echo "request attachment size is: ".strlen($http_request->getAttachment()).PHP_EOL;
                    $received_request->storeAttachment(json_encode($http_request->getAttachment()));
                }
                return $received_request;
            }
        } else {
            throw new \Exception('Failed to validate request.');
        }
    }

    public function setChild(Request $child)
    {
        if (!$this->is_valid || !(int)$this->data['status'] === 1 ||!$child->is_valid) throw new InvalidRequestException;
        $this->data['child'] = $child->request_id;
        $sql = "UPDATE `{$this->updater->local_db}`.`{$this->updater->cfg_requests_table}` SET `child` = '{$child->request_id}', `status` = 2 WHERE `request_id` = '{$this->request_id}'";
        return $this;
    }

    public function setParent(Request $parent)
    {
        if (!$this->is_valid || !(int)$this->data['status'] === 1 ||!$parent->is_valid) throw new InvalidRequestException;
        $this->data['parent'] = $parent->request_id;
        $sql = "UPDATE `{$this->updater->local_db}`.`{$this->updater->cfg_requests_table}` SET `parent` = '{$parent->request_id}' WHERE `request_id` = '{$this->request_id}'";
        return $this;
    }

    /**
     * @param InterfaceModule $module
     * @param $function
     * @param bool $no_client
     * @return Request
     * @throws \Exception
     */
    public static function create(InterfaceModule $module, $function, $no_client = false)
    {
        $updater = Updater::getInstance();
        $class= get_class($module);

        if (!$module->hasJsonMethod($function)) throw new \Exception("Valid function required, using {$class}/{$function}.");
        $request_id = $updater->global_id->make();
        if ($no_client) {
            $client_id = 0;
        } else {
            $client_id = $updater->client->getClientId();
        }
        $module_name = $module->getModuleSpace();
        $status = self::STATUS_NEW;
        $attachment = 0;
        $hash = '';
        sql($sql = "INSERT INTO `{$updater->local_db}`.`{$updater->cfg_requests_table}` (`request_id`, `client_id`, `lastupdated`, `module`, `function`, `status`, `attachment`, `hash`) VALUES ('{$request_id}',{$client_id}, current_timestamp(), '{$module_name}', '{$function}', '{$status}', '{$attachment}', '{$hash}')");
        //echo $sql;
        $request = new Request($request_id);
        $request->updateHash();
        return $request;
    }

    /**
     * @return Request
     * @throws \Exception
     */
    public function updateHash()
    {
        $updater = Updater::getInstance();
        $request_id = $this->request_id;
        $hash_data = $this->data;
        unset($hash_data['hash']);
        $this->data['hash'] = substr(md5(implode(',',$hash_data).$updater->system->getSystemName()), 0, 16);
        $sql = "UPDATE `{$updater->local_db}`.`{$updater->cfg_requests_table}` SET `hash` = '{$this->data['hash']}' WHERE `request_id` = {$this->request_id}";
        sql($sql);
        return $this;
    }

    public function __get($key) {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        throw new \Exception("Key {$key} does not exist.");
    }

    /**
     * @return string
     * @throws InvalidRequestException
     */
    public function getAttachment()
    {
        return json_decode(file_get_contents($this->getAttachmentFilePath()), true);
    }

    /**
     * @return string
     * @throws InvalidRequestException
     */
    public function getAttachmentFilePath()
    {
        if (!$this->is_valid) throw new InvalidRequestException;
        return $this->updater->cfg_attachment_path . '/' . $this->getRequestId();
    }

    /**
     * @return bool
     * @throws InvalidRequestException
     */
    public function hasAttachment()
    {
        if (!$this->is_valid) throw new InvalidRequestException;
        if ((int)$this->data['attachment'] && $this->getAttachmentFilePath()) {
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getRequestId()
    {
        return $this->data['request_id'];
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->data['hash'];
    }

    /**
     * @return int
     */
    public function getClientId()
    {
        return $this->data['client_id'];
    }

    /**
     * @return string
     */
    public function getLastUpdated()
    {
        return $this->data['lastupdated'];
    }

}