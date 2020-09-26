<?php
namespace app\index\controller;

use think\facade\View;
use think\facade\Request;
use think\facade\Session;
use think\facade\Lang;

use app\index\controller\Base;

use app\admin\model\IdxUser;


class Login extends Base{
    public function 登录(){
        return View::fetch();
    }

    public function 登录提交(){
        $id = Request::instance()->param('id', '');
        $password = Request::instance()->param('password', '');
        $validate = new \app\index\validate\登录;
        if(!$validate->check(['id'=> $id, 'password'=> $password])){
            return return_data(2, '', Lang::get($validate->getError()));
        }
        $user = IdxUser::find($id);
        Session::set('user_id', $user->user_id);
        return return_data(1, '', Lang::get('登录成功'));
    }

    public function 忘记密码(){
        return View::fetch();
    }

    public function 忘记密码提交(){
        $助记词 = Request::instance()->param('助记词', '');
        $password = Request::instance()->param('password', '');
        $password_confirm = Request::instance()->param('password_confirm', '');
        $validate = new \app\index\validate\忘记密码;
        if(!$validate->check(['助记词'=> $助记词, 'password'=> $password, 'password_confirm'=> $password_confirm])){
            return return_data(2, '', Lang::get($validate->getError()));
        }
        $user = IdxUser::where('助记词', $助记词)->find();
        $user->password = md5($password . $user->password_salt);
        $res = $user->save();
        return $res ? return_data(1, '', Lang::get('修改成功'), '修改密码') : return_data(2, '', Lang::get('修改失败'));
    }

    public function 注册(){
        View::assign('z', read_word());
        View::assign('code', Request::instance()->param('code'));
        return View::fetch();
    }

    public function 注册提交(){
        $助记词 = Request::instance()->param('助记词', '');
        $invite_code = Request::instance()->param('invite_code', '');
        $password = Request::instance()->param('password', '');
        $password_confirm = Request::instance()->param('password_confirm', '');
        $level_password = Request::instance()->param('level_password', '');
        $level_password_confirm = Request::instance()->param('level_password_confirm', '');
        $validate = new \app\index\validate\注册();
        if(!$validate->check(['invite_code'=> $invite_code, 'password'=> $password, 'password_confirm'=> $password_confirm, 'level_password'=> $level_password, 'level_password_confirm'=> $level_password_confirm])){
            return return_data(2, '', Lang::get($validate->getError()));
        }
        $top_id = IdxUser::where('invite_code', $invite_code)->value('user_id');
        $res = IdxUser::create_data($助记词, $password, $top_id, '会员' . create_captcha(8, 'lowercase+uppercase+figure'), $level_password);
        if($res){
            return return_data(1, $res, Lang::get('注册成功, ID号为') . $res);
        }else{
            return return_data(2, '', Lang::get('注册失败'));
        }
    }

    public function 退出登录(){
        Session::delete('user_id');
        return redirect('/index/登录');
    }
}