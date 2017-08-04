<?php namespace Tracking;

//yubing@wutongwan.org

use Carbon\Carbon;
use Constants\IS;
use Contract\WithLandlord;
use CorpAuth;
use CorpUser;
use Forecast\BuildingDict;
use Traits\OptimusTrait;
use Xiaoqu;

/**
 * Table: 外部房源采集和跟踪表.
 *
 * 内部小CRM的核心数据表
 *
 * @property integer $id
 * @property string $city
 * @property integer $xiaoqu_id
 * @property string $address                               地址
 * @property string $doorplate                             门牌号
 * @property integer $resource_house_id                    楼盘字典房间 ID
 * @property string $landlord_name                         业主姓名
 * @property integer $landlord_phone                       业主电话
 * @property string $recorder_note                         备注(信息部录入房源备注  核验信息备注)
 * @property string $record_channel                        客户来源
 * @property string $record_type                           录入方式: 销售自录、渠道录入等
 * @property integer $record_by_staff_id                   录入销售 ID
 * @property integer $record_by_external_user_id           录入渠道人 ID。这两个字段如果都存在, 表示是销售录入, 但选了渠道作为信息来源
 * @property integer $record_by_old_landlord_id            录入老业主 ID
 * @property integer $external_user_inviter_id             邀请人（收房销售 或者信息部）ID 即将废弃
 * @property string $record_house_type                     房源信息, 录入人填, 电销核&改 公寓类型
 * @property integer $record_bedroom_num                   卧室数目
 * @property integer $record_toilet_num                    卫生间数目
 * @property integer $record_keting_num                    客厅数目
 * @property integer $record_balcony_num                   阳台数目
 * @property integer $record_area                          房源面积
 * @property integer $record_price                         业主期望价格
 * @property integer $online_check_assigned_by             线上分派人 （电销负责人分派电销）
 * @property \Carbon\Carbon $online_check_assigned_at      线上分派时间
 * @property integer $online_check_handled_by              线上核验人（电销核验）
 * @property \Carbon\Carbon $online_check_handled_at       线上核验时间
 * @property string $online_check_result                   核验结果, 有效、无效  （收房销售渠道人录入不需要电销核验  销售自己核验 ）
 * @property string $online_check_unavailable_reason       核验说明, 短租、暗厅等特别说明
 * @property string $online_check_result_marks             客户标签  （功能没有实现。留字段）
 * @property string $online_rent_status                    出租状态: 在租、已租等
 * @property string $allow_rent_start                      可起租起止时间
 * @property string $allow_rent_end
 * @property string $direction                             朝向
 * @property string $online_decorate                       装修
 * @property string $appliance                             家具
 * @property string $network_operator                      网络情况
 * @property string $keting_has_window                     客厅有无窗户
 * @property string $heating                               供暖方式
 * @property \Carbon\Carbon $kanfang_oppointment_time      预约看房时间
 * @property \Carbon\Carbon $tuikan_at                     推看时间 分派销售的时间  有了预约看房时间 有效可保存   没有预约时间不算绩效
 * @property integer $offline_check_assigned_by            线下分派人（电销分派销售的电销ID）
 * @property \Carbon\Carbon $offline_check_assigned_at     线下分派时间
 * @property integer $offline_check_handled_by             执行人（线下销售）
 * @property string $offline_check_status                  进度
 * @property string $offline_check_result                  结果
 * @property string $offline_result_mode                   签约模式
 * @property string $offline_check_unavailable_reason      房主无意向原因
 * @property \Carbon\Carbon $track_first_at                首次跟进时间
 * @property \Carbon\Carbon $track_last_at                 最后跟进时间
 * @property string $offline_opened_at                     打回公海时间  如果null 在销售手里
 * @property string $is_pay_channel_cost                   是否支付渠道费
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 * @property-read \CorpUser $record_executor
 * @property-read HouseResourceUser $record_external_user
 * @property integer $danke_plan_id
 * @property integer $lanjing_plan_id
 * @property string $appoint_by_online
 * @property-read \CorpUser $online_assigner
 * @property-read \CorpUser $online_executor
 * @property-read \CorpUser $offline_assigner
 * @property-read \CorpUser $offline_executor
 * @property-read Xiaoqu $xiaoqu
 * @property-read HouseResourceBill $bill
 * @property-read \Contract\WithLandlord $contract
 * @property-read \Illuminate\Database\Eloquent\Collection|\Contract\WithLandlord[] $contracts
 * @property-read \Forecast\DankePlan $danke_plan
 * @property-read \Forecast\LanJingPlan $lanjing_plan
 */
