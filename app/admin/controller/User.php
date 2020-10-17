<?php
namespace app\admin\controller;

use think\facade\View;
use think\facade\Session;
use think\facade\Request;

use app\admin\controller\Admin;

use app\admin\model\IdxUser;
use app\admin\model\IdxUserCount;
use app\admin\model\IdxUserFund;
use app\admin\model\IdxUserData;
use app\admin\model\SysLevel;
use app\admin\model\LogUserFund;


class User extends Admin{
    public function __construct(){
        parent::__construct();
        View::assign('user_fund_type', $this->user_fund_type);
        View::assign('user_delete_onoff', $this->user_delete_onoff);
        View::assign('user_identity', $this->user_identity);
        View::assign('user_identity_text', $this->user_identity_text);
    }

    /**
     * 会员管理--列表
     *
     * @return void
     */
    public function user(){
        $user_id = Request::instance()->param('user_id', '');
        $top_user_id = Request::instance()->param('top_user_id', '');
        $nickname = Request::instance()->param('nickname', '');
        $level_id = Request::instance()->param('level_id', '');
        $vip = Request::instance()->param('vip', '');
        $user = new IdxUser;
        $user = ($user_id != '') ? $user->where('user_id', $user_id) : $user;
        $user = ($top_user_id != '') ? $user->where('top_id', $top_user_id) : $user;
        $user = ($level_id != '') ? $user->where('level', $level_id) : $user;
        $user = ($vip !== '') ? $user->where('vip', $vip) : $user;
        $user = ($nickname != '') ? $user->where('nickname', 'like', '%' . $nickname . '%') : $user;
        $list = $user->order('register_time desc')->paginate(['list_rows'=> 50, 'query'=>Request()->param()]);
        $this->many_assign(['list'=> $list, 'user_id'=> $user_id, 'nickname'=> $nickname, 'top_user_id'=> $top_user_id, 'level_id'=> $level_id, 'vip'=> $vip]);
        $level = SysLevel::select();
        View::assign('level', $level);
        return View::fetch();
    }

    /**
     * 会员信息添加表单
     *
     * @return void
     */
    public function user_add(){
        View::assign('z', read_word());
        return View::fetch();
    }

    /**
     * 会员信息添加提交
     *
     * @return void
     */
    public function user_add_submit(){
        $nickname = Request::instance()->param('nickname', '');
        $助记词 = Request::instance()->param('助记词', '');
        $password = Request::instance()->param('password', '');
        $level_password = Request::instance()->param('level_password', '');
        $top_user_identity = Request::instance()->param('top_user_identity', '');
        $validate = new \app\admin\validate\User;
        $validate_data = ['nickname'=> $nickname, '助记词'=> $助记词, 'password'=> $password, 'level_password'=> $level_password, 'top_user_identity'=> $top_user_identity];
        if(!$validate->scene('add')->check($validate_data)){
            return return_data(2, '', $validate->getError());
        }
        $top_user_id = IdxUser::field('user_id')->where($this->user_identity, $top_user_identity)->value('user_id');
        $top_user_id = $top_user_id == null ? 0 : $top_user_id;
        $res = IdxUser::create_data($助记词, $password, $top_user_id, $nickname, $level_password);
        if($res != 0){
            return return_data(1, '', '添加成功', '会员信息添加：'.$res);
        }else{
            return return_data(2, '', '添加失败，请联系管理员');
        }
    }

    /**
     * 会员团队列表
     *
     * @param [type] $id
     * @return void
     */
    public function user_team($id){
        $user = IdxUser::find($id);
        $has_data = "true";
        if(!$user){
            $has_data = "false";
            $user = new IdxUser;
            $user_identity = $this->user_identity;
            $user->$user_identity = '';
        }
        $user_count = IdxUserCount::find($id);
        $team = array();
        $this->team_select($team, $id);
        View::assign('user', $user);
        View::assign('user_count', $user_count);
        View::assign('has_data', $has_data);
        View::assign('team', $team);
        return View::fetch();
    }

    public function user_pan($id){
        $user = IdxUser::find($id);
        $has_data = "true";
        if(!$user){
            $has_data = "false";
            $user = new IdxUser;
            $user_identity = $this->user_identity;
            $user->$user_identity = '';
        }
        $team = IdxUser::where('pan_user_id', $id)->select();
        View::assign('user', $user);
        View::assign('has_data', $has_data);
        View::assign('team', $team);
        return View::fetch();
    }


    private function team_select(&$team, $user_id, $level=1){
        $user = IdxUser::field('user_id, top_id, nickname, ' . $this->user_identity)->where('top_id', $user_id)->select();
        if($user){
            foreach($user as $v){
                $v->level = $level;
                $team[] = $v;
                $this->team_select($team, $v->user_id, $level + 1);
            }
        }
    }

    /**
     * 会员详情信息
     *
     * @param [type] $id
     * @return void
     */
    public function user_detail($id){
        $user = IdxUser::find($id);
        $has_data = "true";
        if(!$user){
            $has_data = "false";
        }
        View::assign('detail', $user);
        View::assign('has_data', $has_data);
        return View::fetch();
    }

    /**
     * 会员信息编辑表单
     *
     * @param [type] $id
     * @return void
     */
    public function user_update($id){
        $user = IdxUser::find($id);
        $has_data = "true";
        if(!$user){
            $has_data = "false";
        }
        View::assign('detail', $user);
        View::assign('has_data', $has_data);
        View::assign('level', SysLevel::select());
        return View::fetch();
    }

