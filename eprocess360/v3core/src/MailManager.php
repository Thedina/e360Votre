<?php

namespace eprocess360\v3core;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Project;
use eprocess360\v3core\Mail\MailConfig;
use eprocess360\v3core\Mail\MailException;
use eprocess360\v3core\Mail\MailQueue;
use eprocess360\v3core\Mail\Email;
use eprocess360\v3core\Mail\Template;

/**
 * Class MailManager
 * Provides access to email services
 * @package eprocess360\v3core
 */
class MailManager
{
    /**
     * @var MailConfig|NULL $config
     */
    private static $config = NULL;

    /**
     * @return MailConfig
     */
    public static function getConfig()
    {
        return self::$config;
    }

    /**
     * Make a MailConfig from config settings in array format
     * @param $settings
     */
    public static function initConfig($settings)
    {
        self::$config = new MailConfig($settings);
    }

    /**
     * Set up MailManager::$config from sysvar options
     * @throws \Exception
     */
    public static function initConfigFromSysvar() {
        global $pool;
        self::initConfig([
            'host'=>$pool->SysVar->get('mailServerHost'),
            'port'=>$pool->SysVar->get('mailServerPort'),
            'user'=>$pool->SysVar->get('mailServerUser'),
            'password'=>$pool->SysVar->get('mailServerPassword'),
            'fromEmail'=>$pool->SysVar->get('mailFromAddress'),
            'fromName'=>$pool->SysVar->get('mailFromName'),
            'mailOff'=>$pool->SysVar->get('mailOff')
        ]);
    }

    /**
     * Send an email from a template. $to is an array of user IDs.
     * @deprecated don't use this
     * @param array $to
     * @param $templateController
     * @param $templateName
     * @param array $vars
     * @param array $filesToLink
     */
    public static function sendMail(array $to, $templateController, $templateName, array $vars = [], array $filesToLink = []) {
        if(!is_object(self::$config)) {
            self::initConfigFromSysvar();
        }

        $template = Template::getByName($templateController, $templateName);
        $email = new Email($to, $template, $vars);
        $email->send(self::$config);
    }

    /**
     * Add an email (from template) to the DB and enqueue it to be sent at the
     * next opportunity
     * @param $idController
     * @param $idProject
     * @param Keydict|null $projKeydict
     * @param $templateName
     * @param array $to
     * @param array $vars
     * @param array $filesToLink
     */
    public static function mail($idController, $idProject, Keydict $projKeydict = NULL, $templateName, array $to, array $vars = [], array $filesToLink = []) {
        global $pool;

        if(!is_object(self::$config)) {
            self::initConfigFromSysvar();
        }

        try {
            $idUser = $pool->User->getIdUser();
        }
        catch(\Exception $e) {
            $idUser = 0;
        }

        $template = Template::getByName($idController, $templateName);

        if($projKeydict !== NULL) {
            $projVars = $template->filterKeydictVars($projKeydict);
            $vars = array_merge($vars, $projVars);
        }

        $e = Email::create($idProject, $to, $template, $vars, $idUser, $filesToLink);
        MailQueue::addMail($e);
    }

    /**
     * Retry all unsent mails in the queue
     */
    public static function retryQueueUnsent() {
        if(!is_object(self::$config)) {
            self::initConfigFromSysvar();
        }

        $queue = MailQueue::getUnsentQueued();
        foreach($queue as $q) {
            /**
             * @var MailQueue $q
             */
            if($q->canRetry()) {
                $e = $q->getEmail();
                $sent = $e->send(self::$config);
                $q->tried($sent);
            }
        }
    }

    /**
     * Force resend an email (regardless of whether it has reached the normal try limit)
     * @param int $idMail
     * @param bool|false $preserveVars
     * @return bool
     */
    public static function forceRetry($idMail, $preserveVars = false) {
        if(!is_object(self::$config)) {
            self::initConfigFromSysvar();
        }

        $q = MailQueue::getByID($idMail);
        $e = $q->getEmail();

        if(!$preserveVars) {
            $projController = Project::getProjectControllerByIdProject($e->getIDProject());
            $e->updateProjectVars($projController->getKeydict());
        }

        $sent = $e->send(self::$config);
        $q->tried($sent, true);

        return (bool)$sent;
    }

