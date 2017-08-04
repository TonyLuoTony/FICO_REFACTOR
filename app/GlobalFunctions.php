<?php
//yubing@wutongwan.org

/**
 * Global helper functions
 */
use App\Jobs\Queueable\CallObjectMethod;
use App\Jobs\Queueable\ClosureRunInQueue;
use Symfony\Component\ClassLoader\ClassMapGenerator;

/**
 * @param string $file Public下的文件相对路径
 * @return string
 */
function cdn($file)
{
    return isProduction() ? CDN::file($file) : $file;
}

/**
 * 1. 在DataEdit中将Option数组combine好，并可以增加默认的PlaceHolder字符串。
 * 2. 在DataFilter中如需不选中任何数据,请传入''。
 *
 * @param array $list
 * @param null $placeholder_label
 * @return array
 */
function selectOpts(array $list, $placeholder_label = null)
{
    $opts = [];
    if (is_array(array_values($list)[0] ?? null)) {
        foreach ($list as $key => $val) {
            $opts[$key] = array_combine($val, $val);
        }
    } else {
        $opts = array_combine($list, $list);
    }

    if (!is_null($placeholder_label)) {
        //在DataEditor中,传入''作为PlaceHolder很方便,而在DataFilter中则用 "* xx *"的形式比较统一.
        $text = $placeholder_label ? "* {$placeholder_label} *" : '';
        $opts = ['' => $text] + $opts;
    }

    return $opts;
}

/**
 * 是否生产环境
 * @return bool
 */
function isProduction()
{
    return config('app.env') == 'production';
}

function isTesting()
{
    return config('app.env') == 'testing';
}

function setOperatorName($name)
{
    Session::put('operator.name', $name);
}

/**
 * 返回当前 员工姓名｜用户昵称
 * @return string
 */
function operatorName()
{
    switch (true) {

        case $corp = CorpAuth::user():
            return '公司员工：' . $corp->name;

        case $user = Auth::user():
            return $user->operatorName();

        case $user = VendorAuth::user():
            if ($staff = $user->design_supplier_staff) {
                return '装修供应商:' . $staff->name;
            }
            return '供应商微信昵称:' . $user->nickname;

        case $name = Session::get('operator.name');
            return $name;

        case PHP_SAPI === 'cli':
            return '脚本批量修改'; // 之前在model history里使用，为了兼容就不改了，知道是脚本操作就好

        default:
            return '未登录用户';
    }
}

/**
 * 快捷函数,检查员工是否有权限使用某个功能
 * @param string $permission
 * @return bool
 */
function can(string $permission)
{
    $staff = \CorpAuth::user();

    return $staff && $staff->can($permission);
}

/**
 * 快捷函数,检查员工是否属于某个权限组
 * @param string $role
 * @return bool
 */
function role($role)
{
    $staff = \CorpAuth::user();

    return $staff && $staff->hasRole($role);
}

/**
 * 快捷函数,检查员工是否属于某个部门,查看的是parent_text是否含有相应部门字符串
 * @param string $departmentName
 * @return bool
 */
function in($departmentName)
{
    $staff = \CorpAuth::user();

    return $staff && $staff->in($departmentName);
}

/**
 * ACL工具函数, 解析权限判定语句, eg: allow('is:管理员|can:创建合同|in:tom,jerry')
 *
 * - is 语句对应 role() 函数, 判断当前用户是否该角色
 * - role 语句对应 role() 函数, 判断当前用户是否该角色
 * - can 语句对应 can() 函数, 判断当前用户是否具有该权限
 * - staff 语句, 判断当前用户的工号是否在指定列表中
 * - in 语句，判断当前用户的部门是否属于权限要求的部门
 *
 * @param      $pattern
 * @param bool $or
 * @return bool
 * @throws Exception
 */
