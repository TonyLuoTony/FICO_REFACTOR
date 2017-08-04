<?php namespace App\Jobs\Queueable;

use Illuminate\Database\Eloquent\Model;

/**
 * 队列中执行的 call_user_func_array
 *
 * 由于 Laravel 5.3 开始不支持 Closure 序列化，所以此处针对调用对象的函数做了定制化
 */
class CallObjectMethod extends QueueJob
{
    private $object;
    private $method;
    private $paramArray;

    private $corpUserId;

    function __construct($object, $method, $paramArray = [])
    {
        $this->object = $object;
        $this->method = $method;
        $this->paramArray = $paramArray;
    }

    function withCorpAuth($corpId = null)
    {
        $this->corpUserId = $corpId ?? \CorpAuth::id();

        return $this;
    }

    /**
     * 队列中执行的处理函数
     */
    public function handle()
    {
        if ($this->object instanceof Model) {
            $this->object = $this->object->fresh();
        }

        if ($this->corpUserId) {
            \CorpAuth::login($this->corpUserId);
        }

        call_user_func_array([$this->object, $this->method], $this->paramArray);

        \CorpAuth::logout();
    }
}
