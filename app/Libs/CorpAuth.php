<?php
//yubing@wutongwan.org

/**
 * 提供公司员工登录检查等功能
 *
 * 基于微信企业号应用
 */
class CorpAuth extends \Acl\AbstractAuth
{
    const LOG_TYPE = AuditLog::TYPE_CORP;
    const SESSION_KEY = 'login_corp_user';
    const DEBUG_UID_CONFIG_KEY = 'app.debug_corp_user';

    const SESSION_KEY_NAME = 'login_corp_user_name';

    /**
     * 当前的登陆对象
     */
    protected static function findUser($id)
    {
        return CorpUser::find($id);
    }

    /**
     * 登陆后的操作
     */
    protected static function afterLogin()
    {
        $staff = self::user();
        
        //Session中保存用户名,方便Debug
        Session::put(self::SESSION_KEY_NAME, $staff->name);
    }

    protected static function onVisit()
    {
        AuditLog::log(AuditLog::INFO, self::LOG_TYPE, self::code());
    }

    protected static function afterLogout()
    {
        Session::forget(self::SESSION_KEY_NAME);
    }

    /**
     * @return CorpUser
     */
    public static function user()
    {
        return parent::user();
    }

    public static function id()
    {
        return self::user()->id ?? null;
    }

    public static function code()
    {
        return self::user()->code ?? null;
    }

    public static function name()
    {
        return self::user()->name ?? null;
    }

    public static function equals(CorpUser $staff = null)
    {
        return $staff ? self::id() === $staff->id : false;
    }
}
