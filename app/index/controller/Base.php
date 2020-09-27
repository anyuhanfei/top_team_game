<?php
namespace app\index\controller;

use think\facade\Cache;
use think\facade\Request;
use think\facade\Cookie;
use think\facade\View;

use app\admin\model\SysSetting;
use app\admin\model\SysLevel;
use app\admin\model\IdxUser;
use app\admin\model\IdxUserFund;
use app\admin\model\IdxTtPrice;

class Base{
    protected $middleware = [
        \app\index\middleware\Language::class,
    ];
    protected static $游戏质押规格 = [1=> 20, 20=> 100, 50=> 200];

    public function __construct(){
        // 系统设置
        if(!Cache::get('settings')){
            $settings = SysSetting::where('type', 'value')->field('sign, value')->select();
            $settings_array = [];
            foreach($settings as $v){
                $settings_array[$v['sign']] = $v['value'];
            }
            Cache::set('settings', $settings_array, 30);
        }
        // 等级设置
        if(!Cache::get('level')){
            $level = SysLevel::order('level_id asc')->select()->toArray();
            for($i=0; $i < count($level); $i++) {
                if($i == 0){
                    $level[$i]['奖励_终'] = $level[$i]['奖励'];
                }else{
                    $level[$i]['奖励_终'] = $level[$i]['奖励'] - $level[$i - 1]['奖励'];
                }
            }
            Cache::set('level', $level, 30);
        }
        // 今日价格
        if(!Cache::get('today_tt_price')){
            $price = IdxTtPrice::where('id', date("Y-m-d", time()))->find();
            if(!$price){
                $price = IdxTtPrice::order('id desc')->find();
                IdxTtPrice::create([
                    'id'=> date("Y-m-d", time()),
                    'price'=> $price->price
                ]);
            }
            Cache::set('today_tt_price', $price->price, 6000);
        }

        //语言
        if(Cookie::get('think_lang') == 'zh-cn'){
            View::assign('language', 'English');
        }else{
            View::assign('language', '中文');
        }
    }

    public function 非法操作(){
        return View::fetch('index/非法操作');
    }
}