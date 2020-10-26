<?php
namespace app\index\controller;

use think\facade\View;
use think\facade\Request;
use think\facade\Session;
use think\facade\Lang;
use think\facade\Cache;
use think\facade\Db;

use app\index\controller\Index;

use app\admin\model\IdxTtPrice;
use app\admin\model\IdxDeal;
use app\admin\model\IdxUserFund;
use app\admin\model\LogUserFund;


class Deal extends Index{
    public function __construct(){
        parent::__construct();
        $tt_price = IdxTtPrice::where('id', '<=', date('Y-m-d', time()))->order('id desc')->find();
        $this->usdt2tt = $tt_price->price;
        View::assign('usdt2tt', $this->usdt2tt);
    }

    public function 交易大厅(){
        $deals = IdxDeal::where('status', 0)->select();
        View::assign('deals', $deals);
        View::assign('c2c_ttp_fee', Cache::get('settings')['c2c_ttp_fee']);
        View::assign('c2c_usdt_fee', Cache::get('settings')['c2c_usdt_fee']);
        return View::fetch();
    }

    public function 挂单(){
        $type = Request::instance()->param('type', '');
        $number = intval(Request::instance()->param('number', 0));
        $level_password = Request::instance()->param('level_password', '');
        $validate = new \app\index\validate\挂单;
        if(!$validate->check(['type'=> $type, 'number'=> $number, 'level_password'=> $level_password])){
            return return_data(2, '', Lang::get($validate->getError()));
        }
        Db::startTrans();
        $user_fund = IdxUserFund::find($this->user_id);
        if($type == 'sell'){
            $fee = $number * Cache::get('settings')['c2c_ttp_fee'] * 0.01;
            $user_fund->TTP -= $number + $fee;
            LogUserFund::create_data($this->user_id, '-' . $number, 'TTP', '挂单', '挂单-卖单');
            LogUserFund::create_data($this->user_id, '-' . $fee, 'TTP', '挂单', '挂单-卖单手续费');
        }else{
            $fee = $this->usdt2tt * $number * Cache::get('settings')['c2c_usdt_fee'] * 0.01;
            $user_fund->USDT -= $this->usdt2tt * $number + $fee;
            LogUserFund::create_data($this->user_id, '-' . $this->usdt2tt * $number, 'USDT', '挂单', '挂单-买单');
            LogUserFund::create_data($this->user_id, '-' . $fee, 'USDT', '挂单', '挂单-买单手续费');
        }
        $res_one = $user_fund->save();
        $res_two = IdxDeal::create([
            'deal_id'=> 'sn' . create_captcha(12),
            'sell_user_id'=> $type == 'sell' ? $this->user_id : 0,
            'buy_user_id'=> $type == 'buy' ? $this->user_id : 0,
            'TT'=> $number,
            'USDT'=> $this->usdt2tt * $number,
            'price'=> $this->usdt2tt,
            'TT_fee'=> $number * Cache::get('settings')['c2c_ttp_fee'] * 0.01,
            'USDT_fee'=> $this->usdt2tt * $number * Cache::get('settings')['c2c_usdt_fee'] * 0.01,
            'insert_time'=> date("Y-m-d H:i:s", time())
        ]);
        if($res_one && $res_two){
            Db::commit();
            return return_data(1, '', Lang::get('挂单成功'), $type == 'sell' ? '交易挂单-卖出' : '交易挂单-买入');
        }else{
            Db::rollback();
            return return_data(2, '', Lang::get('挂单失败'));
        }
    }

