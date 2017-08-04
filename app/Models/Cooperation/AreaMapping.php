<?php namespace Cooperation;

/**
 * Cooperation\AreaMapping
 *
 * @property int $id
 * @property int $area_id                   // areas表id
 * @property int $cooperation_id            // 合作方映射id
 * @property string $source                 // 合作方名称
 * @property int $cooperation_name          // 合作方地区名称
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AreaMapping extends \BaseModel
{
    protected $description = '合作方地区映射表';
    protected $table = 'cooperation_area_mappings';

    public static function rules()
    {
        return [
            'area_id' => 'required|integer',
            'cooperation_id' => 'required|integer',
            'source' => 'required|string|max:16',
        ];
    }
}
