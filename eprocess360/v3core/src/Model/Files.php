<?php

namespace eprocess360\v3core\Model;
use eprocess360\v3core\Files\File;
use eprocess360\v3core\Files\Folder;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Model;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\Integer;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\FixedString256;
use eprocess360\v3core\Keydict\Entry\Datetime;
use eprocess360\v3core\Keydict\Entry\Bits8;
use eprocess360\v3core\Keydict\Entry\Bit;


class Files extends Model
{
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idFile', 'File ID'),
            IdInteger::build('idFolder', 'Folder ID')->joinsOn(Folders::model()),
            IdInteger::build('idUser', 'User (creator) ID'),
            FixedString128::build('fileName', 'File Name'),//TODO take off File and just leave name
            FixedString128::build('category', 'Category'),
            FixedString256::build('description', 'Description'),
            Integer::build('size', 'Size'),
            Bits8::make('flags',
                Bit::build(0, 'active', 'Active'),
                Bit::build(1, 'local', 'Local Storage')
            ),
            Datetime::build('dateCreated', 'Date Created'),
            Datetime::build('cloudDatetime', 'Cloud Datetime')
        )->setName('Files')->setLabel('Files');
    }


    /**
     * Update a file DB entry with category and description
     * @param Table $table
     * @param $category
     * @param $description
     * @return array|string
     * @throws Keydict\Exception\InvalidValueException
     * @throws Keydict\Exception\KeydictException
     */
    public static function edit(Table $table, $category, $description)
    {
        $table->category->set($category);
        $table->description->set($description);
        $table->update();
        return $table->sleep();
    }

    public static function downloadUrl(&$array)
    {
        global $pool;
        $folder = Folders::sqlFetch($array['idFolder']);
        $array['url'] = $pool->SysVar->get('siteUrl') . '/download/' . $array['idFile'] . '/' . $folder->hash->get();
    }

    /**
     * @param Folder $folder
     * @return array
     * @throws \Exception
     */
    public static function uploadFiles(Folder $folder)
    {
        $files = $folder->acceptUploads();
        $new = array();
        /** @var File $file */
        foreach ($files as $file) {
            /** @var Keydict $data */
            $data = $file->getData();
            $resultArray = $data->toArray();
            Files::downloadUrl($resultArray);
            $new[] = $resultArray;
        }
        return $new;
    }

    /**
     * @param $idFile
     * @return array|string
     * @throws \Exception
     */
    public static function getFileById($idFile)
    {
        $sqlResult = Files::sqlFetch($idFile);
        $resultArray = $sqlResult->visualize();
        Files::downloadUrl($resultArray);

        return $resultArray;
    }
}