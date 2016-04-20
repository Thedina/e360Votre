<?php
namespace eprocess360\v3core\CloudStorage;
use Exception;

/**
 * Class GoogleCloudStorageAdapter
 * Implementation of cloud storage interface via Google Cloud Store
 * @package eprocess360\v3core\CloudStorage
 */
class GoogleCloudStorageAdapter implements CloudStorageAdapterInterface
{
    const CHUNK_SIZE = 32 * 1048576;
    private $storage;
    private $uploadBucket;

    /**
     * Get the ACL string for public access if $public is true, otherwise for
     * no public access
     * @param bool $public
     * @return string
     */
    private function getACLString($public) {
        return $public ? 'publicRead' : 'private';
    }

    /**
     * @param \Google_Client $client
     * @param string $uploadBucket
     */
    public function __construct(\Google_Client $client, $uploadBucket) {
        $this->client = $client;
        $this->storage = new \Google_Service_Storage($client);
        $this->uploadBucket = $uploadBucket;
    }

    /*
     * Check if file exists on GCS - this version really just tries to get it
     * and throws an exception if it can't.
     * @param $uri
     * @throws Exception
     */
    public function exists($uri) {
        try {
            $this->storage->objects->get($this->uploadBucket, $uri);
        }
        catch (\Google_Service_Exception $e) {
            throw new Exception("GoogleCloudStorageAdapter->exists(): Google Service API exception {$e->getMessage()}");
        }
    }

    /**
     * Get the size in bytes of a file on GCS
     * @param $uri
     * @return int
     * @throws Exception
     */
    public function size($uri) {
        try {
            $obj = $this->storage->objects->get($this->uploadBucket, $uri);
            return (int)$obj->getSize();
        }
        catch(\Google_Service_Exception $e) {
            throw new Exception("GoogleCloudStorageAdapter->size(): Google Service API exception {$e->getMessage()}");
        }
    }

    /*
     * Get a public URL (by building a storage.googleapis.com subdomain URL)
     * for a file on GCS
     * @param $uri
     * @return mixed
     */
    public function getURL($uri) {
        return 'https://storage.googleapis.com/'.$this->uploadBucket.'/'.$uri;
    }

    /**
     * Get a public URL (by requesting the media link through the API) for a
     * file on GCS
     * @param $uri
     * @return mixed
     * @throws Exception
     */
    public function getURLFromAPI($uri) {
        try {
            $obj = $this->storage->objects->get($this->uploadBucket, $uri);
            return $obj->getMediaLink();
        }
        catch (\Google_Service_Exception $e) {
            throw new Exception("GoogleCloudStorageAdapter->getURLFromAPI(): Google Service API exception {$e->getMessage()}");
        }
    }

    /**
     * Copy a file from one GCS path to another
     * @param $src
     * @param $dest
     * @throws Exception
     */
    public function copy($src, $dest) {
        try {
            $obj = new \Google_Service_Storage_StorageObject();
            $this->storage->objects->rewrite($this->uploadBucket, $src, $this->uploadBucket, $dest, $obj);
        }
        catch (\Google_Service_Exception $e) {
            throw new Exception("GoogleCloudStorageAdapter->copy(): Google Service API exception {$e->getMessage()}");
        }
    }

    /**
     * Move a file from one GCS path to another/change it's path. Implemented
     * as a copy followed by a delete...
     * @param $src
     * @param $dest
     * @throws Exception
     */
    public function move($src, $dest) {
        try {
            $this->copy($src, $dest);
            $this->delete($src);
        }
        catch(\Google_Service_Exception $e) {
            throw new Exception("GoogleCloudStorageAdapter->move(): Google Service API exception {$e->getMessage()}");
        }
    }

    /**
     * Delete a file from GCS
     * @param $uri
     * @throws Exception
     */
    public function delete($uri) {
        try {
            $this->storage->objects->delete($this->uploadBucket, $uri);
        }
        catch (\Google_Service_Exception $e) {
            throw new Exception("GoogleCloudStorageAdapter->delete(): Google Service API exception {$e->getMessage()}");
        }
    }

    /**
     * Insert a file into GCS from memory
     * @param $data
     * @param $uri
     * @param bool|false $is_public
     * @throws Exception
     */
    public function insert($data, $uri, $is_public = false) {
        $acl = $this->getACLString($is_public);

        try {
            $obj = new \Google_Service_Storage_StorageObject();
            $obj->setName($uri);
            $obj->setContentType('application/octet-stream');

            $this->storage->objects->insert($this->uploadBucket, $obj, ['uploadType' => 'media', 'predefinedAcl' => $acl, 'data' => $data]);
        }
        catch (\Google_Service_Exception $e) {
            throw new Exception("GoogleCloudStorageAdapter->insert(): Google Service API exception {$e->getMessage()}");
        }
    }

    /**
     * Insert a file into GCS streaming from a local file path
     * @param $filepath
     * @param $uri
     * @param bool|false $is_public
     * @throws Exception
     */
    public function insertFromFile($filepath, $uri, $is_public = false) {
        $acl = $this->getACLString($is_public);

        try {
            $obj = new \Google_Service_Storage_StorageObject();
            $obj->setName($uri);
            $obj->setContentType('application/octet-stream');
            $obj->setBucket($this->uploadBucket);

            $this->client->setDefer(true);

            /**
             * @var \Google_Http_Request $request
             */
            $request = $this->storage->objects->insert($this->uploadBucket, $obj, ['uploadType' => 'resumable', 'predefinedAcl' => $acl]);

            $media = new \Google_Http_MediaFileUpload($this->client, $request, 'application/octet-stream', null, true, self::CHUNK_SIZE);
            $media->setFileSize(\filesize($filepath));
            $done = false;
            $f = \fopen($filepath, 'rb');

            while (!$done && !\feof($f)) {
                $chunk = \fread($f, self::CHUNK_SIZE);
                $done = $media->nextChunk($chunk);
            }

            \fclose($f);

            $this->client->setDefer(false);
        }
        catch (\Google_Service_Exception $e) {
            throw new Exception("GoogleCloudStorageAdapter->insertFromFile(): Google Service API exception {$e->getMessage()}");
        }
    }

    /**
     * Set permissions on a GCS file to public or private
     * @param $uri
     * @param bool|false $is_public
     * @throws Exception
     */
    public function modPermissions($uri, $is_public = false) {
        /* TODO Permissions work when using getURLFromAPI() URL but not storage.googleapis.com URL */
        $acl = $this->getACLString($is_public);

        $obj = new \Google_Service_Storage_StorageObject();
        $obj->setContentType('application/octet-stream');

        try {
            $this->storage->objects->update($this->uploadBucket, $uri, $obj, ['predefinedAcl' => $acl]);
        }
        catch (\Google_Service_Exception $e) {
            throw new Exception("GoogleCloudStorageAdapter->modPermissions(): Google Service API exception {$e->getMessage()}");
        }
    }
}