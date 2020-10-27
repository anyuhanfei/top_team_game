<?php
namespace app\admin\controller;

use think\facade\View;
use think\facade\Request;
use think\facade\Env;

use app\admin\controller\Admin;

use app\admin\model\GameAuto;
use app\admin\model\GameInning;
use app\admin\model\IdxDeal;
use app\admin\model\SysLevel;
use app\admin\model\IdxTtPrice;
use app\admin\model\IdxUserMill;
use app\admin\model\TokenConfig;
use app\admin\model\UserCharge;
use app\admin\model\UserAddr;
use app\admin\model\SysSetting;
use app\admin\model\IdxUser;
use app\admin\model\IdxUserFund;
use app\admin\model\LogUserFund;


class App extends Admin{
    public function 质押列表(){
        $user_id = Request::instance()->param('user_id', '');
        $obj = new GameAuto();
        $obj = ($user_id != '') ? $obj->where('user_id', $user_id) : $obj;
        $list = $obj->order('id desc')->paginate(['list_rows'=> $this->page_number, 'query'=>Request()->param()]);
        View::assign('list', $list);
        View::assign('user_id', $user_id);
        return View::fetch();
    }

    public function 游戏日志(){
        $user_id = Request::instance()->param('user_id', '');
        $obj = new GameInning();
        $field = "player_id_one|player_id_two|player_id_three|player_id_four|player_id_five|player_id_six|player_id_seven|player_id_eight|player_id_nine|player_id_ten";
        $obj = ($user_id != '') ? $obj->where($field, $user_id) : $obj;
        $list = $obj->order('insert_time desc')->paginate(['list_rows'=> $this->page_number, 'query'=>Request()->param()]);
        View::assign('list', $list);
        View::assign('user_id', $user_id);
        return View::fetch();
    }

    public function 交易列表(){
        $sell_user_id = Request::instance()->param('user_id', '');
        $buy_user_id = Request::instance()->param('user_id', '');
        $obj = new IdxDeal();
        $obj = ($sell_user_id != '') ? $obj->where('sell_user_id', $sell_user_id) : $obj;
        $obj = ($buy_user_id != '') ? $obj->where('buy_user_id', $buy_user_id) : $obj;
        $list = $obj->order('insert_time desc')->paginate(['list_rows'=> $this->page_number, 'query'=>Request()->param()]);
        View::assign('list', $list);
        View::assign('sell_user_id', $sell_user_id);
        View::assign('buy_user_id', $buy_user_id);
        return View::fetch();
    }

    public function 等级(){
        $list = SysLevel::select();
        View::assign('list', $list);
        return View::fetch();
    }

    public function 等级_update(){
        $level_id = Request::instance()->param('level_id', 0);
        $obj = SysLevel::find($level_id);
        if(!$obj){
            return return_data(2, '', '非法操作');
        }
        View::assign('detail', $obj);
        return View::fetch();
    }

    public function 等级_update_submit(){
        $level_id = Request::instance()->param('level_id', 0);
        $obj = SysLevel::find($level_id);
        if(!$obj){
            return return_data(2, '', '非法操作');
        }
        $obj->level_name = Request::instance()->param('level_name', '');
        $obj->level_name_us = Request::instance()->param('level_name_us', '');
        $obj->直推人数 = Request::instance()->param('直推人数', 0);
        $obj->团队人数 = Request::instance()->param('团队人数', 0);
        $obj->奖励 = Request::instance()->param('奖励', 0);
        $obj->增加局数 = Request::instance()->param('增加局数', 0);
        $obj->矿机加速 = Request::instance()->param('矿机加速', 0);
        $res = $obj->save();
        if($res){
            return return_data(1, '', '修改成功', '修改会员等级设置');
        }else{
            return return_data(2, '', '修改失败');
        }
    }

    public function tt价格(){
        $list = IdxTtPrice::order('id desc')->paginate(20);
        foreach($list as &$v){
            $v->is_update = strtotime($v->id) > strtotime(date("Y-m-d", time())) ? 1 : 0;
        }
        View::assign('list', $list);
        View::assign('next_date', date("Y-m-d", strtotime($list[0]->id) + (60 * 60 * 24)));
        return View::fetch();
    }

