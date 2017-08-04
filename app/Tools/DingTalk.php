<?php
//yubing@wutongwan.org
use App\Jobs\Queueable\DingTalk\SendMessage;

/**
 * 钉钉相关接口方法
 */
class DingTalk
{
    const APP_公告 = 7270313;
    const APP_收房 = 9254769;
    const APP_出房 = 9254866;
    const APP_运营 = 9255008;

    const METHOD_GET = 'get';
    const METHOD_POST = 'post';

    /**
     * 同步发送钉钉消息，不推荐在请求中直接调用
     *
     * 如需指定发送钉钉消息，请使用下方 sendMessage or sendTextMessage
     *
     * @param int $appID
     * @param array|CorpUser[] $receivers 工号列表 or CorpUser列表
     * @param array $data
     * @return array|bool
     * @throws Exception
     */
    public function sendMessageSynchronous(int $appID, array $receivers, array $data)
    {
        $receivers = collect($receivers);

        if (is_string($receivers->first())) {
            $receivers = CorpUser::whereIn('code', $receivers->toArray())->get();
        }

        Log::info(__METHOD__, [
            'receiver' => $receivers->pluck('name'),
            'data' => $data,
        ]);

        if (!isProduction()) {
            return [];
        }

        if ($receivers->isEmpty()) {
            throw new \Exception('please set $receiver param');
        }

        $result = $this->callApi(
            'message/send?access_token=' . $this->getAccessToken(),
            self::METHOD_POST,
            array_merge([
                'touser' => join('|', $receivers->pluck('dingtalk_id')->toArray()),
                'agentid' => $appID,
            ], $data)
        );

        Log::info(__METHOD__, [json_stringify($result)]);

        return $result;
    }

    /**
     * 发送企业消息
     */
    public function sendTextMessage(int $app_id, array $receivers, string $msg)
    {
        dispatch(new SendMessage($app_id, $receivers, [
            'msgtype' => 'text',
            'text' => ['content' => $msg],
        ]));
    }

    public function sendMessage(int $app_id, array $receivers, $title, $desc, $url = '')
    {
        dispatch(new SendMessage($app_id, $receivers, [
            'msgtype' => 'link',
            'link' => [
                'title' => $title,
                'text' => $desc,
                "picUrl" => "@dsa8d87y7c8d8c", // 在官方demo里找的一个link icon, 如果有需求可以改掉
                'messageUrl' => $url,
            ]
        ]));
    }

    /**
     * @param string $uri 钉钉的API地址
     * @param string $method get|post
     * @param array $post_data
     * @return bool|array
     */
    public function callApi($uri, $method = self::METHOD_GET, $post_data = [])
    {
        if (!isProduction()) {
            Log::info(__METHOD__, func_get_args());
            return false;
        }

        $url = 'https://oapi.dingtalk.com/' . $uri;

        $this->_error_msg = ''; //reset before every api call
        $c = new \HttpClient();

        if ($method === self::METHOD_GET) {
            $info = json_decode($c->get($url), true);
        } else {
            // POST请求需在HTTP Header中设置 Content-Type:application/json，否则接口调用失败
            $c->setHeader(["Content-Type:application/json"]);
            $info = json_decode($c->post($url, json_encode($post_data), 20), true);
        }

        Log::info(__METHOD__ . ':Response: ' . json_stringify($info));

        //请求失败 or 参数错误
        if (!is_array($info) || $info['errcode'] != 0) {
            $this->_error_msg = is_array($info) ? $info['errmsg'] : 'unknown error';
            Log::info(__METHOD__ . ':errmsg:' . $this->_error_msg, func_get_args());

            return false;
        }

        return $info;
    }

    private $_error_msg = '';

    public function getErrorMsg()
    {
        return $this->_error_msg;
    }

    /**
     *   http://ddtalk.github.io/dingTalkDoc/#js接口api
     */

