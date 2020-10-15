<?php
namespace app\index\controller;

use think\facade\Session;
use think\facade\View;
use think\facade\Cookie;
use think\facade\Request;
use think\facade\Lang;
use think\facade\Db;
use think\facade\Cache;

use app\admin\model\IdxUser;
use app\admin\model\AutoValue;
use app\admin\model\SysAd;
use app\admin\model\IdxTtPrice;
use app\admin\model\IdxUserCount;
use app\admin\model\LogUserFund;
use app\admin\model\SysLevel;
use app\admin\model\IdxUserFund;
use app\admin\model\GameQueue;
use app\admin\model\GameAuto;
use app\admin\model\GameInning;

use app\index\controller\Base;
use app\index\controller\Fund;

class Index extends Base{
    protected $user = null;
    protected $middleware = [
        \app\index\middleware\CheckIndex::class,
        \app\index\middleware\LogOperation::class,
    ];

    public function __construct(){
        parent::__construct();
        $this->user_id = Session::get('user_id');
        $this->user = IdxUser::find($this->user_id);
        try {
            if($this->user->language != Cookie::get('think_lang')){
                Cookie::set('think_lang', $this->user->language);
                return redirect(Request::instance()->url());
            }
            //检查自己的门票
            if($this->user->usercount->has_门票 == 1){
                if(strtotime($this->user->usercount->门票购买_time) + (Cache::get('settings')['门票有效期'] * 86400) < time()){
                    $usercount = IdxUserCount::find($this->user_id);
                    $usercount->has_门票 = 0;
                    $usercount->门票购买_time = "0000-00-00 00:00:00";
                    $usercount->save();
                }
            }
        } catch (\Throwable $th) {

        }
        View::assign('user_id', $this->user_id);
        View::assign('user', $this->user);
    }

    public function 启动图(){
        return View::fetch();
    }

    public function index(){
        Fund::矿机生产($this->user_id);
        //轮播图
        $banners = SysAd::where('sign', 'index_banner')->select();
        view::assign('banners', $banners);
        //公告
        $notice = SysAd::where('sign', '首页公告' . Cookie::get('think_lang'))->value('value');
        View::assign('notice', $notice);
        //TT价格表
        $tt_prices = IdxTtPrice::where('id', '<=', date('Y-m-d', time()))->order('id desc')->limit(7)->select();
        foreach($tt_prices as &$v){
            $v->id = substr($v->id, 5);
            $v->price = $v->price;
        }
        View::assign('tt_prices', $tt_prices);
        //币种价格
        $btc = AutoValue::find(1);
        $eth = AutoValue::find(2);
        View::assign('btc', $btc);
        View::assign('eth', $eth);
        // if($btc){
        //     if(intval($btc->insert_time) < time() - 10){
        //         exec('python3 ./python/get_data.py');
        //     }
        // }else{
        //     exec('python3 ./python/get_data.py');
        // }
        return View::fetch();
    }

    public function 更多(){
        return View::fetch();
    }

    public function 购买门票(){
        $level_password = Request::instance()->param('level_password', '');
        $price = Cache::get('settings')['门票价格'] * Cache::get('today_tt_price');
        if($level_password != $this->user->level_password){
            return return_data(2, '', Lang::get('二级密码输入错误'));
        }
        $user_fund = IdxUserFund::find($this->user_id);
        if($user_fund->门票 < $price){
            return return_data(2, '', Lang::get('门票余额不足'));
        }
        Db::startTrans();
        $user_fund->门票 -= $price;
        $res_one = $user_fund->save();
        $user_count = IdxUserCount::find($this->user_id);
        $user_count->has_门票 = 1;
        $user_count->门票购买_time = date("Y-m-d H:i:s", time());
        $res_two = $user_count->save();
        if($res_one && $res_two){
            if(!LogUserFund::where('user_id', $this->user_id)->where('fund_type', '购买门票')->find()){
                //第一次
                if($this->user->top_id != 0){
                    $top_user_fund = IdxUserFund::find($this->user->top_id);
                    $top_user_fund->能量石 += Cache::get('settings')['有效会员奖励'];
                    $top_user_fund->save();
                    LogUserFund::create_data($this->user->top_id, Cache::get('settings')['有效会员奖励'], '能量石', '下级成为有效会员', '下级第一次购买门票的上级奖励');
                }
            }
            LogUserFund::create_data($this->user_id, '-' . $price, '门票', '购买门票', '购买门票');
            Db::commit();
            return return_data(1, '', Lang::get('购买成功'), '购买门票');
        }else{
            Db::rollback();
            return return_data(2, '', Lang::get('购买失败'));
        }
    }

