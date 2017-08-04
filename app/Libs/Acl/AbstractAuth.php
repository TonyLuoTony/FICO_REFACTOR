<?php namespace Acl; // zhangwei@wutongwan.org

abstract class AbstractAuth
{
    const SESSION_KEY = 'should be rewrite';
    const DEBUG_UID_CONFIG_KEY = 'should be rewrite';
    const LOG_TYPE = 'should be rewrite';

    protected static $memory = [];

    /**
     * @return bool
     */
    public static function guest()
    {
        return is_null(static::user());
    }

    abstract protected static function findUser($id);

    /**
     * 拿到当前的登陆用户
     */
    public static function user()
    {
        if (static::current()) {
            return static::current();
        }

        $uid = \Session::get(static::SESSION_KEY);

        if (!$uid && !isProduction()) {
            $uid = config(static::DEBUG_UID_CONFIG_KEY);
        }

        if ($uid) {
            static::remember(static::findUser($uid));
        }

        return static::current();
    }

    /**
     * 登陆
     */
    public static function login($id)
    {
        if ($user = static::findUser($id)) {
            static::remember($user);

            \Session::put(static::SESSION_KEY, $id);

            static::afterLogin();
        }
    }

    /**
     * 登出
     */
    public static function logout()
    {
        static::forget();

        $id = \Session::get(static::SESSION_KEY) ?: 0;
        \Session::forget(static::SESSION_KEY);

        static::afterLogout();
    }

    private static function remember($user)
    {
        static::$memory[static::class] = $user;
    }

    private static function current()
    {
        return static::$memory[static::class] ?? null;
    }

    private static function forget()
    {
        unset(static::$memory[static::class]);
    }

    /**
     * 登陆后的回调
     */
    protected static function afterLogin()
    {
    }

    /**
     * 注销后的回调
     */
    protected static function afterLogout()
    {
    }

    /**
     * 每次请求调用一次
     */
    protected static function onVisit()
    {

    }
}
