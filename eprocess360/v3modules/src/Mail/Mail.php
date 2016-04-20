<?php

namespace eprocess360\v3modules\Mail;


use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Module;
use eprocess360\v3core\Controller\Persistent;
use eprocess360\v3core\Controller\Project;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\Rules;
use eprocess360\v3core\Controller\Triggers;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Exception\InvalidValueException;
use eprocess360\v3core\Mail\Template;
use eprocess360\v3core\MailManager;
use eprocess360\v3core\Request\Request;
use eprocess360\v3core\View\Column;
use eprocess360\v3core\View\StandardView;

/**
 * Class Mail
 * @package eprocess360\v3modules\Mail
 * Email system controllerModule
 */
class Mail extends Controller
{
    use Router, Module, Persistent, Triggers, Rules;

    /**
     * @var Array $templateMapping
     */
    private $templateKeydicts = [];


    /*********************************************   #ROUTING#  **********************************************/


    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     */
    public function routes()
    {
        if ($this->getParent()->hasObjectId()) {
            $this->routes->map('GET', '/logs', function () {
                $this->getMailLogsAPI($this->getParent()->getObjectId());
            });
            $this->routes->map('GET', '/logs/[i:idMail]?', function ($idMail) {
                $this->getMailLogAPI($idMail);
            });
            $this->routes->map('PUT', '/resend/[i:idMail]', function ($idMail) {
                $this->resendMailAPI($idMail);
            });
            $this->routes->map('GET', '/templates', function () {
                global $pool;

                // HACK: redirect to workflow templates route
                header('Location: '.$pool->SysVar->get('siteUrl').'/controllers/'.$this->getParent()->getId().'/modules/mail/templates');
            });
            $this->routes->map('GET', '/templates/[i:idMail]?', function ($idTemplate) {
                global $pool;
                
                // HACK: redirect to workflow template route
                header('Location: '.$pool->SysVar->get('siteUrl').'/controllers/'.$this->getParent()->getId().'/modules/mail/templates/'.$idTemplate);
            });
        } else {
            $this->routes->map('GET', '/logs', function () {
                $this->getMailLogsAPI();
            });
            $this->routes->map('GET', '/logs/[i:idMail]?', function ($idMail) {
                $this->getMailLogAPI($idMail);
            });
            $this->routes->map('PUT', '/resend/[i:idMail]', function ($idMail) {
                $this->resendMailAPI($idMail);
            });
            $this->routes->map('GET', '/templates', function () {
                $this->getMailTemplatesAPI();
            });
            $this->routes->map('POST', '/templates', function () {
                $this->createMailTemplateAPI();
            });
            $this->routes->map('GET', '/templates/new', function() {
                $this->blankMailTemplateAPI();
            });
            $this->routes->map('GET', '/templates/[i:idMail]?', function ($idTemplate) {
                $this->getMailTemplateAPI($idTemplate);
            });
            $this->routes->map('PUT', '/templates/[i:idMail]', function ($idTemplate) {
                $this->editMailTemplateAPI($idTemplate);
            });
            $this->routes->map('DELETE', '/templates/[i:idMail]', function ($idTemplate) {
                $this->deleteMailTemplateAPI($idTemplate);
            });
        }
    }

    /******************************************   #ROUTE HANDLERS#  ******************************************/

    /**
     * @param int|null $idProject
     * @throws \Exception
     */
    function getMailLogsAPI($idProject = NULL) {
        $setup = MailManager::mailLogMultiviewBuild($this->getId(), $idProject);
        $table = $setup['keydict'];

        /** @var StandardView $test Assign the namespace and keydict to the view */
        $view = StandardView::build('MailLog.All', 'MailLog', $table, $setup);

        // Column::import($table->recipients, 'Recipients')->filterBySearch()->bucketBy()->setSort(true)->setIsLink(true)
        // Column::import($table->dataAdded, 'Date Added')->filterBySearch()->bucketBy()->setSort(true)

        $statusFilter = [
            [
                'option'=>'Sent',
                'sql'=>'MailQueue.sent = 1'
            ],
            [
                'option'=>'Unsent',
                'sql'=>'MailQueue.sent = 0'
            ],
            [
                'option'=>'Failed',
                'sql'=>'MailQueue.failed = 1'
            ],
            [
                'option'=>'Dummy',
                'sql'=>'MailLog.fakeMail = 1'
            ]
        ];

        $view->add(
            Column::import($table->templateName, 'Template Name')->bucketBy()->setSort(true)->setIsLink(true),
            Column::import($table->projectTitle, 'Project Title')->setIsLink(true),
            Column::import($table->recipients, 'Recipients')->setSort(true)->setIsLink(true),
            Column::import($table->dateAdded, 'Date Added')->bucketBy()->setSort(true),
            Column::import($table->lastDate, 'Last Tried')->bucketBy()->setSort(true),
            Column::import($table->tries, 'Tries')->setSort(true),
            Column::import($table->sent, 'Sent')->setEnabled(false),
            Column::import($table->failed, 'Failed')->setEnabled(false),
            Column::import($table->fakeMail, 'Dummy Mail')->setEnabled(false),
            Column::build('compositeSearch', 'Search')->setEnabled(false)->filterBySearch()->setSort(false, "CONCAT(MailTemplates.templateName, ' ', Projects.title)"),
            Column::build('status', 'Status')->setTemplate("mvCustomMailStatus")->filterByValue($statusFilter)
        );

        $view->response($this);
        $data = $view->json(false);

        $this->standardResponse($data, 'mailLog');
    }

