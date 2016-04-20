<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 6/9/2015
 * Time: 4:24
 */

namespace eprocess360\v3core;


use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model\Users;
use Exception;

/** 
 * Class User
 * @package eprocess360\v3core
 * @deprecated Needs to be moved
 */
class User
{
    const HASH_ALGO = PASSWORD_BCRYPT;
    const HASH_COST = 12;

    private $controllerPermissions = [];
    private $projectPermissions = [];
    private $failed = false;
    /**
     * @param array $user_data
     */
    protected function __construct($user_data = [])
    {
        $this->valid = false;
        $this->data = Users::keydict();
        if (is_array($user_data) && isset($user_data['idUser'])) {
            $this->data->wakeup($user_data);
            $this->valid = true;
        } elseif ($user_data instanceof Table) {
            $this->data = $user_data;
            $this->valid = true;
        }
    }

    /**
     * todo move validation inside of function
     * @param $fname
     * @param $lname
     * @param $email
     * @param $password
     * @param $phone
     * @return User
     */
    public static function register($fname, $lname, $email, $password, $phone)
    {
        // need to add
        $user = User::create($email, $password); // get a valid user checking email and password
        $user->data->firstName->set($fname);
        $user->data->lastName->set($lname);
        $user->data->phone->set($phone);
        $user->data->status->canLogin->set(true);
        $user->data->status->isActive->set(true);
        $user->save();
        return $user;
    }

    /**
     * Get an empty User object
     * @return User
     */
    public static function getEmpty()
    {
        return new self();
    }

    /**
     * @return User
     */
    public static function getFailed()
    {
        $user = new self();
        $user->setFailedState();
        return $user;
    }

    public function setFailedState()
    {
        $this->failed = true;
    }

    /**
     * Is the User valid
     * @return bool
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * Does the User have and Id
     * @return bool
     */
    public function isIdentified()
    {
        return (bool)$this->data->idUser->get();
    }

    /**
     * Get and return a specific idUser
     * @param $iduser
     * @return User
     * @throws Exception
     */
    public static function get($iduser)
    {
        $userModel = Users::sqlFetch($iduser);
        return new self($userModel);
    }


    /**
     * Get the idUser
     * @return int
     * @throws Exception
     */
    public function getIdUser()
    {
        if ($this->isIdentified())
            return $this->data->idUser->get();
        else
            throw new Exception("getIdUser failed because a User is not loaded.");
    }

    /**
     * @param $email
     * @param $password
     * @return User
     * @throws Exception
     */
    public static function login($email, $password)
    {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address and/or password.");
        }
        $email = DB::cleanse($email);

        $sql = "SELECT * FROM Users WHERE `email` = '{$email}' LIMIT 1";
        $results = DB::sql($sql);