    public function 游戏(){
        //统计
        $user_count = IdxUserCount::find($this->user_id);
        if($user_count->today_date != date("Y-m-d", time())){
            $user_count->today_date = date("Y-m-d", time());
            $level_局数 = $this->user->level_id == 0 ? 0 : SysLevel::where('level_id', $this->user->level_id)->value('增加局数');
            $user_count->今日最大局数 = Cache::get('settings')['每日最大局数'] + $level_局数;
            $user_count->今日局数 = 0;
            $user_count->save();
        }
        View::assign('usercount', $user_count);
        View::assign('rw', Cache::get('settings')['任务局数']);
        View::assign('day', Cache::get('settings')['门票有效期']);
        View::assign('price', Cache::get('settings')['门票价格'] * Cache::get('today_tt_price'));
        return View::fetch();
    }

    public function 手动参与(){
        if($this->user->usercount->has_门票 != 1){
            return return_data(2, '', Lang::get('请购买门票'));
        }
        $cache_settings = Cache::get('settings');
        if($cache_settings['每日游戏开始时间'] > date("H:i", time()) || $cache_settings['每日游戏结束时间'] < date("H:i", time())){
            return return_data(2, '', Lang::get('游戏通道已关闭, 每日游戏时间为:') . $cache_settings['每日游戏开始时间'] . '-' . $cache_settings['每日游戏结束时间']);
        }
        $user_fund = IdxUserFund::find($this->user_id);
        if($user_fund->number <= 0){
            if($user_fund->USDT < $cache_settings['下注金额']){
                return return_data(2, '', Lang::get('USDT余额不足'));
            }
        }
        $user_count = IdxUserCount::find($this->user_id);
        if($user_count->今日最大局数 <= $user_count->今日局数){
            return return_data(2, '', Lang::get('今日可玩局数已完成'));
        }
        // if(GameQueue::where('user_id', $this->user_id)->where('is_pop', 0)->find()){
        //     return return_data(2, '', Lang::get('正在游戏中, 请等待'));
        // }
        if(time() - Session::get('game_time') < 10){
            return return_data(2, '', Lang::get('手动参与游戏的间隔时间为10秒'));
        }
        Db::startTrans();
        $res_one = GameQueue::create([
            'user_id'=> $this->user_id
        ]);
        if($user_fund->number <= 0){
            $user_fund->USDT -= $cache_settings['下注金额'];
            LogUserFund::create_data($this->user_id, '-' . $cache_settings['下注金额'], 'USDT', '参与游戏', '参与游戏');
        }else{
            $user_fund->number -= 1;
            LogUserFund::create_data($this->user_id, '-1', '免费次数', '参与游戏', '参与游戏');
        }
        $res_two = $user_fund->save();
        if($res_one && $res_two){
            $user_count->今日局数 += 1;
            $user_count->save();
            Session::set('game_time', time());
            Db::commit();
            return return_data(1, '', Lang::get('参与成功, 请稍后查询游戏结果'), '手动参与游戏');
        }else{
            Db::rollback();
            return return_data(2, '', Lang::get('参与失败'));
        }
    }

