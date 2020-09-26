<?php
namespace app\index\validate;

use think\Validate;
use think\facade\Session;

use app\admin\model\IdxUser;


class 登录 extends Validate{
    protected $rule = [
        'id'=> 'require|checkLogin',
        'password'=> 'require'
    ];

    protected $message = [
        'id.require'=> '请填写ID号',
        'password.require'=> '请填写密码'
    ];

    public function checkLogin($value, $rule, $data){
        $user = IdxUser::find($data['id']);
        if(!$user){
            return '账号或密码错误';
        }
        if(md5($data['password'] . $user->password_salt) !=  $user->password){
            return '账号或密码错误';
        }
        if($user->is_login == 0){
        	return '此账号已冻结';
        }
        return true;
    }
}