    public function getAccessToken()
    {
        /**
         * 缓存accessToken。accessToken有效期为两小时。
         */
        $cache_key = __METHOD__ . ':corp_access_token';

        if ($token = Cache::get($cache_key)) {
            return $token;
        }

        $corp_id = config('services.dingtalk.corp_id');
        $corp_secret = config('services.dingtalk.corp_secret');
        $token_info = $this->callApi("gettoken?corpid={$corp_id}&corpsecret={$corp_secret}");
        if ($token_info) {
            $token = $token_info['access_token'];
            Cache::put($cache_key, $token, 90);

            return $token;
        } else {
            return false;
        }
    }

    private function getJsTicket()
    {
        /**
         * 缓存jsTicket。jsTicket有效期为两小时。
         */
        $cache_key = __METHOD__ . ':js_ticket';

        if ($ticket = Cache::get($cache_key)) {
            return $ticket;
        }

        $response = $this->callApi("get_jsapi_ticket?access_token=" . $this->getAccessToken());
        if ($response) {
            $ticket = $response['ticket'];
            Cache::put($cache_key, $ticket, 90);

            return $ticket;
        } else {
            return false;
        }

    }


    public function getJsConfig()
    {
        $nonceStr = $this->generateNonceStr();
        $timeStamp = time();

        $url = Request::fullUrl();

        $ticket = $this->getJsTicket();

        $plain = 'jsapi_ticket=' . $ticket .
            '&noncestr=' . $nonceStr .
            '&timestamp=' . $timeStamp .
            '&url=' . $url;

        $signature = sha1($plain);

        $config = array(
            'url' => $url,
            'nonceStr' => $nonceStr,
            'timeStamp' => $timeStamp,
            'corpId' => config('services.dingtalk.corp_id'),
            'signature' => $signature
        );

        return json_encode($config, JSON_UNESCAPED_SLASHES);
    }


