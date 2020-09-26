<?php
namespace app\index\validate;

use think\Validate;
use think\facade\Session;

use app\admin\model\IdxUser;


class Base extends Validate{
    public function checkLevelPassword($value, $rule, $data){
        $user = IdxUser::find(Session::get('user_id'));
        if($user->level_password != $value){
            return "二级密码输入错误";
        }
        return true;
    }

    public function checkLevelPasswordRule($value, $rule, $data){
        $rule = "/^\d{6}$/";
        if(preg_match($rule, $value)){
            return true;
        }else{
            return '输入二级密码不符合要求，二级密码必须是6位纯数字';
        }
    }

    public function checkPasswordRule($value, $rule, $data){
        $rule = "/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,20}$/";
        if(preg_match($rule, $value)){
            return true;
        }else{
            return '输入密码不符合要求，6到20位数字和字母的组合';
        }
    }
}