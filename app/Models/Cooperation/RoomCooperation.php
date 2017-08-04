<?php namespace Cooperation;

/**
 * RoomCooperation
 *
 * @property integer $id
 * @property string $room_id
 * @property string $source
 * @property string $creator_id
 * @property string $desc
 * @property string $third_party_id
 * @property string $source_url
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class RoomCooperation extends \BaseModel
{
    protected $description = '第三方房源管理';
    protected $table = 'cooperation_rooms';

    const STATUS_已发布 = '已发布';
    const STATUS_已下架 = '已下架';

    /*
     * 获取ROOM关联关系
     */
    public function room()
    {
        return $this->belongsTo(\Room::class);
    }

    /*
     * 获取CorpUser关联关系
     */
    public function creator()
    {
        return $this->belongsTo(\CorpUser::class);
    }

    /**
     * 限定某个城市
     */
    public function scopeOnlyCity($query, $city)
    {
        if (!$city) {
            return $query;
        }

        /** @var RoomCooperation $query */
        return $query->whereHas('room', function ($query) use ($city) {
            /** @var Room $query */
            $query->onlyCity($city);
        });
    }
}
