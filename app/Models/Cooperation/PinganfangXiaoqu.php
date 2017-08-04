<?php namespace cooperation;

/**
 * pinganfangXiaoqu
 *
 * @property integer $id
 * @property integer $xiaoqu_id
 * @property integer $pinganfang_xiaoqu_id
 * @property integer $city_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */

class PinganfangXiaoqu extends \BaseModel
{
    protected $description = '平安好房小区信息表';
    protected $table = 'cooperation_pinganfang_xiaoqus';

}