class HouseResource extends \BaseModel implements IS
{
    use CommentHistoryTrait;
    use OptimusTrait;

    protected $description = '外部房源信息';
    protected $dates = [
        'online_check_assigned_at',
        'online_check_handled_at',
        'offline_check_assigned_at',
        'kanfang_oppointment_time',
        'track_first_at',
        'track_last_at',
        'tuikan_at',
    ];
    protected $fillable = ['landlord_name', 'landlord_phone', 'address'];

    public function isAccepted()
    {
        return $this->online_check_result === self::ONLINE_RESULT_有效;
    }

    public function isRecycled()
    {
        return $this->offline_opened_at !== null;
    }

    public function isSigned()
    {
        return $this->signed_contract;
    }

    public function getSignedContractAttribute()
    {
        return $this->id ?
            WithLandlord::whereResourceId($this->id)
                ->where('type', 'like', $this->offline_result_mode . '%')
                ->first()   //  同模式只能绑定一份
            : null;
    }

    public function isOnline()
    {
        return $this->online_check_handled_by === \CorpAuth::user()->id;
    }

    public function isOffline()
    {
        return $this->offline_check_handled_by === \CorpAuth::user()->id;
    }

    public function allow($action)
    {
        $isOfflineLeader = $this->offline_executor
            ? CorpAuth::user()->isDepLeaderOf($this->offline_executor)
            : false;
        $isAssigner = can('收房_分派线上核验任务');
        $isAdmin = role('收房-销售-管理员');

        if ($isAdmin) {
            return true;
        }

        switch ($action) {
            case 'assignOffline':  //  分派任务给收房
                return
                    (\CorpAuth::user()->isDepLeader() ||
                        ($this->isAccepted() && ($isAssigner || $this->isOnline()))
                    )
                    && !$this->isSigned();

            case 'recycle': //  打回公海
                return
                    !$this->isSigned()
                    && ($isAssigner || $this->isOffline());
            case 'showDetail':
                return
                    $this->isOnline() || $this->isOffline() || $isAssigner || $isOfflineLeader;
            case 'modifyOffline':
                return
                    $this->isOffline() || $isAssigner || $isAdmin;
        }

        return false;
    }

    public static function allowSaleAgent()
    {
        if (role('收房-销售-管理员') || can('收房_修改渠道权限')) {
            return true;
        }

        $id = CorpAuth::id();
        return \CorpUserDepartment::whereStaffId($id)
            ->whereHas('department', function ($query) {
                return $query->where('parent_text', 'like', '%#收房BD团队#%');
            })
            ->exists();
    }

    public static function rules()
    {
        return [
            'address' => 'max:60',
            'landlord_name' => 'max:10',
            'landlord_phone' => 'required|mobile',
            'record_house_type' => 'max:10',
            'record_channel' => 'max:10',
            'record_bedroom_num' => 'integer',
            'record_toilet_num' => 'integer',
            'record_keting_num' => 'integer',
            'record_balcony_num' => 'integer',
            'record_area' => 'integer',
            'record_price' => 'integer',
            'recorder_note' => 'max:255',
        ];
    }

    public static function boot()
    {
        self::commentHistory([
            'landlord_phone' => '手机号',
            'record_channel' => '渠道',
            'address' => '物业地址',
            'doorplate' => '门牌号',
            'online_check_result' => '核验结果',
            'online_check_unavailable_reason' => '核验说明',
            'offline_check_result' => '收房结果',
            'offline_check_status' => '进度',
        ]);

        parent::boot();
    }


    public function save(array $options = [])
    {
        if ($this->isDirty('resource_house_id') && $this->resource_house_id) {
            $dictHouse = BuildingDict\ResourceHouse::findOrError($this->resource_house_id);
            $this->doorplate = $dictHouse->doorplate();
        }

        return parent::save($options);
    }

    public function resource_house()
    {
        return $this->belongsTo(BuildingDict\ResourceHouse::class, 'resource_house_id');
    }

