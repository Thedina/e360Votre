<?php

namespace eprocess360\v3core\Mail;
use eprocess360\v3core\DB;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Pool;
use eprocess360\v3core\Model;
use eprocess360\v3core\Keydict\Table;

/**
 * Class Template
 * Class for loading, saving, and rendering Twig-driven email templates
 * @package eprocess360\v3core\Mail
 */
class Template
{
    const HTML_BODY_HEADER = "<!-- htmlBody -->";
    const TEXT_BODY_HEADER = "<!-- textBody -->";
    /**
     * @var Table $data
     */
    private $data;
    private $varsUsed;

    /**
     * @param array $rowData
     */
    public function __construct($rowData = []) {
        //$rowData = ['idController'=>$idController, 'templateName'=>$name, 'template'=>$template];
        $this->data = Model\MailTemplates::keydict();
        $this->data->acceptArray($rowData);
        $this->varsUsed = [];
    }

    /**
     * Render the template and return split into subject/html body/text body
     * @param array $vars
     * @return array
     * @throws MailException
     */
    public function render(array $vars) {
        $pool = Pool::getInstance();

        foreach($vars as $k=>$v) {
            $pool->add($v, $k);
        }

        $template = $this->data->template->get();
        $rendered = $pool->evaluate($template);

        $split = explode(self::HTML_BODY_HEADER, $rendered);
        if(count($split) != 2) {
            throw new MailException("Template->render(): improperly declared html body section");
        }
        $subject = trim($split[0]);
        $split = explode(self::TEXT_BODY_HEADER, $split[1]);
        if(count($split) != 2) {
            throw new MailException("Template->render(): improperly declared text body section");
        }
        $htmlBody = trim($split[0]);
        $textBody = trim($split[1]);

        return [
            'subject'=>$subject,
            'bodyHTML'=>$htmlBody,
            'bodyText'=>$textBody
        ];
    }

    /**
     * Split this template into subject, html body, and text body, without
     * rendering
     * @return array
     * @throws MailException
     */
    public function getSections() {
        $template = $this->data->template->get();

        $split = explode(self::HTML_BODY_HEADER, $template);
        if(count($split) != 2) {
            throw new MailException("Template->render(): improperly declared html body section");
        }
        $subject = trim($split[0]);
        $split = explode(self::TEXT_BODY_HEADER, $split[1]);
        if(count($split) != 2) {
            throw new MailException("Template->render(): improperly declared text body section");
        }
        $htmlBody = trim($split[0]);
        $textBody = trim($split[1]);

        return [
            'subject'=>$subject,
            'bodyHTML'=>$htmlBody,
            'bodyText'=>$textBody
        ];
    }

    /**
     * Extract variable names used in this template and store in varsUsed
     * @throws \eprocess360\v3core\Keydict\Exception\InvalidValueException
     */
    public function extractVariables() {
        // Split to omit text body
        $split = explode(self::TEXT_BODY_HEADER, $this->getTemplate());
        $this->varsUsed = [];

        if(preg_match_all('/\{\{\s*([a-zA-Z0-9]+)\s*\}\}/', $split[0], $matches)) {
            foreach($matches[1] as $varName) {
                $this->varsUsed[] = $varName;
            }
        }

        $this->data->varsUsed->set(serialize($this->varsUsed));
    }

    /**
     * Insert a DB entry for this template
     */
    public function insert() {
        $this->extractVariables();
        $this->data->insert();
    }

    /**
     * Update the DB entry for this template
     */
    public function update() {
        $this->extractVariables();
        $this->data->update();
    }

    public function delete() {
        Model\MailTemplates::deleteById($this->data->idTemplate->get());
    }

    /**
     * Load template data from the DB by id
     * @param $idTemplate
     * @throws \Exception
     */
    public function load($idTemplate = NULL) {
        if(!$idTemplate) {
            $idTemplate = $this->data->idTemplate->get();
        }

        $rows = DB::sql("SELECT * FROM MailTemplates WHERE idTemplate = ".(int)$idTemplate);

        if(!empty($rows)) {
            $this->data->wakeup($rows[0]);
            $this->updateVarsUsed();
        }
    }

