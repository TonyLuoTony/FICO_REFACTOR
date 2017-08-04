<?php namespace Cooperation;


/**
 * XiaoquMapping
 *
 * @property integer $id
 * @property integer $xiaoqu_id
 * @property string $third_party_name
 * @property string $source
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class XiaoquMapping extends \BaseModel
{
    protected $description = '合作方小区映射';
    protected $table = 'cooperation_xiaoqu_mappings';
	
}
