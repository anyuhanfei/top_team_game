<?php
namespace app\index\validate;

use think\Validate;
use think\facade\Cache;
use think\facade\Session;

use app\admin\model\IdxUser;
use app\admin\model\IdxUserFund;

use app\index\validate\Base;


class 兑换 extends Base{
    protected $rule = [
        'number'=> 'require|checkNumber',
        'level_password'=> 'require|checkLevelPassword',
    ];

    protected $message = [
        'number.require'=> '请填写正确的数量',
        'level_password.require'=> '请填写二级密码',
    ];

    public function checkNumber($value, $rule, $data){
        if(intval($value) <= 0){
            return "请填写正确的数量";
        }
        $user_fund = IdxUserFund::find(Session::get('user_id'));
        if(Cache::get('settings')['能量石兑换比例'] * $value > $user_fund->能量石){
            return "能量石不足";
        }
        return true;
    }
}