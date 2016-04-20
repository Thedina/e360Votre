<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 7/11/2015
 * Time: 4:26 PM
 */

namespace eprocess360\v3core;


use eprocess360\v3core\Session\Exception\AbnormalSessionException;
use eprocess360\v3core\Session\Strategy\NoSession;


/**
 * Class Session
 * @package eprocess360\v3core
 */
class Session
{
    const SESSION_EMPTY = 0;
    const SESSION_START = 1;
    const USER_EXPIRED = 2;
    const USER_OKAY = 4;
    const KEEP_ALIVE = 32;
    /**
     * @var Session The reference to *Singleton* instance of this class
     */
    private static $instance;

    /**
     * Returns the *Singleton* instance of this class
     * @return Session The *Singleton* instance.
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static(User::getEmpty());
        }

        return static::$instance;
    }

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     * @param User $user
     * @throws AbnormalSessionException
     */
    protected function __construct(User $user)
    {
        $this->user = $user;
        $this->cookie_iduser = 0;
        $this->state = self::SESSION_EMPTY;
        $this->strategy = new NoSession();
        if ($user->isIdentified()) {
            $this->user = $user;
            $this->state = self::SESSION_START + self::USER_OKAY;
            return $this;
        }

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
            $this->state = self::SESSION_START;
        }

        $this->use_cookie = isset($_COOKIE["eproid"]) ? (bool)$_COOKIE["eproid"] : false;
        if ($this->use_cookie) {
            $this->cookie_iduser = $_COOKIE["eproid"]; // iduser
            $this->cookie_series = $_COOKIE["eprose"]; // series
            $this->cookie_seauth = $_COOKIE["eprosa"]; // sessionauth
        } elseif (isset($_SESSION["eproid"])) {
            $this->cookie_iduser = $_SESSION["eproid"]; // iduser
            $this->cookie_series = $_SESSION["eprose"]; // series
            $this->cookie_seauth = $_SESSION["eprosa"]; // sessionauth
            $this->cookie_iduser = $_SESSION["eproid"];
        }

        if ($this->cookie_iduser) {
            $iduser = (int)$this->cookie_iduser;
            $series = DB::cleanse(substr($this->cookie_series,0,16));
            $sql = "SELECT *, UNIX_TIMESTAMP(`expires`) as `expires` FROM `Sessions` WHERE `idUser` = '{$iduser}' AND `series` = '{$series}'";
            $results = DB::sql($sql);

            if (sizeof($results)) {
                $result = array_shift($results);
                if ($result['auth'] == $this->cookie_seauth) {
                    // user valid - session normal but may be expired
                    $this->user = User::get($result['idUser']);
                    if ($result['expires']<time()) {
                        self::cleanSessionCookies();
                        self::killByIdUserSeries($result['idUser'], $result['series']);
                        $this->state += self::USER_EXPIRED;
                    } else {
                        self::update($this->user->getIdUser(), $result['series'], $this->use_cookie);
                        $this->state += self::USER_OKAY;
                    }
                } else {
                    self::killAllByIdUser($this->cookie_iduser);
                    throw new AbnormalSessionException("Session found by auth token mismatched. Removed all of user's previous sessions as precaution.");
                }
            } else {
                return $this->strategy->onSessionFailure($this);
            }
        } else {
            return $this->strategy->onSessionFailure($this);
        }
        return $this;
    }

    public static function cleanSessionCookies()
    {
        //echo 'clean cookies'.PHP_EOL;
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        setcookie("eproid", null);
        setcookie("eprose", null);
        setcookie("eprosa", null);
        $_SESSION["eproid"] = null;
        $_SESSION["eprose"] = null;
        $_SESSION["eprosa"] = null;
    }

    /**
     * @param User $user
     * @param bool|false $cookie
     * @return Session
     */
    public static function create(User $user, $cookie = false)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $series = Toolbox::generateSalt();
        $auth = Toolbox::generateSalt();
        $keep_alive = self::KEEP_ALIVE;

        $sql = "INSERT INTO Sessions (idUser, series, auth, expires) VALUES ('{$user->getIdUser()}', '{$series}', '{$auth}', date_add(CURRENT_TIMESTAMP(), INTERVAL {$keep_alive} DAY))";
        DB::sql($sql);

        $_SESSION["eproid"] = $user->getIdUser();
        $_SESSION["eprose"] = $series;
        $_SESSION["eprosa"] = $auth;

        if ($cookie) {
            setcookie("eproid", $user->getIdUser(), time() + self::KEEP_ALIVE * 86400);
            setcookie("eprose", $series, time() + self::KEEP_ALIVE * 86400);
            setcookie("eprosa", $auth, time() + self::KEEP_ALIVE * 86400);
        } else {
            setcookie("eproid", null);
            setcookie("eprose", null);
            setcookie("eprosa", null);
        }
        static::$instance = new static($user, new NoSession());
        return static::$instance;
    }

    /**
     * @param $iduser
     * @param $series
     * @param bool|false $cookie
     */
    protected static function update($iduser, $series, $cookie = false)
    {
        $iduser = (int)$iduser;
        $series = DB::cleanse(substr($series,0,16));
        $auth = Toolbox::generateSalt();
        $sql = "UPDATE Sessions SET auth = '{$auth}', expires = date_add(CURRENT_TIMESTAMP(), INTERVAL 1 MONTH) WHERE idUser = '{$iduser}' AND series = '{$series}'";
        DB::sql($sql);
        $_SESSION['eprosa'] = $auth;
        if ($cookie) {
            setcookie('eprosa', $auth, time() + self::KEEP_ALIVE * 86400);
        }
    }

    public function kill()
    {
        self::killByIdUserSeries($this->cookie_iduser, $this->cookie_series);
    }

    /**
     * @param $iduser
     * @param $series
     */
    protected static function killByIdUserSeries($iduser, $series)
    {
        $iduser = (int)$iduser;
        $series = DB::cleanse(substr($series,0,16));
        $sql = "DELETE FROM Sessions WHERE idUser = {$iduser} AND series = '{$series}'";
        DB::sql($sql);
    }

    /**
     * @param $iduser
     */
    protected static function killAllByIdUser($iduser)
    {
        $iduser = (int)$iduser;
        $sql = "DELETE FROM Sessions WHERE idUser = {$iduser}";
        DB::sql($sql);
    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * *Singleton* instance.
     *
     * @return void
     */
    private function __clone()
    {
    }
}