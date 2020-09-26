<?php
namespace app\admin\model;

use think\Model;


class IdxUserMill extends Model{
    protected $table = "idx_user_mill";
    protected $pk = "mill_id";

    public function user(){
        return $this->hasOne('idx_user', 'user_id', 'user_id');
    }

    public function getStatusTextAttr($value, $data){
        $res = ['运行中', '已结束'];
        return $res[$data['status']];
    }
}