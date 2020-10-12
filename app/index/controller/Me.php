<?php
namespace app\index\controller;

use think\facade\View;
use think\facade\Request;
use think\facade\Session;
use think\facade\Cookie;
use think\facade\Lang;

use app\index\controller\Index;

use app\admin\model\CmsArticle;
use app\admin\model\IdxUserData;
use app\admin\model\IdxUser;
use app\admin\model\SysAd;

class Me extends Index{
    public function __construct(){
        parent::__construct();
    }

    public function index(){
        //等级
        return View::fetch();
    }

    public function 关于我们(){
        if(Cookie::get('think_lang') == 'zh-cn'){
            View::assign('content', CmsArticle::where('title', '关于我们(中文)')->value('content'));
        }else{
            View::assign('content', CmsArticle::where('title', '关于我们(英文)')->value('content'));
        }
        return View::fetch();
    }

    public function ttp销毁(){
        if(Cookie::get('think_lang') == 'zh-cn'){
            View::assign('content', CmsArticle::where('title', 'TTP销毁(中文)')->value('content'));
        }else{
            View::assign('content', CmsArticle::where('title', 'TTP销毁(英文)')->value('content'));
        }
        return View::fetch();
    }

    public function 百问百答(){
        if(Cookie::get('think_lang') == 'zh-cn'){
            View::assign('content', CmsArticle::where('title', '百问百答(中文)')->value('content'));
        }else{
            View::assign('content', CmsArticle::where('title', '百问百答(英文)')->value('content'));
        }
        return View::fetch();
    }

    public function 钱包地址(){
        return View::fetch();
    }

    public function 钱包地址提交(){
        $usdt = Request::instance()->param('USDT', '');
        $ttp = Request::instance()->param('TTP', '');
        $tta = Request::instance()->param('TTA', '');
        $this->user->USDT = $usdt;
        $this->user->TTP = $ttp;
        $this->user->TTA = $tta;
        $res = $this->user->save();
        return $res ? return_data(1, '', Lang::get('修改成功'), '修改钱包地址') : return_data(2, '', Lang::get('修改失败'));
    }

    public function 安全(){
        return View::fetch();
    }

    public function 实名认证(){
        return View::fetch();
    }

    public function 实名认证提交(){
        $username = Request::instance()->param('username', '');
        $id_card = Request::instance()->param('id_card', '');
        if($username == '' || $id_card == ''){
            return return_data(2, '', Lang::get('请填写空缺信息'));
        }
        $user_data = IdxUserData::find($this->user_id);
        $user_data->id_card_username = $username;
        $user_data->id_card_code = $id_card;
        $res = $user_data->save();
        return $res ? return_data(1, '', Lang::get('认证成功'), '实名认证') : return_data(2, '', Lang::get('认证失败'));
    }

    public function 助记词(){
        return View::fetch();
    }

    public function 忘记二级密码(){
        return View::fetch();
    }

    public function 忘记二级密码提交(){
        $助记词 = Request::instance()->param('助记词', '');
        $level_password = Request::instance()->param('level_password', '');
        $level_password_confirm = Request::instance()->param('level_password_confirm', '');
        $validate = new \app\index\validate\忘记二级密码;
        if(!$validate->check(['助记词'=> $助记词, 'level_password'=> $level_password, 'level_password_confirm'=> $level_password_confirm])){
            return return_data(2, '', Lang::get($validate->getError()));
        }
        $this->user->level_password = $level_password;
        $res = $this->user->save();
        return $res ? return_data(1, '', Lang::get('修改成功'), '修改二级密码') : return_data(2, '', Lang::get('修改失败'));
    }

    public function 修改密码(){
        $type = Request::instance()->param('type', 'p');
        View::assign('type', $type);
        return View::fetch();
    }

    public function 修改密码提交(){
        $type = Request::instance()->param('type', '');
        $old_password = Request::instance()->param('old_password', '');
        $password = Request::instance()->param('password', '');
        $password_confirm = Request::instance()->param('password_confirm', '');
        $validate = new \app\index\validate\密码修改;
        if(!$validate->check(['type'=> $type, 'old_password'=> $old_password, 'password'=> $password, 'password_confirm'=> $password_confirm])){
            return return_data(2, '', Lang::get($validate->getError()));
        }
        if($type == 'p'){
            $this->user->password = md5($password . $this->user->password_salt);
        }else{
            $this->user->level_password = $password;
        }
        $res = $this->user->save();
        if($res){
            $content = $type == 'p' ? '登录' : '二级';
            return return_data(1, '', Lang::get($content . '密码修改成功'), '修改' . $content . '密码');
        }else{
            return return_data(2, '', Lang::get('修改失败'));
        }
    }

