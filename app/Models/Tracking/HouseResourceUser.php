<?php namespace Tracking;

use Illuminate\Database\Eloquent\SoftDeletes;
use Trade\BankBranch;

/**
 * 渠道管理
 *  包括 信息部渠道 和 销售成单渠道
 * HouseResourceUser
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $alias                 渠道名, 仅用来标记识别
 * @property string $status                审核与否
 * @property string $mobile
 * @property string $company
 * @property string $job
 * @property boolean $is_recorder          是信息部的渠道
 * @property integer $total_accept_records 总有效数
 * @property boolean $is_dealer            是收房销售的渠道人
 * @property integer $total_deals          总成单数
 * @property integer $balance              余额
 * @property integer $sum_awards           累计获得奖励
 * @property integer $invited_by_staff_id  邀请人
 * @property integer $verified_by          审核人
 * @property integer $bank_branch_id       对应城市银行
 * @property string $card_bank             开户支行
 * @property string $card_name             开户姓名
 * @property string $card_id               帐号
 * @property string $sales                 合作的销售列表 [弃用]
 * @property string $note                  备注
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 * @property-read \User $user
 * @property-read \CorpUser $inviter
 * @property-read \Trade\BankBranch $bank_branch
 * @property-read \CorpUser $verifier
 */
class HouseResourceUser extends BaseCooperator
{
    use SoftDeletes;

    protected $description = '外部录入的邀请信息';

    const TYPE_信息渠道 = '信息渠道';
    const TYPE_收房渠道 = '收房渠道';

    const JOB_中介经纪人 = '中介经纪人';
    const JOB_中介公司 = '中介公司';
    const JOB_业主 = '业主';
    const JOB_物业工作人员 = '物业工作人员';
    const JOB_其他 = '其他';

    public static function rules()
    {
        return [
            'mobile' => 'mobile',
        ];
    }

    public static function boot()
    {
        self::commentHistory([
            'alias' => '渠道别名',
            'status' => '认证状态',
            'card_bank' => '支行',
            'bank_branch.bank_name' => '银行',
            'card_name' => '开户人',
            'card_id' => '账号',
        ]);

        parent::boot();
    }

    public function save(array $options = [])
    {
        if (!boolval($this->is_recorder) xor boolval($this->is_dealer)) {
            throw new \ErrorMessageException('不能同时是两个渠道的渠道人！');
        }

        $save = parent::save($options);

        $prems = [
            'is_recorder' => '录入信息权限',
            'is_dealer' => '收房渠道权限',
        ];

        foreach ($prems as $perm => $description) {
            if ($this->isDirty($perm)) {
                $action = $this->$perm ? '开通 ' : '关闭 ';
                $this->comment($action . $description);
            }
        }

        return $save;
    }

    public function scopeWhereType($query, $val)
    {
        switch ($val) {
            case self::TYPE_信息渠道:
                return $query->where('is_recorder', true);
            case self::TYPE_收房渠道:
                return $query->where('is_dealer', true);
            default:
                return $query;
        }
    }

    public static function listTypeInfo($type)
    {
        return [
            self::TYPE_信息渠道 => [
                'field' => 'is_recorder',
                'created' => function (HouseResourceUser $user) {
                    $operator = new \Trade\HouseResourceOperator;
                    $operator->award(
                        $user,
                        config('house_resource_award_rules.award_at_invited'),
                        HouseResourceBill::TAG_赠送
                    );
                    flash('恭喜您成为蛋壳业主！奖励您' . config('house_resource_award_rules.award_at_invited') . '元红包，您可通过“个人中心－我的账单”查看。');
                },
                'title' => '蛋壳业主',
            ],

            self::TYPE_收房渠道 => [
                'field' => 'is_dealer',
                'title' => '蛋壳渠道',
                'created' => function (HouseResourceUser $user) {
                    flash('恭喜您成为蛋壳渠道！');
                },
            ],
        ] [$type];
    }

    //  是不是某个销售的渠道
    public function isPartner(\CorpUser $corp)
    {
        return strcmp($this->invited_by_staff_id, $corp->id);
    }

    //  总录入数
    public function countRecord()
    {
        return HouseResource::whereRecordByExternalUserId($this->id)->count();
    }

    public function internalLink()
    {
        return '';//action('Admin\Tracking\HouseResourceUserController@anyAgent') . '?modify=' . $this->id;
    }

    public function internalTitle()
    {
        return '<渠道> ' . $this->card_name;
    }

    public function bank_branch()
    {
        return $this->belongsTo(BankBranch::class);
    }

    public function user()
    {
        return $this->belongsTo(\User::class);
    }

    public function invited_by_staff()
    {
        return $this->belongsTo(\CorpUser::class, 'invited_by_staff_id');
    }
}
