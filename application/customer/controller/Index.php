<?php

namespace app\customer\controller;

use controller\BasicAdmin;
use think\Db;
use app\common\model\CustomerModel;

/*
 * 客户列表首页
 */

class Index extends BasicAdmin
{
    public $table = 'v9_gn_customer';

    /**
     * @return array|string
     */
    public function index()
    {
        $db = Db::name($this->table);
        $db->where('del', '=', 0);
        $get = $this->request->get();
        if (isset($get['customer_name']) && $get['customer_name'] != '') {
            $customer_name = trim($get['customer_name']);
            $db->where('name', 'like', "%$customer_name%");
        }
        if (isset($get['customer_mobile']) && $get['customer_mobile'] != '') {
            $customer_mobile = trim($get['customer_mobile']);
            $db->where('name', '=', $customer_mobile);
        }
        if (isset($get['date']) && $get['date'] != '') {
            $date = trim($get['date']);
            $date = str_replace("+-+", " - ", $date);
            list($start, $end) = explode(' - ', $date);
            $start = strtotime($start . " 00:00:00");
            $end = strtotime($end . " 23:59:59");
            $db->whereBetween('addtime', [$start, $end]);
        }
        $db->order('id desc');
        return $this->_list($db);
    }

    public function edit()
    {
        return $this->_form($this->table);
    }

}