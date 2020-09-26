<?php
namespace app\index\validate;

use think\Validate;
use think\facade\Cache;
use think\facade\Session;

use app\index\validate\Base;

use app\admin\model\IdxUserFund;


class 提现 extends Base{
    protected $rule = [
        'type'=> 'require|checkType',
        'number'=> 'require|checkNumber',
        'address'=> 'require',
        'level_password'=> 'require|checkLevelPassword'
    ];

    protected $message = [
        'type.require'=> '非法操作',
        'number.require'=> '请输入提现金额',
        'address.require'=> '请先填写钱包地址',
        'level_password.require'=> '请输入二级密码'
    ];

    public function checkType($value, $rule, $data){
        if($value != 'USDT' && $value != 'TTP'){
            return "非法操作";
        }
        return true;
    }

    public function checkNumber($value, $rule, $data){
        if($value <= 0){
            return "提现金额必须大于0";
        }
        $fee = $value * Cache::get('settings')['提现手续费'] * 0.01;
        $user_fund = IdxUserFund::find(Session::get('user_id'));
        $coin_type = $data['type'];
        if($user_fund->$coin_type < ($value + $fee)){
            return $coin_type . '余额不足';
        }
        return true;
    }
}