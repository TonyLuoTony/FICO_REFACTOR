<?php namespace General;

/**
 * Class HistoryTrait
 * @package General
 * @mixin \BaseModel
 */
trait HistoryTrait
{
    /**
     * 根据改动记录返回当前对象的历史版本
     *
     * @param \Carbon\Carbon $datetime 历史时间
     * @param bool $nullIfBeforeCreated 当datetime超过创建时间时是否返回null
     * @return static 实际上还会返回null（指定的时间超过了创建时间时）
     */
    public function getHistoryVersion(\Carbon\Carbon $datetime, $nullIfBeforeCreated = false)
    {
        $version = clone $this;

        if ($version->isDirty()) {
            $version = static::find($version->getKey());
        }

        // 如果指定时间点大于创建时间，根据参数判断是否直接返回null
        if ($nullIfBeforeCreated && $datetime->lt($this->getAttribute($this->getCreatedAtColumn()))) {
            return null;
        }

        $histories = History::where('table_name', $this->getTable())
            ->where('data_id', $this->getKey())
            ->where('created_at', '>=', $datetime)
            ->orderBy('id', 'desc')
            ->get();

        /**
         * 根据历史回滚属性
         * @var History $history
         */
        foreach ($histories as $history) {
            if ($history->type === History::ACTION_CREATE) {
                return $nullIfBeforeCreated ? null : $version;
            }

            $diff = $history->getDiff(true);
            foreach ($diff as $key => $value) {
                $version->setAttribute($key, array_get($value, 'old'));
            }
        }

        return $version;
    }
}