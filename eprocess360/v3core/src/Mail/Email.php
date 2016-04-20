<?php

namespace eprocess360\v3core\Mail;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry\Date;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\JSONArrayText;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\UnsignedTinyInteger;
use eprocess360\v3core\Logger;
use eprocess360\v3core\Model\MailLog;
use eprocess360\v3core\DB;
use eprocess360\v3core\Files\File;
use eprocess360\v3core\Model\MailQueue;
use eprocess360\v3core\Model\Users;
use eprocess360\v3core\User;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Keydict\Entry\Datetime;
use eprocess360\v3core\Keydict\Entry\FixedString64;
use eprocess360\v3core\Keydict\Entry\Text;

class Email
{
    const MAIL_DEBUG_FILE = 'mail-log';

    /**
     * @var Table $data
     */
    protected $data;

    /**
     * @var Template $template
     */
    protected $template;
    protected $subject = NULL;
    protected $bodyHTML = NULL;
    protected $bodyText = NULL;
    protected $emailTo = NULL;
    protected $recipientPrefetch = NULL;
    protected $filesToLink = NULL;
    protected $recipientsChanged = false;
    protected $filesChanged = false;
    protected $projectData = [];

    /**
     * Interface with PHPMailer to send mail via SMTP. $to is an array of
     * ['email'=>'blah@blah', 'name'=>'blah blah']
     * @param array $to
     * @param MailConfig $cfg
     * @throws MailException
     */
    protected function sendRaw(array $to, MailConfig $cfg) {
        try {
            $mail = new \PHPMailer();

            $mail->isSMTP();
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = 'tls';
            $mail->Timeout = $cfg->getTimeout();

            $mail->Host = $cfg->getHost();
            $mail->Port = $cfg->getPort();
            $mail->Username = $cfg->getUsr();
            $mail->Password = $cfg->getPwd();

            $mail->Debugoutput = function($str, $level) {
                Logger::log($str, self::MAIL_DEBUG_FILE);
            };
            $mail->SMTPDebug = 0; //TODO add to SysVar?

            /*$mail->From = $cfg->getFromEmail();
            $mail->FromName = $cfg->getFromName();*/
            $mail->setFrom($cfg->getFromEmail(), $cfg->getFromName());
            $mail->isHTML(true);
            $mail->CharSet = "text/html; charset=UTF-8;";

            foreach ($to as $recipient) {
                $mail->addAddress($recipient['email'], $recipient['name']);
            }

            $mail->Subject = $this->subject;
            $mail->Body = $this->bodyHTML;
            $mail->AltBody = $this->bodyText;

            $sent = $mail->send();
        }
        catch(\phpmailerException $e) {
            throw new MailException($e);
        }

        if(!$sent) {
            throw new MailException($mail->ErrorInfo);
        }
    }

    /**
     * Return html link section for uploads associated with this email
     * @return string
     */
    protected function linkFiles() {
        $linksHTML = [];
        $linksText = [];

        foreach(File::getMultiple($this->filesToLink) as $u) {
            /**
             * @var File $u
             */
            $linksHTML[] = "<a href='".$u->getDownloadURL()."'>".$u->getFileName()."</a>";
            $linksText[] = $u->getFileName().": ".$u->getDownloadURL();
        }
        $this->bodyHTML .= "<br>".implode("<br>", $linksHTML);
        $this->bodyText .= "\n".implode("\n", $linksText);
    }

    /**
     * Get file data for files associated with this email.
     * @return array
     */
    protected function getFileInfo() {
        $files = [];

        foreach(File::getMultiple($this->filesToLink) as $f) {
            /**
             * @var File $f
             */
            $fileInfo = $f->getData()->toArray();
            $fileInfo['url'] = $f->getDownloadURL();
            $files[] = $fileInfo;
        }

        return $files;
    }

