<?php namespace Cooperation;


/**
 * BaixingArea
 *
 * @property integer $id
 * @property string $baixing_id
 * @property integer $level
 * @property string $name
 * @property string $area_id
 * @property string $parent_baixing_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class BaixingArea extends \BaseModel
{
    protected $description = '百姓网地域信息';
    protected $table = 'cooperation_baixing_areas';
}