function allow($pattern, $or = true)
{
    $staff = CorpAuth::user();

    if (!$staff) {
        return false;
    }

    $functions = [
        'is' => [$staff, 'hasRole'],
        'role' => [$staff, 'hasRole'],
        'can' => [$staff, 'can'],
        'staff' => function ($param) use ($staff) {
            return in_array($staff->code, explode(',', $param));
        },
        'in' => [$staff, 'in']
    ];

    $allow = null;
    $keywords = join('|', array_keys($functions));
    $patterns = is_array($pattern) ? $pattern : explode('|', $pattern);
    foreach ($patterns as $ptn) {
        preg_match('/(' . $keywords . '):(.*)/', $ptn, $match);
        if (count($match) !== 3 || last($match) === '') {
            throw new Exception('未知allow语句');
        }

        $result = call_user_func($functions[$match[1]], $match[2]);
        if ($or && $result) {
            return true;
        }
        $allow = is_null($allow) ? $result : $allow && $result;
    }
    return $allow;
}

/**
 * 将需要跨页面传递的数据存在Cache里,页面间(很可能是不同设备间)通过Code传递.
 *
 * @param $data
 * @param int $ttl 单位, 秒
 * @return string
 */
function cacheData($data, $ttl = 300)
{
    $json = serialize($data);
    $code = md5(time() . $json);
    \Cache::put("cache_data:{$code}", $json, $ttl / 60);

    return $code;
}

/**
 * @param String $code
 * @return mixed|false
 */
function fetchData($code)
{
    $json = \Cache::get("cache_data:{$code}");

    return unserialize($json);
}

/**
 * 从新标签打开的`link_to`函数
 */
function link_to_blank($url, $title = null, $attributes = [])
{
    $attributes['target'] = '_blank';

    return link_to($url, $title, $attributes);
}

/**
 * 生成数字验证码或密码，返回值为字符串
 * @param int $length 长度
 * @return string
 */
function generateNumberCode($length = 6)
{
    $code = rand(pow(10, $length - 1), pow(10, $length) - 1);
    $code = strval($code);

    if ($length < 2 || $length > 9) {
        throw new RuntimeException('Length should between 2 and 9.');
    }

    if (strpos('0123456789876543210', $code) !== false) {
        return generateNumberCode($length);
    }

    if (count(array_unique(str_split($code))) <= intval($length / 2)) {
        return generateNumberCode($length);
    }

    return $code;
}

/**
 * 将传入的对象准换成DateString
 * eg：
 *  - 2016-01-17 00:00:00 => 2016-01-17
 *  - 1453086079          => 2016-01-17
 * @param $time int|string|\Carbon\Carbon|DateTime
 * @return string|null
 */
function toDateString($time = null)
{
    $format = 'Y-m-d';

    if (is_int($time)) {
        return date($format, $time);
    }

    if (is_string($time)) {
        return date($format, strtotime($time));
    }

    if ($time instanceof DateTime) {
        return $time->format($format);
    }

    return null;
}

/**
 * 用单个String表示整个反向的调用栈关系，同时记Log
 * 注意: Ignore了所有Illuminate中的代码关系和Closure调用,否则太长了,不利于阅读
 * Log形如: SampleClass:doSomething|Pages_Controller::index|route
 */
function logBackTrace()
{
    $trace = array();
    foreach (array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 1) as $c) {
        if (!isset($c['function'])
            || $c['function'] == 'call_user_func_array'
            || $c['function'] == 'call_user_func'
            || (isset($c['class']) && strpos($c['class'], 'Illuminate') === 0)
        ) {
            continue;
        }
        $trace[] = (isset($c['class']) ? "{$c['class']}:" : '') . $c['function'];
    }
    Log::info(__METHOD__ . " : " . implode("|", $trace));
    return $trace;
}

/**
 * 读取database/sql/{$filename}.sql的内容
 *
 * @param $filename
 * @return string
 */
function sql($filename)
{
    $path = database_path("sql/{$filename}.sql");
    assert(is_file($path), "未找到sql文件: {$path}");
    return file_get_contents($path);
}

