<?php

namespace eprocess360\v3controllers\Login;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Form;
use eprocess360\v3core\Keydict\Entry\Email;
use eprocess360\v3core\Keydict\Entry\Password;
use eprocess360\v3core\Keydict\Entry\String;
use eprocess360\v3core\Session;
use eprocess360\v3core\User;
use Exception;

/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 12/8/2015
 * Time: 9:34 AM
 */
class Login extends Controller
{
    use Router;


    /*********************************************   #ROUTING#  **********************************************/


    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     */
    public function routes()
    {
        $this->routes->map('GET', '', function () {
            $this->getLoginAPI();
        });
        $this->routes->map('POST', '', function () {
            $this->postLoginAPI();
        });
        $this->routes->map('GET', '/reset', function () {
            $this->getResetAPI();
        });
        $this->routes->map('POST', '/reset', function () {
            $this->postResetAPI();
        });
        $this->routes->map('GET', '/reset/do', function () {
            $this->getResetDoAPI();
        });
        $this->routes->map('POST', '/reset/do', function () {
            $this->postResetDoAPI();
        });
    }

    /**
     * API Function to get the Login page. If pool user is Identified, header to /eula.
     * @throws Exception
     */
    public function getLoginAPI()
    {
        global $pool;
        if ($pool->User->isIdentified()) {
            if($pool->User->hasAcceptedEULA()) {
                header('Location: ' . $pool->SysVar->get('siteUrl') . '/dashboard');
            }
            else {
                header('Location: ' . $pool->SysVar->get('siteUrl') . '/eula');
            }
            die();
        }
        $form = Form::build(0, 'userLogin', 'User Login')->setPublic(true);
// Declare what values the form can accept

        $form->accepts(
            Email::build('loginEmail', 'Email')->setRequired(),
            Password::build('loginPassword', 'Password')->setRequired()
        )->setLabel('Login')->setDescription('Login form.');

        $data['form'] = $form;
        $data['keydict'] = $form->getKeydict();

        $responseData = [
            'data' => $data
        ];

        $response = $this->getResponseHandler();
        $response->setTemplate('Login.main.html.twig');
        $response->setResponse($responseData);
    }

    /**
     * API Function that logs the user in. If successful redirect the User to /eula.
     * @throws Exception
     */
    public function postLoginAPI()
    {
        global $pool;
        
        $form = Form::build(0, 'userLogin', 'User Login')->setPublic(true);
        // Declare what values the form can accept

        $form->accepts(
            Email::build('loginEmail', 'Email')->setRequired(),
            Password::build('loginPassword', 'Password')->setRequired()->setMeta('ignore')
        )->setLabel('Login')->setDescription('Login form.');

        $form->acceptPost();
        $keydict = $form->getKeydict();
        if (!$keydict->hasException()) {
            try {
                
                $user = User::login($keydict->getField('loginEmail')->sleep(), $keydict->getField('loginPassword')->sleep());
                $user->setPermissions(true);
                if (!$user->isCanLogin()) {
                    throw new \Exception('user status canLogin: ' . $user->isCanLogin());
                }
                $session = Session::create($user);

                $pool->add($user, 'User');
                $pool->add($session, 'Session');
                if($user->hasAcceptedEULA()) {
                    header('Location: ' . $pool->SysVar->get('siteUrl') . '/dashboard');
                    if ($pool->User->isMustChangePassword()) {
                        header('Location: ' . $pool->SysVar->get('siteUrl') . '/profile');
                    }
                }
                else
                    header('Location: ' . $pool->SysVar->get('siteUrl') . '/eula');
                die();
            } catch (Exception $e) {
                $pool->add(User::getFailed(), 'User');
            }
        }
        else {
            $data['errors'] = $form->getKeydict()->getException();
        }



        $data['form'] = $form;
        $data['keydict'] = $form->getKeydict();
        $responseData = [
            'data' => $data
        ];

        $response = $this->getResponseHandler();
        $response->setTemplate('Login.main.html.twig');
        $response->setResponse($responseData);
    }

