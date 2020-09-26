<?php
namespace app\index\validate;

use think\facade\Validate;
use think\facade\Session;

use app\index\validate\Base;

use app\admin\model\IdxUser;
use app\admin\model\IdxUserFund;


class 转账 extends Base{
    protected $rule = [
        'coin_type'=> 'require|checkCoinType',
        'to_user_id'=> 'require|checkToUserId',
        'number'=> 'require|checkNumber',
        'level_password'=> 'require|checkLevelPassword'
    ];

    protected $message = [
        'coin_type.require'=> '请选择转账币种',
        'to_user_id.require'=> '请选择账号',
        'number.require'=> '请输入转账金额',
        'level_password.require'=> '请输入二级密码'
    ];

    public function checkCoinType($value, $rule, $data){
        if($value != 'TTP' && $value != 'USDT'){
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
        if($from_user->pan_user_id == 0){
            if($to_user->pan_user_id == $from_user->user_id){
                return true;
            }
        }else{
            if($to_user->pan_user_id == $from_user->pan_user_id){
                return true;
            }
        }
        return "非法操作";
    }

    public function checkNumber($value, $rule, $data){
        $user_fund = IdxUserFund::find(Session::get('user_id'));
        $coin_type = $data['coin_type'];
        if($user_fund->$coin_type < $value){
            return $coin_type . '余额不足';
        }
        return true;
    }
}