    public function scopeDatesearch($query, $value, $field)
    {
        /* @var HouseResource $query */
        if (is_null($value)) {
            return $query;
        }
        return $query->where($field, 'like', "%{$value}%");
    }

    public function scopeOnlineResultSearch($query, $value)
    {
        /* @var HouseResource $query */
        if (!$value) {
            return $query;
        }
        if ($value === self::ONLINE_RESULT_已推看) {
            return $query->whereNotNull('tuikan_at');
        }
        return $query->whereOnlineCheckResult($value);
    }

    const RECORD_TYPE_官网自录 = '官网自录';
    const RECORD_TYPE_外部录入 = '外部录入';
    const RECORD_TYPE_信息部单录 = '信息部单录';
    const RECORD_TYPE_批量上传 = '批量上传';
    const RECORD_TYPE_电销自录 = '电销自录';
    const RECORD_TYPE_收房自录 = '收房自录';
    const RECORD_TYPE_业主转介绍 = '业主转介绍';
    const RECORD_TYPE_平安好房 = '平安好房';

    const RecordChannel_业主转介绍 = '业主转介绍';
    const RecordChannel_自营 = '自营';

    public static function listRecordChannel()
    {
        return [
            '中介',
            '物业',
            '地推',
            '网络',
            '官网',
            '业主转介绍',
            '其他'
        ];
    }

    public static function listSaleRecordChannel()
    {
        return [
            '自营',
        ];
    }

    const ONLINE_RESULT_待分派 = '待分派';
    const ONLINE_RESULT_待处理 = '待处理';
    const ONLINE_RESULT_再跟进 = '再跟进';
    const ONLINE_RESULT_未接听 = '未接听';
    const ONLINE_RESULT_有效 = '有效';
    const ONLINE_RESULT_无效 = '无效';
    const ONLINE_RESULT_已推看 = '已推看';

    public static function listOnlineCheckResult()
    {
        return [
            self::ONLINE_RESULT_待处理,
            self::ONLINE_RESULT_再跟进,
            self::ONLINE_RESULT_有效,
            self::ONLINE_RESULT_未接听,
            self::ONLINE_RESULT_无效,
        ];
    }

    public static function listOnlineMark()
    {
        return [
            self::ONLINE_RESULT_有效 => [
                '不能优化',
                '短租',
            ],
            self::ONLINE_RESULT_再跟进 => [
                '期房',
                '转租',
                '再联系',
            ],
            self::ONLINE_RESULT_未接听 => [
                '挂断',
                '无人接听',
                '关机',
                '停机',
                '暂停服务',
            ],
            self::ONLINE_RESULT_无效 => [
                '已租',
                '自住',
                '面积',
                '价格',
                '未开区',
                '第三方',
                '三天未接听',
                '其他',
            ],
        ];
    }

    public static function listOfflineCheckStatus()
    {
        return [
            '联系业主中',
            '实勘粗量中',
            '设计师精算中',
            '签约洽谈中',
            '任务结束',
        ];
    }

    const OFFLINE_RESULT_未跟进 = '未跟进';
    const OFFLINE_RESULT_意向 = '意向';
    const OFFLINE_RESULT_蛋壳签约 = '蛋壳签约';
    const OFFLINE_RESULT_可再跟 = '跟进中';
    const OFFLINE_RESULT_他签 = '他签';
    const OFFLINE_RESULT_无意向 = '无意向';

    public static function listOfflineCheckResult()
    {
        return [
            self::OFFLINE_RESULT_未跟进,
            self::OFFLINE_RESULT_可再跟,
            self::OFFLINE_RESULT_意向,
            self::OFFLINE_RESULT_蛋壳签约,
            self::OFFLINE_RESULT_他签,
            self::OFFLINE_RESULT_无意向,
        ];
    }

    const OFFLINE_RESULT_MODE_蛋壳 = WithLandlord::TYPE_蛋壳;
    const OFFLINE_RESULT_MODE_蓝鲸 = WithLandlord::TYPE_蓝鲸;
    const OFFLINE_RESULT_MODE_蛋壳租房 = WithLandlord::TYPE_蛋壳租房;
    const OFFLINE_RESULT_MODE_双模式 = '双模式都谈';

    public static function listDirection()
    {
        return ['东', '南', '西', '北', '东南', '东北', '西南', '西北', '东西', '南北'];
    }

