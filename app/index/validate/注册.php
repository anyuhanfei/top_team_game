<?php
namespace app\index\validate;

use think\Validate;
use think\facade\Session;

use app\admin\model\IdxUser;
use app\admin\model\SysSetting;

use app\index\validate\Base;


class 注册 extends Base{
    protected $rule = [
        'invite_code'=> 'checkInviteCode',
        'password'=> 'require|confirm:password_confirm|checkPasswordRule',
        'password_confirm'=> 'require',
        'level_password'=> 'require|confirm:level_password_confirm|checkLevelPasswordRule',
        'level_password_confirm'=> 'require'
    ];

    protected $message = [
        'password.require'=> '请填写登录密码',
        'password.confirm'=> '填写确认登录密码与登录密码不一致',
        'password_confirm.require'=> '请填写确认登录密码',
        'level_password.require'=> '请填写二级密码',
        'level_password.confirm'=> '填写确认二级密码与二级密码不一致',
        'level_password_confirm.require'=> '请填写确认二级密码'
    ];

    public function checkInviteCode($value, $rule, $data){
        if(SysSetting::where('sign', '注册推荐码')->value('value') == 'on'){
            if($value == ''){
                return "请输入推荐码";
            }
            $user = IdxUser::where('invite_code', $value)->find();
            if(!$user){
                return '无效邀请码';
            }
        }
        return true;
    }
}