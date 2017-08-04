<?php

use Acl\Role;
//use App\Events\Internal\CorpJoined;
use Tracking\HouseResourceUser;

/**
 * CorpUser
 *
 * @property integer $id
 * @property string $name                   全名
 * @property string $code                   工号(姓名的拼音,同时也是邮箱的前缀)
 * @property integer $mobile                手机号
 * @property string $status                 状态(active: 正常, disabled: 离职)
 * @property string $email                  邮箱
 * @property string $dingtalk_id            钉钉的第三方id
 * @property string $wechat_qy_id           微信企业号第三方id
 * @property string $position               职位
 * @property string $wechat_id              微信号(暂时没用)
 * @property string $gender                 性别
 * @property string $is_frozen              是否已冻结(是 or 否)
 * @property string $workplace              工作地区
 * @property string $avatar                 头像链接
 * @property \Carbon\Carbon $last_login     最后一次登录时间
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Area\City $city
 */
class CorpUser extends BaseModel implements \Constants\IS
{
    use \Acl\CorpUserACLTrait;

    const STATUS_ACTIVE = 'active';
    const STATUS_DISABLED = 'disabled';

    const MAIL_SUFFIX = '@dankegongyu.com';

    protected $description = '公司员工';
    protected $dates = ['last_login'];

    public static function rules()
    {
        return [
            'name' => 'required|min:2',
            'code' => 'required|min:2',
            'mobile' => 'required|min:4',
            'status' => 'required',
        ];
    }