    /**
     * 会员信息编辑提交
     *
     * @param [type] $type
     * @param [type] $id
     * @return void
     */
    public function user_update_submit($type, $id){
        $control_user_identity = $this->user_identity;
        $validate = new \app\admin\validate\User;
        if(!$validate->scene('get')->check(['type'=> $type, 'id'=> $id])){
            return return_data(2, '', $validate->getError());
        }
        $validate = new \app\admin\validate\User;
        $user = IdxUser::find($id);
        if($type == 'detail'){
            $nickname = Request::instance()->param('nickname', '');
            if(!$validate->scene('detail')->check(['id'=> $id, 'nickname'=> $nickname])){
                return return_data(2, '', $validate->getError());
            }
            $user->nickname = $nickname;
        }elseif($type == 'password'){
            $password = Request::instance()->param('password', '');
            if(!$validate->scene('password')->check(['id'=> $id, 'password'=> $password])){
                return return_data(2, '', $validate->getError());
            }
            $user->password = md5($password . $user->password_salt);
        }elseif($type == 'level_password'){
            $level_password = Request::instance()->param('level_password', '');
            if(!$validate->scene('level_password')->check(['id'=> $id, 'level_password'=> $level_password])){
                return return_data(2, '', $validate->getError());
            }
            $user->level_password = $level_password;
        }elseif($type == 'level'){
            $level = Request::instance()->param('level');
            $user->level = $level;
            if($level == 0){
                $user->is_admin_up_level = 0;
            }else{
                $user->is_admin_up_level = 1;
            }
        }
        $res = $user->save();
        if($res){
            $update_type = array('detail'=> '信息编辑', 'password'=> '密码修改', 'level_password'=> '二级密码修改', 'level'=> '等级设置');
            return return_data(1, '', '修改成功', '会员信息--'.$update_type[$type].'：'.$user->$control_user_identity);
        }else{
            return return_data(2, '', '修改失败或未修改信息');
        }
    }

    /**
     * 会员登录权限设置
     *
     * @param [type] $id
     * @return void
     */
    public function user_freeze($id){
        $user = IdxUser::find($id);
        if(!$user){
            return return_data(2, '', '非法操作');
        }
        $user->is_login = $user->is_login == 1 ? 0 : 1;
        $res = $user->save();
        if($res){
            $control_user_identity = $this->user_identity;
            $operation_type = $user->is_login == 1 ? '解冻' : '冻结';
            return return_data(1, $user->is_login, '修改成功', '会员登录权限-'.$operation_type.'：'.$user->$control_user_identity);
        }else{
            return return_data(2, '', '修改失败或未修改信息');
        }
    }

    public function user_c($id){
        $user = IdxUser::find($id);
        if(!$user){
            return return_data(2, '', '非法操作');
        }
        $user->vip = $user->vip == 1 ? 0 : 1;
        $res = $user->save();
        if($res){
            $control_user_identity = $this->user_identity;
            $operation_type = $user->vip == 1 ? '解除创世节点' : '设为创世节点';
            return return_data(1, $user->vip, '修改成功', '会员权限-'.$operation_type.'：'.$user->$control_user_identity);
        }else{
            return return_data(2, '', '修改失败或未修改信息');
        }
    }

    /**
     * 会员信息删除提交
     *
     * @param [type] $id
     * @return void
     */
    public function user_delete($id){
        if($this->user_delete_onoff == false){
            return return_data(2, '', '无此权限');
        }
        $user = IdxUser::find($id);
        if(!$user){
            return return_data(2, '', '非法操作');
        }
        $res = IdxUser::where('user_id', $id)->delete();
        if($res){
            IdxUserCount::where('user_id', $id)->delete();
            IdxUserData::where('user_id', $id)->delete();
            IdxUserFund::where('user_id', $id)->delete();
            $control_user_identity = $this->user_identity;
            return return_data(1, '', '删除成功', '会员信息删除：'.$user->$control_user_identity);
        }else{
            return return_data(3, '', '删除失败,请联系管理员');
        }
    }

    /**
     * 会员充值表单
     *
     * @param [type] $id
     * @return void
     */
    public function user_recharge($id){
        $user = IdxUser::find($id);
        $has_data = "true";
        if(!$user){
            $has_data = "false";
            $user = new IdxUser;
            $user_identity = $this->user_identity;
            $user->$user_identity = '';
            $user->user_id = 0;
        }
        View::assign('detail', $user);
        View::assign('has_data', $has_data);
        return View::fetch();
    }

    /**
     * 会员充值提交
     *
     * @param [type] $id
     * @return void
     */
    public function user_recharge_submit($id){
        $fund_type = Request::instance()->param('fund_type', '');
        $radio_number = Request::instance()->param('radio_number', 0);
        $input_number = Request::instance()->param('input_number', 0);
        $validate = new \app\admin\validate\UserRecharge;
        if(!$validate->check(['fund_type'=> $fund_type, 'radio_number'=> $radio_number, 'input_number'=> $input_number])){
            return return_data(2, '', $validate->getError());
        }
        $control_user_identity = $this->user_identity;
        $user = IdxUser::field('user_id, ' . $control_user_identity)->where('user_id', $id)->find();
        $user_fund = IdxUserFund::find($id);
        $add_number = $input_number == 0 ? $radio_number : $input_number;
        $user_fund->$fund_type += $add_number;
        $res = $user_fund->save();
        if($res){
            $fund_type_array = array_flip($this->user_fund_type);
            LogUserFund::create_data($id, '-' . $add_number, $fund_type, '后台充值', '后台充值');
            return return_data(1, '', '充值成功', '会员充值：给'.$user->$control_user_identity.'充值'.$add_number.$fund_type_array[$fund_type]);
        }else{
            return return_data(3, '', '充值失败,请联系管理员');
        }
    }
}