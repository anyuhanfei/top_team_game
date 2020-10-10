<?php
namespace app\admin\model;

use think\Model;
use think\facade\Env;

use app\admin\model\IdxUser;


class LogUserFund extends Model{
    protected $table = 'log_user_fund';
    protected $pk = "id";

    public function user(){
        return $this->hasOne('idx_user', 'user_id', 'user_id');
    }

    public static function fund_type_text(){
        return ['提现', '提现手续费'];
    }

    public static function create_data($user_id, $number, $coin_type, $fund_type, $content, $remark = '', $insert_time = ''){
        self::create([
            'user_id'=> $user_id,
            'user_identity'=> IdxUser::where('user_id', $user_id)->value('phone'),
            'number'=> $number,
            'coin_type'=> $coin_type,
            'fund_type'=> $fund_type,
            'content'=> $content,
            'remark'=> $remark,
            'insert_time'=> $insert_time == '' ? date("Y-m-d H:i:s", time()) : $insert_time
        ]);
    }
}