<?php
namespace app\admin\model;

use think\Model;

class UserAddr extends Model{
    protected $table = "user_addr";
    protected $pk = "id";

    public function user(){
        return $this->hasOne('idx_user', 'user_id', 'user_id')->field('user_id, phone, address');
    }

    public function getTypeTextAttr($value, $data){
        $res = ['', '夸克', 'ETH', 'BTC'];
        return $res[$data['type']];
    }
}