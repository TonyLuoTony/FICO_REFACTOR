<?php namespace App\Jobs\Queueable;

use SuperClosure\Serializer;

class ClosureRunInQueue extends QueueJob
{
    /**
     * @var string
     */
    protected $callable;

    protected $corpAuthId;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($callable)
    {
        $callable = (new Serializer)->serialize($callable);
        $this->callable = $callable;
    }

    public function setCorpAuthId($id)
    {
        $this->corpAuthId = $id;
        return $this;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $callback = (new Serializer)->unserialize($this->callable);

        \CorpAuth::login($this->corpAuthId);

        $callback();

        \CorpAuth::logout();
    }
}