    public function tt价格_add_submit(){
        $price = Request::instance()->param('price', 0);
        $id = Request::instance()->param('id', 0);
        if($price <= 0){
            return return_data(2, '', '请输入正确的USDT金额');
        }
        if($id == 0){
            $next_date =date("Y-m-d", strtotime(IdxTtPrice::order('id desc')->value('id')) + (60 * 60 * 24));
            $obj = new IdxTtPrice();
            $res = $obj->create([
                'id'=> $next_date,
                'price'=> $price
            ]);
            $day = $next_date;
        }else{
            if($price <= 0){
                return return_data(2, '', '请输入正确的USDT金额');
            }
            $obj = IdxTtPrice::find($id);
            if(!$obj){
                return return_data(2, '', '非法操作');
            }
            $obj->price = $price;
            $res = $obj->save();
            $day = $obj->id;
        }
        if($res){
            return return_data(1, '', '设置成功', '设置或更新' . $day . '的TT价格');
        }else{
            return return_data(2, '', '设置失败');
        }
    }

    public function tt价格_update_submit(){
        $id = Request::insance()->param('id', 0);
        $price = Request::instance()->param('price', 0);
        if($price <= 0){
            return return_data(2, '', '请输入正确的USDT金额');
        }
        $obj = IdxTtPrice::find($id);
        if(!$obj){
            return return_data(2, '', '非法操作');
        }
        $obj->price = $price;
        $res = $obj->save();
        if($res){
            return return_data(1, '', '设置成功', '设置TT价格');
        }else{
            return return_data(2, '', '设置失败');
        }
    }

    public function 矿机管理(){
        $user_id = Request::instance()->param('user_id', '');
        $obj = new IdxUserMill();
        $obj = ($user_id != '') ? $obj->where('user_id', $user_id) : $obj;
        $list = $obj->order('mill_id desc')->paginate(['list_rows'=> $this->page_number, 'query'=>Request()->param()]);
        View::assign('list', $list);
        View::assign('user_id', $user_id);
        return View::fetch();
    }

    /**
     * 提币地址列表
     *
     * @return void
     */
    public function address(){
        $user_identity = Request::instance()->param('user_identity', '');
        $start_time = Request::instance()->param('start_time', '');
        $end_time = Request::instance()->param('end_time', '');
        $log = new UserAddr;
        $user = IdxUser::field('user_id, phone, address')->where('user_id', $user_identity)->find();
        if($user){
            $log = $log->where('user_id', $user->user_id);
        }
        $log = $this->where_time($log, $start_time, $end_time, 'create_time');
        $list = $log->where('is_deleted', 0)->order('create_time desc')->paginate(['list_rows'=> $this->page_number, 'query'=>Request()->param()]);
        $this->many_assign(['list'=> $list, 'user_identity'=> $user_identity, 'start_time'=> $start_time, 'end_time'=> $end_time]);
        return View::fetch();
    }

    public function user_fund_link(){
        $kuake_ip = Env::get('ANER_ADMIN.KUAKE_IP');
        $user_identity = Request::instance()->param('user_identity', '');
        $stock_code_search = Request::instance()->param('stock_code_search', '');
        $user = new IdxUser;
        $user = $user_identity == '' ? $user : $user->where('user_id', $user_identity);
        $list = $user->order('user_id desc')->paginate(['list_rows'=> 100, 'query'=>Request()->param()]);
        foreach($list as &$v){
            $user_addr = UserAddr::where('user_id', $v->user_id)->find();
            if(!$user_addr){
                $url = "http://". $kuake_ip ."/wallet/createAddr?userId=" . $v->user_id;
                $opts = array(
                    'http'=>array(
                    'method'=>"POST",
                    )
                );
                $context = stream_context_create($opts);
                $res = json_decode(file_get_contents($url, false, $context));
                if($res->code == 200){
                    $addr = $res->data;
                }else{
                    continue;
                }
            }else{
                $addr = $user_addr->addr;
            }
            $v->address = $addr;
            $url = "http://".$kuake_ip."/wallet/balance?from=".$addr;
            $res = json_decode(file_get_contents($url));
            if($res->code == 200){
                $v->coin = $res->data;
            }else{
                $v->coin = json_decode("");
            }
        }
        if($stock_code_search != ''){
            $array_count = count($list);
            for($i = 1; $i < $array_count; $i++){
                for($j = $i; $j > 0 && $list[$j]['coin']->$stock_code_search > $list[$j-1]['coin']->$stock_code_search; $j--){
                    $middle = $list[$j-1];
                    $list[$j-1] = $list[$j];
                    $list[$j] = $middle;
                }
            }
        }
        View::assign('token_config', TokenConfig::select());
        View::assign('list', $list);
        View::assign('user_identity', $user_identity);
        View::assign('stock_code_search', $stock_code_search);
        return View::fetch();
    }

