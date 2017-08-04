<?php
//yubing@wutongwan.org
use Cooperation\BaixingArea;
use Cooperation\PinganfangArea;

/**
 * Area
 *
 * @property integer $id
 * @property string $name
 * @property integer $parent_id
 * @property string $level
 * @property double $longitude
 * @property double $latitude
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Area extends BaseModel
{
    protected $description = '地区基础数据';

    const LEVEL_城市 = '城市';
    const LEVEL_行政区 = '行政区';
    const LEVEL_商圈 = '商圈';

    const CITY_北京市 = '北京市';
    const CITY_深圳市 = '深圳市';
    const CITY_上海市 = '上海市';
    const CITY_杭州市 = '杭州市';

    public static function listCity()
    {
        return array_values(self::enums('city'));
    }

    public function parent()
    {
        return $this->belongsTo(Area::class);
    }

    public function children()
    {
        return $this->hasMany(Area::class, 'parent_id');
    }

    //任意一个节点,返回他向父级的完整路径
    private $path = [];

    public function path()
    {
        if ($this->path) {
            return $this->path;
        }
        $obj = $this;
        while ($obj) {
            $this->path[$obj->level] = $obj;
            if ($obj->parent_id) {
                $obj = $obj->parent;
            } else {
                break;
            }
        }

        return $this->path;
    }

    /**
     * @return Area|null
     */
    public function city()
    {
        return $this->path()[self::LEVEL_城市] ?? null;
    }

    /**
     * @return Area|null
     */
    public function district()
    {
        return $this->path()[self::LEVEL_行政区] ?? null;
    }

    /**
     * @return Area|null
     */
    public function block()
    {
        return $this->path()[self::LEVEL_商圈] ?? null;
    }

    //获取百姓网关联
    public function baixing_area()
    {
        return $this->hasOne(BaixingArea::class);
    }

    /**
     * 获取地区映射关联
     */
    public function area_mapping($source)
    {
        return \Cooperation\AreaMapping::where('area_id', $this->id)
            ->where('source', $source)
            ->first();
    }

    // 获得平安好房地区关联关系
    public function pinganfang_area()
    {
        return $this->hasOne(PinganfangArea::class, 'area_id');
    }

    //  获得商圈的销售分区团队
    public function role()
    {
        return $this->belongsToMany(\Acl\Role::class, 'acl_permission_by_areas', 'area_id', 'role_id');
    }

    public function cityName()
    {
        $city = ($this->level == self::LEVEL_城市) ? $this : $this->city();

        return $city->name;
    }

    public function cityCode()
    {
        $city = ($this->level == self::LEVEL_城市) ? $this : $this->city();

        return array_search($city->name, City::list());
    }

    public function scopeWhereIsCity($query)
    {
        return $query->where('level', self::LEVEL_城市);
    }

    public static function getBlockAutoComplete($keyword, $currentCity)
    {
        return \Area::where('level', self::LEVEL_商圈)
            ->whereHas('parent.parent', function ($query) use ($currentCity) {
                return $query->where('name', $currentCity);
            })
            ->where(function ($query) use ($keyword) {
                $pattern = "%{$keyword}%";
                /** @var \Area $query */
                $query->where('name', 'like', $pattern);
            })
            ->take(10)
            ->get()
            ->map(function (self $area) {
                return [
                    'id' => $area->id,
                    'name' => $area->name,
                ];
            })
            ->toArray();
    }

    public function internalTitle()
    {
        $current = $this;
        $text = collect([]);
        while ($current->name ?? null) {
            $text->push($current->name);
            $current = $current->parent;
        }
        return $text->reverse()->implode(' - ');
    }
}
