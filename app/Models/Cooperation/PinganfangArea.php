<?php namespace Cooperation;

/**
 * pinganfangArea
 *
 * @property integer $id
 * @property string $pinganfang_id
 * @property integer $level
 * @property string $name
 * @property string $area_id
 * @property string $parent_pinganfang_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */

class PinganfangArea extends \BaseModel
{
    protected $description = '平安房地域信息表';
    protected $table = 'cooperation_pinganfang_areas';
}