    /**
     * @param int $idMail
     */
    function getMailLogAPI($idMail) {
        $mailInfo = MailManager::allInfoByID($idMail);
        $this->standardResponse($mailInfo, 'mailLog');
    }

    /**
     * @param int $idMail
     */
    function resendMailAPI($idMail) {
        $data = Request::get()->getRequestBody();
        $preserveVars = isset($data['preserveVars']) ? ($data['preserveVars'] === 'true' ? true : false) : false;

        $sent = MailManager::forceRetry($idMail, $preserveVars);

        $this->standardResponse(['sent'=>$sent], 'mailLog');
    }

    function getMailTemplatesAPI() {
        $setup = MailManager::templateMultiviewBuild($this->getId());
        $table = $setup['keydict'];

        /** @var StandardView $test Assign the namespace and keydict to the view */
        $view = StandardView::build('MailTemplates.All', 'MailTemplates', $table, $setup);

        $view->add(
            Column::import($table->templateName, 'Name')->filterBySearch()->setSort(true)->setIsLink(true),
            Column::build("options", "Options")->setTemplate("mvCustomColumnRemove")
        );

        $view->response($this);
        $data = $view->json(false);

        $this->standardResponse($data, 'mailTemplate');
    }

    /**
     * @param int $idTemplate
     */
    function getMailTemplateAPI($idTemplate) {
        $responseCode = 200;
        $error = false;
        $data = NULL;

        try {
            $data = MailManager::getTemplate($idTemplate);
        }
        catch(\Exception $e) {
            $error = $e->getMessage();
            $responseCode = $e->getCode();
        }

        $this->templateResponse($data, 'mailTemplate', $responseCode, $error);
    }

    function createMailTemplateAPI() {
        $data = Request::get()->getRequestBody();

        $title = isset($data['templateName']) ? $data['templateName'] : null;
        $subject = isset($data['subject']) ? $data['subject'] : null;
        $bodyHTML = isset($data['bodyHTML']) ? $data['bodyHTML'] : null;
        $bodyText = isset($data['bodyText']) ? $data['bodyText'] : null;

        $data = MailManager::createTemplate($this->getId(), $title, $subject, $bodyHTML, $bodyText);
        $this->standardResponse($data, 'mailTemplate');
    }

    function blankMailTemplateAPI() {
        $this->templateResponse((object)NULL, 'mailTemplate');
    }

    /**
     * @param int $idTemplate
     */
    function editMailTemplateAPI($idTemplate) {
        $data = Request::get()->getRequestBody();

        $title = isset($data['templateName']) ? $data['templateName'] : null;
        $subject = isset($data['subject']) ? $data['subject'] : null;
        $bodyHTML = isset($data['bodyHTML']) ? $data['bodyHTML'] : null;
        $bodyText = $bodyHTML ? strip_tags($bodyHTML) : null;

        $data = MailManager::editTemplate($idTemplate, $this->getId(), $title, $subject, $bodyHTML, $bodyText);
        $this->standardResponse($data, 'mailTemplate');
    }

    /**
     * @param int $idTemplate
     */
    function deleteMailTemplateAPI($idTemplate) {
        $data = MailManager::deleteTemplate($idTemplate);
        $this->standardResponse($data, 'mailTemplate');
    }

    /**********************************************   #HELPER#  **********************************************/


