<?php namespace Forecast;

use App\Jobs\Queueable\RiskForecast\CallHousePricingNotice;
use Contract\Landlords\LandlordContractChange;
use Contract\WithLandlord;
use Illuminate\Foundation\Bus\DispatchesJobs;
use RiskForecast\HouseStateOperator;
use Tracking\HouseResource;
use Traits\ModelAllowTrait;

/**
 * Forecast\HousePricing
 *
 * @property integer $id
 * @property integer $landlord_wish_price_yuan      房东期望收房价
 * @property integer $sales_wish_decoration_cost    预估装修成本
 * @property boolean $is_active                     激活状态
 * @property integer $plan_id                       改造方案id
 * @property integer $owner_id                      申请人id
 * @property integer $pricing_id                    评估人id
 * @property string $error_note                     评估反馈信息
 * @property string $apply_time                     申请时间
 * @property string $calculate_status               计算状态
 * @property integer $contract_period               签约年限(年)
 * @property \Carbon\Carbon $created_at             创建时间
 * @property string $estimate_status                评估状态
 * @property string $estimate_time                  评估时间
 * @property string $free_day_of_per_year_json      每年的空置期
 * @property integer $free_room_from_system         模型计算出的每年的空置期(天)
 * @property string $free_room_type                 空置期类型,内置或外置
 * @property float $gross_margin                    毛利率
 * @property string $high_level_approve_result      一级特批人审批结果
 * @property string $high_level_approve_time        一级特批人审批时间
 * @property integer $high_level_approver_id        一级特批人审批人id
 * @property string $low_level_approve_result       三级特批人审批结果
 * @property string $low_level_approve_time         三级特批人审批时间
 * @property integer $low_level_approver_id         三级特批人审批人id
 * @property string $middle_level_approve_result    二级特批人审批结果
 * @property string $middle_level_approve_time      二级特批人审批时间
 * @property integer $middle_level_approver_id      二级特批人审批人id
 * @property float $net_profit_margin               净利率
 * @property string $note                           备注
 * @property integer $period_of_cost_recovery
 * @property string $price_for_per_year_json        每年的价格
 * @property integer $reform_cost_yuan              改造成本
 * @property integer $sales_wish_sum_price_yuan     预估总出房价
 * @property \Carbon\Carbon $updated_at
 * @property integer $price_of_per_year_from_system 模型预估的每年出房价
 * @property integer $laputa_house_resource_id      对应的房源跟踪id
 */
class HousePricing extends \BaseModel
{
    use ModelAllowTrait, DispatchesJobs;

    protected $connection = 'forecast';
    protected $table = 'house_pricing';

    protected $casts = [
        'price_for_per_year_json' => 'array',
        'free_day_of_per_year_json' => 'array',
        'price_of_per_year_from_system' => 'array',
        'free_room_from_system' => 'array',
    ];

    const STATUS_草稿 = '草稿';
    const STATUS_待审核 = '待审核';
    const STATUS_处理中 = '处理中';
    const STATUS_评估通过 = '评估通过';
    const STATUS_驳回 = '驳回';
    const STATUS_特批通过 = '特批通过';

    const CALCULATE_STATUS_计算中 = '计算中';
    const CALCULATE_STATUS_计算完成 = '计算完成';

    public function save(array $options = [])
    {
        if (!$this->isCreated()) {
            $this->estimate_status = self::STATUS_草稿;
        }
        return parent::save($options);
    }

    public function isCreated()
    {
        return $this->id;
    }

    public function plan()
    {
        return $this->belongsTo(HouseStatePlanTicket::class);
    }

    public function owner()
    {
        return $this->belongsTo(Account::class);
    }

    public function pricing_account()
    {
        return $this->belongsTo(Account::class, 'pricing_id');
    }

    public function receiver()
    {
        return $this->belongsTo(Account::class);
    }

    public function house_resource()
    {
        return $this->belongsTo(HouseResource::class, 'laputa_house_resource_id');
    }

    public function change()
    {
        return $this->hasOne(LandlordContractChange::class, 'house_pricing_id')
            ->where('status', '!=', LandlordContractChange::STATUS_撤销);
    }