    /**
     * Insert DB entries for mail recipients join table
     */
    protected function saveRecipients() {
        $sql = "INSERT INTO MailRecipients (idMail, idUser) VALUES ";
        $vals = [];
        foreach($this->emailTo as $idUser) {
            $vals[] = "(".(int)$this->data->idMail->get().",".(int)$idUser.")";
        }

        $sql .= implode(',', $vals);
        DB::insert($sql);
    }

    /**
     * Clear DB entries for mail recipients join table for this idMail
     * @throws \Exception
     */
    protected function deleteRecipients() {
        DB::sql("DELETE * FROM MailRecipients WHERE idMail = ".(int)$this->data->idMail->get());
    }

    /**
     * Insert DB entries for mail files join table
     */
    protected function saveFiles() {
        $sql = "INSERT INTO MailFiles (idMail, idFile) VALUES ";
        $vals = [];
        foreach($this->filesToLink as $idFile) {
            $vals[] = "(".(int)$this->data->idMail->get().",".(int)$idFile.")";
        }

        $sql .= implode(',', $vals);
        DB::insert($sql);
    }

    /**
     * Clear DB entries for mail files join table for this idMail
     * @throws \Exception
     */
    protected function deleteFiles() {
        DB::sql("DELETE * FROM MailFiles WHERE idMail = ".(int)$this->data->idMail->get());
    }

    /**
     * Get recipients as array of ['email'=>?, 'name'=>?]
     * @return array
     * @throws \Exception
     */
    protected function unpackRecipients() {
        $to = [];

        foreach($this->emailTo as $idUser) {
            if(is_array($this->recipientPrefetch) && is_array($this->recipientPrefetch[$idUser])) {
                $to[] = $this->recipientPrefetch[$idUser];
            }
            else {
                $u = User::get($idUser);
                $to[] = ['email'=>$u->getEmail(), 'name'=>$u->getFirstName().' '.$u->getLastName()];
            }
        }

        return $to;
    }

    /**
     * @param int $idProject
     * @param array $to
     * @param Template|null $template
     * @param array $vars
     * @param array $filesToLink
     */
    public function __construct($idProject = 0, $to = [], $template = NULL, $vars = [], $idUser = 0, $filesToLink = []) {
        $this->data = MailLog::keydict();
        $this->data->idProject->set($idProject);
        $this->data->idUser->set($idUser);
        $this->data->vars->set($vars);
        $this->emailTo = $to;
        $this->template = $template;
        $this->filesToLink = $filesToLink;
    }

    /**
     * Render subject and body from $this->template
     */
    public function fromTemplate() {
        if(!$this->template) {
            if($this->data->idTemplate->get()) {
                $this->template = Template::getByID($this->data->idTemplate->get());
            }
            else {
                throw new MailException("Email->fromTemplate(): template is null");
            }
        }

        $render = $this->template->render($this->data->vars->get());
        $this->subject = $render['subject'];
        $this->bodyHTML = $render['bodyHTML'];
        $this->bodyText = $render['bodyText'];
    }

    /**
     * Replace stored project variable values with current values from a keydict
     * @param Keydict $projKeydict
     * @throws Keydict\Exception\InvalidValueException
     */
    public function updateProjectVars(Keydict $projKeydict) {
        $this->data->vars->set(array_merge($this->data->vars->get(), $this->template->filterKeydictVars($projKeydict)));
    }

    /**
     * Send this email and log it, using configuration from MailConfig $cfg.
     * Returns the mail log id
     * @param MailConfig $cfg
     * @return int
     */
    public function send(MailConfig $cfg) {
        $to = $this->unpackRecipients();

        if($this->subject === NULL) {
            $this->fromTemplate();
        }

        if(!empty($this->filesToLink)) {
            $this->linkFiles();
        }

        $this->data->fakeMail->set((int)$cfg->isMailOff());
        $sent = false;

        if(!$cfg->isMailOff()) {
            try {
                $this->sendRaw($to, $cfg);
                $sent = true;
            } catch (MailException $e) {
                Logger::log($e->getMessage(), self::MAIL_DEBUG_FILE);
            }
        }
        else {
            $sent = true;
        }

        if(!$this->data->idMail->get()) {
            $this->insert();
        }
        else {
            $this->update();
        }

        return $sent;
    }

