<?php namespace App\Http\Controllers\Api;

Use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Redis;

class JumpController extends BaseApiController
{

    public function getIndex()
    {
        //业务同步登陆
        $url = Request()->input('url');
        $token = Request()->input("token");
        $previous_url = url()->previous();

        if (!empty($token) && stripos($previous_url, env('LAPUTA_API_URL')) !== false) {
            $Encrypter = new Encrypter(env('LAPUTA_API_KEY'), 'AES-256-CBC');

            $token = $Encrypter->decrypt($token);
            list($session_id, $dateTime) = explode('|', $token);

            if (time() - $dateTime <= 180) {

                $session_info = Redis::get("l5:" . $session_id);
                $session_info = @unserialize(@unserialize($session_info));

                $uid = $session_info['login_corp_user'];

                //同步登陆
                \CorpAuth::login($uid);

                return redirect($url);

            } else {
                abort('404', '登陆超时');
            }
        } else {
            abort('404', '登陆失败');
        }

    }
}