    const STATUS_公海 = '公海';

    public function scopeStatusSearch($query, $val)
    {
        /* @var HouseResource $query */
        if ($val === self::STATUS_公海) {
            return $query->whereNotNull('offline_opened_at');
        }
        return $query;
    }

    public function scopeVisible($query)
    {
        /* @var HouseResource $query */
        return $query->where(function ($query) {
            /* @var HouseResource $query */
            return $query->whereNull('offline_opened_at')->where(function ($query) {
                /* @var HouseResource $query */
                $query->where('online_check_result', '!=', self::ONLINE_RESULT_无效);
            });
        });
    }

    public function scopeOnlyCity($query, $city)
    {
        if (!$city) {
            return $query;
        }

        /* @var HouseResource $query */
        return $query->whereHas('xiaoqu', function ($query) use ($city) {
            /* @var Xiaoqu $query */
            return $query->onlyCity($city);
        });
    }

    public function scopeStaffHouses($query, $id)
    {
        /* @var HouseResource $query */
        return $query->where(function ($query) use ($id) {
            /* @var HouseResource $query */
            return $query->where(function ($query) use ($id) {
                /* @var HouseResource $query */
                return $query->whereOfflineCheckHandledBy($id);
            })->orWhere(function ($query) use ($id) {
                /* @var HouseResource $query */
                $query->whereHas('offline_executor', function ($query) use ($id) {
                    /* @var \CorpUser $query */
                    $query->departmentLeaderIs($id);
                });
            });
        });
    }

    //  针对外部录入，分派和核验都显示  待核验
    public function isPending()
    {
        return in_array($this->online_check_result, [
            self::ONLINE_RESULT_待分派,
            self::ONLINE_RESULT_待处理,
        ]);
    }

    //  渠道录入的信息
    public function isExternalRecord()
    {
        return $this->record_by_external_user_id && !$this->record_by_staff_id;
    }

    //  收房渠道成的单子
    public function isExternalSale()
    {
        return $this->record_by_external_user_id && $this->record_external_user->is_dealer;
    }

    public function isDuplicate()
    {
        if (can('收房-录入重复信息')) {//  操作人有权处理
            return false;
        } else {
            //  录入人有权录入, 任何人可以跟进
            if ($corp = CorpUser::find($this->record_by_staff_id)) {
                if ($corp->can('收房-录入重复信息')) {
                    return false;
                }
            }
        }

        return self::whereLandlordPhone($this->landlord_phone)
            ->where('id', '!=', $this->id)
            ->exists();
    }

    public function checkDuplicate()
    {
        if ($this->isDuplicate()) {
            // 外部渠道重复录入时，信息进入唤醒列表
            if (!$this->record_channel) {
                $house = self::whereLandlordPhone($this->landlord_phone)->where('id', '!=', $this->id)->first();
                $houseRecorderUser = \Auth::user()->externalHouseRecorder;
                $rouse = new HouseResourceRouse();
                $rouse->house_id = $house->id;
                $rouse->record_channel = $houseRecorderUser->job;
                $rouse->record_type = HouseResource::RECORD_TYPE_外部录入;
                $rouse->record_by = $houseRecorderUser->id;
                if (!$rouse->save()) {
                    \Log::info('rouse-house-resource', [$rouse->getSavingErrors()]);
                }
            }
            throw new \ErrorMessageException('该信息已存在');
        }
    }

    public static function listenDuplicate()
    {
        self::saving(function (HouseResource $h) {
            if ($h->isDirty('landlord_phone')) {
                $h->checkDuplicate();
            }
        });
    }

    //  是否可以分派给电销
    public function canDistributeToOnline()
    {
        return in_array($this->online_check_result, [
            self::ONLINE_RESULT_待分派,
            self::ONLINE_RESULT_待处理,
        ]);
    }

    public function canDistributeToOffline()
    {
        //  有效 & 已预约 & 未分派
        return $this->online_check_result === self::ONLINE_RESULT_有效
            && $this->kanfang_oppointment_time != null
            && !$this->offline_check_handled_by
            || $this->offline_opened_at != null;
    }

    public function smartTime($field)
    {
        if (!($this->$field instanceof \Carbon\Carbon)) {
            return $this->$field;
        }

        /* @var Carbon $date */
        $date = $this->$field;

        if ($date->isToday()) {
            return $date->format('H:i');
        } else {
            return $date->format('n-j');
        }
    }

