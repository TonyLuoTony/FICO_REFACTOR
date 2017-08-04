<?php // zhangwei@wutongwan.org

Use Illuminate\Encryption\Encrypter;

class AdminMenu
{
    /**
     * 所有管理员菜单
     *
     * '菜单名称' => [
     *      'allow' => 'can|is|staff|in:role', // 'allow' => 'can:查看设计师报价', 调用的是 CorpAuth::user()->can(role)
     *      'children' => [
     *          // 同父节点
     *      ]
     *      'url' => action('...'), // 没有设置children时，使用url作为当前菜单的链接
     * ]
     *
     * !!! role 和 permission 有一个即可, 不要同时用
     *
     */
    private function all()
    {
        $Encrypter = new Encrypter(env('LAPUTA_API_KEY'), 'AES-256-CBC');
        $token = $Encrypter->encrypt(env('LAPUTA_API_KEY') . '|' . time());

        $client = app(\HttpClient::class);
        $menu = $client->get(env('LAPUTA_API_URL') . '/fico/menu?token=' . $token);
        $json = json_decode($menu, true);

        if ($json && !empty($json["data"])) {
            foreach ($json["data"] as $key => $val) {
                if (isset($val["children"])) {
                    foreach ($val["children"] as $k => $v) {
                        if (isset($v['url']) && strpos($v['url'], 'forecast') !== false) {
                            $json["data"][$key]["children"][$k]['url'] = env('LAPUTA_API_URL') . $v['url'];
                        }
                    }
                } else {
                    if (isset($val['url']) && strpos($val['url'], 'forecast') !== false) {
                        $json["data"][$key]['url'] = env('LAPUTA_API_URL') . $val['url'];
                    }
                }
            }
            $menu = $json["data"];
        } else {
            $menu = [];
        }

        return $menu;
    }

    /**
     * 当前用户能看到的菜单列表, 生产环境缓存10分钟
     *
     * @return array
     */
    public function visibleLinks()
    {
        if (!isProduction()) {
            return $this->parseMenu($this->all(), true);
        }

        return Cache::remember($this->getCacheKey(), 10, function () {
            return $this->parseMenu($this->all(), true);
        });
    }

    private function getCacheKey(CorpUser $staff = null)
    {
        $staff = $staff ?? CorpAuth::user();

        return 'admin.menu.staff.' . $staff->id;
    }

    private function parseMenu($item, $isFirstLevel = false)
    {
        if (isset($item['allow']) && !allow($item['allow'])) {
            return [];
        }

        //  非管理员才判 except
        if (!CorpAuth::user()->isSuperAdmin() &&
            isset($item['except']) && allow($item['except'])
        ) {
            return [];
        }


        if ($link = array_get($item, 'url')) {
            return $link;
        }

        $menu = [];
        $children = $isFirstLevel ? $item : array_get($item, 'children', []);
        foreach ($children as $name => $child) {
            if ($links = $this->parseMenu($child)) {
                $menu[$name] = $links;
            }
        }

        return $menu;
    }

    private function taskMenus()
    {
        $menus = [];
        foreach (config('task') as $menu => $_) {
            $menus[$menu] = [
                'url' => action('Admin\TaskController@getList', $menu),
            ];
        };
        return $menus;
    }

    private function cleaningMenus($type = null)
    {
        $menus = [];
        foreach ([
                     \CustomerService\Cleaning\Task::STATUS_待分发服务商,
                     \CustomerService\Cleaning\Task::STATUS_服务商受理中,
                     \CustomerService\Cleaning\Task::STATUS_服务商派单中,
                     \CustomerService\Cleaning\Task::STATUS_上门中,
                     \CustomerService\Cleaning\Task::STATUS_已完成,
                 ] as $item) {
            $menus [$item] = [
                'allow' => 'can:保洁_查看所有工单',
                'url' => action('Admin\CustomerService\Cleaning\TaskController@getList' . $type, $item),
            ];
        }

        $menus += [
            '回访工单' => [
                'allow' => 'can:保洁_查看回访列表',
                'url' => action('Admin\CustomerService\Cleaning\EvalutionController@getList' . $type),
            ],
            '创建保洁工单' => [
                'allow' => 'can:保洁_创建工单',
                'url' => action('Admin\CustomerService\Cleaning\TaskController@anyItem'),
            ],
        ];

        return $menus;
    }
}
