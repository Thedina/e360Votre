<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 10/26/2015
 * Time: 10:59 AM
 */

namespace eprocess360\v3modules\Form;


use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Module;
use eprocess360\v3core\Controller\Persistent;
use eprocess360\v3core\Controller\ProjectToolbar;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\Rules;
use eprocess360\v3core\Controller\Trigger\InterfaceTriggers;
use eprocess360\v3core\Controller\Triggers;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Field;
use eprocess360\v3core\Controller\Warden\Privilege;
use Exception;

/**
 * Class Form
 * @package eprocess360\v3modules\Form
 */
class Form extends Controller implements InterfaceTriggers
{
    use Router, Module, Persistent, Triggers, Rules, ProjectToolbar;
    protected $keydict;
    protected $handler;
    protected $templates = [
        'server'=>'module.form.default.html.twig',
        'client'=>[],
        'override'=>''
    ];
    protected $templateOverride = false;
    protected $directory = __DIR__;
    protected $formSaved;


    /*********************************************   #ROUTING#  **********************************************/


    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     */
    public function routes()
    {
        $this->routes->map('GET', '/?', function () {
            $this->getFormAPI();
        });

        //TODO Change the Twig Template of Forms to call this through a PUT request. Change the mapping also to be PUT.
        $this->routes->map('POST', '/?', function () {
            $this->updateFormAPI();
        });
    }

    /**
     * API Function that builds and returns the given Form.
     * @Required_Privilege: Read
     */
    public function getFormAPI()
    {
        $this->verifyPrivilege(Privilege::READ);

        $this->setData($this->getKeydict()->sleep());

        $this->standardResponse($this);
    }

    /**
     * Updates the Form with the inputted values; values will be checked against their corresponding Entry Types.
     * @Required_Privilege: Write
     * @Triggers: onUpdate
     */
    public function updateFormAPI()
    {
        $this->verifyPrivilege(Privilege::WRITE);

        $this->getKeydict()->acceptPost();

        $this->setSaved();

        $this->standardResponse($this);

        if(!$this->getKeydict()->hasException())
            $this->trigger('onUpdate');
    }


    /**********************************************   #HELPER#  **********************************************/


    /**
     * Helper function that sets the Response's template, data, response code, and errors.
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
        if ($this->templateOverride) {
            $response->setTemplate($this->templates['override'], 'server');
        }
        else {
            $response->setTemplate('module.form.default.html.twig', 'server');
        }


        $response->setResponse($responseData, $responseCode, false);
        if($error)
            $response->setErrorResponse(new \Exception($error));
    }


    /*********************************************   #WORKFLOW#  *********************************************/


    /**
     * Gets the return path for updating the Form.
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
     * Set the form to accept the given Entries
     * @param Field[]|Form[] $entries
     * @return $this
     * @throws Exception
     */
    public function accepts(...$entries) {
        $keydict = $this->getKeydict();
        foreach ($entries as $entry) {
            if ($entry instanceof Form) {
//                $this->forms[$entry->getName()] = $entry;
                foreach ($entry->getKeydict()->allFields() as $field) {
                    $keydict->add($field);
                }
            } elseif ($entry instanceof Field) {
                $keydict->add($entry);
            } else {
                $type = gettype($entry);
                throw new Exception("Form::accept() only accepts Field or Form objects. {$type} given.");
            }
        }
        $this->keydict = $keydict;
        return $this;
    }

    /**
     * Returns the Form's current keydict
     * @return Keydict
     */
    public function getKeydict()
    {
        if(!$this->keydict)
            $this->keydict = new Keydict();
        return $this->keydict;
    }

    /**
     * @return string
     * @deprecated No Use in Entire Project
     */
    public function getFormName()
    {
        return 'form__'.$this->getName();
    }

    /**
     * Returns whether the Form has been saved or not.
     * @return bool
     */
    public function isSaved()
    {
        return $this->formSaved;
    }

    /**
     *
     */
    public function overrideTemplate($template)
    {
        $this->templateOverride = true;
        $this->templates['override'] = $template;
    }


    /******************************************   #INITIALIZATION#  ******************************************/


    /**
     * Sets the return path for updating the Form.
     * @param mixed $handler
     * @return string
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * Sets the formSaved value, showing whether the Form has been saved.
     * @param bool|true $value
     */
    public function setSaved($value = true)
    {
        $this->formSaved = $value;
    }


    /*********************************************   #TRIGGERS#  *********************************************/


    /**
     * Trigger fired when the Form has been Updated on a successful POST/PUT request.
     * @param \Closure $closure
     * @return \eprocess360\v3core\Controller\Trigger\Trigger
     */
    public function onUpdate($closure)
    {
        return $this->addTrigger(__FUNCTION__, $closure);
    }
}