<?php namespace General;

/**
 * Class CommentTrait
 * @package General
 * @mixin \BaseModel
 */
trait CommentTrait
{
    /**
     * Model上添加备注的工具函数
     *
     * @param $content
     * @return bool
     */
    public function comment($content, $others = [])
    {
        $comment = new \General\Comment();

        $comment->table_name = $this->getTable();
        $comment->data_id = $this->getKey();

        $json = $comment->json;
        $json['content'] = $content;
        $comment->json = array_merge($json, $others);

        return $comment->save();
    }

    /**
     * @return mixed|Comment|null 返回最后一条评论
     */
    public function lastComment()
    {
        return $this->hasOne(Comment::class, 'data_id')->where('table_name', $this->getTable())->latest();
    }
}