    /**
     * API Function to get the Reset Password form.
     * @throws Exception
     */
    public function getResetAPI()
    {
        global $pool;

        $form = Form::build(0, 'resetPassword', 'Reset Password')->setPublic(true);

        $form->accepts(
            Email::build('resetEmail', 'Email Address')->setRequired()
        )->setLabel('Reset Password')->setDescription('Enter your e-mail address to begin the reset process.');

        $data['form'] = $form;
        $data['keydict'] = $form->getKeydict();

        $responseData = [
            'data' => $data
        ];

        $response = $this->getResponseHandler();
        $response->setTemplate('Login.reset.html.twig');
        $response->setResponse($responseData);
    }

    /**
     * API Function to send a Reset Password Form.
     * @throws Exception
     */
    public function postResetAPI()
    {
        global $pool;
        $form = Form::build(0, 'resetPassword', 'Reset Password')->setPublic(true);

        $form->accepts(
            Email::build('resetEmail', 'Email Address')->setRequired()
        )->setLabel('Reset Password')->setDescription('Enter your e-mail address to begin the reset process.');

        try {
            $form->acceptPost();
            if (!$form->getKeydict()->hasException()) {
                // reset password
                User::resetPassword($form->getKeydict()->getField('resetEmail')->sleep());
                // if successful, push to /login/reset/do
                header('Location: ' . $pool->SysVar->get('siteUrl') . '/login/reset/do');
                die();
            }
            else {
                $data['errors'] = $form->getKeydict()->getField('resetEmail')->getException();
            }
        }
        catch (Exception $iue) {
            $form->getKeydict()->getField('resetEmail')->addException($iue);

        }
        // figure out hwo to grab the URL string, w/ the email + reset code, figure out a better way to arrange things

        $data['form'] = $form;
        $data['keydict'] = $form->getKeydict();

        $responseData = [
            'data' => $data
        ];

        $response = $this->getResponseHandler();
        $response->setTemplate('Login.reset.html.twig');
        $response->setResponse($responseData);
    }

    /**
     * API Function to get the Reset Password Form, where the user may input their email and received reset code to reset their password.
     * @throws Exception
     */
    public function getResetDoAPI()
    {
        $form = Form::build(0,'resetPassword', 'Reset Password')->setPublic(true);

        $form->accepts(
            Email::build('resetEmail', 'Email Address')->setRequired(),
            String::build('resetCode', 'Reset Code')->setRequired()
        )->setLabel('Reset Password')->setDescription("We've sent you an e-mail that contains a special password reset code.  Please check your e-mail and click the reset link in it, or copy the code in the space provided below.");

        $data['form'] = $form;
        $data['keydict'] = $form->getKeydict();

        $responseData = [
            'data' => $data
        ];

        $response = $this->getResponseHandler();
        $response->setTemplate('Login.reset.do.html.twig');
        $response->setResponse($responseData);
    }

    /**
     * API Function to update the Reset Password Form, where the user may input their email and received reset code to reset their password.
     * @throws Exception
     */
    public function postResetDoAPI()
    {
        global $pool;

        $form = Form::build(0,'resetPassword', 'Reset Password')->setPublic(true);

        $form->accepts(
            Email::build('resetEmail', 'Email Address')->setRequired(),
            String::build('resetCode', 'Reset Code')->setRequired()
        )->setLabel('Reset Password')->setDescription("We've sent you an e-mail that contains a special password reset code.  Please check your e-mail and click the reset link in it, or copy the code in the space provided below.");

        $form->acceptPost();

        if (!$form->getKeydict()->hasException()) {
            // make temp password

            try {
                $isReset = User::useResetCode($form->getKeydict()->getField('resetEmail')->sleep(), $form->getKeydict()->getField('resetCode')->sleep());

                if ($isReset) {
                    header('Location: ' . $pool->SysVar->get('siteUrl') . '/login'); // or some intermediate confirmation page
                    die();
                }
                else {
                    throw new Exception("The reset code you entered is invalid. Please try again.");
                }
            }
            catch (Exception $e) {
                $form->getKeydict()->getField('resetEmail')->addException($e);
                $form->setDescription($e->getMessage());
            }
        }

        $data['form'] = $form;
        $data['keydict'] = $form->getKeydict();

        $responseData = [
            'data' => $data
        ];

        $response = $this->getResponseHandler();
        $response->setTemplate('Login.reset.do.html.twig');
        $response->setResponse($responseData);
    }
}