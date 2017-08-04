<?php namespace Cooperation;


/**
 * Cooperation\PublishFailRecord
 *
 * @property int $id
 * @property int $room_id
 * @property string $source
 * @property int $fail_num
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 */
class PublishFailRecord extends \BaseModel
{
    protected $table = 'cooperation_publish_fail_records';

    public static function recordPublish($roomId, $source)
    {
        $record = self::where('room_id', $roomId)->where('source', $source)->first();
        if ($record) {
            $record->fail_num++;
        } else {
            $record = new PublishFailRecord();
            $record->room_id = $roomId;
            $record->source = $source;
            $record->fail_num = 1;
        }
        $record->saveOrError();
    }

    public static function clearRecord($roomId, $source)
    {
        $record = self::where('room_id', $roomId)->where('source', $source)->first();
        if ($record) {
            $record->fail_num = 0;
            $record->saveOrError();
        }
    }
}