<?php

namespace Tracking;

/**
 *  Tracking\LandlordChannelCostCategory
 *
 * @property integer $id
 * @property integer $city_id              城市
 * @property string $status                状态
 * @property string $channel_name          渠道名字
 * @property string $type                  渠道类别
 * @property \Carbon\Carbon $effective_at  生效时间
 * @property string $note                  渠道说明
 * @property integer $min
 * @property integer $max
 * @property integer $upper_limit          渠道费上限
 */

use Carbon\Carbon;
use Constants\USEABLE;
use Contract\WithLandlord;

class LandlordChannelCostCategory extends \BaseModel implements USEABLE
{
    protected $table = 'landlord_channel_cost_categories';
    protected $description = '收房渠道费配置';

    protected $dates = ['effective_at'];

    const TYPE_月租金天数 = '月租金天数';
    const TYPE_月租金百分比 = '月租金百分比';
    const TYPE_现金 = '现金';
    const TYPE_礼品卡 = '礼品卡';
    const TYPE_加油卡 = '加油卡';

    public function city()
    {
        return $this->belongsTo(\Area\City::class);
    }

    public static function channelCategoryName(WithLandlord $landlord)
    {
        $old = $landlord->resource->record_old_landlord;
        return LandlordChannelCostCategory::whereChannelName($old ? '业主' : $landlord->resource->record_external_user->job)
            ->where('city_id', $landlord->xiaoqu->block_relation->district->city->id)
            ->where('effective_at', '>=', Carbon::now())
            ->where('status', self::USEABLE_启用)
            ->get()
            ->keyBy('id')
            ->map(function ($cate) {
                return $cate->type . ' (' . $cate->min . '-' . $cate->max . ')';
            })
            ->prepend('* 选择渠道奖励方式', '');
    }

    public function save(array $options = [])
    {
        if (!$this->upper_limit) {
            $this->upper_limit = 99999;
        }
        return parent::save($options);
    }
}
