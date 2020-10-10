<?php
namespace app\index\validate;

use think\facade\Validate;
use think\facade\Session;
use think\facade\Cache;

use app\index\validate\Base;

use app\admin\model\IdxUser;
use app\admin\model\IdxUserFund;


class 转入 extends Base{
    protected $rule = [
        'type'=> 'require|checkCoinType',
        'number'=> 'require|checkNumber',
        'level_password'=> 'require|checkLevelPassword'
    ];

    protected $message = [
        'type.require'=> '非法操作',
        'number.require'=> '请输入转账金额',
        'level_password.require'=> '请输入二级密码'
    ];

    public function checkCoinType($value, $rule, $data){
        if($value != 'TTP' && $value != 'TTA'){
            return "非法操作";
        }
        return true;
    }

    public function checkNumber($value, $rule, $data){
        $user_fund = IdxUserFund::find(Session::get('user_id'));
        $coin_type = $data['type'];
        if($user_fund->$coin_type < $value){
            return $coin_type . '余额不足';
        }
        return true;
    }
}