/**
 * afterApprove => after-approve
 * after_approve => after-approve
 * after approve => after-approve
 *
 * @param string $string
 * @param string $separator
 * @return mixed
 */
function slug_case($string, $separator = '-')
{
    return str_replace('_', $separator, snake_case(camel_case($string)));
}

/**
 * 将换行符替换为特定字符串
 *
 * @param string $string
 * @param string $replace
 * @return string
 */
function nl_to(string $string, string $replace = '')
{
    return str_replace(["\r\n", "\r", "\n"], $replace, $string);
}

/**
 * 读取数据库中的配置项
 *
 * @param string $key
 * @param null $default
 * @return mixed|null
 */
function db_config($key, $default = null)
{
    return Configuration::read($key, $default);
}

/**
 * json_decode, 不转义斜线和Unicode字符
 *
 * @param $arr
 * @return string
 */
function json_stringify($arr)
{
    return json_encode($arr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

/**
 * 返回环境文件的路径
 * @param $key
 * @return string
 */
function env_file($key)
{
    $file = env('ENV_DIR', '/tmp') . '/' . $key;
    laputa_assert(is_file($file), "未找到 env_file <{$key}>");
    return file_get_contents($file);
}


function laputa_assert($condition, $message)
{
    if (!$condition) {
        throw new LaputaException($message);
    }
}

//  当前函数从哪个函数调用过来
function called_from()
{
    $call = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3)[2];
    return data_get($call, 'class') . '::' . data_get($call, 'function')
        . '(' . join(',', data_get($call, 'args')) . ')';
}

/**
 * 异步执行
 * @param $callable
 * @param int $delay 延时多久执行，单位为秒
 */
function run_in_queue($callable, $delay = 0)
{
    $job = (new ClosureRunInQueue($callable))
        ->setCorpAuthId(CorpAuth::id());
    Queue::later($delay, $job);
}

/**
 * 异步执行成员函数
 * @param $object
 * @param $method
 * @param array $params
 */
function call_in_queue($object, $method, $params = [])
{
    $job = (new CallObjectMethod($object, $method, $params))
        ->withCorpAuth();
    dispatch($job);
}

/**
 * 列出特定目录中所有可以实例化的类
 *
 * @param $directory
 * @param string|null $parent 仅当前类的子类
 * @return array [path => class, ...]
 */
function classes_in($directory, $parent = null)
{
    $map = ClassMapGenerator::createMap($directory);
    $classes = [];
    foreach ($map as $class => $path) {
        $rft = new \ReflectionClass($class);
        if ($rft->isInstantiable()) {
            if ($parent && !$rft->isSubclassOf($parent)) {
                continue;
            }

            $classes[$path] = $class;
        }
    };
    return $classes;
}

function handle()
{
    foreach (func_get_args() as $handler) {
        assert($handler instanceof \HandleAble);
        $handler->handle();
    }
}

function event_with_fail_message(\App\Events\Event $event)
{
    try {
        Event::fire($event);
    } catch (Exception $e) {
        $eventType = get_class($event);
        Email::send(
            'dev@dankegongyu.com',
            'Event Fire Fail: ' . $eventType,
            "Event: {$eventType}\n"
            . 'Last Listener: ' . Event::firing() . "\n"
            . 'Time: ' . date('Y-m-d H:i:s') . "\n"
            . 'Data: ' . json_stringify($event)
        );

        throw $e;
    }
}

/**
 * array to excel
 *
 * @return \Maatwebsite\Excel\Writers\LaravelExcelWriter|mixed
 */
function array_to_excel($array, $filename = '文件')
{
    return \Excel::create(
        $filename . date('Y-m-d.His'),
        function (\Maatwebsite\Excel\Writers\LaravelExcelWriter $excel) use ($array) {
            $excel->sheet('SheetName', function (\PHPExcel_Worksheet $sheet) use ($array) {
                $sheet->fromArray($array);
            });
        });
}
