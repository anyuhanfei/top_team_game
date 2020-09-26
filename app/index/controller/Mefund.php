<?php
namespace app\index\controller;

use think\facade\View;
use think\facade\Env;
use think\facade\Lang;
use think\facade\Request;
use think\facade\Session;
use think\facade\Cache;
use think\facade\Db;

use app\index\controller\Index;

use app\admin\model\IdxUserFund;
use app\admin\model\LogUserFund;
use app\admin\model\IdxUserMill;
use app\admin\model\UserCharge;


class Mefund extends Index{
    public function __construct(){
        parent::__construct();
    }

    public function 资产(){
        return View::fetch();
    }

    public function coin($coin_type){
        if($coin_type != 'USDT' && $coin_type != 'TTP' && $coin_type != 'TTA' && $coin_type != '能量石'){
            return redirect('/index/非法操作');
        }
        $log = LogUserFund::where('coin_type', $coin_type)->where('user_id', $this->user_id)->order('id desc')->select();
        View::assign('coin_type', $coin_type);
        View::assign('coin_amount', $this->user->userfund->$coin_type);
        View::assign('log', $log);
        return View::fetch();
    }

    public function 收益记录(){
        $log = LogUserFund::where('user_id', $this->user_id)->where('fund_type', 'in', ['奖金', '推广奖励'])->order('id desc')->select();
        $推广收益 = LogUserFund::where('user_id', $this->user_id)->where('fund_type', '奖金')->sum('number');
        $团队收益 = LogUserFund::where('user_id', $this->user_id)->where('fund_type', '推广奖励')->sum('number');
        View::assign('log', $log);
        View::assign('tg', $推广收益);
        View::assign('td', $团队收益);
        return View::fetch();
    }

    public function 兑换(){
        View::assign('b', Cache::get('settings')['能量石兑换比例']);
        return View::fetch();
    }

    public function 兑换提交(){
        $number = Request::instance()->param('number', 0);
        $level_password = Request::instance()->param('level_password', '');
        $validate = new \app\index\validate\兑换;
        if(!$validate->check(['number'=> $number, 'level_password'=> $level_password])){
            return return_data(2, '', Lang::get($validate->getError()));
        }
        $能量石兑换比例 = Cache::get('settings')['能量石兑换比例'];
        $money = $number * $能量石兑换比例;
        $user_fund = IdxUserFund::find($this->user_id);
        $user_fund->能量石 -= $money;
        $user_fund->number += $number;
        $res = $user_fund->save();
        LogUserFund::create_data($this->user_id, '-' . $money, '能量石', '兑换', '能量石兑换可玩次数');
        if($res){
            return return_data(1, '', Lang::get('兑换成功'), '能量石兑换');
        }else{
            return return_data(2, '', Lang::get('兑换失败'));
        }
    }

    public function 充值(){
        if($this->user->address == ''){
            $kuake_ip = Env::get('ANER_ADMIN.KUAKE_IP');
            $url = "http://". $kuake_ip ."/wallet/createAddr?userId=" . $this->user_id;
            $opts = array(
                'http'=>array(
                  'method'=>"POST",
                  "header"=> "Content-Type:application/x-www-form-urlencoded"
                )
            );
            $context = stream_context_create($opts);
            $res = json_decode(file_get_contents($url, false, $context));
            if($res->code == 200){
                $this->user->address = $res->data;
                $this->user->address_qrcode = png_erwei($this->user->address, $this->user->address);
                $this->user->save();
            }
        }
        if($this->user->address_qrcode == ''){
            $this->user->address_qrcode = png_erwei($this->user->address, $this->user->address);
            $this->user->save();
        }
        View::assign('address', $this->user->address);
        View::assign('address_qrcode', $this->user->address_qrcode);
        return View::fetch();
    }

    public function 提现(){
        $type = Request::instance()->param('type', '');
        View::assign('type', $type);
        View::assign('fee', Cache::get('settings')['提现手续费']);
        return View::fetch();
    }

