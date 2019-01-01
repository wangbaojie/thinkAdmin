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

namespace app\admin\controller;

use controller\BasicAdmin;
use Workerman\Worker;

class Test extends BasicAdmin
{
    public function index()
    {
        require_once __DIR__ . '/Workerman/Autoloader.php';
        $http_worker = new Worker("http://www.thinkadmin.com:8080");
        // 启动4个进程对外提供服务
        $http_worker->count = 4;

// 接收到浏览器发送的数据时回复hello world给浏览器
        $http_worker->onMessage = function($connection, $data)
        {
            // 向浏览器发送hello world
            $connection->send('hello world');
        };

// 运行worker
        Worker::runAll();

    }
}