<?php namespace Area;

class City extends AbstractArea
{
    const LEVEL_NAME = \Area::LEVEL_城市;

    public function districts()
    {
        return $this->hasMany(District::class, 'parent_id');
    }

    public function getBlocksAttribute()
    {
        return Block::whereHas('district', function ($query) {
            return $query->where('parent_id', $this->id);
        })->get();
    }

    public static function list()
    {
        return \Area::where('level', \Area::LEVEL_城市)->pluck('name', 'id')->all();
    }

    public function children()
    {
        return $this->hasMany(\Area::class, 'parent_id');
    }

    //  以下两个函数是为了兼容原 \Area::cityCode
    public function cityCode()
    {
        return array_search($this->name, \City::list());
    }

    public function cityName()
    {
        return $this->name;
    }
}