    /**
     * $staff有权查看
     * @param \CorpUser|null $staff
     */
    public function scopeAllAllow($query, \CorpUser $staff = null)
    {
        $staff = $staff ?? \CorpAuth::user();

        $accountId = $staff->account->id ?? null;

        // 跨库关联,使用whereHas时候被当作同一个库
        $departmentLeaderIds = \CorpUser::department('leader_id', $staff->id)->pluck('dingtalk_id')->toArray();
        $ownerIds = Account::whereIn('dingtalk_id', $departmentLeaderIds)->pluck('id')->toArray();
        if (!$this->allow('manger')) {
            return $query->where('owner_id', $accountId)
                ->orWhereIn('owner_id', $ownerIds);
        } else {
            return $query->where('estimate_status', '!=', HousePricing::STATUS_草稿)
                ->orWhere('owner_id', '=', $accountId);
        }
    }

    /**
     * 重置收房方案计算状态
     */
    public function resetCalStatus()
    {
        $this->calculate_status = self::CALCULATE_STATUS_计算中;
        return $this->save();
    }

    // 判断
    public function isPassed()
    {
        return in_array($this->estimate_status, [HousePricing::STATUS_特批通过, HousePricing::STATUS_评估通过]);
    }

    protected function processAllow($action)
    {
        switch ($action) {
            case 'owner' :
                return Account::findByStaff(\CorpAuth::user())->id === $this->owner_id
                    || $this->allow('leader');
            case 'manger' :
                return can('评估收房方案');
            case 'leader' :
                return \CorpUser::whereId($this->owner->user->id)
                    ->departmentLeaderIs(\CorpAuth::id())
                    ->exists();
            case 'house_pricing_editable' :
                return $this->allow('manger') || $this->allow('owner');
            case 'house_pricing_report' :
                return $this->allow('house_pricing_editable') && in_array($this->estimate_status,
                        [self::STATUS_草稿, self::STATUS_驳回]);
        }
    }

    /**
     * @param $id
     * @return static
     * @throws \ErrorMessageException
     */
    public static function findIfAllow($id)
    {
        $housePricing = self::findOrError($id);
        if (!$housePricing->allow('house_pricing_editable')) {
            throw new \ErrorMessageException('您无权访问该页面');
        }
        return $housePricing;
    }

    /**
     * 关联的当前合同
     */
    public function contract()
    {
        /** @var WithLandlord $relation */
        $relation = $this->hasOne(WithLandlord::class, 'house_pricing_id');
        return $relation->where('manage_status', '!=', WithLandlord::MANAGE_STATUS_作废);
    }

    /*
     * 申请通过,驳回,特批通过后触发通知消息
     */
    public function estimateNotice()
    {
        $params = [
            'user_id' => $this->owner_id,
            'deal_id' => $this->id,
            'house_id' => $this->plan->house_id ?? null,
            'house' => $this->plan->resource_house->name ?? null,
            'analyst' => $this->owner->username ?? null,
            'status' => $this->estimate_status,
        ];
        $this->dispatch(new CallHousePricingNotice(CallHousePricingNotice::TYPE_评估通知, $params));
    }

    /*
     * 申请风控审核时候的通知
     */
    public function applyNotice()
    {
        $params = [
            'user_id' => $this->owner_id,
            'id' => $this->id,
        ];
        $this->dispatch(new CallHousePricingNotice(CallHousePricingNotice::TYPE_申请通知, $params));
    }

    /*
     * 方案对于的房源地址
     */
    public function scopePlanAddress($query, $address)
    {
        if ($address) {
            $query = $query->whereHas('plan.resource_house', function ($query) use ($address) {
                return $query->where('address', 'like', "%{$address}%");
            })->orWhereHas('plan.resource_house.community', function ($query) use ($address) {
                return $query->where('name', 'like', "%{$address}%");
            });
        }
        return $query;

    }

    /**
     * 请求计算收房方案成本,不返回结果
     */
    public function requestCaculate()
    {
        (new HouseStateOperator())->requestData(config('app.url') . '/forecast/house-state/rcalculator-01/' . $this->id . '.api');
    }
}