    public function delayTime($type)
    {
        if ($type === 'online') {
            $last = $this->online_check_handled_at ?? $this->online_check_assigned_at;
            if ($this->isPending() && $last->diffInDays()) {
                return $last->diffInDays();
            }
        }
        if ($type === 'offline') {
            $last = $this->track_last_at ?? $this->offline_check_assigned_at;
            if (in_array($this->offline_check_result, [
                    self::OFFLINE_RESULT_未跟进,
                    self::OFFLINE_RESULT_意向,
                ]) && $last->diffInDays()
            ) {
                return $last->diffInDays();
            }
        }
        return 0;
    }

    public function labelDelayTime($type)
    {
        $delay = $this->delayTime($type);
        return $delay ? \HTMLWidget::label("{$delay}天未跟进", 'danger') : '';
    }

    public function labelOnlineResult()
    {
        //  已推看
        if ($this->tuikan_at) {
            return \HTMLWidget::label('已推看', 'info');
        }
        return \ModelTool::label($this, 'online_check_result');
    }

    //  收房自录
    public function createFromOffline()
    {
        $this->record_by_staff_id = CorpAuth::user()->id;
        $this->record_type = HouseResource::RECORD_TYPE_收房自录;

        $this->online_check_assigned_by = CorpAuth::user()->id;
        $this->online_check_assigned_at = Carbon::now();
        $this->online_check_handled_by = CorpAuth::user()->id;
        $this->online_check_handled_at = Carbon::now();

        $this->offline_check_assigned_by = CorpAuth::user()->id;
        $this->offline_check_assigned_at = Carbon::now();
        $this->offline_check_handled_by = CorpAuth::user()->id;

        $this->track_first_at = Carbon::now();
        $this->track_last_at = Carbon::now();
    }

    public function afterSigned()
    {
        $this->online_check_result = HouseResource::ONLINE_RESULT_有效;
        $this->offline_check_result = HouseResource::OFFLINE_RESULT_蛋壳签约;

        //  给收房渠道计件
        if ($this->isExternalSale()) {
            $user = $this->record_external_user;
            $user->total_deals += 1;
            $user->save();
        }

        $this->save();
    }

    public function textInfoForWechat()
    {
        return view('admin.house_resource.text-message', ['house' => $this]);
    }

    public function record_executor()
    {
        return $this->belongsTo(CorpUser::class, 'record_by_staff_id');
    }

    public function record_external_user()
    {
        return $this->belongsTo(HouseResourceUser::class, 'record_by_external_user_id');
    }

    public function record_old_landlord()
    {
        return $this->belongsTo(\Human::class, 'record_by_old_landlord_id');
    }

    public function online_assigner()
    {
        return $this->belongsTo(CorpUser::class, 'online_check_assigned_by');
    }

    public function online_executor()
    {
        return $this->belongsTo(CorpUser::class, 'online_check_handled_by');
    }

    public function offline_assigner()
    {
        return $this->belongsTo(CorpUser::class, 'offline_check_assigned_by');
    }

    public function offline_executor()
    {
        return $this->belongsTo(CorpUser::class, 'offline_check_handled_by');
    }

    public function xiaoqu()
    {
        return $this->belongsTo(Xiaoqu::class);
    }

    public function bill()
    {
        return $this->hasOne(HouseResourceBill::class);
    }

    /**
     * 关联的当前合同
     */
    public function contract()
    {
        /** @var WithLandlord $relation */
        $relation = $this->hasOne(WithLandlord::class, 'resource_id');
        return $relation->where('manage_status', '!=', WithLandlord::MANAGE_STATUS_作废);
    }

    /**
     * 一份房源可能签出多份合同, 仅有一个有效合同, 但有时候需要取用所有合同
     */
    public function contracts()
    {
        return $this->hasMany(WithLandlord::class, 'resource_id');
    }

    public function danke_plan()
    {
        return $this->belongsTo(\Forecast\DankePlan::class);
    }

    public function lanjing_plan()
    {
        return $this->belongsTo(\Forecast\LanJingPlan::class);
    }

