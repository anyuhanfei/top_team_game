<?php
namespace app\admin\model;

use think\Model;


class IdxDeal extends Model{
    protected $table = "idx_deal";
    protected $pk = "deal_id";

    public function selluser(){
        return $this->hasOne('idx_user', 'sell_user_id', 'user_id');
    }

    public function buyuser(){
        return $this->hasOne('idx_user', 'buy_user_id', 'user_id');
    }

    public function getStatusTextAttr($value, $data){
        $res = ['挂单中', '交易完成', '撤销'];
        return $res[$data['status']];
    }
}