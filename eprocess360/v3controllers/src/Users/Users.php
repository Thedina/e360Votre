<?php
namespace eprocess360\v3controllers\Users;

use eprocess360\v3core\Controller\Auth;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Dashboard;
use eprocess360\v3core\Controller\Identifier\Identifier;
use eprocess360\v3core\Controller\Project;
use eprocess360\v3core\Controller\Roles;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\Warden;
use eprocess360\v3core\Controller\Warden\Privilege;
use eprocess360\v3core\Form;
use eprocess360\v3core\Keydict\Entry\Email;
use eprocess360\v3core\Keydict\Entry\PhoneNumber;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\String;
use eprocess360\v3core\Keydict\Entry\String64;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model\UserRoles;
use eprocess360\v3core\Request\Request;
use eprocess360\v3core\User;
use eprocess360\v3core\Model\Users as UsersModel;
use eprocess360\v3core\View\Column;
use eprocess360\v3core\View\StandardView;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class Users
 * @package eprocess360\v3controllers\Users
 * Global user admin and search controller
 */
class Users extends Controller
{
    use Router, Auth, Dashboard, Warden;

    private $objectType;
    private $messages = [200 => false,
        404 => "404 Not Found - Resource not found",
        403 => "Permissions are not sufficient."];

    public function routes()
    {
        $this->routes->map('GET', '', function () {
            $this->getUsersAPI();
        });

        $this->routes->map('GET', '/[i:idUser]', function ($idUser) {
            $this->getUser((int)$idUser);
        });

        $this->routes->map('POST', '/[i:idUser]', function ($idUser) {
            $this->updateUser((int)$idUser);
        });

        $this->routes->map('PUT', '/[i:idUser]/password', function ($idUser) {
            $this->resetPasswordAPI((int)$idUser);
        });

        $this->routes->map('GET', '/find', function () {
            $this->findUserAPI();
        });
    }

    /**
     * @param int $idUser
     */
    public function getUser($idUser) {
        $userKeydict = \eprocess360\v3core\Model\Users::sqlFetch($idUser);
        $projectInfo = UserRoles::getUserProjects($idUser);

        foreach($projectInfo as &$p) {
            $this->roleLookup($p);
        }

        unset($p);

        $form = $this->buildUserForm($userKeydict);

        $this->standardResponse(['Form' => $form, 'idUser'=>$idUser, 'projectInfo'=>$projectInfo]);
    }

    /**
     * @param int $idUser
     */
    public function updateUser($idUser) {
        $userKeydict = \eprocess360\v3core\Model\Users::sqlFetch($idUser);
        $projectInfo = UserRoles::getUserProjects($idUser);
        $form = $this->buildUserForm($userKeydict);

        foreach($projectInfo as &$p) {
            $this->roleLookup($p);
        }

        unset($p);

        $userKeydict->acceptPost();
        $userKeydict->update();

        $this->standardResponse(['Form' => $form, 'idUser'=>$idUser, 'projectInfo'=>$projectInfo]);
    }

    /**
     * @param $idUser
     * @throws \Exception
     */
    public function resetPasswordAPI($idUser) {
        $httpCode = 200;
        $error = false;
        $requestData = Request::get()->getRequestBody();

        try {
            $newPassword = $requestData['newPassword'];
            $confirmPassword = $requestData['confirmPassword'];
            $mustChange = $requestData['mustChange'];

            if($newPassword !== $confirmPassword) {
                throw new \Exception("Password fields do not match", 500);
            }

            $user = User::get($idUser);
            $user->setPassword($newPassword, $mustChange);
        }
        catch(\Exception $e) {
            $error = $e->getMessage();
            $httpCode = $e->getCode() ? $e->getCode() : 500;
        }

        $response = $this->getResponseHandler();
        $response->setTemplate('user.admin.html.twig');
        $response->setResponse([], $httpCode, false);

        if($error) {
            $response->setErrorResponse(new \Exception($error));
        }
    }

    /**
     * Replace role IDs in project info row with proper names
     * @param array $p
     */
    public function roleLookup(Array &$p) {
        $projectController = Project::getProjectControllerByIdController($p['idController']);
        /**
         * @var Roles $projectController
         */
        $projectController->buildRoles();
        $rolesTable = $projectController->getRolesById();
        $roleIDs = explode(',', $p['localRoles']);
        $roleNames = [];

        foreach($roleIDs as $rid) {
            $roleNames[] = $rolesTable[$rid];
        }

        $p['localRoles'] = implode(',', $roleNames);
    }

