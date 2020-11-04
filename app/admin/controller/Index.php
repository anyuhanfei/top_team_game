<?php
namespace app\admin\controller;

use think\facade\View;
use think\facade\Session;

use app\admin\controller\Admin;

use app\admin\model\LogUserFund;

class Index extends Admin{

    public function index(){
        $今日门票 = LogUserFund::where('fund_type', '购买门票')->where('insert_time', 'like', date("Y-m-d", time()) . '%')->sum('number');
        $累计门票 = LogUserFund::where('fund_type', '购买门票')->sum('number');
        View::assign('today_m', $今日门票);
        View::assign('acc_m', $累计门票);
        return View::fetch();
    }
}