    /**
     * 根据工号查找员工
     *
     * @param $code
     * @return static
     */
    public static function findByCode($code)
    {
        return self::whereCode($code)->first();
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'acl_corp_user_roles', 'corp_user_id', 'role_id');
    }

    public function departments()
    {
        return $this->belongsToMany(CorpDepartment::class, 'corp_user_departments', 'staff_id', 'department_id');
    }

    /**
     * 当前用户是否已冻结, 管理团队控制工具, 冻结后, 无法登陆后台
     *
     * @return bool
     */
    public function isFrozen()
    {
        return $this->is_frozen === self::IS_是;
    }

    public function freeze()
    {
        $this->is_frozen = self::IS_是;
    }

    /**
     * 是否部门主管
     * @param null $department
     * @return bool
     *
     *  1. 是主管
     *  2. 是指定部门主管, 可以传字符串和部门 class
     */
    public function isDepLeader()
    {
        return CorpDepartment::whereLeaderId($this->id)->exists();
    }

    /**
     * 关联到Forecast的account
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(\Forecast\Account::class, 'dingtalk_id', 'dingtalk_id');
    }

    /**
     * 此员工是否指定员工的主管
     * @param CorpUser $staff
     * @return bool
     */
    public function isDepLeaderOf(CorpUser $staff)
    {
        return CorpUser::whereId($staff->id)->departmentLeaderIs($this->id)->exists();
    }

    /**
     * 此员工职位是否是出房销售
     * @return bool
     */
    public function isRentalSales()
    {
        return in_array($this->position, ['出房销售', '出房销售实习生', '渠道销售', '蛋壳租房']);
    }

    /**
     * 此员工职位是否是收房销售(不包括部门主管）
     * @return bool
     */
    public function isLandlordSales()
    {
        return !$this->isDepLeader() && self::isLandlordTeam() && !$this->isSuperAdmin();
    }

    /**
     * 此员工职位是否属于收房
     * @return bool
     */
    public static function isLandlordTeam()
    {
        return in('收房BD团队') || in('蛋壳租房');
    }

    /**
     * 此员工职位是否是出房电销
     */
    public function isRentalOnlineSales()
    {
        return $this->position === '出房电销';
    }

    /**
     * 检查是否属于某部门, 支持所有子部门员工
     * @param CorpDepartment|string $departmentName
     * @return bool
     */
    public function in($departmentName)
    {
        if (!$departmentName) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        $dep = $this->departments->implode('parent_text');

        return str_contains($dep, "#{$departmentName}#");
    }

    public static function registerByWeChat(array $info)
    {
        $user = self::whereWechatQyId($info['userid'])->first();
        if (!$user) {
            $user = new CorpUser();
            $user->name = $info['name'];
            $user->mobile = $info['mobile'];
            $user->email = $info['email'] ?: $info['userid'] . self::MAIL_SUFFIX;
            $user->wechat_qy_id = $info['userid'];
            $user->code = $info['userid'];
            $user->wechat_id = $info['weixinid'];
            $user->position = $info['position'];
            $user->gender = ['1' => '男', '2' => '女'][$info['gender']];
            $user->status = ($info['status'] == 2) ? self::STATUS_DISABLED : self::STATUS_ACTIVE;
            $user->save();
        } elseif ($info['status'] == 2) {
            // 关注状态: 1 => 已关注，2 => 已冻结，4 => 未关注
            if ($user->status == self::STATUS_ACTIVE) {
                $user->status = self::STATUS_DISABLED;
                $user->save();
            }
            AuditLog::log(AuditLog::ACTION_LOGIN_FAIL, AuditLog::TYPE_CORP, $user->wechat_qy_id);
            return null;
        }

        return $user;
    }

    /**
     * 从钉钉同步员工信息
     *
     * @var array $info 钉钉的员工数据, eg:
     *   array:19 [▼
     *     "active" => true
     *     "avatar" => ""
     *     "department" => array:1 [▶]
     *     "dingId" => "$:LWCP_v1:$rZ9CDbNWuzVkdZ7/ZtcuuQ=="
     *     "email" => ""
     *     "isAdmin" => false
     *     "isBoss" => false
     *     "isHide" => false
     *     "isLeader" => true
     *     "jobnumber" => "zhuhong"
     *     "mobile" => "13581978849"
     *     "name" => "朱虹"
     *     "openId" => "xiPRHma4QOLtJ7ulf0mAqYAiEiE"
     *     "order" => 180276233359381122
     *     "position" => "财务"
     *     "remark" => ""
     *     "tel" => ""
     *     "userid" => "405717279149"
     *     "workPlace" => "北京"
     *   ]
     */
    public function syncFromDingTalk(array $info)
    {
        $this->name = $info['name'];
        $this->mobile = $info['mobile'];
        //@todo 导出的信息中不包括企业邮箱地址,在联系官方,暂时先不弄.
        $this->email = $info['jobnumber'] . self::MAIL_SUFFIX;
        $this->dingtalk_id = $info['userid'];
        $this->code = $info['jobnumber'];
        $this->wechat_qy_id = $info['jobnumber'];
        $this->position = $info['position'];
        $this->avatar = $info['avatar'];
        $this->status = self::STATUS_ACTIVE;//默认在列表里面的都是active的
        $this->workplace = $info['workPlace'];
    }

    /**
     * 返回示例：张卫 13701936024，手机号可点
     */
    public function contractInfo()
    {
        return $this->name . ' ' . link_to("tel:{$this->mobile}", $this->mobile);
    }

    public function pos()
    {
        return $this->hasOne(\Trade\Pos::class, 'staff_id');
    }

    // 得到销售所在的片区
    public function getRoleByArea()
    {
        $roles = array_column($this->roles->toArray(), 'id');
        $permissionByAreas = array_column(\Acl\PermissionByArea::get()->toArray(), 'role_id');
        //  先取第一个
        return Role::find(array_first(array_intersect($roles, $permissionByAreas)));
    }

    /**
     * 登陆时的hook
     */
    public function onLogin()
    {
        $this->last_login = \Carbon\Carbon::now();
        $this->save();
    }

    /**
     * 有效员工
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * 根据主管筛选员工
     *
     * @param CorpUser $query
     * @param $leaderId
     * @return CorpUser
     */
    public function scopeDepartmentLeaderIs($query, $leaderId)
    {
        if (!$leaderId) {
            return $query;
        }

        // 如果此员工不是leader, 直接返回 0=1, 减少查询
        $depNames = \CorpDepartment::whereLeaderId($leaderId)
            ->pluck('parent_text')
            ->toArray();
        if (!$depNames) {
            return $query->whereRaw('0=1');
        }

        return $query->whereHas('departments',
            function ($query) use ($depNames) {
                /** @var \CorpDepartment $query */
                foreach ($depNames as $idx => $suffix) {
                    $query->where(
                        'parent_text',
                        'like',
                        '%' . $suffix,
                        $idx === 0 ? 'and' : 'or'
                    );
                }
            }
        );
    }

    public function dealer_channel_user()
    {
        return $this->hasOne(HouseResourceUser::class, 'invited_by_staff_id');
    }

    public function save(array $options = [])
    {
        $isFreshman = !$this->id;

        $save = parent::save($options);

        if ($isFreshman && $save) {
//            event(new CorpJoined($this->id));
        }

        return $save;
    }

    /**
     * @return \Area\City
     */
    public function getCityAttribute()
    {
        if ($parents = $this->departments[0]->parents ?? null) {
            $city = array_reverse($parents)[1];
            if (in_array($name = $city . '市', Area::listCity())) {
                return City::find($name);
            }
        }
        return City::find('bj');
    }
}