    public function 提现提交(){
        $type = Request::instance()->param('type', '');
        $number = Request::instance()->param('number', 0);
        $address = $this->user->$type;
        $level_password = Request::instance()->param('level_password', '');
        $validate = new \app\index\validate\提现;
        if(!$validate->check(['type'=> $type, 'number'=> $number, 'address'=> $address, 'level_password'=> $level_password])){
            return return_data(2, '', Lang::get($validate->getError()));
        }
        Db::startTrans();
        $fee = Cache::get('settings')['提现手续费'];
        $user_fund = IdxUserFund::find($this->user_id);
        $user_fund->$type -= $number + $fee;
        $res_one = $user_fund->save();
        $swift_no = 'sn' . date("YmdHis", time()) . rand(1000, 9999) . substr($this->user->phone, 7, 4);
        $withdraw_address = Cache::get('settings')['withdraw_address'];
        $res_two = UserCharge::create([
            'swift_no'=> $swift_no,
            'user_id'=> $this->user_id,
            'code'=> $type,
            'balance'=> $number,
            'charge_type'=> 2,
            'poundage'=> $fee,
            'create_time'=> date("Y-m-d H:i:s", time()),
            'to_addr'=> $address,
            'from_addr'=> $withdraw_address
        ]);
        if($res_one && $res_two){
            Db::commit();
            LogUserFund::create_data($this->user_id, '-' . $number, $type, '提现', $type . '提现');
            LogUserFund::create_data($this->user_id, '-' . $fee, $type, '提现手续费', $type . '提现手续费');
            return return_data(1, '', Lang::get('提币申请成功'), $type . '提现申请');
        }else{
            Db::rollback();
            return return_data(2, '', Lang::get('提币申请失败'));
        }
    }

    public function 转账(){
        View::assign('accounts', $this->get_账号s());
        return View::fetch();
    }

    public function 转账提交(){
        $coin_type = Request::instance()->param('coin_type', '');
        $to_user_id = Request::instance()->param('to_user_id', '');
        $number = Request::instance()->param('number', 0);
        $level_password = Request::instance()->param('level_password', '');
        $validate = new \app\index\validate\转账();
        if(!$validate->check(['coin_type'=> $coin_type, 'to_user_id'=> $to_user_id, 'number'=> $number, 'level_password'=> $level_password])){
            return return_data(2, '', Lang::get($validate->getError()));
        }
        Db::startTrans();
        $from_user_fund = IdxUserFund::find($this->user_id);
        $to_user_fund = IdxUserFund::find($to_user_id);
        $from_user_fund->$coin_type -= $number;
        $to_user_fund->$coin_type += $number;
        $res_one = $from_user_fund->save();
        $res_two = $to_user_fund->save();
        if($res_one && $res_two){
            LogUserFund::create_data($from_user_fund->user_id, '-' . $number, $coin_type, '转账', '给'. $to_user_id .'转账');
            LogUserFund::create_data($to_user_fund->user_id, $number, $coin_type, '转账', $this->user_id . '给我转账');
            Db::commit();
            return return_data(1, '', Lang::get('转账成功'), '给' . $to_user_id . '转账');
        }else{
            Db::rollback();
            return return_data(2, '', Lang::get('转账失败'));
        }
    }

    public function 矿机(){
        $cache_settings = Cache::get('settings');
        $mills = IdxUserMill::where('user_id', $this->user_id)->select();
        $mill_earnings = LogUserFund::where('user_id', $this->user_id)->where('fund_type', '矿机生产')->sum('number');
        $mill_number = 0;
        foreach($mills as &$mill){
            if($mill->status == 0){
                $mill_number += 1;
            }
            $mill->价值 = $cache_settings['矿机价值'];
            $mill->收益天 = $cache_settings['矿机价值'] * $cache_settings['每日收益PCT'] * 0.01;
            $mill->收益周期 = $cache_settings['收益周期'];
            $mill->预计收益 = $cache_settings['矿机价值'] * $cache_settings['每日收益PCT'] * 0.01 * $cache_settings['收益周期'];
            $mill->已运行时间 = intval((time() - strtotime($mill->insert_time)) / 60);
        }
        View::assign('mills', $mills);
        View::assign('mill_earnings', $mill_earnings);
        View::assign('mill_number', $mill_number);
        return View::fetch();
    }

    public function 矿机记录(){
        $log = LogUserFund::where('user_id', $this->user_id)->where('fund_type', '矿机生产')->order('id desc')->select();
        View::assign('log', $log);
        return View::fetch();
    }
}