<?php


/**
 * Subway
 *
 * 地铁站
 *
 * @property integer $id
 * @property string $city
 * @property string $name
 * @property string $lng_lat
 * @property string $nearby     周围地铁站，json对象数组，存了站点编号、距离、换乘次数
 * @property string $lines      所在地铁线，英文逗号分隔
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Subway extends BaseModel
{
    protected $description = '地铁站信息';

    public function isDuplicate()
    {
        return Subway::whereCity($this->city)->whereName($this->name)->exists();
    }

    /**
     * @param Area|\Area\City $city
     * @return array
     */
    public static function listLines($city)
    {
        $mass = self::whereCity($city->name)
            ->distinct()
            ->pluck('lines')
            ->toArray();
        $list = [];
        foreach ($mass as $row) {
            foreach (explode(',', $row) as $_line) {
                $list[$_line] = $_line;
            }
        }
        return array_keys($list);
    }
}
