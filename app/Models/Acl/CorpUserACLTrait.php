<?php namespace Acl;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * CorpUser 与 ACL 相关的函数
 */
trait CorpUserACLTrait
{
    /**
     * 是否超级管理员
     *
     * @return bool
     */
    public function isSuperAdmin()
    {
        return in_array($this->name, config('acl.super_admin'));
    }

    /**
     * 拥有某角色的所有员工
     *
     * @param $role
     * @return Collection|\CorpUser[]
     */
    public static function whoIs($role)
    {
        return \CorpUser::whereHas('roles', function ($query) use ($role) {
            /** @var Builder $query */
            $query->where('title', $role);
        })->get();
    }

    /**
     * 拥有某个权限的所有人
     *
     * @param string $permission
     * @return Collection|static[]
     */
    public static function whoCan(string $permission)
    {
        return \CorpUser::whereHas(
            'roles.permissions',
            function ($query) use ($permission) {
                /** @var Builder $query */
                $query->where('key', $permission);
            }
        )->get();
    }

    /**
     * 检测当前员工是否拥有指定权限
     *
     * @param string $permissionName
     * @return bool
     */
    public function can(string $permissionName)
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $perm = Permission::where('key', $permissionName)->first();
        if (!$perm) {
            if (isProduction()) {
                $this->noticeDev("[WARNING] Permission: {$permissionName} is not configured yet.");
            }
            return false;
        }

        $permRoles = PermissionRole::where('permission_id', $perm->id)->pluck('role_id')->toArray();
        $userRoles = CorpUserRole::where('corp_user_id', $this->id)->pluck('role_id')->toArray();

        return array_intersect($permRoles, $userRoles) ? true : false;
    }

    /**
     * 看用户在特定辖区是否拥有指定权限
     * @param string $permissionName
     * @param \Area $area 是否在所属地区
     * @return bool
     */
    public function canInArea(string $permissionName, \Area $area)
    {
        $perm = \Acl\PermissionByArea::where('key', $permissionName)->first();
        if (!$perm && isProduction()) {
            $this->noticeDev("[WARNING] PermissionByArea: {$permissionName} is not configured yet.");
            return false;
        }

        // 超级管理员
        if ($this->isSuperAdmin()) {
            return true;
        }

        //参数检查
        assert($area->level != $area::LEVEL_商圈, "Area {$area->name}'s level should be " . $area::LEVEL_商圈);

        if (!isset($perm->meta[$area->id])) {
            return false;
        }

        return CorpUserRole::where('role_id', $perm->meta[$area->id]['role_id'])->where('corp_user_id', $this->id)->exists();
    }

    /**
     * 当前员工是否拥有某角色
     *
     * @param $roleName
     * @return bool
     */
    public function hasRole(string $roleName)
    {
        //超级管理员
        if ($this->isSuperAdmin()) {
            return true;
        }

        $role = Role::where('title', $roleName)->first();

        if (!$role) {
            if (isProduction()) {
                $this->noticeDev("[WARNING] Role: {$roleName} is not configured yet.");
            }
            return false;
        }

        return CorpUserRole::where('corp_user_id', $this->id)->where('role_id', $role->id)->exists();
    }

    private function noticeDev($subject)
    {
        return \Email::send('dev@dankegongyu.com', $subject, date('c'));
    }
}
