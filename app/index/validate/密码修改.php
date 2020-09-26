<?php
namespace app\index\validate;

use think\facade\Session;
use think\Validate;

use app\admin\model\IdxUser;


class 密码修改 extends Validate{
    protected $rule = [
        'type'=> 'require|checkType',
        'old_password'=> 'require|checkOldPassword',
        'password'=> 'require|confirm:password_confirm|checkPassword',
        'password_confirm'=> 'require',
    ];

    protected $message = [
        'type.require'=> '非法操作',
        'old_password.require'=> '请输入旧密码',
        'password.require'=> '请输入新密码',
        'password.confirm'=> '重复密码与输入密码不一致',
    ];

    public function checkType($value, $rule, $data){
        if($value != 'p' && $value != 'lp'){
            return '非法操作';
        }
        return true;
    }

    public function checkOldPassword($value, $rule, $data){
        $user = IdxUser::find(Session::get('user_id'));
        if($data['type'] == 'p'){
            if(md5($value . $user->password_salt) != $user->password){
                return '旧登录密码输入错误';
            }
        }else{
            if($value != $user->level_password){
                return '旧二级密码输入错误';
            }
        }
        return true;
    }

    public function checkPassword($value, $rule, $data){
        if($data['type'] == 'p'){
            $rule = "/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,20}$/";
        }else{
            $rule = "/^\d{6}$/";
        }
        if(preg_match($rule, $value)){
            return true;
        }else{
            return '输入密码不符合要求，6到20位数字和字母的组合';
        }
    }
}