    /**
     * 矿工费
     *
     * @return void
     */
    public function fee_submit(){
        //获取信息
        $user_ids = Request::instance()->param('user_ids', '');
        $stock_code = Request::instance()->param('stock_code', '');
        $validate = new \app\admin\validate\Block;
        if(!$validate->check(['user_ids'=> $user_ids, 'stock_code'=> $stock_code])){
            return return_data(2, '', $validate->getError(), 'json');
        }
        // 一些定义
        $kuake_ip = Env::get('ANER_ADMIN.KUAKE_IP');
        $fee_vpt = SysSetting::where('sign', 'fee_vpt')->value('value');
        $fee_number = SysSetting::where('sign', 'fee_number')->value('value');
        $fee_address = SysSetting::where('sign', 'fee_address')->value('value');
        $fee_address_key = SysSetting::where('sign', 'fee_address_key')->value('value');
        //循环会员
        $res_array = array();
        $user_id_array = explode(',', $user_ids);
        foreach($user_id_array as $k=> $v){
            if($v == ''){
                continue;
            }
            $user_id = $v;
            // 获取当前金额
            $user = UserAddr::where('user_id', $user_id)->find();
            $url = "http://".$kuake_ip."/wallet/balance?from=".$user->addr;
            $res = json_decode(file_get_contents($url));
            if($res->code == 200){
                foreach($res->data as $dk=> $dv){
                    if($dk == $stock_code){
                        if($dv < $fee_vpt){
                            $res_array[] = $user_id;
                            continue;
                        }
                        $url = "http://". $kuake_ip ."/wallet/send?code=ETH&balance=".$fee_number."&from=".$fee_address."&privateKey=".$fee_address_key."&to=".$user->addr.'&type=1';
                        $opts = array('http'=>array('method'=>"POST",));
                        $context = stream_context_create($opts);
                        $res = json_decode(file_get_contents($url, false, $context));
                        if($res->code != 200){
                            $res_array[] = $user_id;
                        }
                    }
                }
            }else{
                $res_array[] = $user_id;
                continue;
            }
        }
        $str = '分发矿工费完成,请手动刷新页面.';
        if($res_array){
            $str .= '有分发矿工费失败或未满足条件的会员:';
            foreach($res_array as $v){
                $str .= $v . ' ';
            }
        }else{
            $str .= '所选会员全部分发成功';
        }
        return return_data(1, '', $str, 'json');
    }

    /**
     * 归集
     *
     * @return void
     */
    public function cc_submit(){
        //获取信息
        $user_ids = Request::instance()->param('user_ids', '');
        $stock_code = Request::instance()->param('stock_code', '');
        $validate = new \app\admin\validate\Block;
        if(!$validate->check(['user_ids'=> $user_ids, 'stock_code'=> $stock_code])){
            return return_data(2, '', $validate->getError(), 'json');
        }
        // 一些定义
        $collection_vpt = SysSetting::where('sign', 'collection_vpt')->value('value');
        $golden_address = SysSetting::where('sign', 'golden_address')->value('value');
        $kuake_ip = Env::get('ANER_ADMIN.KUAKE_IP');
        //循环会员
        $res_array = array();
        $user_id_array = explode(',', $user_ids);
        foreach($user_id_array as $k=> $v){
            if($v == ''){
                continue;
            }
            $user_id = $v;
            $user = UserAddr::where('user_id', $user_id)->find();
            // 获取当前金额
            $url = "http://".$kuake_ip."/wallet/balance?from=".$user->addr;
            $res = json_decode(file_get_contents($url));
            if($res->code == 200){
                foreach($res->data as $dk=> $dv){
                    if($dk == $stock_code){
                        if($dv < $collection_vpt){
                            $res_array[] = $user_id;
                            continue;
                        }
                        $url = "http://". $kuake_ip ."/wallet/send?code=".$dk."&balance=".$dv."&from=".$user->addr."&privateKey=".$user->salt."&to=".$golden_address.'&type=2';
                        $opts = array(
                            'http'=>array(
                            'method'=>"POST",
                            )
                        );
                        $context = stream_context_create($opts);
                        $res = json_decode(file_get_contents($url, false, $context));
                        if($res->code != 200){
                            $res_array[] = $user_id;
                        }
                    }
                }
            }else{
                $res_array[] = $user_id;
                continue;
            }
        }
        $str = '归集完成,请手动刷新页面.';
        if($res_array){
            $str .= '有归集失败或未满足条件的会员:';
            foreach($res_array as $v){
                $str .= $v . ' ';
            }
        }else{
            $str .= '所选会员全部归集成功';
        }
        return return_data(1, '', $str, 'json');
    }

