<?php
namespace app\admin\validate;

use think\Validate;

use app\admin\model\TokenConfig;


class Block extends Validate{
    protected $rule = [
        'user_ids'=> 'require',
        'stock_code'=> 'require|checkSC'
    ];

    protected $message = [
        'user_ids.require'=> '请选择会员',
        'stock_code.require'=> '请选择币种',
    ];

    public function checkSC($value, $rule, $data){
        $data = TokenConfig::where('stock_code', $value)->find();
        if(!$data){
            return "非法操作";
        }
        return true;
    }
}