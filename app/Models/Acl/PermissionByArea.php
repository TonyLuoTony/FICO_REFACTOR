<?php namespace Acl;

/**
 * Acl\PermissionByArea
 *
 * @property integer $id
 * @property string $name 名称
 * @property integer $role_id
 * @property integer $area_id 注: name + area_id 在数据库层面是Unique的
 * @property string $intro
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PermissionByArea extends \BaseModel
{
    protected $table = 'acl_permission_by_areas';
    protected $description = '分辖区的后台权限';

    const N_北京销售分区_出房 = "北京_销售分区_出房";
    const N_北京销售分区_收房 = "北京_销售分区_收房";
    const N_深圳销售分区_收房 = "深圳_销售分区_收房";

    public static function listNames()
    {
        return [
            '1' => self::N_北京销售分区_出房,
            '2' => self::N_北京销售分区_收房,
            '3' => self::N_深圳销售分区_收房,
        ];
    }

    public static function list($name)
    {
        //name + area_id 在数据库层面是Unique的,所以可以用area_id做key
        return self::where('name', $name)->get()->keyBy('area_id');
    }

    public static function listOwner($name, $area_id)
    {
        $obj = self::where('name', $name)->where('area_id', $area_id)->first();
        if (!$obj) {
            return [];
        } else {
            return $obj->role->corp_users;
        }

    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function area()
    {
        return $this->belongsTo(\Area::class);
    }

}