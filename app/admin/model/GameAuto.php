<?php
namespace app\admin\model;

use think\Model;


class GameAuto extends Model{
    protected $table = "game_auto";
    protected $pk = "id";

    public function getStatusTextAttr($value, $data){
        $res = ['质押中', '已结算'];
        return $res[$data['status']];
    }

    public function user(){
        return $this->hasOne('idx_user', 'user_id', 'user_id');
    }
}