    /**
     * Load template data from the DB by name and controller
     * @param $idController
     * @param $name
     * @throws \Exception
     */
    public function loadByName($idController, $name) {
        $rows = DB::sql("SELECT * FROM MailTemplates WHERE idController = '".(int)$idController."' AND templateName = '".DB::cleanse($name)."'");
        if(!empty($rows)) {
            $this->data->wakeup($rows[0]);
            $this->updateVarsUsed();
        }
    }

    /**
     * Update $this->varsUsed from $this->data->varsUsed
     * @return Array
     */
    public function updateVarsUsed() {
        return $this->varsUsed = unserialize($this->data->varsUsed->get());
    }

    /**
     * @return mixed
     */
    public function getIdTemplate()
    {
        return $this->data->idTemplate->get();
    }

    /**
     * @param mixed $idTemplate
     */
    public function setIdTemplate($idTemplate)
    {
        $this->data->idTemplate->set($idTemplate);
    }

    /**
     * @return int
     */
    public function getIdController()
    {
        return $this->data->idController->get();
    }

    /**
     * @param int $idController
     */
    public function setIdController($idController)
    {
        $this->data->idController->set($idController);
    }

    /**
     * @return string
     */
    public function getTemplateName()
    {
        return $this->data->templateName->get();
    }

    /**
     * @param string $name
     */
    public function setTemplateName($name)
    {
        $this->data->templateName->set($name);
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->data->template->get();
    }

    /**
     * @param string $subject
     */
    public function setTemplate($template)
    {
        $this->data->template->set($template);
    }

    /**
     * @return Table
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @return Array
     */
    public function getVariablesUsed() {
        return $this->varsUsed;
    }

    /**
     * Given a keydict containing (for example) project data, iterate through
     * varsUsed and find the value of each variable if it's in the keydict.
     * @param Keydict $keydict
     * @return array
     */
    public function filterKeydictVars(Keydict $keydict) {
        $varVals = [];

        foreach($this->varsUsed as $var) {
            $varVals[$var] = $keydict->$var->get();
        }

        return $varVals;
    }

    public static function getByID($idTemplate) {
        $t = self::build();
        $t->load($idTemplate);
        return $t;
    }

    /**
     * Load from the DB the email template for $idController with $name
     * @param $controller
     * @param $name
     * @return Template
     */
    public static function getByName($idController, $name) {
        $t = self::build();
        $t->loadByName($idController, $name);
        return $t;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public static function getAll() {
        $results = [];
        $rows = DB::sql("SELECT * FROM MailTemplates");

        foreach($rows as $r) {
            $t = new self();
            $t->getData()->wakeup($r);
            $t->updateVarsUsed();
            $results[] = $t;
        }

        return $results;
    }

    /**
     * Insert a new email template into the DB
     * @param $idController
     * @param $name
     * @param $subject
     * @param $body
     * @return Template
     */
    public static function create($idController, $name, $template) {
        $t = self::build($idController, $name, $template);
        $t->insert();
        return $t;
    }

    /**
     * @param int|null $idController
     * @param string|null $name
     * @param string|null $template
     */
    public static function build($idController = 0, $name = NULL, $template = NULL) {
        $rowData = ['idController'=>$idController, 'templateName'=>$name, 'template'=>$template];
        return new self($rowData);
    }

    /**
     * @param $idTemplate
     */
    public static function deleteByID($idTemplate) {
        Model\MailTemplates::deleteById($idTemplate);
    }

    /**
     * Generate base SQL and keydict for mail template multiview
     * @param $idController
     * @return array
     */
    public static function multiviewBuild($idController) {
        $keydict = Model\MailTemplates::keydict();

        $select = "MailTemplates.*";
        $where = "MailTemplates.idController = ".(int)$idController;

        $result = ['keydict'=>$keydict, 'select'=>$select,'join'=>NULL, 'where'=>$where];
        return $result;
    }
}