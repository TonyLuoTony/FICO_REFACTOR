<?php namespace Forecast\BuildingDict;

/**
 * 楼盘字典-小区
 *
 * @property integer $id
 * @property string $name                  小区名称
 * @property string $city                  所属城市
 * @property tinyint $is_important         重点小区
 * @property integer $room_avg_price_yuan  房屋均价
 */
class Community extends BaseDict
{
    protected $description = '楼盘字典-小区';
    protected $connection = 'forecast';
    protected $table = 'hybrid_community';

    public $timestamps = false;

    public function buildings()
    {
        return $this->hasMany(ResourceBuilding::class);
    }

    public function units()
    {
        return $this->hasMany(ResourceUnit::class);
    }

    public function houses()
    {
        return $this->hasMany(ResourceHouse::class);
    }

    /**
     * 新增楼盘
     * @return ResourceBuilding
     */
    public function addBuilding($name)
    {
        if ($building = ResourceBuilding::whereName($name)->where('community_id', $this->id)->first()) {
            return $building;
        }

        $building = new ResourceBuilding();
        $building->name = $name;
        $building->address = $this->name;
        $building->community_id = $this->id;
        $building->owner_id = \CorpAuth::id();
        $building->is_active = self::YES;
        $building->is_readonly = self::YES;
        $building->saveOrError();

        return $building;
    }
}