    /**
     * 生成随机字串
     * @param int $length 长度，默认为16字节
     * @return string
     */
    private function generateNonceStr($length = 16)
    {
        // 密码字符集，可任意添加你需要的字符
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        return $str;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function listDepartment()
    {
        $list = $this->callApi('department/list?access_token=' . $this->getAccessToken());
        if (!$list) {
            Log::info("Error on fetching Department List:" . $this->getErrorMsg());
            return [];
        } else {
            return $list['department'];
        }
    }

    /**
     * 获取部门详情
     *
     * 文档 http://ddtalk.github.io/dingTalkDoc/?spm=a3140.7785475.0.0.P08B5u#获取部门详情
     *
     * @param $departmentId
     * @return array 详见文档
     */
    public function fetchDepartment($departmentId)
    {
        $data = $this->callApi("department/get?access_token={$this->getAccessToken()}&id={$departmentId}");
        return $data ?: [];
    }

    /**
     * 同步钉钉的部门
     */
    public function syncDepartment()
    {
        $now = new \Carbon\Carbon();

        if (!$departments = $this->listDepartment()) {
            return false;
        }

        foreach ($departments as $department) {
            /** @var CorpDepartment $d */
            $d = CorpDepartment::whereDingtalkId($department['id'])->first();
            if (!$d) {
                $d = new CorpDepartment();
                $d->dingtalk_id = $department['id'];
            }
            $d->name = $department['name'];

            // 关联上一级
            if ($parentId = array_get($department, 'parentid')) {
                $parent = CorpDepartment::whereDingtalkId($parentId)->first();
                if ($parent) {
                    $d->parent_id = $parent->id;
                }
            }

            // 同步主管(如果有多个主管, 先到先得)
            $detail = $this->fetchDepartment($department['id']);
            if (isset($detail['deptManagerUseridList'])) {
                $managers = explode('|', $detail['deptManagerUseridList']);

                foreach ($managers as $managerId) {
                    if (!$managerId) {
                        continue;
                    }
                    if (!$staff = CorpUser::whereDingtalkId($managerId)->first()) {
                        continue;
                    }
                    $d->leader_id = $staff->id;
                    break;
                }
            }

            if ($d->isDirty()) {
                $d->save();
            } else {
                $d->touch();
            }
        }

        //清理老的部门数据
        CorpDepartment::where('updated_at', '<', $now)
            ->each(function (CorpDepartment $dep) {
                $dep->delete(); // 一个好处: 可以记录改动历史
            });

        // 创建层级搜索关键字
        CorpDepartment::each(function (CorpDepartment $dep) {
            $dep->save();
        });

        return true;
    }

    /**
     * 列出部门所有员工
     *
     * @param $departmentId
     * @return array
     */
    public function listDepartmentStaffs($departmentId)
    {
        $resp = $this->callApi('user/list?access_token=' . $this->getAccessToken() . '&department_id=' . $departmentId);
        if (!$resp) {
            Log::info("Error on fetching User List:" . $this->getErrorMsg());
        }

        return array_get($resp, 'userlist', []);
    }

    /**
     * 从钉钉同步所有员工
     */
    public function syncAllStaff()
    {
        $start_at = new \Carbon\Carbon();
        $users = [];

        $list = $this->listDepartment();
        if (!$list) {
            return false;
        }

        foreach ($list as $_arr) {
            if (!$userList = $this->listDepartmentStaffs($_arr['id'])) {
                continue;
            }

            foreach ($userList as $u) {
                $users[strval($u['userid'])] = $u;
            }
        }
        if (count($users) < 100) {
            throw new \Exception("钉钉用户数不足100人,应该有bug,请查证.");
        }
        foreach ($users as $u) {
            $this->syncOneStaff($u);
            Log::info("CorpUser " . $u['name'] . " sync active from dingtalk");
        }

        $to_be_disable = CorpUser::whereStatus(CorpUser::STATUS_ACTIVE)->where('updated_at', '<', $start_at)->get();
        if ($to_be_disable->count()) {
            foreach ($to_be_disable as $_u) {
                $_u->status = CorpUser::STATUS_DISABLED;
                $_u->save();
                Log::info("CorpUser " . $_u->name . " disabled");
            }
            Email::send('yubing@wutongwan.org', '企业用户被禁用', var_export($to_be_disable->toArray(), true));
        }

        return true;
    }

    public function syncOneStaff(array $u)
    {
        $staff = CorpUser::whereDingtalkId($u['userid'])->first();
        //首次导入数据时候的检查,和微信保持一致
        if (!$staff) {
            $staff = CorpUser::whereWechatQyId($u['jobnumber'])->first();
        }

        if (!$staff) {
            $staff = new CorpUser();
        }

        $staff->syncFromDingTalk($u);
        if ($staff->getDirty()) {
            $staff->save();
        } else {
            $staff->touch();
        }

        return $staff;
    }

    /**
     * 同步员工和部门的对应关系
     *
     * 独立出来可以避免新增员工or新增部门时关联失败的情况
     */
    public function syncDepartmentStaffRelationship()
    {
        $start = \Carbon\Carbon::now();

        foreach ($this->listDepartment() as $department) {

            $staffs = $this->listDepartmentStaffs($department['id']);
            foreach ($staffs as $staff) {
                if (!$staff = CorpUser::where('dingtalk_id', $staff['userid'])->first()) {
                    continue;
                }

                if (!$dep = CorpDepartment::where('dingtalk_id', $department['id'])->first()) {
                    continue;
                }

                $relation = CorpUserDepartment::where('staff_id', $staff->id)
                    ->where('department_id', $dep->id)
                    ->first();
                if ($relation) {
                    $relation->touch();
                    continue;
                }

                $relation = new CorpUserDepartment();
                $relation->staff_id = $staff->id;
                $relation->department_id = $dep->id;
                $relation->save();

                Log::info(__METHOD__ . " corp: {$staff->code}, {$dep}: {$dep->id}-{$dep->name}");
            }
        }

        // 删掉旧的对应关系
        CorpUserDepartment::where('updated_at', '<', $start)
            ->each(function (CorpUserDepartment $dep) {
                $dep->delete(); // 一个好处: 可以用记录改动历史
            });

        return true;
    }
}
