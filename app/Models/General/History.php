<?php namespace General;

/**
 * History
 *
 * @property integer $id
 * @property integer $data_id
 * @property string $table_name
 * @property string $operator
 * @property string $type
 * @property array $diff
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class History extends \BaseModel
{
    const ACTION_UPDATE = "更新";
    const ACTION_DELETE = "删除";
    const ACTION_CREATE = "创建";
    const DIFF_TEXT_EMPTY = "";

    protected $description = '数据修改记录';
    protected $table = 'model_histories';
    protected $casts = [
        'diff' => 'array',
    ];

    protected static $disabled = false;

    /**
     * 全局禁用 Model History
     */
    public static function disable($condition = true)
    {
        static::$disabled = $condition;
    }

    public static function listType()
    {
        return [self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE];
    }

    public static function listAllowedModel()
    {
        $ret = [];
        foreach (config('model_history.models') as $name => $value) {
            /** @var \BaseModel $model */
            $model = new $name;
            $ret[$model->getTable()] = $model->getDescription();
        }
        return $ret;
    }

    public static function getColumnTester(\BaseModel $model)
    {
        $data = config('model_history.models');

        // 是否需要侦听记录
        $clsName = get_class($model);
        if (array_key_exists($clsName, $data)) {
            $config = $data[$clsName];
            if (isset($config['field']) && is_array($config['field'])) {
                return function ($key) use ($config) {
                    return in_array($key, $config['field']);
                };

            } elseif (isset($config['exclude']) && is_array($config['exclude'])) {
                return function ($key) use ($config) {
                    return !in_array($key, $config['exclude']);
                };

            } else {
                return function ($key) {
                    return true;
                }; // 默认全部保存
            }
        }
        return false;
    }

    public static function record(\BaseModel $model, $action)
    {
        if (static::$disabled) {
            return;
        }

        assert(in_array($action, History::constants('ACTION')));

        if (get_class($model) === __CLASS__) {
            return; // 防止手残加了当前Model
        }

        $tester = self::getColumnTester($model);
        if ($tester === false) {
            return;
        }

        $diff = self::recordDiff($model, $action, $tester);
        if ($action === self::ACTION_UPDATE && !$diff) {
            return;
        }

        $record = new self();
        $record->data_id = $model->id;
        $record->table_name = $model->getTable();
        $record->operator = operatorName();
        $record->type = $action;
        $record->diff = $diff;
        $record->save();
    }

    private static function recordDiff(\BaseModel $model, $action, $tester)
    {
        $res = [];

        switch ($action) {
            case self::ACTION_CREATE:
                foreach ($model->getAttributes() as $key => $value) {
                    $res [$key] = ['old' => null, 'new' => $value];
                }
                break;

            case self::ACTION_UPDATE:
                // http://laravel.com/api/5.1/Illuminate/Database/Eloquent/Model.html#method_getOriginal
                if ($changedAttrList = $model->getDirty()) {
                    $newModel = $model->fresh();
                    foreach ($changedAttrList as $key => $value) {
                        if (!in_array($key, ['created_at', 'updated_at']) && $tester($key)) {
                            $old = $model->getOriginal($key);
                            $new = $newModel->getOriginal($key);
                            if ($old != $new) {
                                $res[$key] = ['old' => $old, 'new' => $new];
                            }
                        }
                    }
                }
                break;

            case self::ACTION_DELETE:
                foreach ($model->getAttributes() as $key => $value) {
                    $res [$key] = ['old' => $value, 'new' => null];
                }
                break;

            default:
                throw new \Exception("recordDiff异常: " . $action);
        }
        return $res;
    }

    /**
     * @param bool|false $raw 是否获取原始数据，否时会尽可能解析diff中的id
     * @return array|string
     */
    public function getDiff($raw = false)
    {
        $diff = $this->diff;

        // 兼容老数据, 最初的创建、删除事件, 只记录了如下内容, 无法展示
        if ($diff == ['old' => self::DIFF_TEXT_EMPTY] || $diff == ['new' => self::DIFF_TEXT_EMPTY]) {
            return [];
        }

        if ($raw) {
            return $this->diff;
        }

        foreach ($diff as $key => &$versions) {
            foreach ($versions as $version => &$value) {
                if ($value && $result = $this->parseModelId($key, $value)) {
                    $value .= "（解析结果：{$result}）";
                }
            }
        }

        return $diff;
    }

    private function parseModelId($key, $id)
    {
        if (!($key && $id)) {
            return null;
        }

        switch ($key) {
            // 员工
            case 'staff_id':
            case 'dealer_id':
            case 'first_approve_by':
            case 'second_approve_by':
            case 'rent_approve_by':
                return call_user_func(config('rapyd.cell.staff'), \CorpUser::find($id));

            // Human
            case 'human_id':
            case 'landlord_id':
            case 'customer_id':
            case 'agent_id':
            case 'sharer_id':
            case 'referrer_id':
                return ($human = \Human::find($id)) ? "Human {$human->id_name} $human->mobile" : null;

            // 房间
            case 'room_id':
                return ($room = \Room::find($id)) ? \ModelTool::href($room) : null;

            // 公寓
            case 'suite_id':
                return ($suite = \Suite::find($id)) ? \ModelTool::href($suite) : null;

            // 小区
            case 'xiaoqu_id':
                return ($xq = \Xiaoqu::find($id)) ? \ModelTool::href($xq) : null;

            default:
                return null;
        }
    }

    public function getSourceModel()
    {
        foreach (config('model_history.models', []) as $class => $info) {
            /** @var \BaseModel $model */
            $model = new $class;
            if ($model->getTable() === $this->table_name) {
                return $model::find($this->data_id);
            }
        }
        return null;
    }

    public function getTypeIconAttribute()
    {
        return [
            self::ACTION_DELETE => 'minus',
            self::ACTION_UPDATE => 'pencil',
            self::ACTION_CREATE => 'plus',
        ][$this->type];
    }
}
