<?php
namespace app\admin\model;

use think\Model;
use think\facade\Cookie;

use app\admin\controller\Base;

use app\admin\model\IdxUserData;
use app\admin\model\IdxUserFund;
use app\admin\model\IdxUserCount;
use app\admin\model\SysLevel;

class IdxUser extends Model{
    protected $table = "idx_user";
    protected $pk = 'user_id';

    /**
     * 关联自己的上级
     *
     * @return void
     */
    public function top(){
        $Base = new Base();
        return $this->hasOne('idx_user', 'user_id', 'top_id')->field($Base->user_identity);
    }

    public function panuser(){
        $Base = new Base();
        return $this->hasOne('idx_user', 'user_id', 'pan_user_id')->field($Base->user_identity);
    }


    /**
     * 关联会员资金表
     *
     * @return void
     */
    public function userfund(){
        $Base = new Base();
        $field = '';
        foreach($Base->user_fund_type as $k => $v){
            $field .= $v . ',';
        }
        $field = substr($field, 0, strlen($field) - 1);
        return $this->hasOne('idx_user_fund', 'user_id', 'user_id')->field($field);
    }

    /**
     * 关联会员统计表
     *
     * @return void
     */
    public function usercount(){
        return $this->hasOne('idx_user_count', 'user_id', 'user_id');
    }

    /**
     * 关联会员资料表
     *
     * @return void
     */
    public function userdata(){
        return $this->hasOne('idx_user_data', 'user_id', 'user_id');
    }

    /**
     * 关联会员资料--身份证
     *
     * @return void
     */
    public function userDataIdCard(){
        $field = 'id_card_username, id_card_code, id_card_front_img, id_card_verso_img, id_card_hand_img';
        return $this->hasOne('idx_user_data', 'user_id', 'user_id')->field($field);
    }

    /**
     * 关联会员资料--银行卡
     *
     * @return void
     */
    public function userDataBank(){
        $field = 'bank_username, bank_phone, bank_code, bank_name';
        return $this->hasOne('idx_user_data', 'user_id', 'user_id')->field($field);
    }

    /**
     * 关联会员资料--地址
     *
     * @return void
     */
    public function userDataSite(){
        $field = 'site_username, site_phone, site_province, site_city, site_district, site_address';
        return $this->hasOne('idx_user_data', 'user_id', 'user_id')->field($field);
    }

    /**
     * 关联会员资料--微信
     *
     * @return void
     */
    public function userDataWx(){
        $field = 'wx_account, wx_nickname';
        return $this->hasOne('idx_user_data', 'user_id', 'user_id')->field($field);
    }

    /**
     * 关联会员资料--支付宝
     *
     * @return void
     */
    public function dataAlipay(){
        $field = 'alipay_account, alipay_username';
        return $this->hasOne('idx_user_data', 'user_id', 'user_id')->field($field);
    }

    /**
     * 关联会员资料--qq
     *
     * @return void
     */
    public function userDataQq(){
        $field = 'qq';
        return $this->hasOne('idx_user_data', 'user_id', 'user_id')->field($field);
    }

    /**
     * 是否可以登录
     *
     * @param [type] $value
     * @param [type] $data
     * @return void
     */
    public function getIsLoginTextAttr($value, $data){
        $res = ['冻结', '正常'];
        return $res[$data['is_login']];
    }

    public function getLanguageTextAttr($value, $data){
        $res = ['zh-cn'=> '中文', 'en-us'=> 'English'];
        return $res[$data['language']];
    }

    public function getLevelNameAttr($value, $data){
        if($data['level'] == 0){
            return Cookie::get('think_lang') == 'zh-cn' ? "普通用户" : "normal user";
        }
        if($data['level'] >= 5){
            $levels = SysLevel::where('level_id', '<=', $data['level'])->select();
            $level_name = '';
            foreach($levels as $level){
                $level_name .= (Cookie::get('think_lang') == 'zh-cn' ? $level->level_name : $level->level_name_us) . ' ';
            }
        }else{
            $level_name = Cookie::get('think_lang') == 'zh-cn' ? SysLevel::where('level_id', $data['level'])->value('level_name') : SysLevel::where('level_id', $data['level'])->value('level_name_us');
        }
        return $level_name;
    }

    public function getVipTextAttr($value, $data){
        $res = ['', '创世节点'];
        return $res[$data['vip']];
    }

    /**
     * 注册会员
     *
     * @param string $助记词
     * @param string $password 密码
     * @param integer $top_id 上级id，选填
     * @param string $nickname 昵称，选填
     * @param string $level_password 二级密码，选填
     * @return void
     */
    public static function create_data($助记词, $password, $top_id = 0, $nickname = '', $level_password = '', $pan_user_id = 0){
        $password_salt = create_captcha(6, 'lowercase+uppercase+figure');
        $user_id = create_captcha(9);
        while($user_id <= 100000000 && self::find($user_id)){
            $user_id = create_captcha(9);
        }
        $Base = new Base();
        $res = self::create([
            'user_id'=> $user_id,
            '助记词' => $助记词,
            'password'=> md5($password . $password_salt),
            'password_salt'=> $password_salt,
            'level_password'=> $level_password,
            'nickname'=> $nickname,
            'top_id'=> $top_id,
            'pan_user_id'=> $pan_user_id,
            'register_time'=> date('Y-m-d', time())
        ]);
        if($res){
            IdxUserData::create(['user_id'=> $res->user_id]);
            IdxUserFund::create(['user_id'=> $res->user_id]);
            IdxUserCount::create(['user_id'=> $res->user_id]);
            IdxUserCount::add_team_number($top_id);
            return $user_id;
        }else{
            return 0;
        }
    }
}