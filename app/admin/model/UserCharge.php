<?php
namespace app\admin\model;

use think\Model;


class UserCharge extends Model{
    protected $table = "user_charge";
    protected $pk = "id";

    public function getChargeTypeTextAttr($value, $data){
        $res = ['', '入金', '出金'];
        return $res[$data['charge_type']];
    }

    public function getPayTypeTextAttr($value, $data){
        $res = ['', '在线', '人工充值', '后台充值'];
        return $res[$data['pay_type']];
    }

    public function getWithdrawStatusTextAttr($value, $data){
        $res = ['', '区块处理中', '成功', '支付失败'];
        return $res[$data['withdraw_status']];
    }

    public function getInspectStatusTextAttr($value, $data){
        $res = ['待审核', '已到账', '拒绝', '', '已撤销', '等待最终确认'];
        return $res[$data['inspect_status']];
    }

    public function user(){
        return $this->hasOne('idx_user', 'user_id', 'user_id')->field('user_id, nickname, phone');
    }
}