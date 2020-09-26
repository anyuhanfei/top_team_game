<?php
namespace app\index\validate;

use think\facade\Validate;
use think\facade\Session;

use app\index\validate\Base;

use app\admin\model\IdxUser;


class 忘记二级密码 extends Base{

    protected $rule = [
        '助记词'=> 'require|check助记词',
        'level_password'=> 'require|confirm:level_password_confirm|checkLevelPasswordRule',
        'level_password_confirm'=> 'require',
    ];

    protected $message = [
        '助记词.require'=> '请输入助记词',
        'level_password.require'=> '请输入新二级密码',
        'level_password_confirm.require'=> '请输入新二级密码确认密码',
        'level_password.confirm'=> '输入的新二级密码和确认密码不一致'
    ];

    function check助记词($value, $rule, $data){
        $user = IdxUser::find(Session::get('user_id'));
        if($user->助记词 != $value){
            return "助记词输入错误";
        }
        return true;
    }
}