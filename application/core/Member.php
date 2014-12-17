<?php


class Member
{


    /**
     * $_profile
     *
     * Default member profile
     */

    private static $_profile = null;


    /**
     * $_permissions
     *
     * Default member permissions
     * Empty array denied from all delegated actions
     */

    private static $_permissions = array();


    /**
     * getProfile
     *
     * Return full profile data of member
     *
     * @return StdClass Profile data
     */

    public static function getProfile()
    {
        return self::$_profile;
    }


    /**
     * getPermissions
     *
     * Return existst permissions
     *
     * @return StdClass Profile permissions
     */

    public static function getPermissions()
    {
        return self::$_permissions;
    }


    /**
     * isPermission
     *
     * Return status of existst permission
     *
     * @return bool Status of existst permission
     */

    public static function isPermission($name)
    {
        return property_exists(self::$_permissions, $name);
    }


    /**
     * isAuth
     *
     * Return auth status of member
     *
     * @return bool Auth status of member
     */

    public static function isAuth()
    {
        return self::$_profile->auth;
    }


    /**
     * beforeInit
     *
     * Init default profile via config
     *
     * @return null
     */

    public static function beforeInit()
    {
        self::$_profile = App::getConfig(App::isCLI() ? 'member_cli' : 'member_guest');
    }


    /**
     * init
     *
     * Try member authenfication
     *
     * @return null
     */

    public static function init()
    {

        $initPermissions = false;
        if (App::isCLI()) {
            $initPermissions = true;
        } else {
            $cnf = App::getConfig('main')->system;
            if (array_key_exists($cnf->cookie_name, $_COOKIE)) {
                $value = (string) $_COOKIE[$cnf->cookie_name];
                $value = trim(substr($value, 0, 32));
                if ($value) {
                    $conn = DBI::getConnection('slave');
                    $data = $conn->sendQuery(
                        'SELECT * FROM members WHERE cookie = :cookie',
                        array(':cookie' => $value)
                    )->fetch(PDO::FETCH_OBJ);
                    if (!$data) {
                        setcookie($cnf->cookie_name, '', -1, '/');
                    } else {
                        $initPermissions = true;
                        $expires = time() + $cnf->cookie_expires_time;
                        setcookie($cnf->cookie_name, $value, $expires, '/');
                        self::$_profile->auth = true;
                        foreach ($data as $k => $v) {
                            self::$_profile->{$k} = $v;
                        }
                    }
                }
            }
        }
        if ($initPermissions) {
            self::initPermissions();
        }

    }


    /**
     * initPermissions
     *
     * Set member role permissions with group_id
     *
     * @return null
     */

    public static function initPermissions()
    {
        $conn = DBI::getConnection('slave');
        $permissions = $conn->sendQuery(
            'SELECT p.name
                FROM groups_permissions gp
                INNER JOIN permissions p ON p.id = gp.permission_id
                WHERE gp.group_id = :group_id',
            array(':group_id' => self::$_profile->group_id)
        )->fetch(PDO::FETCH_OBJ);
        self::$_permissions = $permissions ? $permissions : new StdClass();
    }


    /**
     * signOut
     *
     * Sign out with remove member auth cookie
     *
     * @return null
     */

    public static function signOut()
    {
        setcookie(App::getConfig('main')->system->cookie_name, '', -1, '/');
        self::beforeInit();
    }
}