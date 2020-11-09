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
        'level_password'=> 'require|checkLevelPassword',
        'lian_type'=> 'require|checkLianType'
    ];

    protected $message = [
        'type.require'=> '非法操作',
        'number.require'=> '请输入提现金额',
        'address.require'=> '请先填写钱包地址',
        'level_password.require'=> '请输入二级密码',
        'lian_type.require'=> '请选择类型'
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
        $user_fund = IdxUserFund::find(Session::get('user_id'));
        $coin_type = $data['type'];
        if($user_fund->$coin_type < ($value + Cache::get('settings')[$coin_type . '提现手续费'])){
            return $coin_type . '余额不足';
        }
        return true;
    }

    public function checkLianType($value, $rule, $data){
        if($data['type'] == 'USDT'){
            if($data['lian_type'] != 'ERC20' && $data['lian_type'] != 'TRC20'){
                return "请选择提现方式";
            }
        }
        return true;
    }
}