    /**
     * Set Email member data from a DB row. Optionally include template data,
     * in which case a Template will be instantiated. If no template data in
     * $row and $forceLoadTemplate is true, will load the template from the DB
     * with an additional query.
     * @param $row
     */
    public function loadRaw($row, $recipients = [], $filesToLink = [], $projectData = [], $forceLoadTemplate = true) {
        $this->data->wakeup($row);
        $this->emailTo = [];
        $this->recipientPrefetch = [];
        $this->projectData = $projectData;

        foreach($recipients as $r) {
            $split = explode('#', $r);
            $this->emailTo[] = (int)$split[0];
            $this->recipientPrefetch[(int)$split[0]] = [
                'email'=>$split[1],
                'name'=>$split[2]
            ];
        }

        $this->filesToLink = $filesToLink;

        if(isset($row['templateName'])) {
            $this->template = new Template([
                'idTemplate'=>$row['idTemplate'],
                'idController'=>$row['templateController'],
                'templateName'=>$row['templateName'],
                'template'=>$row['templateDef']
            ]);
        }
        elseif($forceLoadTemplate && isset($row['idTemplate'])) {
            $this->template = Template::getByID((int)$row['idTemplate']);
        }
        else {
            $this->template = NULL;
        }
        $this->recipientsChanged = false;
        $this->filesChanged = false;
    }