        if (!empty($results) && self::verifyPassword($password, $results[0]['password'])) {
            return new self(array_shift($results));
        }
        throw new Exception("Invalid email address and/or password.");
    }

    /**
     * Create a new user
     * @param $email
     * @param $password
     * @return User
     * @throws Exception
     */
    public static function create($email, $password)
    {
        $email = trim($email);
        if (self::emailExists($email)) {
            throw new Exception("Email address already in use.");
        }
        Users::keydict()->password->validate($password);
        $email = DB::cleanse($email);
        $password = self::hashPassword($password);
        $sql = "INSERT INTO Users (email, `password`) VALUES ('{$email}','{$password}')";
        DB::sql($sql);
        return User::get(DB::iid());
    }

    /**
     * @param $password
     * @param $old_password
     * @throws Exception
     */
    public function updatePassword($password, $old_password = false)
    {
        if (!$this->valid) throw new Exception;

        $sql = "SELECT `idUser`, `password` FROM Users WHERE `idUser` = {$this->getIdUser()}";
        $results = DB::sql($sql);

        if (!empty($results) && self::verifyPassword($old_password, $results[0]['password'])) {
            $this->setPassword($password, false);
            return;
        }

        // password reset without current password
        if (!empty($results) && !$old_password && $this->isMustChangePassword()) {
            $this->setPassword($password);
            $this->unsetMustChangePassword();
            return;
        }

        throw new Exception('Your password is incorrect.');
    }

    /**
     * Begin the password reset process.
     * todo email
     * @param $email
     * @return bool
     * @throws Exception
     */
    public static function resetPassword($email)
    {
        global $pool;
        $user = self::getByEmail($email);
        if ($user->valid) {
            // do reset
            $resetcode = Toolbox::generateSalt(8);
            $sql = "UPDATE Users SET resetCode = '{$resetcode}' WHERE idUser = '{$user->getIdUser()}'";
            DB::sql($sql);
            // $resetemail = $user->getEmail();
            // message: "Hello,\n\nA password reset was requested for this account.  If this request was made in error, you can safely disregard this e-mail.\n\nIn order to proceed with the password reset, please visit the following address:\n\n{$pool->SysVar->get('siteUrl')}/login/reset/do/{urlencode($email)}/{$resetcode}\n\nAlternatively, you may also go to {$pool->SysVar->get('siteUrl')}/login/reset/do and enter your e-mail address along with the reset code: {$resetcode}";
            // send email to $resetemail
            return true;
        }
        throw new Exception("We couldn't find that e-mail address. If you have completely lost access to your e-mail or account information, please <a href='{$pool->SysVar->get('siteUrl')}/register'>re-register</a>.");
    }

    /**
     * Use a reset code to reset the password on a User account
     * todo email
     * @param $email
     * @param $resetCode
     * @return bool
     * @throws Exception
     */
    public static function useResetCode($email, $resetCode) {
        $user = self::getByEmail($email);
        if ($user->valid) {
            // compare reset codes
            if ($user->data->resetCode->get() == trim($resetCode)) {
                $temporaryPassword = Toolbox::generateSalt(8);
                $user->setPassword($temporaryPassword);
                //$resetemail = $user->getEmail();
                // $message = "Hello,\n\nYour password was successfully reset!  You may now log in at: ".$pool->SysVar->get('siteUrl')."/login with the new password: ".$temppass;
                return true;
            }
            else {
                throw new Exception("The reset code provided doesn't match the code on file.");
            }
        }
        throw new Exception;
    }

    /**
     * @param $email
     * @return User
     * @throws Exception
     */
    public static function getByEmail($email)
    {
        $email = DB::cleanse($email);
        $sql = "SELECT * FROM Users WHERE `email` = '{$email}'";
        $results = DB::sql($sql);
        if ($results && sizeof($results)) {
            return new self(array_shift($results));
        }
        return new self(0);
    }

    /**
     * Set password for User
     * @param $password
     * @throws Exception
     */
    public function setPassword($password, $mustChangePassword = 1)
    {
        if (!$this->valid) throw new Exception;
        $password = $this->data->password->validate($password);
        $password = self::hashPassword($password);
        $this->data->status->mustChangePassword->set($mustChangePassword);
        $status = $this->data->status->sleep();
        $status = ord($status['status_0']);
        $sql = "UPDATE Users SET `password` = '{$password}', `status_0` = {$status} WHERE iduser = {$this->getIdUser()}";
        DB::sql($sql);
    }

    /**
     * Checks to see in the email exists in the Users already
     * @param $email
     * @return bool
     * @throws Exception
     */
    public static function emailExists($email)
    {
        $email = Users::keydict()->email->validate($email);
        $email = DB::cleanse($email);
        $sql = "SELECT iduser FROM Users WHERE email = '{$email}' LIMIT 1";
        if (sizeof(DB::sql($sql))) {
            return true;
        }
        return false;
    }

    /**
     * Password hash (use PHP 5.5 secure hash implementation)
     * @param $password
     * @return string
     */
    public static function hashPassword($password)
    {
        return password_hash($password, self::HASH_ALGO, ['cost'=>self::HASH_COST]);
    }

    /**
     * Password hash verify (use PHP 5.5 secure hash implementation)
     * @param $password
     * @param $hash
     * @return bool
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Find users whose full names match $text
     * @param $text
     * @return array
     * @throws \Exception
     */
    public static function findUser($text) {
        $text = DB::cleanse($text);
        $found = [];

        $users = DB::sql("SELECT * FROM Users WHERE CONCAT(firstName, ' ', lastName, ' ', email) LIKE '%{$text}%'");

        if(!empty($users)) {
            foreach($users as $u) {
                $found[] = [
                    'id'=>(int)$u['idUser'],
                    'name'=>$u['firstName'].' '.$u['lastName'].' '.$u['email']
                ];
            }
        }

        return $found;
    }

    /**
     * Get the User's full name
     * @return string
     * @throws Exception
     */
    public function getFullName()
    {
        if (!$this->isValid()) throw new Exception("Trying to get name on invalid User.");
        return sprintf("%s %s", $this->data->firstName->get(), $this->data->lastName->get());
    }

    /**
     * Whether or not the the User has accepted the EULA
     * @return mixed
     * @throws Exception
     */
    public function hasAcceptedEULA()
    {
        if (!$this->isValid()) throw new Exception;
        return $this->data->status->hasAcceptedEULA->get();
    }

    /**
     * Updates the User when they have accepted the EULA
     * @throws Exception
     */
    public function setAcceptedEULA()
    {
        if (!$this->isValid()) throw new Exception;
        $this->data->status->hasAcceptedEULA->set(1);
        $this->save();
    }

    /**
     * Save the keydict minus password or idUser
     * @return User
     * @throws Exception
     */
    public function save()
    {
        $this->data->update();
    }

    /**
     * A basic check to see if the User has completed their profile.
     * @return bool
     * @throws Exception
     */
    public function hasCompleteProfile()
    {
        if (!$this->isValid()) throw new Exception;
        if ($this->getFirstName() == '' || $this->getLastName() == '' || $this->getPhone() == '') return false;
        return true;
    }

    /**
     * Whether or not the user is active
     * @return int
     */
    public function isActive()
    {
        if (!$this->isValid()) return 0;
        return $this->data->status->isActive->get();
    }

    /**
     * @return boolean
     */
    public function isFailed()
    {
        return $this->failed;
    }

    /**
     * Get the User's email address
     * @return mixed
     * @throws Exception
     */
    public function getEmail()
    {
        if (!$this->isValid()) throw new Exception;
        return $this->data->email->get();
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getFirstName()
    {
        if (!$this->isValid()) throw new Exception;
        return $this->data->firstName->get();
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getLastName()
    {
        if (!$this->isValid()) throw new Exception;
        return $this->data->lastName->get();
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getPhone()
    {
        if (!$this->isValid()) throw new Exception;
        return $this->data->phone->get();
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getAlternateEmail()
    {
        if (!$this->isValid()) throw new Exception;
        return $this->data->alternateEmail->get();
    }

    /**
     * Whether or not the User has indicated they are away, out of town.
     * @return mixed
     * @throws Exception
     */
    public function isAway()
    {
        if (!$this->isValid()) throw new Exception;
        return $this->data->status->isAway->get();
    }

    public function isMustChangePassword() {
        if (!$this->isValid()) throw new Exception;
        return $this->data->status->mustChangePassword->get();
    }

    public function unsetMustChangePassword() {
        // make it false again
        if (!$this->isValid()) throw new Exception;
        $this->data->status->mustChangePassword->set(0);
        $this->save();
    }

    public function isCanLogin() {
        if (!$this->isValid()) throw new Exception;
        return $this->data->status->canLogin->get();
    }


    /**
     * Loads User permissions from that database and adds them into controllerPermissions and projectPermissions hash tables.
     * @param bool|false $getSystemRoles
     * @param bool|false $getProjectRoles
     * @param bool|false $idProject
     * @return $this
     * @throws Exception
     */
    public function setPermissions($getSystemRoles = false, $getProjectRoles = false, $idProject = false)
    {
        $idUser = $this->getidUser();
        $sql = "SELECT * FROM UserRoles LEFT JOIN Roles ON Roles.idSystemRole = UserRoles.idSystemRole WHERE UserRoles.idUser = {$idUser}";
        if($getSystemRoles && !$getProjectRoles)
            $sql = $sql." AND UserRoles.idSystemRole > 0";
        else if(!$getSystemRoles && $getProjectRoles)
            $sql = $sql." AND UserRoles.idProject > 0";
        else if(!$getSystemRoles && !$getProjectRoles && $idProject)
            $sql = $sql." AND UserRoles.idProject = {$idProject}";
        else if(!$getSystemRoles && !$getProjectRoles && !$idProject)
            $sql = false;

        $permissions = [];
        if($sql)
            $permissions = DB::sql($sql);

        $controllerPermissions = $this->controllerPermissions;
        $projectPermissions = $this->projectPermissions;
        foreach($permissions as $permission) {
            if($permission['idController'] !== NULL){
                $insert = (int)$permission['idController'];
                $flags = (int)$permission['flags_0'];
                if(isset($controllerPermissions[$insert]))
                    $controllerPermissions[$insert] = $controllerPermissions[$insert] | $flags;
                else
                    $controllerPermissions[$insert] = $flags;
            }
            if($permission['idProject'] !== NULL){
                $insert = (int)$permission['idProject'];
                $role = (int)$permission['idLocalRole'];
                $projectPermissions[$insert][$role] = $role;
            }
        }

        $this->controllerPermissions = $controllerPermissions;
        $this->projectPermissions = $projectPermissions;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getControllerPermissions()
    {
        return $this->controllerPermissions;
    }

    /**
     * @return mixed
     */
    public function getProjectPermissions()
    {
        return $this->projectPermissions;
    }

    /**
     * Adds permissions in case permissions are updated/added and used within the same request.
     * @param $idController
     * @param $flags
     * @param $idProject
     * @param ...$roles
     * @return $this
     */
    public function addPermissions($idController, $flags, $idProject, ...$roles)
    {
        if($idController !== NULL){
            $insert = (int)$idController;
            $flags = (int)$flags;
            if(isset($this->controllerPermissions[$insert]))
                $this->controllerPermissions[$insert] = $this->controllerPermissions[$insert] | $flags;
            else
                $this->controllerPermissions[$insert] = $flags;
        }
        if($idProject !== NULL){
            $insert = (int)$idProject;
            foreach($roles as $role) {
                $role = (int)$role;
                $this->projectPermissions[$insert][$role] = $role;
            }
        }

        return $this;
    }
}