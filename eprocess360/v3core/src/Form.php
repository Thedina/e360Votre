<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/7/2015
 * Time: 8:09 PM
 */

namespace eprocess360\v3core;

use eprocess360\v3core\Keydict\Field;
use Exception;

/**
 * Class Form
 * Setup, render, and get changes.  Form implements a Keydict to manage and store data, however it does not have the
 * ability to directly store data back to the database.
 *
 * @package eprocess360\v3core
 */
class Form
{
    /** @var Keydict */
    private $keydict;
    private $description;
    private $handler;
    private $project;
    private $template;
    private $public = false;
    protected $controller;
    protected $idController;
    protected $name;
    protected $label;
    protected $flags;

    /**
     * Create a Form with the given name and twig template.  A Keydict is created to hold the Form's available fields
     * @param $idController
     * @param $name
     * @param $label
     * @param $template
     * @param $flags
     */
    public function __construct($idController, $name, $label, $template, $flags) {
        $this->setIdController($idController);
        $this->setName($name);
        $this->setLabel($label);
        $this->setFlags($flags);
        $this->setTemplate($template);
        $this->template = $template;
        $this->keydict = new Keydict();
    }

    /**
     * @param $componentID
     * @return $this
     * @throws Exception
     */
    protected function setIdController($idController)
    {
        if ($idController < 0) {
            throw new Exception("Cannot set idController for Mappable to 0 or less.");
        }
        $this->idController = (int)$idController;
        return $this;
    }

    /**
     * @param $flags
     * @return $this
     */
    protected function setFlags($flags)
    {
        $this->flags = $flags;
        return $this;
    }

    /**
     * @param int $idController
     * @param string $name
     * @param string $label
     * @param string $template
     * @param int $flags
     * @return static
     */
    public static function build($idController, $name, $label, $template = 'form.default.html.twig', $flags = 0)
    {
        return new static($idController, $name, $label, $template, $flags);
    }

    /**
     * Set the form to accept the given Entries
     * @param Field[]|Form[] $entries
     * @return $this
     * @throws \Exception
     */
    public function accepts(...$entries) {
        foreach ($entries as $entry) {
            if ($entry instanceof Form) {
                $this->forms[$entry->getName()] = $entry;
                foreach ($entry->getKeydict()->allFields() as $field) {
                    $this->keydict->add($field);
                }
            } elseif ($entry instanceof Field) {
                $this->keydict->add($entry);
            } else {
                $type = gettype($entry);
                throw new Exception("Form::accept() only accepts Field or Form objects. {$type} given.");
            }
        }
        return $this;
    }

    /**
     * Render the Form
     * @return string
     * @throws Exception
     */
    public function render()
    {
        if (!$this->isPublic()) {
            if (!$this->controller) {
                throw new Exception("Private forms require authenticated users.");
            }
        }
        global $twig, $pool;
        $pool->add($this, $this->getObjectReference());
        return $twig->render($this->template, $pool->asArray());
    }

    /**
     *  Read in the POST data for Form, verify the Form is the same, verify each Keydict Entry and generate a diff array
     */
    public function acceptPost()
    {
        if (!$this->isPublic()) {
            if (!$this->controller) {
                throw new Exception("Private forms require authenticated users.");
            }
        }
        $this->getKeydict()->acceptPost();
        return $this;
    }

    /**
     * NOT IMPLEMENTED FOR 2.5.0
     * Allow the Form to represent a specific Model.  The public settings of the Model will be loaded into the Form, and
     * the form will accept the Model for acceptPost, and will save to the database using the update() and save() Model
     * methods.
     *
     * @param Model $model
     */
    public function represents(Model $model)
    {

    }

    /**
     * @return Keydict
     */
    public function getKeydict()
    {
        return $this->keydict;
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getFormName()
    {
        return 'form__'.$this->getName();
    }

    /**
     * @return mixed
     */
    public function getHandler()
    {
        if (!$this->handler) {
        }
        return $this->handler;
    }

    /**
     * @param mixed $handler
     * @return $this
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Form
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param mixed $template
     * @return Form
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Whether or not this form is accessible to unauthenticated users
     * @return mixed
     */
    public function isPublic()
    {
        return $this->public;
    }

    /**
     * @param boolean $public
     * @return Form
     */
    public function setPublic($public)
    {
        $this->public = $public;
        return $this;
    }

    /**
     * The Pool namespace to use when this EventObject gets returns as part of a Trigger activation
     * @return string
     */
    public function getObjectReference()
    {
        $class = get_called_class();
        $class = substr($class, strrpos($class,'\\')+1);
        return $class;
    }
}