    /**
     * Get *all* mail info, including queue/status data, for a particular email ID
     * @param $idMail
     * @return array
     */
    public static function allInfoByID($idMail) {
        $q = MailQueue::getByID($idMail);
        $e = $q->getEmail(true);

        $data = $e->getMailInfoCombined();
        $data['queue'] = $q->getData()->toArray();

        $varsArray = [];
        $data['vars'] = json_decode($data['vars'], true);
        foreach($data['vars'] as $key=>$value) {
            $varsArray[] = [
                'key'=>$key,
                'value'=>$value
            ];
        }
        $data['vars'] = $varsArray;

        return $data;
    }

    /**
     * Get summary data for email log listing, optionally filtered by date
     * @param null $startDate
     * @param null $endDate
     * @return array
     * @throws \Exception
     */
    public static function mailLogSummary($startDate = NULL, $endDate = NULL) {
        return Email::getMailLogSummary($startDate, $endDate);
    }

    /**
     * Generate base SQL and keydict for mail log multiview
     * @param int|null $idController
     * @param int|null $idproject
     * @return array
     */
    public static function mailLogMultiviewBuild($idController = NULL, $idproject = NULL) {
        return Email::multiviewBuild($idController, $idproject);
    }

    /**
     * Get all template data formatted for API output
     * @return array
     * @throws MailException
     */
    public static function getTemplates() {
        $results = [];
        $templates = Template::getAll();

        foreach($templates as $t) {
            /**
             * @var Template $t
             */
            $data = $t->getData()->toArray();
            $data = array_merge($data, $t->getSections());
            unset($data['template']);

            $results[] = $data;
        }

        return $results;
    }

    /**
     * Generate base SQL and keydict for mail template multiview
     * @param $idController
     * @return array
     */
    public static function templateMultiviewBuild($idController) {
        return Template::multiviewBuild($idController);
    }

    /**
     * Get single template data formatted for API output
     * @param $idTemplate
     * @return array
     * @throws MailException
     */
    public static function getTemplate($idTemplate) {
        $template = Template::getByID($idTemplate);
        $data = $template->getData()->toArray();
        $data = array_merge($data, $template->getSections());
        unset($data['template']);

        return $data;
    }

    /**
     * Create an email template with data from API input
     * @param $idController
     * @param $title
     * @param $subject
     * @param $bodyHTML
     * @param $bodyText
     * @return array
     */
    public static function createTemplate($idController, $title, $subject, $bodyHTML, $bodyText) {
        // TODO: generate text body from HTML body?
        $template = $subject."\n".Template::HTML_BODY_HEADER."\n".$bodyHTML."\n".Template::TEXT_BODY_HEADER.$bodyText;

        $t = Template::create($idController, $title, $template);

        $data = $t->getData()->toArray();
        $data = array_merge($data, $t->getSections());
        unset($data['template']);

        return $data;
    }

    /**
     * Update an email template with data from API input
     * @param $idTemplate
     * @param $idController
     * @param $title
     * @param $subject
     * @param $bodyHTML
     * @param $bodyText
     * @return array
     * @throws MailException
     */
    public static function editTemplate($idTemplate, $idController, $title, $subject, $bodyHTML, $bodyText) {
        $template = $subject."\n".Template::HTML_BODY_HEADER."\n".$bodyHTML."\n".Template::TEXT_BODY_HEADER."\n".$bodyText;

        $t = Template::getByID($idTemplate);
        $t->setIdController($idController);
        $t->setTemplateName($title);
        $t->setTemplate($template);

        $t->update();
        $t->load();

        $data = $t->getData()->toArray();
        $data = array_merge($data, $t->getSections());
        unset($data['template']);

        return $data;
    }

    /**
     * @param $idTemplate
     * @return bool
     */
    public static function deleteTemplate($idTemplate) {
        Template::deleteByID($idTemplate);

        return true;
    }
}