<?php
namespace app\index\validate;

use think\facade\Validate;
use think\facade\Session;
use think\facade\Cache;

use app\index\validate\Base;

use app\admin\model\IdxUser;
use app\admin\model\IdxUserFund;


class 转账 extends Base{
    protected $rule = [
        'type'=> 'require',
        'coin_type'=> 'require|checkCoinType',
        'to_user_id'=> 'require|checkToUserId',
        'number'=> 'require|checkNumber',
        'level_password'=> 'require|checkLevelPassword'
    ];

    protected $message = [
        'type.require'=> '非法操作',
        'coin_type.require'=> '请选择转账币种',
        'to_user_id.require'=> '请选择账号',
        'number.require'=> '请输入转账金额',
        'level_password.require'=> '请输入二级密码'
    ];

    public function checkCoinType($value, $rule, $data){
        if($value != 'TTP' && $value != 'USDT' && $value != '门票'){
            return "非法操作";
        }
        return true;
    }

    public function checkToUserId($value, $rule, $data){
        if($value == Session::get('user_id')){
            return "不能给自己转账";
        }
        $from_user = IdxUser::find(Session::get('user_id'));
        $to_user = IdxUser::find($value);
        if($data['type'] == 'z'){
            if($from_user->pan_user_id == 0){
                if($to_user->pan_user_id == $from_user->user_id){
                    return true;
                }
            }else{
                if($to_user->user_id == $from_user->pan_user_id){
                    return true;
                }
            }
            return "非法操作";
        }else{
            if($to_user->pan_user_id != 0){
                return "只能与主账号转账";
            }
        }
        return true;
    }

    public function checkNumber($value, $rule, $data){
        $user_fund = IdxUserFund::find(Session::get('user_id'));
        $coin_type = $data['coin_type'];
        $fee = $data['type'] == 'z' ? 0 : $value * Cache::get('settings')['转账fee'] * 0.01;
        if($user_fund->$coin_type < $value + $fee){
            return $coin_type . '余额不足';
        }
        return true;
    }
}