    /**
     * Load an email (with template) from the mail log
     * @param $id
     * @throws \Exception
     */
    public function load($idMail) {
        $rows = DB::sql("
                        SELECT
                          ml.*,
                          mt.idController AS templateController,
                          mt.templateName AS templateName,
                          mt.template as templateDef,
                          GROUP_CONCAT(DISTINCT CONCAT(mr.idUser,'#',u.email,'#',u.firstName,' ', u.lastName)) AS recipients,
                          GROUP_CONCAT(DISTINCT mf.idFile) AS files
                          FROM MailLog ml
                            INNER JOIN MailTemplates mt ON ml.idTemplate = mt.idTemplate
                            LEFT JOIN MailRecipients mr ON mr.idMail = ml.idMail
                            LEFT JOIN Users u ON mr.idUser = u.idUser
                            LEFT JOIN MailFiles mf ON mf.idMail = ml.idMail
                          WHERE ml.idMail = ".(int)$idMail."
                          GROUP BY ml.idMail
                        ");
        if(!empty($rows)) {
            $this->loadRaw($rows[0], explode(',', $rows[0]['recipients']), explode(',', $rows[0]['files']));
        }
    }

    public function loadWithProject($idMail) {
        $rows = DB::sql("
                          SELECT
                            ml.*,
                            mt.idController AS templateController,
                            mt.templateName AS templateName,
                            mt.template as templateDef,
                            pwf.projectTitle,
                            pwf.idWorkflow,
                            pwf.workflowTitle,
                            GROUP_CONCAT(DISTINCT CONCAT(mr.idUser,'#',u.email,'#',u.firstName,' ', u.lastName)) AS recipients,
                            GROUP_CONCAT(DISTINCT mf.idFile) AS files
                          FROM MailLog ml
                            INNER JOIN MailTemplates mt ON ml.idTemplate = mt.idTemplate
                            LEFT JOIN MailRecipients mr ON mr.idMail = ml.idMail
                            LEFT JOIN Users u ON mr.idUser = u.idUser
                            LEFT JOIN MailFiles mf ON mf.idMail = ml.idMail
                            LEFT JOIN (
                              SELECT
                                p.idProject,
                                p.title AS projectTitle,
                                wf.idController AS idWorkflow,
                                wf.title AS workflowTitle
                              FROM Projects p
                                INNER JOIN Controllers wf ON wf.idController = p.idController
                            ) AS pwf ON pwf.idProject = ml.idProject
                          WHERE ml.idMail = ".(int)$idMail."
                          GROUP BY ml.idMail
                        ");
        if(!empty($rows)) {
            $this->loadRaw(
                $rows[0],
                explode(',', $rows[0]['recipients']),
                explode(',', $rows[0]['files']),
                [
                    'projectTitle'=>$rows[0]['projectTitle'],
                    'idWorkflow'=>$rows[0]['idWorkflow'],
                    'workflowTitle'=>$rows[0]['workflowTitle']
                ]
            );
        }
    }

    public function update() {
        $idTemplate = ($this->template !== NULL ? $this->template->getIdTemplate() : 0);
        $this->data->idTemplate->set($idTemplate);
        $this->data->update();

        if(count($this->emailTo)) {
            if($this->recipientsChanged) {
                $this->deleteRecipients();
                $this->saveRecipients();
            }
        }

        if(count($this->filesToLink)) {
            if($this->filesChanged) {
                $this->deleteFiles();
            }
            $this->saveFiles();
        }
    }

    public function insert() {
        $idTemplate = ($this->template !== NULL ? $this->template->getIdTemplate() : 0);
        $this->data->idTemplate->set($idTemplate);

        if($this->data->dateAdded->get() === NULL) {
            $this->data->dateAdded->set(date('Y-m-d H:i:s'));
        }

        $this->data->insert();

        if(count($this->emailTo)) {
            $this->saveRecipients();
        }

        if(count($this->filesToLink)) {
            $this->saveFiles();
        }

        return $this->data->idMail->get();
    }

    /**
     * Load the email with $id and return it
     * @param int $id
     * @param boolean $withProject
     * @return Email
     */
    public static function getByID($id, $withProject = false) {
        $e = new Email();

        if($withProject) {
            $e->loadWithProject($id);
        }
        else {
            $e->load($id);
        }

        return $e;
    }

    /**
     * Build a new Email and insert it into the DB
     * @param int $idProject
     * @param array $to
     * @param null $template
     * @param array $vars
     * @param array $filesToLink
     */
    public static function create($idProject, $to = [], $template = NULL, $vars = [], $idUser = 0, $filesToLink = []) {
        $e = new self($idProject, $to, $template, $vars, $idUser, $filesToLink);
        $e->insert();
        return $e;
    }

    /**
     * @return int
     */
    public function getIDMail()
    {
        return (int)$this->data->idMail->get();
    }

    /**
     * @return int
     */
    public function getIDProject() {
        return (int)$this->data->idProject->get();
    }

    /**
     * @return string
     */
    public function getDateAdded() {
        return $this->data->dateAdded->get();
    }

    /**
     * @return string
     */
    public function getSubject() {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject($subject) {
        $this->subject = $subject;
    }

    /**
     * @return string
     */
    public function getBodyHTML() {
        return $this->bodyHTML;
    }

    /**
     * @param string $bodyHTML
     */
    public function setBodyHTML($bodyHTML) {
        $this->bodyHTML = $bodyHTML;
    }

    /**
     * @return string
     */
    public function getBodyText() {
        return $this->bodyText;
    }

    /**
     * @param string $bodyText
     */
    public function setBodyText($bodyText) {
        $this->bodyHTML = $bodyText;
    }

    /**
     * Get combined MailLog, recipient and files data plus template rendered in
     * context
     * @return array
     * @throws MailException
     */
    public function getMailInfoCombined() {
        $this->fromTemplate();
        $data = $this->data->toArray();
        $data['recipients'] = $this->unpackRecipients();
        $data['files'] = $this->getFileInfo();
        $data['projectData'] = $this->projectData;
        $data['subject'] = $this->subject;
        $data['bodyHTML'] = $this->bodyHTML;
        $data['bodyText'] = $this->bodyText;

        return $data;
    }

    /**
     * Get summary data for email log listing
     * @param null $startDate
     * @param null $endDate
     * @return array
     * @throws \Exception
     */
    public static function getMailLogSummary($startDate = NULL, $endDate = NULL) {
        $whereClauses = [];
        $out = [];

        if($startDate !== NULL)  {
            $startDate = DB::cleanse(date('Y-m-d', strtotime($startDate)));
            $whereClauses[] = "ml.dateAdded >= {$startDate}";
        }

        if($endDate !== NULL) {
            $endDate = DB::cleanse(date('Y-m-d', strtotime($endDate)));
            $whereClauses[] = "ml.dateAdded <= {$endDate}";
        }

        $sql = "SELECT
                  ml.idMail, ml.idUser, ml.idTemplate, ml.fakeMail, ml.dateAdded,
                  mq.*,
                  GROUP_CONCAT(u.email) AS recipients
                FROM MailLog ml
                INNER JOIN MailQueue mq
                  ON mq.idMail = ml.idMail
                INNER JOIN MailTemplates mt
                  ON mt.idTemplate = ml.idTemplate
                LEFT JOIN MailRecipients mr
                  ON mr.idMail = ml.idMail
                INNER JOIN Users u
                  ON u.idUser = mr.idUser
                ".implode(" AND ", $whereClauses)."
                GROUP BY ml.idMail";

        $results = DB::sql($sql);

        foreach($results as $r) {
            $mailKeydict = MailLog::keydict();
            $mailKeydict->wakeup($r);
            $queueKeydict = MailQueue::keydict();
            $queueKeydict->wakeup($r);
            $item = array_merge($mailKeydict->toArray(), $queueKeydict->toArray());
            $item['dateAdded'] = $r['dateAdded'];
            $item['recipients'] = explode(',', $r['recipients']);
            $out[] = $item;
        }

        return $out;
    }

    /**
     * Generate base SQL and keydict for mail log multiview
     * @param null $idController
     * @param null $idproject
     * @return array
     * @throws \Exception
     * @throws \eprocess360\v3core\Keydict\Exception\KeydictException
     */
    public static function multiviewBuild($idController = NULL, $idproject = NULL) {
        // Add project name/address as a column?

        $keydict = Table::build(
            PrimaryKeyInt::build('idMail', 'Mail ID'),
            IdInteger::build('idUser', 'User ID')->joinsOn(Users::keydict()),
            JSONArrayText::build('vars', 'Template Variables'),
            Datetime::build('dateAdded', 'Date Added'),
            UnsignedTinyInteger::build('fakeMail', 'Fake Mail'),
            UnsignedTinyInteger::build('sent', 'Sent'),
            UnsignedTinyInteger::build('failed', 'Failed'),
            UnsignedTinyInteger::build('tries', 'Tries'),
            Datetime::build('lastDate', 'Last Date'),
            FixedString64::build('templateName', 'Template Name'),
            FixedString128::build('projectTitle', 'Project Title'),
            Text::build('recipients', 'Recipients'),
            Text::build('compositeSearch', 'Composite Search')
        )->setName('MailLog');

        $select = "MailLog.*,
                   MailQueue.*,
                   MailTemplates.templateName,
                   Projects.title AS projectTitle,
                   GROUP_CONCAT(Users.email) AS recipients,
                   CONCAT(MailTemplates.templateName, ' ', Projects.title) AS compositeSearch";
        $from = "MailLog";
        $join = "INNER JOIN MailQueue ON MailQueue.idMail = MailLog.idMail
                 INNER JOIN MailTemplates ON MailTemplates.idTemplate = MailLog.idTemplate
                 LEFT JOIN MailRecipients ON MailRecipients.idMail = MailLog.idMail
                 LEFT JOIN Users ON Users.idUser = MailRecipients.idUser
                 LEFT JOIN Projects ON Projects.idProject = MailLog.idProject";

        $where = [];

        if($idController) {
            $where[] = "MailTemplates.idController = ".(int)$idController;
        }

        if($idproject) {
            $where[] = "MailLog.idProject = ".(int)$idproject;
        }

        $where = implode(' AND ', $where);

        $group = "MailLog.idMail";

        $result = ['keydict'=>$keydict, 'select'=>$select,'join'=>$join, 'where'=>$where, "from"=> $from, 'group'=>$group];
        return $result;
    }
}