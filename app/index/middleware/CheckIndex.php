<?php
namespace app\index\middleware;

use think\facade\Session;

use app\admin\model\IdxUser;


class CheckIndex{
    public function handle($request, \Closure $next){
        //判断会员是否登录
        $user_id = Session::get('user_id');
        $token = Session::get('token');
        $user = IdxUser::find($user_id);
        if(!$user){
            return redirect('/index/登录');
        }
        if($token != $user->token){
            return redirect('/index/登录');
        }
        return $next($request);
    }
}