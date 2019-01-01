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
use service\DataService;
use service\NodeService;
use service\ToolsService;
use think\App;
use think\Db;

/**
 * 后台入口
 * Class Index
 * @package app\admin\controller
 * @author Anyon <zoujingli@qq.com>
 * @date 2017/02/15 10:41
 */
class Token extends BasicAdmin
{
    public $table = 'auth_token';

    public function index()
    {
        $db = Db::name($this->table);
        return $this->_list($db);
    }

    public function change()
    {
        $pid = $this->request->get('pid');
        $domain_ip = Db::name('domain_ip')->where('pid', $pid)->select();
        return $this->fetch('', ['domain_ip' => $domain_ip]);
    }

    /**
     * 永久授权
     */
    public function forever()
    {
        $id = $this->request->get('id');
        $res = Db::name($this->table)->where('id', $id)->update(['end_date' => -1, 'version' => -1]);
        if ($res) {
            $this->success('永久授权成功', '');
        } else {
            $this->error('永久授权失败', '');
        }
    }

    /**
     * 修改试用期时间
     */
    public function edit()
    {
        return $this->_form($this->table);
    }

    /**
     * 禁用 启用
     */
    public function prohibit()
    {
        $id = $this->request->get('id');
        $status = Db::name($this->table)->where('id', $id)->field('status')->find();
        $status = $status['status'];
        $str = '';
        $data = [];
        if ($status == 99) {
            $data = ['status' => -1];
            $str = "禁用成功";
        }
        if ($status == -1) {
            $data = ['status' => 99];
            $str = "启用成功";
        }
        $res = Db::name($this->table)->where('id', $id)->update($data);
        if ($res) {
            $this->success($str, '');
        } else {
            $this->error('操作失败', '');
        }
    }

    public function _form_filter(&$vo)
    {
        if ($this->request->isGet()) {
            $vo['end_date'] = date('Y-m-d', $vo['end_date']);
        }
        if ($this->request->isPost()) {
            $vo['end_date'] = strtotime($vo['end_date']);
        }
    }
}