    /**
     * ETH归集
     *
     * @return void
     */
    public function qki_submit(){
        $user_ids = Request::instance()->param('user_ids', '');
        if($user_ids == ''){
            return return_data(2, '', '请选择归集的会员', 'json');
        }
        $res = array();
        $user_id_array = explode(',', $user_ids);

        $collection_vpt = SysSetting::where('sign', 'collection_vpt')->value('value');
        $golden_address = SysSetting::where('sign', 'golden_address')->value('value');
        $kuake_ip = Env::get('ANER_ADMIN.KUAKE_IP');
        foreach($user_id_array as $k=> $v){
            if($v == ''){
                continue;
            }
            $user_id = $v;
            $user = UserAddr::where('user_id', $user_id)->find();
            //查询这个会员的币
            $url = "http://".$kuake_ip."/wallet/balance?from=".$user->addr;
            $res = json_decode(file_get_contents($url));
            if($res->code == 200){
                foreach($res->data as $dk=> $dv){
                    if($dk == 'ETH' && $dv > $collection_vpt){ // 不是ETH, 并且超过阈值, 可归集
                        $url = "http://". $kuake_ip ."/wallet/send?code=".$dk."&balance=".$dv."&from=".$user->addr."&privateKey=".$user->salt."&to=".$golden_address.'&type=1';
                        $opts = array(
                            'http'=>array(
                            'method'=>"POST",
                            )
                        );
                        $context = stream_context_create($opts);
                        $res = json_decode(file_get_contents($url, false, $context));
                        if($res->code != 200){
                            $res[] = $user_id;
                        }
                    }
                }
            }else{
                $res[] = $user_id;
                continue;
            }
        }
        $str = '归集完成.';
        if($res){
            $str .= '有归集失败或未满足条件的会员:';
            foreach($res as $v){
                $str .= $v . ' ';
            }
        }else{
            $str = '所选会员全部归集成功';
        }
        return return_data(1, '', $str, 'json');
    }

    /**
     * 充值日志
     *
     * @return void
     */
    public function recharge_log(){
        $user_identity = Request::instance()->param('user_identity', '');
        $stock_code = Request::instance()->param('stock_code', '');
        $start_time = Request::instance()->param('start_time', '');
        $end_time = Request::instance()->param('end_time', '');
        $log = new UserCharge;
        $user = IdxUser::field('user_id, phone')->where('user_id', $user_identity)->find();
        if($user){
            $log = $log->where('user_id', $user->user_id);
        }
        $log = ($stock_code != '') ? $log->where('code', $stock_code) : $log;
        $log = $this->where_time($log, $start_time, $end_time, 'create_time');
        $list = $log->where('charge_type', 1)->where('is_deleted', 0)->order('create_time desc')->paginate(['list_rows'=> $this->page_number, 'query'=>Request()->param()]);
        $this->many_assign(['list'=> $list, 'user_identity'=> $user_identity, 'start_time'=> $start_time, 'end_time'=> $end_time, 'stock_code'=> $stock_code]);
        $stock_codes = TokenConfig::select();
        View::assign('stock_codes', $stock_codes);
        return View::fetch();
    }