    /**
     * @return Form
     * @throws \Exception
     */
    public function buildUserForm($keydict) {
        $form = Form::build($this->getId(), 'userAdmin', 'User Admin')->setPublic(true);

        $form->accepts(
            $keydict->firstName,
            $keydict->lastName,
            $keydict->email,
            $keydict->alternateEmail,
            $keydict->phone,
            $keydict->status->isActive,
            $keydict->status->isSystem,
            $keydict->status->canLogin,
            $keydict->status->isAway,
            $keydict->status->mustChangePassword
        );

        $formKeydict = $form->getKeydict();
        $oldEmail = $formKeydict->email->get();

        $formKeydict->addLateValidator('emailExists', function() use ($formKeydict, $oldEmail) {
            if($formKeydict->email->get() != $oldEmail) {
                if(User::emailExists($formKeydict->email->get())) {
                    throw new \Exception("Email address already in use!");
                }
            }
        });

        return $form;
    }

    /**
     * @param $data
     */
    public function standardResponse($data) {
        $response = $this->getResponseHandler();
        $response->setTemplate('user.admin.html.twig');
        $response->setResponse($data);
    }

    public function multiviewResponse($data = [], $objectType = '', $responseCode = 200, $error = false) {
        $this->objectType = $objectType;

        if($error == false)
            $error = $this->messages[$responseCode];

        $responseData = [
            'error' => $error,
            'data' => $data
        ];

        $response = $this->getResponseHandler();

        $response->setTemplate('users.base.html.twig', 'server');
        $response->setTemplate('module.users.handlebars.html', 'client', $this);
        $response->setTemplate(APP_PATH.'/eprocess360/v3controllers/src/SystemController/static/handlebars/global.multiview.handlebars.html', 'client', $this);

        $response->extendResponseMeta('Users', ['objectType'=>$objectType]);

        $response->setResponse($responseData, $responseCode, false);
        if($error)
            $response->setErrorResponse(new \Exception($error));
    }

    /**
     * API Function used to search Users through a given name/email
     * /user/find?name=
     * Handler for user search (by name)
     */
    public function findUserAPI() {
        $found = [];
        if (isset($_GET['name'])) {
            $text = ltrim($_GET['name']);
            if (strlen($text)) {
                $found = \eprocess360\v3core\User::findUser($text);
            }
        }

        $response = $this->getResponseHandler();
        $response->setResponse(['data' => $found]);
    }

    public function getUsersAPI() {
        $this->verifyPrivilege(Privilege::ADMIN);

        $keydict = Table::build(
            PrimaryKeyInt::build('idUser', 'User ID'),
            Email::build('email', 'E-mail Address'),
            String64::build('firstName', 'First Name'),
            String64::build('lastName', 'Last Name'),
            PhoneNumber::build('phone', 'Phone Number')
        )->setName('Users')->setLabel('Users');
        $keydict->add(
            String::build('title', 'Name'),
            String::build('everything', 'Everything'));

        $select = "*, CONCAT(firstName,' ', lastName) as title, CONCAT(firstName,' ', lastName,' ', email,' ', phone) as everything";
        $result = ['keydict'=>$keydict, 'select'=>$select,'join'=>NULL, 'where'=>NULL];

        $table = $result['keydict'];

        $view = StandardView::build('Categories.All', 'Fee Tag Categories', $result['keydict'], $result);
        $view->add(
            Column::import($table->idUser)->setEnabled(false),
            Column::build('everything','User Information')->setEnabled(false)->filterBySearch()->setSort(false, "CONCAT(firstName,' ', lastName,' ', email,' ', phone)"),
            Column::build('title','Name')->bucketBy()->setSort(true, "CONCAT(firstName,' ', lastName)")->setIsLink(true),
            Column::import($table->email)->bucketBy()->setSort(false),
            Column::import($table->phone)->bucketBy()->setSort(false)
        );

        $view->response($this);
        $data = $view->json(false);

        $this->multiviewResponse($data, 'users');
    }

    public function dashboardInit()
    {
        $this->setDashboardIcon('blank fa fa-user');
    }
}