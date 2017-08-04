<?php

/**
 * Xiaoqu
 *
 * @property integer $id
 * @property string $name               标准名称
 * @property string $alias              小区别名 (多个以逗号分割)
 * @property string $city               城市
 * @property string $district           行政区
 * @property string $block              商圈
 * @property string $block_id           商圈ID (Area表ID)
 * @property string $danke_rent_tag     蛋壳租房标记
 * @property integer $subway_id
 * @property string $lng_lat            经纬度
 * @property float $longitude           经度
 * @property float $latitude            纬度
 * @property string $type               类型 (重点小区等)
 * @property string $available_isp_list 可用网络供应商类型
 * @property string $traffic_situation  出行建议(对外)
 * @property string $intro              周边设施介绍(对外)
 * @property string $internal_note      备忘(内部)
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $images
 */
class Xiaoqu extends BaseModel
{
    protected $description = '小区';

    const TYPE_重点 = '重点小区';
    const TYPE_非重点 = '非重点小区';

    const DANKE_RENT_TAG_是 = '是';
    const DANKE_RENT_TAG_否 = '否';

    public static function listCity()
    {
        return Area::listCity();
    }

    public static function listType()
    {
        return [
            self::TYPE_重点,
            self::TYPE_非重点,
        ];
    }

    public static function listIsp()
    {
        return [
            '联通',
            '长宽',
            '电信',
            '歌华',
            '小区宽带',
            '其他',
            '暂时无法接入宽带',
        ];
    }

    public static function rules()
    {
        return [
            'name' => 'required',
            'city' => 'required|in:' . join(',', self::listCity()),
            'district' => 'required',
            'block' => 'required',
            'lng_lat' => 'required'
        ];
    }

    public function suites()
    {
        return $this->hasMany(Suite::class);
    }

    public function rooms()
    {
        return $this->hasManyThrough(Room::class, Suite::class);
    }

    public function block_relation()
    {
        return $this->belongsTo(\Area\Block::class, 'block_id');
    }

    // 平安好房小区
    public function pinganfang()
    {
        return $this->hasOne(\cooperation\PinganfangXiaoqu::class, 'xiaoqu_id');
    }

    public function resource_buildings()
    {
        return $this->hasMany(\Forecast\BuildingDict\ResourceBuilding::class, 'community_id');
    }

    /**
     * 小区的百度地图预览图
     * 文档：http://developer.baidu.com/map/index.php?title=uri
     *
     * @param int $width
     * @param int $height
     * @param int $zoom
     * @return string
     */
    public function baiduMapImg(int $width = 400, int $height = 300, int $zoom = 15)
    {
        return "http://api.map.baidu.com/staticimage?center={$this->lng_lat}&markers={$this->lng_lat}&zoom={$zoom}&width={$width}&height={$height}&markerStyles=l";
    }

    /**
     * 当前小区的百度地图链接
     *
     * 排：坑爹的百度地图,这个接口中传递的经纬度是反过来的 >_<
     * 文档：http://developer.baidu.com/map/index.php?title=uri
     *
     * @return string
     */
    public function baiduMapLink($title = null)
    {
        $title = $title ?: $this->name;
        list($lng, $lat) = explode(',', $this->lng_lat);

        return "http://api.map.baidu.com/marker?location=" . $lat . ',' . $lng . "&output=html&title=" . urlencode($title) . "&content=" . urlencode('蛋壳公寓');
    }

    public function internalLink()
    {
        return ''; //action('Admin\XiaoquController@anyItem', $this->id);
    }

    public function internalTitle()
    {
        return $this->name;
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'block_id');
    }

    public function subway()
    {
        return $this->belongsTo(Subway::class);
    }

    public function save(array $options = [])
    {
        //  City District Block 三个现在是冗余数据,先兼容
        $this->load('area');
        if ($this->area && $this->area->city()) {
            $this->block = $this->area->name;
            $this->city = $this->area->city()->name;
            $this->district = $this->area->district()->name;
        }

        //  保存时根据block更新subway_id
        if ($this->isDirty('block') && $subway = \Subway::where('city', $this->city)->where('name', $this->block)->first()) {
            $this->subway_id = $subway->id;
        }

        // 将lng_lat拆成了两个字段
        if (strpos($this->lng_lat, ',')) {
            $lngLat = explode(',', $this->lng_lat);
            $this->longitude = $lngLat[0];
            $this->latitude = $lngLat[1];
        }

        return parent::save($options);
    }

    public static function listBlock($city_name = null)
    {
        if ($city_name) {
            //此处还有不一致,回头再改吧,老地方还有几个地方用,先不动了.
            if (!$districts = \Area::where('name', $city_name)->first()) {
                return [];
            }
            $districts = $districts->children->pluck('id')->toArray();

            return \Area::whereIn('parent_id', $districts)->pluck('name')->toArray();
        } else {
            $list = [];
            foreach (\Area::where('level', \Area::LEVEL_商圈)->get() as $block) {
                $list[$block->id] = ($block->city()->name ?? null) . '-' . $block->name;
            }

            return $list;
        }
    }

    public static function listDistrict($city = \Area::CITY_北京市)
    {
        $area = \Area::where('name', $city)->first();

        return $area ? $area->children()->get(['name'])->pluck('name')->toArray() : [];
    }

    public static function getAutoCompleteResult($keyword)
    {
        return Xiaoqu::where('type', \Xiaoqu::TYPE_重点)
            ->where(function ($query) use ($keyword) {
                $pattern = "%{$keyword}%";
                /** @var \Xiaoqu $query */
                $query->where('name', 'like', $pattern)
                    ->orWhere('alias', 'like', $pattern);
            })
            ->take(10)
            ->get()
            ->map(function (self $xq) {
                return [
                    'id' => $xq->id,
                    'name' => $xq->name . ($xq->alias ? " ({$xq->alias})" : "")
                ];
            })
            ->toArray();
    }

    /**
     * 限制某个城市
     * @param Xiaoqu $query
     * @param $city
     */
    public function scopeOnlyCity($query, $city)
    {
        if (!$city) {
            return $query;
        }

        return $query->where(function ($query) use ($city) {
            /** @var Xiaoqu $query */
            $relation = 'area.parent.parent';
            $query->has('area', '=', 0)
                ->orHas($relation, '=', 0)
                ->orWhereHas($relation, function ($query) use ($city) {
                    /** @var Area $query */
                    $query->where('name', $city);
                });
        });
    }

    /**
     * 获取 经度
     */
    public function getLongitudeAttribute()
    {
        return explode(',', $this->lng_lat)[0] ?? '';
    }

    /**
     * 获取 纬度
     */
    public function getLatitudeAttribute()
    {
        return explode(',', $this->lng_lat)[1] ?? '';
    }
}
