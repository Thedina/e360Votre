<?php
use eprocess360\v3core\Form;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Model;

/**
 * Settings is a Controller/Module that is designed to configure, accept, and use Blocks to allow the User to edit the
 * configurations options that a Controller needs.  Settings will generate a Model of the data that it needs to save in
 * the database and all Settings will be saved to Controller_Settings in key/value pairs.
 */

/**
 * Interface Source
 * For now it is comprised of a simple array object, but in the future can use special things like SQL or dynamic
 * sources
 */
interface Source
{
    /**
     * @return \Generator options in key=>value format
     */
    public function getOptions();

    /**
     * Set an arbitrary list of available options
     * @param $array
     */
    public function setOptions($array);
}

/**
 * Interface Entry
 * Entry needs to be modified to accept a source
 */
interface Entry
{
    public function getSource();
    public function setSource(Source $source);

    /**
     * Critical function that allows for the serialization of the entire Entry specification into an array
     * {
     *    "name":"keydictName",
     *    "label":"Label Describing Keydict",
     *    "default":"Default Value",
     *    "value":"Current Value",
     *    "type":"text",
     *    "required":true,
     *    "class":"String",
     *    "validate":true,
     *    "id":"ControllerClass/Keydict"
     *    "source":{
     *       "options":{
     *          "default":"Default String",
     *          "special":"Special String"
     *       }
     *    }
     * }
     * @return array
     */
    public function getSerialization();
}

/**
 * Interface Variables
 * Gateway controller for Keydict that is responsible for a given Keydict for a given parent (typically Projects
 * Workflow Controller).
 */
interface Variables
{

}

/**
 * Interface Settings
 * A child Controller or Module that manages Settings for its parent.
 *
 * Settings needs to be able to render a Form that supports Sources.  The basic Form things we are using now need to be
 * upgraded to support this.  Basic Form will need to be able to render a Form from the serialization of Entry.
 */
interface Settings
{
    /**
     * Add a Keydict Entry to the Settings.  Settings will use this when building the form.
     * @param Entry $entry
     */
    public function addEntry(Entry $entry);

    /**
     * Get the Form object that represents the Entry collection in the Settings.  Needs to ultimately write its JSON
     * into the Response so it's accessible by the front end.  When used through a Block, it should be written in the
     * Block response.
     * @return Form
     */
    public function getForm();

    /**
     * Try to accept the Settings values sent from a POST or JSON accept request.  Throw exceptions on failure or such.
     * Optoinally pass the controlling block so that Settings knows how to process the response back..?
     * @param BlockSettings $block
     * @return $this
     */
    public function acceptResponse(BlockSettings $block = null);

    /**
     * Commit the Settings to the database
     */
    public function save();
}

/**
 * Class BlockSettings
 * Allows for a Settings object to be used by the Dashboard.  Settings should be able to listen for the post back to
 * an API.
 */
interface BlockSettings
{
    public function bindSettings(Settings $settings);
}