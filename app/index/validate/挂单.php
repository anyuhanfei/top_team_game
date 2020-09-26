<?php
namespace app\index\validate;

use think\Validate;
use think\facade\Session;

use app\index\validate\Base;

use app\admin\model\IdxUserFund;


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
        if($data['type'] == 'sell'){
            $user_fund = IdxUserFund::find(Session::get('user_id'));
            if($user_fund->TTP < $value){
                return "TTP余额不足";
            }
        }
        return true;
    }
}