    public function withdraw_log(){
        $withdraw5 = UserCharge::where('inspect_status', 5)->select();
        if($withdraw5){
            $kuake_ip = Env::get('ANER_ADMIN.KUAKE_IP');
            foreach($withdraw5 as $v){
                $url = "http://". $kuake_ip ."/wallet/transactionStatus?hash=" . $v->hash;
                $res = json_decode(file_get_contents($url));
                if($res->code == 200){
                    if($res->data == 1){ //失败, 转成待确认
                        $v->inspect_status = 0;
                        $v->save();
                    }elseif($res->data == 2){
                        $v->inspect_status = 1;
                        $v->save();
                    }
                }
            }
        }
        $user_identity = Request::instance()->param('user_identity', '');
        $stock_code = Request::instance()->param('stock_code', '');
        $start_time = Request::instance()->param('start_time', '');
        $end_time = Request::instance()->param('end_time', '');
        $log = new UserCharge;
        if($user_identity != ''){
            $user = IdxUser::field('user_id, phone')->where('phone', $user_identity)->find();
            if($user){
                $log = $log->where('user_id', $user->user_id);
            }
        }
        $log = ($stock_code != '') ? $log->where('code', $stock_code) : $log;
        $log = $this->where_time($log, $start_time, $end_time);
        $list = $log->where('charge_type', 2)->where('is_deleted', 0)->order('inspect_status asc')->order('id desc')->paginate(['list_rows'=> $this->page_number, 'query'=>Request()->param()]);
        $this->many_assign(['list'=> $list, 'user_identity'=> $user_identity, 'stock_code'=> $stock_code, 'start_time'=> $start_time, 'end_time'=> $end_time]);
        $stock_codes = TokenConfig::select();
        View::assign('stock_codes', $stock_codes);
        return View::fetch();
    }

    public function withdraw_submit($swift_no){
        $status = Request::instance()->param('status', -1);
        if($status != 1 && $status != 2){
            return return_data(2, '', '非法操作', 'json');
        }
        $withdraw = UserCharge::where('swift_no', $swift_no)->find();
        if(!$withdraw){
            return return_data(2, '', '非法操作', 'json');
        }
        if($status == 2){
            // 驳回
            $code = $withdraw->code;
            $user_fund = IdxUserFund::find($withdraw->user_id);
            $user_fund->$code += $withdraw->balance + $withdraw->poundage;
            $user_fund->save();
            LogUserFund::create_data($withdraw->user_id, $withdraw->balance + $withdraw->poundage, $code, '提现驳回', '提现审核被驳回');
            $withdraw->inspect_time = date("Y-m-d H:i:s", time());
            $withdraw->inspect_status = 2;
            $withdraw->save();
            return return_data(1, '', '操作成功', 'json');
        }else{
            $withdraw = UserCharge::where('swift_no', $swift_no)->find();
            $withdraw->inspect_time = date("Y-m-d H:i:s", time());
            $withdraw->inspect_status = 1;
            $withdraw->hash = '';
            $withdraw->save();
            return return_data(1, '', '操作成功, 请手动拨款', 'json');

            // // 通过
            // $withdraw_address_key = SysSetting::where('sign', 'withdraw_address_key')->value('value');
            // $withdraw_address = SysSetting::where('sign', 'withdraw_address')->value('value');
            // $kuake_ip = Env::get('ANER_ADMIN.KUAKE_IP');
            // $url = "http://". $kuake_ip ."/wallet/send?code=".$withdraw->code."&balance=".$withdraw->balance."&from=".$withdraw_address."&privateKey=".$withdraw_address_key."&to=".$withdraw->to_addr.'&type=1';
            // $opts = array(
            //     'http'=>array(
            //     'method'=>"POST",
            //     )
            // );
            // $context = stream_context_create($opts);
            // $res = json_decode(file_get_contents($url, false, $context));
            // if($res->code == 200){
            //     $withdraw = UserCharge::where('swift_no', $swift_no)->find();
            //     $withdraw->inspect_time = date("Y-m-d H:i:s", time());
            //     $withdraw->inspect_status = 5;
            //     $withdraw->hash = $res->data;
            //     $withdraw->save();
            //     return return_data(1, '', '操作成功, 再次进入提现列表时进行最终状态的获取', 'json');
            // }else{
            //     // $withdraw = UserCharge::where('swift_no', $swift_no)->find();
            //     // $withdraw->inspect_time = date("Y-m-d H:i:s", time());
            //     // $withdraw->inspect_status = 2;
            //     // $withdraw->save();
            //     // $user_fund = IdxUserFund::find($withdraw->user_id);
            //     // $user_fund->money += $withdraw->number + $withdraw->fee;
            //     // $user_fund->save();
            //     return return_data(2, '', '操作失败', 'json');
            // }
        }
    }
}