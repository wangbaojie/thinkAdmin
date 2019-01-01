<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2017 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.ctolog.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

namespace app\index\controller;

use think\Controller;
use think\Db;
use think\facade\Request;

/**
 * 应用入口控制器
 * @author Anyon <zoujingli@qq.com>
 */
class Index extends Controller
{
    public $table = 'auth_token';

    public function index()
    {
        $this->redirect('@admin/login');
    }

    public function getAuthToken()
    {
        $domain = $this->request->post('domain'); //域名
        $auth_token = $this->request->post('auth_token'); //授权码
        $ip = $this->request->post('ip');
        $title = $this->request->post('title'); //网站标题
        $type = $this->request->post('type');//type=1 信贷云  type=2 中控
        //用IP和域名查 判断是否有记录
        //   $res = Db::name($this->table)->where('type', $type)->where('domain', $domain)->whereOr('ip', $ip)->find();
        $sql = " select * from auth_token where type = " . $type . " AND ( domain= '" . $domain . "' or ip= '" . $ip . "') LIMIT 1";
        $res = Db::query($sql);
        //如果没有,则证明是新网站  给30天试用
        if (empty($res)) {
            $key = md5('西安瓜牛金融外包服务有限公司');
            $jie = $this->encrypt($auth_token, 'D', $key);
            $data = unserialize($jie);
            $data['domain'] = $domain;
            $data['ip'] = $ip;
            $data['end_date'] = time() + 2592000;
            $data['name'] = $title;
            $data['version'] = 30;
            $data['key'] = $key;
            $data['created_at'] = time();
            $data['status'] = 99;
            $data['type'] = $type;
            Db::name($this->table)->insert($data);//入库
            unset($data['key']);
            unset($data['created_at']);
            $data = serialize($data);
            $str = $this->encrypt($data, 'E', $key);
            return json(['code' => -1, 'auth_token' => $str, 'key' => $key, 'status' => 99]); //拼装新的auth_token返回回去
            //如果通过IP或者域名查到了,说明是老网站
        } else {
            $res = $res[0];
            if ($res['domain'] != $domain && $res['ip'] == $ip) { //判断IP没变,域名变了  则更新域名
                Db::name($this->table)->where('type', $type)->where('ip', $ip)->update(['domain' => $domain]);
                $data_arr = [
                    'pid' => $res['id'],
                    'title' => $title,
                    'domain' => $domain,
                    'ip' => $ip,
                    'created_at' => time()
                ];
                Db::name('domain_ip')->insert($data_arr);//如果域名变了 则把变动记录记录在 域名ip表
                $res['domain'] = $domain;
            }
            if ($res['ip'] != $ip && $res['domain'] == $domain) { //判断域名没变  IP变了  则更新IP
                Db::name($this->table)->where('type', $type)->where('domain', $domain)->update(['ip' => $ip]);
                $data_arr = [
                    'pid' => $res['id'],
                    'title' => $title,
                    'domain' => $domain,
                    'ip' => $ip,
                    'created_at' => time()
                ];
                Db::name('domain_ip')->insert($data_arr);//如果IP变了 则把变动记录记录在 域名ip表
                $res['ip'] = $ip;
            }
            $key = md5($res['key'] . time());//每次请求都重新生成key
            Db::name($this->table)->where('domain', $domain)->update(['key' => $key]);  //把这个key保存起来
            unset($res['id']);
            unset($res['key']);
            unset($res['created_at']);
            unset($res['type']);
            $res = serialize($res);
            $str = $this->encrypt($res, 'E', $key); //用key加密密文 ,只有这个key才能解开密文
            return json(['code' => -1, 'auth_token' => $str, 'key' => $key, 'status' => 99]); //把密文 和key返回回去 让解密
        }
    }

    /**
     * @param $string
     * @param $operation :E加密 D解密
     * @param string $key
     * @return bool|mixed|string
     * 加密解密算法  key 需要MD5加密后的值
     */


    private function encrypt($string, $operation, $key = '')
    {
        $key_length = strlen($key);
        $string = $operation == 'D' ? base64_decode($string) : substr(md5($string . $key), 0, 8) . $string;
        $string_length = strlen($string);
        $rndkey = $box = array();
        $result = '';
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($key[$i % $key_length]);
            $box[$i] = $i;
        }
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if ($operation == 'D') {
            if (substr($result, 0, 8) == substr(md5(substr($result, 8) . $key), 0, 8)) {
                return substr($result, 8);
            } else {
                return '';
            }
        } else {
            return str_replace('=', '', base64_encode($result));
        }
    }
}
