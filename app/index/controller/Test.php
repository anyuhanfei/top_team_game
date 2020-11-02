<?php
namespace app\index\controller;

use think\facade\View;
use think\facade\Request;
use think\facade\Session;
use think\facade\Cookie;
use think\facade\Lang;
use think\facade\Cache;

use app\index\controller\Index;
use app\index\controller\Fund;

use app\admin\model\IdxUserFund;
use app\admin\model\IdxUserCount;
use app\admin\model\IdxUser;
use app\admin\model\GameAuto;
use app\admin\model\LogUserFund;

class Test extends Index{
    public function __construct(){
        parent::__construct();
    }

    public function 批量增加会员(){
        $i = 0;
        $password = '123456a';
        $top_id = 0;
        $level_password = '123456';
        while($i <= 1000){
            $助记词 = read_word();
            IdxUser::create_data($助记词, $password, $top_id, create_captcha(8, 'lowercase+uppercase+figure'), $level_password);
            $i++;
        }
    }

    public function 批量质押一次(){
        $users = IdxUser::select();
        foreach($users as $v){
            if($v->usercount->has_门票 != 1){
                return return_data(2, '', Lang::get('请购买门票'));
            }
            $usdt = 200;
            if($usdt != 20 && $usdt != 50 && $usdt != 100 && $usdt != 200){
                return return_data(2, '', Lang::get('非法操作'));
            }
            $user_fund = IdxUserFund::find($v->user_id);
            if($user_fund->USDT < $usdt){
                return return_data(2, '', Lang::get('USDT余额不足'));
            }
            $usdt_array = [20=> 1, 50=>10, 100=> 20, 200=> 50];
            $user_count = IdxUserCount::find($v->user_id);
            if($user_count->今日最大局数 <= ($user_count->今日局数 + $usdt_array[$usdt])){
                return return_data(2, '', Lang::get('今日可玩局数不足'));
            }
            $user_fund->USDT -= $usdt;
            $res_two = $user_fund->save();

            $未中奖局数 = $usdt_array[$usdt] / 10;
            $中奖局数 = $usdt_array[$usdt] - $未中奖局数;
            $res_one = GameAuto::create([
                'user_id'=> $v->user_id,
                'type'=> $usdt_array[$usdt],
                '质押USDT'=> $usdt,
                '可玩局数'=> $usdt_array[$usdt],
                '已玩局数'=> $usdt_array[$usdt],
                '中奖局数'=> $中奖局数,
                '未中奖局数'=> $未中奖局数,
                '质押日期'=> date("Y-m-d", time()),
                'insert_time'=> date("Y-m-d H:i:s", time())
            ]);

            LogUserFund::create_data($v->user_id, '-' . $usdt, 'USDT', '自动质押', '自动质押');
            if($res_one && $res_two){
                $user_count->今日局数 += $usdt_array[$usdt];
                $user_count->save();
                Fund::是否合格($v->user_id);
                // return return_data(1, '', Lang::get('质押成功, 请稍后查询游戏结果'), '自动参与游戏');
            }else{
                // return return_data(2, '', Lang::get('质押失败'));
            }
        }
    }

    public function 批量质押到死(){
        $users = IdxUser::select();
        $users_count = count($users);
        while(true){
            $user = $users[rand(0, $users_count - 1)];
            if($user->usercount->has_门票 != 1){
                return return_data(2, '', Lang::get('请购买门票'));
            }
            $usdt = 200;
            if($usdt != 20 && $usdt != 50 && $usdt != 100 && $usdt != 200){
                return return_data(2, '', Lang::get('非法操作'));
            }
            $user_fund = IdxUserFund::find($user->user_id);
            if($user_fund->USDT < $usdt){
                return return_data(2, '', Lang::get('USDT余额不足'));
            }
            $usdt_array = [20=> 1, 50=>10, 100=> 20, 200=> 50];
            $user_count = IdxUserCount::find($user->user_id);
            if($user_count->今日最大局数 <= ($user_count->今日局数 + $usdt_array[$usdt])){
                return return_data(2, '', Lang::get('今日可玩局数不足'));
            }
            $user_fund->USDT -= $usdt;
            $res_two = $user_fund->save();

            $未中奖局数 = $usdt_array[$usdt] / 10;
            $中奖局数 = $usdt_array[$usdt] - $未中奖局数;
            $res_one = GameAuto::create([
                'user_id'=> $user->user_id,
                'type'=> $usdt_array[$usdt],
                '质押USDT'=> $usdt,
                '可玩局数'=> $usdt_array[$usdt],
                '已玩局数'=> $usdt_array[$usdt],
                '中奖局数'=> $中奖局数,
                '未中奖局数'=> $未中奖局数,
                '质押日期'=> date("Y-m-d", time()),
                'insert_time'=> date("Y-m-d H:i:s", time())
            ]);

            LogUserFund::create_data($user->user_id, '-' . $usdt, 'USDT', '自动质押', '自动质押');
            if($res_one && $res_two){
                $user_count->今日局数 += $usdt_array[$usdt];
                $user_count->save();
                Fund::是否合格($user->user_id);
                // return return_data(1, '', Lang::get('质押成功, 请稍后查询游戏结果'), '自动参与游戏');
            }else{
                // return return_data(2, '', Lang::get('质押失败'));
            }
        }
    }
}