    public function 自动参与(){
        if($this->user->usercount->has_门票 != 1){
            return return_data(2, '', Lang::get('请购买门票'));
        }
        $cache_settings = Cache::get('settings');
        if($cache_settings['每日游戏开始时间'] > date("H:i", time()) || $cache_settings['每日游戏结束时间'] < date("H:i", time())){
            return return_data(2, '', Lang::get('游戏通道已关闭, 每日游戏时间为:') . $cache_settings['每日游戏开始时间'] . '-' . $cache_settings['每日游戏结束时间']);
        }
        $usdt = Request::instance()->param('usdt', 0);
        if($usdt != 20 && $usdt != 100 && $usdt != 200){
            return return_data(2, '', Lang::get('非法操作'));
        }
        $user_fund = IdxUserFund::find($this->user_id);
        if($user_fund->USDT < $usdt){
            return return_data(2, '', Lang::get('USDT余额不足'));
        }
        $usdt_array = [20=> 1, 100=> 20, 200=> 50];
        // if(GameAuto::where('user_id', $this->user_id)->where('质押日期', date("Y-m-d", time()))->find()){
        //     return return_data(2, '', Lang::get('今日已质押, 无法多次质押'));
        // }
        $user_count = IdxUserCount::find($this->user_id);
        if($user_count->今日最大局数 <= $user_count->今日局数){
            return return_data(2, '', Lang::get('今日可玩局数不足'));
        }
        Db::startTrans();
        $res_one = GameAuto::create([
            'user_id'=> $this->user_id,
            'type'=> $usdt_array[$usdt],
            '质押USDT'=> $usdt,
            '可玩局数'=> $user_count->今日最大局数 < $user_count->今日局数 + $usdt_array[$usdt] ? $user_count->今日最大局数 - $user_count->今日局数 : $usdt_array[$usdt],
            '质押日期'=> date("Y-m-d", time()),
            'insert_time'=> date("Y-m-d H:i:s", time())
        ]);
        $user_fund->USDT -= $usdt;
        $res_two = $user_fund->save();
        LogUserFund::create_data($this->user_id, '-' . $usdt, 'USDT', '自动质押', '自动质押');
        if($res_one && $res_two){
            $user_count->今日局数 += $user_count->今日最大局数 < $user_count->今日局数 + $usdt_array[$usdt] ? $user_count->今日最大局数 - $user_count->今日局数 : $usdt_array[$usdt];
            $user_count->save();
            Db::commit();
            return return_data(1, '', Lang::get('质押成功'), '自动参与游戏');
        }else{
            Db::rollback();
            return return_data(2, '', Lang::get('质押失败'));
        }
    }

    public function 游戏记录(){
        $list = GameInning::where('player_id_one|player_id_two|player_id_three|player_id_four|player_id_five|player_id_six|player_id_seven|player_id_eight|player_id_nine|player_id_ten', $this->user_id)->order('insert_time desc')->select();
        foreach($list as &$v){
            if($v->end_time != NULL){
                foreach (['one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten'] as $num) {
                    $key = 'player_id_' . $num;
                    if($v->$key == $this->user_id){
                        $key = 'is_win_' . $num;
                        if($v->$key == 1){
                            $v->color = 'orange_color';
                            $v->status = Lang::get('中奖');
                        }else{
                            $v->color = 'red_color';
                            $v->status = Lang::get('未中奖');
                        }
                    }
                }
            }else{
                $v->color = 'green_color';
                $v->status = Lang::get('未开奖');
            }
        }
        View::assign('list', $list);
        return View::fetch();
    }

    public function 分红(){
        $log = LogUserFund::where('user_id', $this->user_id)->where('fund_type', '奖金')->order('id desc')->select();
        $money = LogUserFund::where('user_id', $this->user_id)->where('fund_type', '奖金')->sum('number');
        View::assign('log', $log);
        View::assign('money', $money);
        return View::fetch();
    }

    public function get_账号s(){
        if($this->user->pan_user_id == 0){
            //我就是主账号
            $accounts = [['id'=> $this->user_id, 'nickname'=> Lang::get('主账号'), 'is_login'=> 1]];
        }else{
            //我是子账号
            $accounts = [['id'=> $this->user->pan_user_id, 'nickname'=> Lang::get('主账号'), 'is_login'=> 0]];
        }
        foreach(IdxUser::where('pan_user_id', $accounts[0]['id'])->select() as $v){
            $accounts[] = ['id'=> $v->user_id, 'nickname'=> $v->nickname, 'is_login'=> ($v->user_id == $this->user_id ? 1 : 0)];
        }
        return $accounts;
    }
}