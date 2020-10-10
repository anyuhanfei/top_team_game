<?php
namespace app\index\validate;

use think\Validate;
use think\facade\Cache;
use think\facade\Session;

use app\index\validate\Base;

use app\admin\model\IdxUserFund;
use app\admin\model\IdxTtPrice;


class 挂单 extends Base{
    protected $rule = [
        'type'=> 'require|checkType',
        'number'=> 'require|checkNumber',
        'level_password'=> 'require|checkLevelPassword'
    ];

    protected $message = [
        'type.require'=> "非法操作",
        'number.require'=> '请输入TT金额',
        'level_password.require'=> '请输入二级密码'
    ];

    public function checkType($value, $rule, $data){
        if($value != 'buy' && $value != 'sell'){
            return "非法操作";
        }
        return true;
    }

    public function checkNumber($value, $rule, $data){
        if(intval($value) != $value){
            return "请填写整数";
        }
        if($value <= 0){
            return "请填写大于0的整数";
        }
        $user_fund = IdxUserFund::find(Session::get('user_id'));
        if($data['type'] == 'sell'){
            $fee = $data['number'] * Cache::get('settings')['c2c_ttp_fee'] * 0.01;
            if($user_fund->TTP < $value + $fee){
                return "TTP余额不足";
            }
        }else{
            $fee = IdxTtPrice::where('id', '<=', date('Y-m-d', time()))->order('id desc')->value('price') * $data['number'] * Cache::get('settings')['c2c_usdt_fee'] * 0.01;
            if($user_fund->USDT < $value + $fee){
                return "USDT余额不足";
            }
        }
        return true;
    }
}