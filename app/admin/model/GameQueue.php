<?php
namespace app\admin\model;

use think\Model;


class GameQueue extends Model{
    protected $table = "game_queue";
    protected $pk = "id";

    public function user(){
        return $this->hasOne('idx_user', 'user_id', 'user_id');
    }
}