    /**
     *  回收到线下公海
     */
    public function recycleAction()
    {
        $this->offline_opened_at = Carbon::now();
        $this->is_pay_channel_cost = self::IS_否;
    }

    public function toOfflineAction($staffId)
    {
        $this->offline_check_assigned_by = \CorpAuth::user()->id;
        $this->offline_check_assigned_at = Carbon::now();
        $this->offline_check_handled_by = $staffId;
        $this->offline_check_result = self::OFFLINE_RESULT_未跟进;

        $this->offline_opened_at = null;

        if ($this->kanfang_oppointment_time) {
            //  @todo   大多数地方都以来“有效”这个结果，所以“已推看”作为独立的字段。以后如果还要加结果的话，得把所有的结果重新整理一下
            $this->tuikan_at = Carbon::now();
        }
    }

    /** ！！！暂时只能用来给信息部批量导入信息，限定格式！！！
     * @param $inputs
     * @return array
     */
    public static function importFromArray($inputs)
    {
        $output = [];

        //  指定列顺序
        $fields = [
            'xiaoqu_name',
            'address',
            'landlord_name',
            'landlord_phone',
            'record_area',
            'record_bedroom_num',
            'record_keting_num',
            'record_toilet_num',
            'record_price',
            'record_channel',
            'recorder_note',
        ];

        $appendRow = function ($row, $msg) use (&$output) {
            $row [] = $msg;
            $output [] = $row;
        };

        foreach ($inputs as $rowId => $row) {

            //  跳过表头
            if ($rowId === 0) {
                $appendRow($row, "失败说明");
                continue;
            }

            //  跳过因手抖在底部写入空数据
            if (is_null($row[0])) {
                continue;
            }

            $xq = Xiaoqu::where('type', Xiaoqu::TYPE_重点)->where('name', $row[0])->first();
            if (is_null($xq)) {
                $appendRow($row, "非重点小区");
                continue;
            }

            $house = new HouseResource();

            $house->xiaoqu_id = $xq->id;

            foreach ($row as $colId => $value) {
                if ($colId === 0) {  //跳过xiaoqu_name字段
                    continue;
                }
                $house->{$fields[$colId]} = $value;
            }

            //判重
            if ($house->isDuplicate()) {
                $appendRow($row, "重复信息");
                continue;
            }

            $house->online_check_result = self::ONLINE_RESULT_待分派;
            $house->record_by_staff_id = \CorpAuth::user()->id;
            $house->record_type = self::RECORD_TYPE_批量上传;

            if (!$house->save()) {
                $content = \HTMLWidget::lines([
                    join("||", $row),
                    json_encode($house->getSavingErrors(), JSON_UNESCAPED_UNICODE),
                ], true);
                \Email::send("zhanghuiren@wutongwan.org", "批量导入错误", $content, true);
                $appendRow($row, "信息有误");
                continue;
            }

            $house->comment('通过批量上传录入了该信息');
        }
        return $output;
    }

    public function onlinePermissionArea(\CorpUser $staff)
    {
        if ($staff->can('管理外部房源-电销流程')) {
            return new HouseResource();
        } else {
            return self::whereOnlineCheckHandledBy($staff->id);
        }
    }

    public function offlinePermissionArea(\CorpUser $staff)
    {
        if ($staff->can('管理外部房源-地面流程')) {
            return new HouseResource();
        } else {
            return self::whereOfflineCheckHandledBy($staff->id);
        }
    }

    public function getPlan()
    {
        switch ($this->offline_result_mode) {
            case self::OFFLINE_RESULT_MODE_蛋壳:
                return $this->danke_plan;

            case self::OFFLINE_RESULT_MODE_蓝鲸:
                return $this->lanjing_plan;

            default:
                return null;
        }
    }

    /**
     * 是否可以修改小区
     * @return bool
     */
    public static function canModifyCommunity()
    {
        return role('销售管理团队') || can('楼盘字典_修改楼盘信息');
    }

    public static function getAutoXiaoquNameResult($keyword)
    {
        return \Xiaoqu::where(function ($query) use ($keyword) {
            return $query->where('name', 'like', "%{$keyword}%");
        })
            ->take(10)
            ->get()
            ->map(function ($xiaoqu) {
                return [
                    'id' => $xiaoqu->id,
                    'name' => $xiaoqu->name,
                ];
            })
            ->toArray();
    }
}
