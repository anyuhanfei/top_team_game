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
        return ['提现', '提现手续费', '交易', '兑换', '参与游戏', '后台充值', '归集', '挂单', '游戏中奖', '游戏中奖支付矿工费', '矿机生产', '自动质押', '质押USDT结算', '购买门票', '转入门票', '转账', '交易撤销', '入金'];
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