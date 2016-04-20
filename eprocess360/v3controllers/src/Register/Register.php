<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 12/8/15
 * Time: 2:37 PM
 */

namespace eprocess360\v3controllers\Register;


use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\Trigger\InterfaceTriggers;
use eprocess360\v3core\Controller\Triggers;
use eprocess360\v3core\Form;
use eprocess360\v3core\Keydict\Entry\Email;
use eprocess360\v3core\Keydict\Entry\Password;
use eprocess360\v3core\Keydict\Entry\PhoneNumber;
use eprocess360\v3core\Keydict\Entry\String;
use eprocess360\v3core\Keydict\Exception\InvalidValueException;
use eprocess360\v3core\Request\Request;
use eprocess360\v3core\Session;
use eprocess360\v3core\Session\Exception\BadCredentialsException;
use eprocess360\v3core\User;

/**
 * Class Register
 * @package eprocess360\v3controllers\Register
 */
class Register extends Controller implements InterfaceTriggers
{
    use Router, Triggers;


    /*********************************************   #ROUTING#  **********************************************/


    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     */
    public function routes()
    {
        $this->routes->map('GET', '', function () {
            $this->getRegisterAPI();
        });
        $this->routes->map('POST', '', function () {
            $this->postRegisterAPI();
        });
    }

    /**
     * API Function that gives the User registration form.
     * @throws \Exception
     */
    public function getRegisterAPI()
    {
        $form = Form::build(0, 'userRegistration', 'User Registration')->setPublic(true);

        $form->accepts(
            String::build('firstName', 'First Name')->setRequired(),
            String::build('lastName', 'Last Name')->setRequired(),
            Email::build('email', 'Email Address')->setRequired(),
            Email::build('email2', 'Repeat Email')->setRequired(),
            PhoneNumber::build('phone', 'Phone Number')->setRequired(),
            Password::build('password', 'Password')->setRequired(),
            Password::build('password2', 'Repeat Password')->setRequired()
        )->setLabel('Registration')->setDescription('Registration form.');

        $data['form'] = $form;
        $data['keydict'] = $form->getKeydict();

        $responseData = [
            'data' => $data
        ];

        $response = $this->getResponseHandler();
        $response->setTemplate('Register.main.html.twig');
        $response->setResponse($responseData);
    }

    /**
     * API Function that processes the User Registration form. On a successful registration, redirected to /eula
     * @throws \Exception
     */
    public function postRegisterAPI()
    {
//        global $pool;
//        $httpCode = 200;
//        $error = false;
//        $requestData = Request::get()->getRequestBody();
//
//        try {
//            foreach ($requestData as $item) {
//            }
//
//            $firstName = String::validate($requestData['firstName']);
//            $lastName = String::validate($requestData['lastName']);
//            $email = Email::validate($requestData['email']);
//            $email2 = Email::validate($requestData['email2']);
//            $phone = PhoneNumber::validate($requestData['phone']);
//            $password = Password::validate($requestData['password']);
//            $password2 = Password::validate($requestData['password2']);
//
//            if ($email !== $email2) {
//                throw new \Exception('Emails do not match.', 301);
//            }
//            if ($password !== $password2) {
//                throw new \Exception('Passwords do not match.', 301);
//            }
//
//            $user = User::register(
//                $firstName,
//                $lastName,
//                $email,
//                $password,
//                $phone
//            );
//            $session = Session::create($user);
//            $pool->add($user, 'User');
//            $pool->add($session, 'Session');
//            $this->trigger('onSuccess');
//            $pool->User->setPermissions(true);
//            header('Location: ' . $pool->SysVar->get('siteUrl') . '/eula');
//            die();
//        }
//        catch (BadCredentialsException $e) {
//            $pool->add(User::getFailed(), 'User');
//        }
//        catch (InvalidValueException $e) {
//            $error = $e->getMessage();
//            $httpCode = $e->getCode() ? $e->getCode() : 500;
//            var_dump($error);
//        }
//        catch (\Exception $e) {
//            $error = $e->getMessage();
//            $httpCode = $e->getCode() ? $e->getCode() : 500;
//        }
//
//
//        $response = $this->getResponseHandler();
//        $response->setTemplate('Register.main.html.twig');
//        $response->setResponse([], $httpCode, false);
//
//        if($error) {
//            $response->setErrorResponse(new \Exception($error));
//        }
        global $pool;

        $form = Form::build(0, 'userRegistration', 'User Registration')->setPublic(true);

        $form->accepts(
            String::build('firstName', 'First Name')->setRequired(),
            String::build('lastName', 'Last Name')->setRequired(),
            Email::build('email', 'Email Address')->setRequired(),
            Email::build('email2', 'Repeat Email Address')->setRequired(),
            PhoneNumber::build('phone', 'Phone Number')->setRequired(),
            Password::build('password', 'Password')->setRequired(),
            Password::build('password2', 'Repeat Password')->setRequired()
        )->setLabel('Registration')->setDescription('Registration form.');

        $form->acceptPost();
        if (!$form->getKeydict()->hasException()) {
            try {
                if ($form->getKeydict()->getField('email')->getValue() != $form->getKeydict()->getField('email2')->getValue()) {
                    throw new \Exception('Emails do not match.');
                }

                if ($form->getKeydict()->getField('password')->getValue() != $form->getKeydict()->getField('password2')->getValue()) {
                    throw new \Exception('Passwords do not match.');
                }

                // create user
                $user = User::register(
                    $form->getKeydict()->getField('firstName')->sleep(),
                    $form->getKeydict()->getField('lastName')->sleep(),
                    $form->getKeydict()->getField('email')->sleep(),
                    $form->getKeydict()->getField('password')->sleep(),
                    $form->getKeydict()->getField('phone')->sleep()
                );
                $session = Session::create($user);
                $pool->add($user, 'User');
                $pool->add($session, 'Session');
                $this->trigger('onSuccess');
                $pool->User->setPermissions(true);

                header('Location: ' . $pool->SysVar->get('siteUrl') . '/eula');
                die();
            } catch (BadCredentialsException $e) {
                $pool->add(User::getFailed(), 'User');
            } catch (\Exception $e) {
                $data['errors'][] = $e;
            }
        } else {
            $data['errors'] = $form->getKeydict()->getException();
        }

        $data['form'] = $form;
        $data['keydict'] = $form->getKeydict();

        $responseData = [
            'data' => $data
        ];

        $response = $this->getResponseHandler();
        $response->setTemplate('Register.main.html.twig');
        $response->setResponse($responseData);
    }


    /*********************************************   #TRIGGERS#  *********************************************/


    /**
     * Trigger fired on a successful User Registration
     * @param \Closure $closure
     * @return \eprocess360\v3core\Controller\Trigger\Trigger
     */
    public function onSuccess(\Closure $closure)
    {
        return $this->addTrigger(__FUNCTION__, $closure);
    }
}