    /**
     * Helper function that sets the Response's template, data, response code, and errors.
     * @param array $data
     * @param int $responseCode
     * @param bool|false $error
     */
    private function standardResponse($data = [], $objectType = '', $responseCode = 200, $error = false)
    {
        $responseData = [
            'error' => $error,
            'data' => $data
        ];

        $response = $this->getResponseHandler();
        $response->setTemplate('mail.base.html.twig', 'server');
        $response->setTemplate('module.mail.handlebars.html', 'client', $this);
        $response->setTemplate(APP_PATH.'/eprocess360/v3controllers/src/SystemController/static/handlebars/global.multiview.handlebars.html', 'client', $this);

        $response->extendResponseMeta('Mail', ['objectType'=>$objectType]);

        $response->setResponse($responseData, $responseCode, false);
        if($error)
            $response->setErrorResponse(new \Exception($error));
    }

    private function templateResponse($data = [], $objectType = '', $responseCode = 200, $error = false) {
        $variables = [];

        try {
            $keydict = $this->getParent()->getKeydict();
            $variables = $this->flatVariableHelper($keydict->toArray(), '');
        }
        catch(\Exception $e) {
            $error = $e->getMessage();
        }

        $responseData = [
            'error' => $error,
            'data' => $data
        ];

        $response = $this->getResponseHandler();
        $response->setTemplate('mail.base.html.twig', 'server');
        $response->setTemplate('module.mail.handlebars.html', 'client', $this);
        $response->setTemplate(APP_PATH.'/eprocess360/v3controllers/src/SystemController/static/handlebars/global.multiview.handlebars.html', 'client', $this);

        $response->extendResponseMeta('Mail', ['variableOptions'=>$variables]);
        $response->extendResponseMeta('Mail', ['objectType'=>$objectType]);

        $response->setResponse($responseData, $responseCode, false);
        if($error)
            $response->setErrorResponse(new \Exception($error));
    }

    /**
     * @param $array
     * @param $append
     * @return array
     */
    private function flatVariableHelper($array,$append)
    {
        $result = [];
        foreach ($array as $key => $value){
            if(is_array($value)) {
                $result = array_merge($result, $this->flatVariableHelper($value, $key));
            }
            else
                $result[] = $append?$append.".".$key:$key;
        }
        return $result;
    }

    /*********************************************   #WORKFLOW#  *********************************************/


    /**
     * Enqueue an email to be sent from the template specified
     * @param $templateName
     * @param $to
     * @param array $vars
     * @param array $filesToLink
     * @throws \Exception
     */
    public function send($templateName, $to, $vars = [], $filesToLink = []) {
        try {
            $this->templateKeydicts[$templateName]->acceptArray($vars);
        }
        catch (InvalidValueException $e) {
            throw new \Exception("Mail->send(): vars passed do not match specified format.");
        }

        if($idProject = $this->getParent()->getObjectId()) {
            $projKeydict = $this->getParent()->getKeydict();
        }
        else {
            $projKeydict = NULL;
        }

        MailManager::mail($this->getId(), $idProject, $projKeydict, $templateName, $to, $vars, $filesToLink);
    }


    /******************************************   #INITIALIZATION#  ******************************************/


    /**
     * Register a validating keydict for specified template with this mail
     * controller
     * @param $templateName
     * @param Keydict $keydict
     */
    public function useTemplate($templateName, Keydict $keydict) {
        $this->templateKeydicts[$templateName] = $keydict;
    }

    /**
     * Register an email template with the Mail module instance. Can either
     * specify an existing template or import one from a file in
     * [controller dir]/presets/email
     * @param $templateName
     * @param null $file
     * @param bool|false $overwrite
     * @throws \Exception
     */
    public function registerTemplate($templateName, $file = NULL, $overwrite = false) {
        $templateText = false;
        $template = Template::getByName($this->getId(), $templateName);

        if(strlen($file)) {
            $filepath = $this->parent->getPresetPath().'/'.$this->getName().'/'.$file;
            $templateText = file_get_contents($filepath);
            if($templateText === false) {
                throw new \Exception("Mail->registerTemplate(): cannot read file {$filepath}");
            }
        }

        if(!$template->getIdTemplate()) {
            if($templateText) {
                $template = Template::create($this->getId(), $templateName, $templateText);
            }
            else {
                throw new \Exception("Mail->registerTemplate(): neither an existing template or a file specified.");
            }
        }
        elseif($overwrite) {
            $template->setTemplate($templateText);
            $template->update();
        }
    }

    /*********************************************   #TRIGGERS#  *********************************************/



}