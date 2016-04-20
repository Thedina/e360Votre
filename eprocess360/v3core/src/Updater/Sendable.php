<?php
/**
 * Created by PhpStorm.
 * User: kira
 * Date: 7/16/2015
 * Time: 10:31 AM
 */

namespace eprocess360\v3core\Updater;


class Sendable
{
    public function __construct()
    {
        $this->data = [];
    }
    public function getContents()
    {
        return $this->data;
    }
    public function getAttachment()
    {
    }
    public function getAttachmentFilePath()
    {
    }
    public function hasAttachment()
    {
        return false;
    }
}