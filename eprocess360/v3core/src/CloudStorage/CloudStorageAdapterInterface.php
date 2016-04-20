<?php

namespace eprocess360\v3core\CloudStorage;

/**
 * Interface CloudStorageAdapterInterface
 * Functions for accessing cloud storage service
 * @package eprocess360\v3core\CloudStorage
 */
interface CloudStorageAdapterInterface
{
    /*
     * Check if cloud resource exists.
     * @param $uri
     * @return bool
     * @throws CloudStorageException
     */
    public function exists($uri);

    /**
     * Get the file size in bytes of a cloud resource
     * @param $uri
     * @return int
     * @throws CloudStorageException
     */
    public function size($uri);

    /*
     * Get the corresponding public URL for a cloud resource.
     * @param $uri
     * @return string
     */
    public function getURL($uri);

    /*
     * Copy a cloud resource from one location to another.
     * @param $src
     * @param $dest
     * @throws CloudStorageException
     */
    public function copy($src, $dest);

    /*
     * Move a cloud resource from one location to another/change its identifier.
     * @param $src
     * @param $dest
     * @throws CloudStorageException
     */
    public function move($src, $dest);

    /*
     * Delete a cloud resource.
     * @param $uri
     * @throws CloudStorageException
     */
    public function delete($uri);

    /**
     * Upload program data to a cloud storage service.
     * @param $data
     * @param $uri
     * @param $is_public
     * @throws CloudStorageException
     */
    public function insert($data, $uri, $is_public = false);

    /**
     * Upload file to a cloud storage service.
     * @param $filepath
     * @param $uri
     * @param $is_public
     * @throws CloudStorageException
     */
    public function insertFromFile($filepath, $uri, $is_public = false);

    /*
     * Modify permissions for a cloud resource.
     * @param $uri
     * @param $public
     * @throws CloudStorageException
     */
    public function modPermissions($uri, $is_public = false);
}