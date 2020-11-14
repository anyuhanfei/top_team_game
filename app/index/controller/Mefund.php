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
use app\admin\model\IdxUser;
use app\admin\model\IdxUserCount;
use app\admin\model\SysData;

class Mefund extends Index{
    public function __construct(){
        parent::__construct();
    }

    public function 资产(){
        return View::fetch();
    }

    public function coin($coin_type){
        if($coin_type != 'USDT' && $coin_type != 'TTP' && $coin_type != 'TTA' && $coin_type != '能量石' && $coin_type != '门票'){
            return redirect('/index/非法操作');
        }
        $log = LogUserFund::where('coin_type', $coin_type)->where('user_id', $this->user_id)->whereDay('insert_time')->order('id desc')->select();
        View::assign('coin_type', $coin_type);
        View::assign('coin_amount', $this->user->userfund->$coin_type);
        View::assign('log', $log);
        return View::fetch();
    }

    public function 收益记录(){
        // $cache_log = Cache::get($this->user_id . 'log');
        // if($cache_log == NULL){
        //     $log = LogUserFund::field('insert_time, content, number, coin_type, fund_type')->where('user_id', $this->user_id)->where('fund_type', 'like', '%奖励')->order('id desc')->select()->toArray();
        //     $推广收益 = 0;
        //     $团队收益 = 0;
        //     $创世节点收益 = 0;
        //     foreach($log as $v){
        //         if($v['fund_type'] == '直推链接奖励' || $v['fund_type'] == '间推链接奖励'){
        //             $推广收益 += $v['number'];
        //         }elseif($v->fund_type == '创世节点奖励'){
        //             $创世节点收益 += $v['number'];
        //         }else{
        //             $团队收益 += $v['number'];
        //         }
        //     }
        //     Cache::set($this->user_id . 'log', ['log'=> $log, '推广收益'=> $推广收益, '团队收益'=> $团队收益, '创世节点收益'=> $创世节点收益], 600);
        // }else{
        //     $log = $cache_log['log'];
        //     $推广收益 = $cache_log['推广收益'];
        //     $团队收益 = $cache_log['团队收益'];
        //     $创世节点收益 = $cache_log['创世节点收益'];
        // }
        // // $推广收益 = LogUserFund::where('user_id', $this->user_id)->where('fund_type', 'in', ['直推链接奖励', '间推链接奖励'])->sum('number');
        // // $团队收益 = LogUserFund::where('user_id', $this->user_id)->where('fund_type', 'like', '%勋章奖励')->sum('number');
        // // $创世节点收益 = LogUserFund::where('user_id', $this->user_id)->where('fund_type', '创世节点奖励')->sum('number');
        // $z = SysData::where('id', 1)->find();
        // View::assign('log', $log);
        // View::assign('tg', $推广收益);
        // View::assign('td', $团队收益);
        // View::assign('cs', $创世节点收益);
        // View::assign('a', $z->昨日推广分红);
        // View::assign('b', $z->昨日团队分红);
        // View::assign('c', $z->昨日创世节点分红);
        // return View::fetch();
        // $log = LogUserFund::where('user_id', $this->user_id)->where('fund_type', 'like', '%奖励')->order('id desc')->select();
        // $推广收益 = LogUserFund::where('user_id', $this->user_id)->where('fund_type', 'in', ['直推链接奖励', '间推链接奖励'])->sum('number');
        // $团队收益 = LogUserFund::where('user_id', $this->user_id)->where('fund_type', 'like', '%勋章奖励')->sum('number');
        // $创世节点收益 = LogUserFund::where('user_id', $this->user_id)->where('fund_type', '创世节点奖励')->sum('number');

        $log = LogUserFund::field('insert_time, content, number, coin_type, fund_type')->where('user_id', $this->user_id)->where('fund_type', 'like', '%奖励')->order('id desc')->select()->toArray();
        $list = [];
        $推广收益 = 0;
        $团队收益 = 0;
        $创世节点收益 = 0;
        foreach($log as $v){
            if(date("Y-m-d", strtotime($v['insert_time'])) == date("Y-m-d", time() - 86400)){
                $list[] = $v;
            }
            if($v['fund_type'] == '直推链接奖励' || $v['fund_type'] == '间推链接奖励'){
                $推广收益 += $v['number'];
            }elseif($v->fund_type == '创世节点奖励'){
                $创世节点收益 += $v['number'];
            }else{
                $团队收益 += $v['number'];
            }
        }
        Cache::set($this->user_id . 'log', ['log'=> $log, '推广收益'=> $推广收益, '团队收益'=> $团队收益, '创世节点收益'=> $创世节点收益], 600);
        View::assign('log', $list);
        View::assign('tg', $推广收益);
        View::assign('td', $团队收益);
        View::assign('cs', $创世节点收益);
        View::assign('a', SysData::where('id', 1)->value('昨日推广分红'));
        View::assign('b', SysData::where('id', 1)->value('昨日团队分红'));
        View::assign('c', SysData::where('id', 1)->value('昨日创世节点分红'));
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
        $res_one = $user_fund->save();
        $user_count = IdxUserCount::find($this->user_id);
        $user_count->今日最大局数 += $number;
        $res_two = $user_count->save();
        LogUserFund::create_data($this->user_id, '-' . $money, '能量石', '兑换', '能量石兑换可玩次数');
        if($res_two && $res_one){
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
        if($this->user->taddress == ''){
            $kuake_ip = Env::get('ANER_ADMIN.KUAKE_IP');
            $url = "http://". $kuake_ip ."/tron/createAddress?userId=" . $this->user_id;
            $opts = array(
                'http'=>array(
                  'method'=>"GET",
                  "header"=> "Content-Type:application/x-www-form-urlencoded"
                )
            );
            $context = stream_context_create($opts);
            $res = json_decode(file_get_contents($url, false, $context));
            if($res->code == 200){
                $this->user->taddress = $res->data;
                $this->user->taddress_qrcode = png_erwei($this->user->taddress, $this->user->taddress);
                $this->user->save();
            }
        }
        if($this->user->address_qrcode == ''){
            $this->user->address_qrcode = png_erwei($this->user->address, $this->user->address);
            $this->user->save();
        }
        if($this->user->taddress_qrcode == ''){
            $this->user->taddress_qrcode = png_erwei($this->user->taddress, $this->user->taddress);
            $this->user->save();
        }
        View::assign('address', $this->user->address);
        View::assign('address_qrcode', $this->user->address_qrcode);
        View::assign('taddress', $this->user->taddress);
        View::assign('taddress_qrcode', $this->user->taddress_qrcode);
        return View::fetch();
    }

    public function 提现(){
        $type = Request::instance()->param('type', '');
        View::assign('type', $type);
        View::assign('fee', Cache::get('settings')[$type . '提现手续费']);
        return View::fetch();
    }

    public function 提现提交(){
        $type = Request::instance()->param('type', '');
        $number = Request::instance()->param('number', 0);
        $lian_type = Request::instance()->param('lian_type', 0);
        $address = $this->user->$type;
        $level_password = Request::instance()->param('level_password', '');
        $validate = new \app\index\validate\提现;
        if(!$validate->check(['type'=> $type, 'number'=> $number, 'address'=> $address, 'level_password'=> $level_password, 'lian_type'=> $lian_type])){
            return return_data(2, '', Lang::get($validate->getError()));
        }
        $cache_settings = Cache::get('settings');
        if($cache_settings['提现起始'] > date("H:i", time()) || $cache_settings['提现结束'] < date("H:i", time())){
            return return_data(2, '', Lang::get('提现通道已关闭, 每日提现时间为:') . $cache_settings['提现起始'] . '-' . $cache_settings['提现结束']);
        }
        Db::startTrans();
        $fee = Cache::get('settings')[$type . '提现手续费'];
        $user_fund = IdxUserFund::find($this->user_id);
        $user_fund->$type -= $number + $fee;
        $res_one = $user_fund->save();
        $swift_no = 'sn' . date("YmdHis", time()) . rand(1000, 9999) . substr($this->user->phone, 7, 4);
        if($type == 'USDT' && $lian_type == 'TRC20'){
            $withdraw_address = Cache::get('settings')['t_withdraw_address'];
            $res_two = UserCharge::create([
                'swift_no'=> $swift_no,
                'user_id'=> $this->user_id,
                'code'=> $type,
                'balance'=> $number,
                'charge_type'=> 2,
                'poundage'=> $fee,
                'create_time'=> date("Y-m-d H:i:s", time()),
                'coin_type'=> 3,
                'to_addr'=> $this->user->TUSDT,
                'from_address'=> $withdraw_address
            ]);
        }else{
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
                'from_address'=> $withdraw_address
            ]);
        }
        
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
        $type = Request::instance()->param('type', '');
        $coin_type = Request::instance()->param('coin_type', '');
        $to_user_id = Request::instance()->param('to_user_id', '');
        $number = Request::instance()->param('number', 0);
        $level_password = Request::instance()->param('level_password', '');
        $validate = new \app\index\validate\转账();
        if(!$validate->check(['type'=> $type, 'coin_type'=> $coin_type, 'to_user_id'=> $to_user_id, 'number'=> $number, 'level_password'=> $level_password])){
            return return_data(2, '', Lang::get($validate->getError()));
        }
        Db::startTrans();
        $from_user_fund = IdxUserFund::find($this->user_id);
        $to_user_fund = IdxUserFund::find($to_user_id);
        $fee = $type == 'z' ? 0 : $number * Cache::get('settings')['转账fee'] * 0.01;
        $from_user_fund->$coin_type -= $number + $fee;
        $to_user_fund->$coin_type += $number;
        $res_one = $from_user_fund->save();
        $res_two = $to_user_fund->save();
        if($res_one && $res_two){
            LogUserFund::create_data($from_user_fund->user_id, '-' . $number, $coin_type, '转账', '向'. $to_user_id .'转账');
            if($fee > 0){
                LogUserFund::create_data($from_user_fund->user_id, '-' . $fee, $coin_type, '转账', '转账手续费');
            }
            LogUserFund::create_data($to_user_fund->user_id, $number, $coin_type, '转账', $from_user_fund->user_id . '向我转账');
            Db::commit();
            return return_data(1, '', Lang::get('转账成功'), '给' . $to_user_id . '转账');
        }else{
            Db::rollback();
            return return_data(2, '', Lang::get('转账失败'));
        }
    }

    public function 转入(){
        return View::fetch();
    }

    public function 转入提交(){
        $type = Request::instance()->param('type', '');
        $number = Request::instance()->param('number', 0);
        $level_password = Request::instance()->param('level_password', '');
        $validate = new \app\index\validate\转入();
        if(!$validate->check(['type'=> $type, 'number'=> $number, 'level_password'=> $level_password])){
            return return_data(2, '', Lang::get($validate->getError()));
        }
        Db::startTrans();
        $user_fund = IdxUserFund::find($this->user_id);
        $user_fund->$type -= $number;
        $user_fund->门票 += $number;
        $res = $user_fund->save();
        if($res){
            LogUserFund::create_data($this->user_id, '-' . $number, $type, '转入门票', '转入门票');
            LogUserFund::create_data($this->user_id, $number, '门票', '转入门票', '转入门票');
            Db::commit();
            return return_data(1, '', Lang::get('转入成功'), '转入门票');
        }else{
            Db::rollback();
            return return_data(2, '', Lang::get('转入失败'));
        }
    }

    public function 矿机(){
        $cache_settings = Cache::get('settings');
        $mills = IdxUserMill::where('user_id', $this->user_id)->where('status', 0)->order('mill_id desc')->select();
        $user_count = IdxUserCount::find($this->user_id);
        if($user_count->矿机收益 == '0' || $user_count->矿机收益计算时间 != date("Y-m-d", time())){
            $mill_earnings = LogUserFund::where('user_id', $this->user_id)->where('fund_type', '矿机生产')->sum('number');
            $user_count->矿机收益 = $mill_earnings;
            $user_count->矿机收益计算时间 = date("Y-m-d", time());
            $user_count->save();
        }else{
            $mill_earnings = $user_count->矿机收益;
        }
        $mill_number = 0;
        foreach($mills as &$mill){
            if($mill->status == 0){
                $mill_number += 1;
            }
            $mill->价值 = $cache_settings['矿机价值'];
            $mill->收益天 = $cache_settings['矿机价值'] * $cache_settings['每日收益PCT'] * 0.01;
            $mill->收益周期 = $cache_settings['收益周期'];
            $mill->预计收益 = $cache_settings['矿机价值'] * $cache_settings['每日收益PCT'] * 0.01 * $cache_settings['收益周期'];
            // $mill->已运行时间 = intval((time() - strtotime($mill->insert_time)) / 60);
        }
        View::assign('mills', $mills);
        View::assign('mill_earnings', $mill_earnings);
        View::assign('mill_number', $mill_number);
        return View::fetch();
    }

    public function 矿机记录(){
        $log = LogUserFund::where('user_id', $this->user_id)->where('fund_type', '矿机生产')->whereDay('insert_time', 'yesterday')->order('id desc')->select();
        View::assign('log', $log);
        return View::fetch();
    }

    public function 子资产管理(){
        $accounts = $this->get_账号s();
        foreach ($accounts as &$v) {
            $v['userfund'] = IdxUserFund::find($v['id']);
        }
        View::assign('accounts', $accounts);
        return View::fetch();
    }

    public function 归集(){
        $id = Request::instance()->param('id', 0);
        if($id == 0){
            Db::startTrans();
            $user_fund = IdxUserFund::find($this->user_id);
            $pan_users = IdxUser::where('pan_user_id', $this->user_id)->select();
            $usdt_all = 0;
            $ttp_all = 0;
            foreach($pan_users as $v){
                $pan_user_fund = IdxUserFund::find($v->user_id);
                $usdt_all += $pan_user_fund->USDT;
                $ttp_all += $pan_user_fund->TTP;
                $usdt_temp = $pan_user_fund->USDT;
                $ttp_temp = $pan_user_fund->TTP;
                $pan_user_fund->USDT = 0;
                $pan_user_fund->TTP = 0;
                $pan_user_fund->save();
                if($usdt_temp > 0){
                    LogUserFund::create_data($v->user_id, '-' . $usdt_temp, 'USDT', '归集', '归集');
                }
                if($ttp_temp > 0){
                    LogUserFund::create_data($v->user_id, '-' . $ttp_temp, 'TTP', '归集', '归集');
                }
            }
            $user_fund->USDT += $usdt_all;
            $user_fund->TTP += $ttp_all;
            $res = $user_fund->save();
            if($res){
                if($usdt_all > 0){
                    LogUserFund::create_data($this->user_id, $usdt_all, 'USDT', '归集', '归集');
                }
                if($ttp_all > 0){
                    LogUserFund::create_data($this->user_id, $ttp_all, 'TTP', '归集', '归集');
                }
                Db::commit();
                return return_data(1, '', Lang::get('操作成功'), '归集');
            }else{
                Db::rollback();
                return return_data(2, '', Lang::get('操作失败'));
            }
        }else{
            Db::startTrans();
            $user_fund = IdxUserFund::find($this->user_id);
            $z_user = IdxUser::find($id);
            if($z_user->pan_user_id != $this->user_id){
                return return_data(2, '', '非法操作');
            }
            $pan_user_fund = IdxUserFund::find($id);
            $usdt_temp = $pan_user_fund->USDT;
            $ttp_temp = $pan_user_fund->TTP;
            $pan_user_fund->USDT = 0;
            $pan_user_fund->TTP = 0;
            $pan_user_fund->save();
            $user_fund->USDT += $usdt_temp;
            $user_fund->TTP += $ttp_temp;
            $res = $user_fund->save();
            if($usdt_temp > 0){
                LogUserFund::create_data($id, '-' . $usdt_temp, 'USDT', '归集', '归集');
                LogUserFund::create_data($this->user_id, $usdt_temp, 'USDT', '归集', '归集');
            }
            if($ttp_temp > 0){
                LogUserFund::create_data($id, '-' . $ttp_temp, 'TTP', '归集', '归集');
                LogUserFund::create_data($this->user_id, $ttp_temp, 'TTP', '归集', '归集');
            }
            if($res){
                Db::commit();
                return return_data(1, '', Lang::get('操作成功'), '归集');
            }else{
                Db::rollback();
                return return_data(2, '', Lang::get('操作失败'));
            }
        }
    }
}