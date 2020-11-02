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
use app\admin\model\SysSetting;

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
                if(strtotime($this->user->usercount->门票购买_time) + (SysSetting::where('sign', '门票有效期')->value('value') * 86400) < time()){
                    $usercount = IdxUserCount::find($this->user_id);
                    $usercount->has_门票 = 0;
                    $usercount->门票购买_time = "0000-00-00 00:00:00";
                    $usercount->save();
                }
            }
            //清理统计
            if($this->user->usercount->today_date != date("Y-m-d", time())){
                self::清理统计($this->user_id, $this->user);
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
        $price = round(Cache::get('settings')['门票价格'] / Cache::get('today_tt_price'), 2);
        if($level_password != $this->user->level_password){
            return return_data(2, '', Lang::get('二级密码输入错误'));
        }
        $user_fund = IdxUserFund::find($this->user_id);
        if($user_fund->门票 < $price){
            return return_data(2, '', Lang::get('门票区余额不足，请在交易区购买TTP，在我的资产中转入门票'));
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

    public static function 清理统计(){
        $users_count = IdxUserCount::select();
        foreach($users_count as $v){
            if($v->today_date != date("Y-m-d", time())){
                $v->today_date = date("Y-m-d", time());
                $user = IdxUser::find($v->user_id);
                $level_局数 = $user->level == 0 ? 0 : SysLevel::where('level_id', $user->level)->value('增加局数');
                $v->今日最大局数 = SysSetting::where('sign', '每日最大局数')->value('value') + $level_局数;
                $v->今日局数 = 0;
                $v->今日我合格 = 0;
                $v->今日直推合格 = 0;
                $v->今日间推合格 = 0;
                $v->今日团队合格 = 0;
                $v->save();
            }
        }
    }

    public function 游戏(){
        Fund::矿机生产($this->user_id);
        //统计
        $user_count = IdxUserCount::find($this->user_id);
        View::assign('usercount', $user_count);
        View::assign('rw', Cache::get('settings')['任务局数']);
        View::assign('day', Cache::get('settings')['门票有效期']);
        View::assign('price', round(Cache::get('settings')['门票价格'] / Cache::get('today_tt_price')), 2);
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
        if($user_count->今日最大局数 < ($user_count->今日局数 + 1)){
            return return_data(2, '', Lang::get('今日可玩局数不足'));
        }
        if(time() - Session::get('game_time') < 10){
            return return_data(2, '', Lang::get('手动参与游戏的间隔时间为10秒'));
        }
        if($user_fund->number <= 0){
            $user_fund->USDT -= $cache_settings['下注金额'];
            LogUserFund::create_data($this->user_id, '-' . $cache_settings['下注金额'], 'USDT', '参与游戏', '参与游戏');
        }else{
            $user_fund->number -= 1;
            LogUserFund::create_data($this->user_id, '-1', '免费次数', '参与游戏', '参与游戏');
        }
        $res_two = $user_fund->save();
        $user_count->局数 += 1;
        $未中奖局数 = $user_count->局数 >= 10 ? 1 : 0;
        $中奖局数 = 1 - $未中奖局数;
        $user_count->局数 = $user_count->局数 >= 10 ? $user_count->局数 - 10 : $user_count->局数;
        $res_one = GameAuto::create([
            'user_id'=> $this->user_id,
            'type'=> 1,
            '质押USDT'=> 20,
            '可玩局数'=> 1,
            '已玩局数'=> 1,
            '中奖局数'=> $中奖局数,
            '未中奖局数'=> $未中奖局数,
            '质押日期'=> date("Y-m-d", time()),
            'insert_time'=> date("Y-m-d H:i:s", time())
        ]);

        if($res_one && $res_two){
            $user_count->今日局数 += 1;
            $user_count->save();
            Session::set('game_time', time());
            Fund::是否合格($this->user_id);
            return return_data(1, '', Lang::get('参与成功, 请稍后查询游戏结果'), '手动参与游戏');
        }else{
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
        if($usdt != 20 && $usdt != 50 && $usdt != 100 && $usdt != 200){
            return return_data(2, '', Lang::get('非法操作'));
        }
        $user_fund = IdxUserFund::find($this->user_id);
        if($user_fund->USDT < $usdt){
            return return_data(2, '', Lang::get('USDT余额不足'));
        }
        $usdt_array = [20=> 1, 50=>10, 100=> 20, 200=> 50];
        $user_count = IdxUserCount::find($this->user_id);
        if($user_count->今日最大局数 <= ($user_count->今日局数 + $usdt_array[$usdt])){
            return return_data(2, '', Lang::get('今日可玩局数不足'));
        }
        $user_fund->USDT -= $usdt;
        $res_two = $user_fund->save();

        $未中奖局数 = $usdt_array[$usdt] / 10;
        $中奖局数 = $usdt_array[$usdt] - $未中奖局数;
        $res_one = GameAuto::create([
            'user_id'=> $this->user_id,
            'type'=> $usdt_array[$usdt],
            '质押USDT'=> $usdt,
            '可玩局数'=> $usdt_array[$usdt],
            '已玩局数'=> $usdt_array[$usdt],
            '中奖局数'=> $中奖局数,
            '未中奖局数'=> $未中奖局数,
            '质押日期'=> date("Y-m-d", time()),
            'insert_time'=> date("Y-m-d H:i:s", time())
        ]);

        LogUserFund::create_data($this->user_id, '-' . $usdt, 'USDT', '自动质押', '自动质押');
        if($res_one && $res_two){
            $user_count->今日局数 += $usdt_array[$usdt];
            $user_count->save();
            Fund::是否合格($this->user_id);
            return return_data(1, '', Lang::get('质押成功, 请稍后查询游戏结果'), '自动参与游戏');
        }else{
            return return_data(2, '', Lang::get('质押失败'));
        }
    }

    public function 游戏记录(){
        $autos = GameAuto::where('user_id', $this->user_id)->where('status', 1)->order('id desc')->limit(100)->select();
        $log = [];
        foreach($autos as $auto){
            if($auto->status == 1){
                $i = 0;
                while($i < $auto->可玩局数){
                    $insert_time = date("Y-m-d H:i:s", strtotime($auto->insert_time) + ($i * 60));
                    $id = strval($auto->id) . strval(strtotime($auto->insert_time) + ($i * 60));
                    if($i < $auto->中奖局数){
                        $log[] = ['color'=> 'orange_color', 'status'=> Lang::get('中奖'), 'id'=> $id, 'insert_time'=> $insert_time];
                    }else{
                        $log[] = ['color'=> 'red_color', 'status'=> Lang::get('未中奖'), 'id'=> $id, 'insert_time'=> $insert_time];
                    }
                    $i++;
                }
            }
        }
        View::assign('list', $log);
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
        foreach(IdxUser::where('pan_user_id', $accounts[0]['id'])->order('register_time desc')->select() as $v){
            $accounts[] = ['id'=> $v->user_id, 'nickname'=> $v->nickname, 'is_login'=> ($v->user_id == $this->user_id ? 1 : 0)];
        }
        return $accounts;
    }
}