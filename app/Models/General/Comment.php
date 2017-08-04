<?php namespace General;
//yubing@wutongwan.org

/**
 * Comment
 *
 * @property integer $id
 * @property string $table_name
 * @property integer $data_id
 * @property string $json
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 */
class Comment extends \BaseModel
{
    protected $description = '信息备注';
    protected $table = 'model_comments';
    protected $casts = ['json' => 'array'];

    public function staff()
    {
        if ($id = $this->getItem('staffId')) {
            return \CorpUser::find($id);
        }
        return null;
    }

    public function user()
    {
        if ($id = $this->getItem('userId')) {
            return \User::find($id);
        }
        return null;
    }

    /**
     * @todo  专属Link 不可轻易遍历
     * @todo  图片上传 (视频,音频,链接, eg...)
     * @todo  change notify people involved
     */

    public function getItem($key, $default = null)
    {
        return array_get($this->json, $key, $default);
    }

    // 获取附件项
    public function getAttach($name)
    {
        $attach = json_decode($this->getItem('attach', '{}'), true);
        return array_get($attach ?? [], $name, []);
    }

    public function save(array $options = [])
    {
        $json = $this->json;

        // 记录操作人
        $json['operator'] = operatorName();
        $json['staffId'] = \CorpAuth::user() ? \CorpAuth::id() : null;
        $json['userId'] = \Auth::user() ? \Auth::user()->id : null;

        $this->json = $json;

        return parent::save($options);
    }
}