<?php namespace Forecast\BuildingDict;

/**
 * 楼盘字典-楼盘
 *
 * @property integer $id
 * @property string $name                 楼盘名称
 * @property string $address              楼盘完整地址(小区地址 不包含楼盘名称)
 * @property integer $community_id        小区 ID
 * @property integer $building_type       楼型,板楼,塔楼等
 * @property integer $owner_id            增加者 ID
 * @property tinyint $is_active           是否被禁, 0: 未被禁, 1: 被禁
 * @property tinyint $is_readonly         是否只读, 0: 非只读, 1: 只读
 */
class ResourceBuilding extends BaseDict
{
    protected $description = '楼盘字典-楼盘';
    protected $connection = 'forecast';
    protected $table = 'resource_building';

    public $timestamps = false;

    public function community()
    {
        return $this->belongsTo(Community::class);
    }

    public function units()
    {
        return $this->hasMany(ResourceUnit::class, 'building_id');
    }

    public function houses()
    {
        return $this->hasMany(ResourceHouse::class);
    }

    public function isLocked()
    {
        return $this->is_active == self::NO || $this->is_readonly == self::NO;
    }

    /**
     * 新增单元
     * @return ResourceUnit
     */
    public function addUnit($name)
    {
        if ($unit = ResourceUnit::whereName($name)->where('building_id', $this->id)->first()) {
            return $unit;
        }

        $unit = new ResourceUnit();
        $unit->name = $name;
        $unit->address = $this->address . '-' . $this->name;
        $unit->community_id = $this->community_id;
        $unit->building_id = $this->id;
        $unit->owner_id = \CorpAuth::id();
        $unit->is_active = self::YES;
        $unit->is_readonly = self::YES;
        $unit->saveOrError();

        return $unit;
    }
}