    public function 设置(){
        return View::fetch();
    }

    public function 修改昵称(){
        return View::fetch();
    }

    public function 修改昵称提交(){
        $nickname = Request::instance()->param('nickname', '');
        $this->user->nickname = $nickname;
        $res = $this->user->save();
        return $res ? return_data(1, '', Lang::get('修改成功'), '修改昵称') : return_data(2, '', Lang::get('修改失败'));
    }

    public function 语言切换(){
        return View::fetch();
    }

    public function 语言切换提交(){
        $language = Request::instance()->param('language', '');
        $this->user->language = $language;
        $res = $this->user->save();
        Cookie::set('think_lang', $language);
        return $res ? return_data(1, '', Lang::get('修改成功'), '修改展示语言') : return_data(2, '', Lang::get('修改失败'));
    }

    public function 团队(){
        $team_number = 0;
        $team_number_level_one = 0;
        $team_number_level_two = 0;
        $team = [];
        foreach(IdxUser::field('user_id, register_time, top_id')->where('top_id', $this->user_id)->order('register_time desc')->select() as $v){
            $v->level = 1;
            $team[] = $v;
            $team_number += 1;
            $team_number_level_one += 1;
            foreach(IdxUser::field('user_id, register_time, top_id')->where('top_id', $v->user_id)->order('register_time desc')->select() as $vv){
                $vv->level = 2;
                $team[] = $vv;
                $team_number += 1;
                $team_number_level_two += 1;
            }
        }
        View::assign('team', $team);
        View::assign('team_number', $team_number);
        View::assign('team_number_level_one', $team_number_level_one);
        View::assign('team_number_level_two', $team_number_level_two);
        return View::fetch();
    }

    public function 邀请(){
        if($this->user->invite_code == ''){
            $this->user->invite_code = create_captcha(10, 'lowercase+uppercase+figure');
            $this->user->save();
        }
        if($this->user->invite_qrcode == ''){
            $this->user->invite_qrcode = png_erwei('http://' . $_SERVER['HTTP_HOST'] . '/index/注册?code=' . $this->user->invite_code, $this->user->user_id);
            $this->user->save();
        }
        View::assign('bgi', Cookie::get('think_lang') == 'zh-cn' ? '2' : '3');
        return View::fetch();
    }

    public function 语言(){
        $this->user->language = Cookie::get('think_lang');
        $this->user->save();
    }

    public function 账户管理(){
        View::assign('accounts', $this->get_账号s());
        return View::fetch();
    }

    public function 切换账号(){
        $user_id = Request::instance()->param('user_id', -1);
        if($user_id == $this->user->pan_user_id){ //主账号
            Session::set('user_id', $user_id);
            return return_data(1, '', Lang::get('切换成功'));
        }
        $user = IdxUser::find($user_id);
        if(!$user){
            return return_data(2, '', Lang::get('非法操作'));
        }
        if($user->is_login == 0){
            return return_data(2, '', Lang::get('此账号已冻结'));
        }
        if($user->pan_user_id == $this->user_id || $user->pan_user_id == $this->user->pan_user_id){
            Session::set('user_id', $user_id);
            $user->password = $this->user->password;
            $user->level_password = $this->user->level_password;
            $user->password_salt = $this->user->password_salt;
            $user->save();
            return return_data(1, '', Lang::get('切换成功'));
        }
        return return_data(2, '', Lang::get('非法操作'));
    }

    public function 添加子账号(){
        $nickname = Request::instance()->param('nickname', '');
        if($nickname == ''){
            return return_data(2, '', '昵称不能为空');
        }
        if($this->user->pan_user_id != 0){
            return return_data(2, '', Lang::get('请在主账号下进行此操作'));
        }
        IdxUser::create_data(read_word(), '', $this->user_id, $nickname, '', $this->user_id);
        return return_data(1, '', Lang::get('添加成功'), '添加子账号');
    }

    public function 客服(){
        View::assign('wx', SysAd::where('sign', '微信号')->value('value'));
        View::assign('wx_img', SysAd::where('sign', '微信二维码')->value('image'));
        return View::fetch();
    }
}