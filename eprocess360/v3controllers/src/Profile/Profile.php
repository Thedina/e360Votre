<?php

namespace eprocess360\v3controllers\Profile;


use eprocess360\v3core\Controller\Auth;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Form;
use eprocess360\v3core\Keydict\Entry\Password;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model\Users;
use eprocess360\v3core\Request\Request;
use eprocess360\v3core\User as CoreUser;
use Exception;

/**
 * Class Profile
 * @package eprocess360\v3controllers\User
 */
class Profile extends Controller
{
    use Router, Auth;
    private $messages = [200 => false,
        404 => "404 Not Found - Resource not found",
        403 => "Permissions are not sufficient."];

    /*********************************************   #ROUTING#  **********************************************/


    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     */
    public function routes()
    {
        $this->routes->map('GET', '', function () {
            $this->userSettingsAPI();
        });

        $this->routes->map('POST', '', function () {
            $this->updateUserSettingsAPI();
        });

        $this->routes->map('POST', '/password', function () {
            $this->updatePasswordAPI();
        });

        $this->routes->map('GET', '/find', function () {
            $this->findUserAPI();
        });
    }

    /**
     * API Function used to search Users through a given name/email
     * /user/find?name=
     * TODO move to new Users controller!!
     */
    public function findUserAPI()
    {
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

    /**
     * API Function to get the User Settings form.
     */
    public function userSettingsAPI()
    {
        $data = $this->buildForm();

        $this->standardResponse($data);
    }

    /**
     * API Function to update the User Settings Form
     * @throws \eprocess360\v3core\Keydict\Exception\KeydictException
     */
    public function updateUserSettingsAPI()
    {
        $formkeydict = $this->buildForm();

        /** @var Form $form */
        $form = $formkeydict['form'];
        $keydict = $formkeydict['keydict'];
        $data = [];

        $form->acceptPost();

        if (!$form->getKeydict()->hasException()) {
            /** @var Table $keydict */
            $keydict->update();
            $keydict->setSaved(true);
        } else {
            $data['errors'] = $form->getKeydict()->getException();
        }

        $data['form'] = $form;
        $data['keydict'] = $keydict;
        $this->standardResponse($data);
    }


    public function updatePasswordAPI() {
        global $pool;
        $httpCode = 200;
        $error = false;
        $requestData = Request::get()->getRequestBody();

        try {
            $currentPassword = $requestData['currentPassword'];
            $newPassword = $requestData['newPassword'];
            $repeatPassword = $requestData['repeatPassword'];

            if($currentPassword !== '' && $newPassword !== '' && $repeatPassword !== '') {
                if ($newPassword === $repeatPassword) {
                    $pool->User->updatePassword($newPassword, $currentPassword);
                } else
                    throw new \Exception('Your new password fields do not match.');
            }
            else if ($pool->User->isMustChangePassword() && $newPassword !== '' && $repeatPassword !== '') {
                if ($newPassword === $repeatPassword) {
                    $pool->User->updatePassword($newPassword, false);
                }
                else {
                    throw new \Exception('New passwords do not match.');
                }
            }
            else {
                throw new \Exception('One or more password fields are empty.');
            }
        }
        catch(\Exception $e) {
            $error = $e->getMessage();
            $httpCode = $e->getCode() ? $e->getCode() : 500;
        }

        $response = $this->getResponseHandler();
        $response->setTemplate('User.settings.html.twig');
        $response->setResponse([], $httpCode, false);

        if($error) {
            $response->setErrorResponse(new \Exception($error));
        }

    }


    /**********************************************   #HELPER#  **********************************************/


    /**
     * Helper function that sets the Response's template, data, response code, and errors.
     * @param $data
     * @param $responseCode
     * @param $error
     */
    private function standardResponse($data = [], $responseCode = 200, $error = false)
    {
        if ($error == false) {
            $error = $this->messages[$responseCode];
        }

        $responseData = [
            'data' => $data
        ];

        $response = $this->getResponseHandler();
        $response->setTemplate('User.settings.html.twig');
        $response->setResponse($responseData, $responseCode, false);

        if ($error)
            $response->setErrorResponse(new \Exception($error));
    }

    /**
     * Helper function to build the User Settings form
     * @return Form
     * @throws Exception
     */
    private function buildForm()
    {
        global $pool;
        $form = Form::build(0, 'myProfile', 'My Profile')->setPublic(true);

        $keydict = Users::sqlFetch($pool->User->getIdUser());
        $form->accepts(
            $keydict->firstName,
            $keydict->lastName,
            $keydict->email,
            $keydict->alternateEmail,
            $keydict->phone,
            $keydict->status->isAway,

            $keydict->status->mustChangePassword,

            Password::build('currentPassword', 'Current Password')->setMeta('ignore'),
            Password::build('newPassword', 'New Password')->setMeta('ignore'),
            Password::build('repeatNewPassword', 'Repeat New Password')->setMeta('ignore')
        );

        $formkeydict = $form->getKeydict();
        $formkeydict->email->setMeta('oldValue', $formkeydict->email->get());

        $formkeydict->addLateValidator('emailExists', function () use ($formkeydict) {
            if ($formkeydict->getField('email')->get() != $formkeydict->email->getMeta('oldValue')) {
                if (CoreUser::emailExists($formkeydict->getField('email')->get())) {
                    throw new Exception('Email already in use!');
                }
            }
        });

        return ['form' => $form, 'keydict' => $keydict];
    }

}