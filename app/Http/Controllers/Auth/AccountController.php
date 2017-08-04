<?php

namespace App\Http\Controllers\Auth;

use App\Models\CorpUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use AdminMenu;

class AccountController extends Controller
{
    const LOGIN_KEY = 'corp_user';

    /**
     * 执行登陆
     * @param Request $request
     */
    public function login(Request $request)
    {
        $requestData = $request->all();
        if (isset($requestData['uid']) && $corpUser = CorpUser::find($requestData['uid'])) {

            $data = $corpUser;

            Session::put(self::LOGIN_KEY, $data);
            dump('执行登陆成功.');
        } else {
            dump('执行登陆失败.');
        }
    }

    /**
     * 登陆验证失败
     */
    public function checkResult()
    {
        dump('登陆状态验证失败');
    }

    /**
     * 验证是否登陆
     * @param Request $request
     */
    public function check(Request $request)
    {
        if (Session::has(self::LOGIN_KEY)) {
            dump('用户已登陆');
        }
    }

    public function payment()
    {
        $menus = (new AdminMenu())->visibleLinks();
        echo '<pre>';
        print_r($menus);
        echo '</pre>';
        exit;

        echo 'payment';
    }

    public function customer()
    {
        echo 'customer';
    }

    public function landlord()
    {
        echo 'landlord';
    }

}