    public function 交易(){
        $deal_id = Request::instance()->param('deal_id', '');
        $deal = IdxDeal::find($deal_id);
        if(!$deal){
            return return_data(2, '', Lang::get('非法操作'));
        }
        if($this->user_id == ($deal->sell_user_id == 0 ? $deal->buy_user_id : $deal->sell_user_id)){
            return return_data(2, '', Lang::get('不能与自己交易'));
        }
        Db::startTrans();
        $user_fund = IdxUserFund::find($this->user_id);
        $to_user_fund = IdxUserFund::find($deal->sell_user_id == 0 ? $deal->buy_user_id : $deal->sell_user_id);
        if($deal->sell_user_id == 0){
            if($user_fund->TTP < $deal->TT + $deal->TT_fee){
                Db::rollback();
                return return_data(2, '', Lang::get('TTP余额不足'));
            }
            // 我是卖家
            $user_fund->TTP -= $deal->TT + $deal->TT_fee;
            $user_fund->USDT += $deal->USDT;
            $res_one = $user_fund->save();
            $to_user_fund->TTP += $deal->TT;
            $res_two = $to_user_fund->save();
            LogUserFund::create_data($this->user_id, '-' . $deal->TT, 'TTP', '交易', '交易匹配');
            LogUserFund::create_data($this->user_id, '-' . $deal->TT_fee, 'TTP', '交易', '交易匹配, 扣除手续费');
            LogUserFund::create_data($this->user_id, $deal->USDT, 'USDT', '交易', '交易匹配');
            LogUserFund::create_data($to_user_fund->user_id, $deal->TT, 'TTP', '交易', '交易匹配');
            $deal->sell_user_id = $this->user_id;
        }else{
            if($user_fund->USDT < $deal->USDT + $deal->USDT_fee){
                Db::rollback();
                return return_data(2, '', Lang::get('USDT余额不足'));
            }
            // 我是买家
            $user_fund->TTP += $deal->TT;
            $user_fund->USDT -= $deal->USDT + $deal->USDT_fee;
            $res_one = $user_fund->save();
            $to_user_fund->USDT += $deal->USDT;
            $res_two = $to_user_fund->save();
            LogUserFund::create_data($this->user_id, $deal->TT, 'TTP', '交易', '交易匹配');
            LogUserFund::create_data($this->user_id, '-' . $deal->USDT, 'USDT', '交易', '交易匹配');
            LogUserFund::create_data($this->user_id, '-' . $deal->USDT_fee, 'USDT', '交易', '交易匹配, 扣除手续费');
            LogUserFund::create_data($to_user_fund->user_id, $deal->USDT, 'USDT', '交易', '交易匹配');
            $deal->buy_user_id = $this->user_id;
        }
        $deal->status = 1;
        $deal->end_time = date("Y-m-d H:i:s", time());
        $res_three = $deal->save();
        if($res_one && $res_two && $res_three){
            Db::commit();
            return return_data(1, '', Lang::get('交易成功'), '交易匹配');
        }else{
            Db::rollback();
            return return_data(2, '', Lang::get('交易失败'));
        }
    }

    public function 撤销(){
        $deal_id = Request::instance()->param('deal_id', '');
        $deal = IdxDeal::find($deal_id);
        if(!$deal){
            return return_data(2, '', Lang::get('非法操作'));
        }
        if($deal->buy_user_id != $this->user_id && $deal->sell_user_id != $this->user_id){
            return return_data(2, '', Lang::get('非法操作'));
        }
        if($deal->status != 0){
            return return_data(2, '', Lang::get('非法操作'));
        }
        Db::startTrans();
        $deal->status = 2;
        $res_one = $deal->save();
        $user_fund = IdxUserFund::find($this->user_id);
        if($deal->buy_user_id == 0){
            $user_fund->TTP += $deal->TT + $deal->TT_fee;
            LogUserFund::create_data($this->user_id, $deal->TT, 'TTP', '交易撤销', '撤销');
        }else{
            $user_fund->USDT += $deal->USDT + $deal->USDT_fee;
            LogUserFund::create_data($this->user_id, $deal->USDT, 'USDT', '交易撤销', '撤销');
        }
        $res_two = $user_fund->save();
        if($res_two && $res_one){
            Db::commit();
            return return_data(1, '', Lang::get('撤销成功'), '交易单撤销');
        }else{
            Db::rollback();
            return return_data(2, '', Lang::get('撤销失败'));
        }
    }

    public function 交易单(){
        $deals = IdxDeal::where('status', 0)->select();
        View::assign('deals', $deals);
        return View::fetch();
    }

    public function 交易记录(){
        $deals = IdxDeal::where('buy_user_id|sell_user_id', $this->user_id)->select();
        View::assign('deals', $deals);
        return View::fetch();
    }
}