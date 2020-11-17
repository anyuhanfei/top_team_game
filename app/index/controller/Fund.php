<?php
namespace app\index\controller;

use think\facade\Cache;
use think\facade\Request;

use app\index\controller\Base;

use app\admin\model\LogUserFund;
use app\admin\model\IdxUser;
use app\admin\model\IdxUserFund;
use app\admin\model\IdxUserCount;
use app\admin\model\SysData;
use app\admin\model\GameAuto;
use app\admin\model\GameInning;
use app\admin\model\GameQueue;
use app\admin\model\IdxTtPrice;
use app\admin\model\IdxUserMill;
use app\admin\model\SysLevel;
use app\admin\model\SysSetting;


class Fund extends Base{
    private static $cache_settings = '';
    private static $cache_level = '';


    public function __construct(){
        parent::__construct();
        self::$cache_settings = Cache::get('settings');
        self::$cache_level = Cache::get('level');
    }

    /**
     * 一天执行一次
     */
    public static function 发放奖励(){
        $users_data = self::升级();
        //计算今日所有奖金
        $data = SysData::find(1);
        $data->昨日推广分红 = 0;
        $data->昨日团队分红 = 0;
        $data->昨日创世节点分红 = 0;
        $money = GameAuto::where('质押日期', date("Y-m-d", time()))->sum('可玩局数') * 0.38;
        //创世节点分发奖金
        $创世节点_money = self::$cache_settings['创世节点分红PCT'] * 0.01 * $money;
        $data->昨日创世节点分红 = $创世节点_money;
        //计算昨日团队分红
        $data->昨日团队分红 = $money * 0.4;
        $vips = IdxUser::where('vip', 1)->field('user_id, vip')->select();
        if(count($vips) > 0){
            $节点可得金额 = round($创世节点_money / count($vips), 2);
            foreach($vips as $vip){
                $vip_fund = IdxUserFund::find($vip->user_id);
                $vip_fund->USDT += $节点可得金额;
                $vip_fund->save();
                LogUserFund::create_data($vip->user_id, $节点可得金额, 'USDT', '创世节点奖励', '创世节点奖励');
            }
        }
        //直推链接奖励 and 间接链接奖励
        $z_num = 0;
        $j_num = 0;
        foreach($users_data as $user_data){
            if($user_data['zjh'] == 1 && $user_data['zh'] >= 1){
                $z_num += 1;
            }
            if($user_data['zjh'] == 1 && $user_data['zh'] >= 1 && $user_data['jh'] >= 1){
                $j_num += 1;
            }
        }
        $直推链接总奖励 = self::$cache_settings['直推玩家奖励PCT'] * 0.01 * $money;
        $直推链接可得金额 = round($直推链接总奖励 / ($z_num == 0 ? 1 : $z_num), 2);
        $间推链接总奖励 = self::$cache_settings['间推玩家奖励PCT'] * 0.01 * $money;
        $间推链接可得金额 = round($间推链接总奖励 / ($j_num == 0 ? 1 : $j_num), 2);
        $data->昨日推广分红 = $直推链接总奖励 + $间推链接总奖励;
        foreach($users_data as $user_data){
            if($user_data['zjh'] == 1 && $user_data['zh'] >= 1){
                $user_fund = IdxUserFund::find($user_data['user_id']);
                $user_fund->USDT += $直推链接可得金额;
                $user_fund->save();
                LogUserFund::create_data($user_data['user_id'], $直推链接可得金额, 'USDT', '直推链接奖励', '直推链接奖励');
            }
            if($user_data['zjh'] == 1 && $user_data['zh'] >= 1 && $user_data['jh'] >= 1){
                $user_fund = IdxUserFund::find($user_data['user_id']);
                $user_fund->USDT += $间推链接可得金额;
                $user_fund->save();
                LogUserFund::create_data($user_data['user_id'], $间推链接可得金额, 'USDT', '间推链接奖励', '间推链接奖励');
            }
        }
        //等级奖励 and 全网分红
        $level_users = IdxUser::where('level', '>=', 1)->select();
        foreach($level_users as &$v){
            $v->user_fund = IdxUserFund::find($v->user_id);
        }
        $level = 1;
        while($level <= 6){
            $会员总数 = 0;
            foreach($level_users as $v){
                if($v->level >= $level){
                    $会员总数 += 1;
                }
            }
            // $会员总数 = count($level_users);
            if($会员总数 == 0){
                break;
            }
            $总奖励 = self::$cache_level[$level - 1]['奖励_终'] * 0.01 * $money;
            $单人奖金 = round($总奖励 / $会员总数, 2);
            # 给所有人发奖
            foreach($level_users as $v){
                if($v->level >= $level){
                    $v->user_fund->USDT += $单人奖金;
                    $v->user_fund->save();
                    LogUserFund::create_data($v->user_id, $单人奖金, 'USDT', self::$cache_level[$level - 1]['level_name'] . '勋章奖励', self::$cache_level[$level - 1]['level_name'] . '勋章奖励');
                }
            }
            # 全网分红
            if($level == 5 || $level == 6){
                $全网分红奖励 = $level == 5 ? (self::$cache_settings['钻石玩家奖励'] * 0.01 * $money) : (self::$cache_settings['王者玩家奖励'] * 0.01 * $money);
                $全网分红单人奖金 = round($全网分红奖励 / $会员总数, 2);
                foreach($level_users as $v){
                    if($v->level >= $level){
                        $v->user_fund->USDT += $全网分红单人奖金;
                        $v->user_fund->save();
                        LogUserFund::create_data($v->user_id, $全网分红单人奖金, 'USDT', self::$cache_level[$level - 1]['level_name'] . '全网分红勋章奖励', self::$cache_level[$level - 1]['level_name'] . '全网分红勋章奖励');
                    }
                }
            }
            # 等级➕1, 将最低一等级的会员去除
            $level++;
            // $temp = [];
            // foreach($level_users as $v){
            //     if($v->level >= $level){
            //         $temp[] = $v;
            //     }
            // }
            // $level_users = $temp;
        }
        //车房基金
        $车房基金 = self::$cache_settings['车房基金'] * 0.01 * $money;
        //平台运营费
        $平台运营费 = self::$cache_settings['平台运营费'] * 0.01 * $money;
        $data->推广玩家收益 = 0;
        $data->车房基金 += $车房基金;
        $data->平台运营费 += $平台运营费;
        $data->save();
    }

    /**
     * 游戏关闭后计算
     */
    public static function 游戏(){
        // 所以设置在这里查
        $s_time = time();
        $autos = GameAuto::where('status', 0)->select();
        foreach($autos as $auto){
            // if($s_time + 600 < time()){
            //     break;
            // }
            if(strtotime($auto->insert_time) + (60 * $auto->可玩局数) <= time()){
                $money = $auto->质押USDT;
                $money += $auto->中奖局数 * (self::$cache_settings['中奖打赏金额'] - self::$cache_settings['中奖支付矿工费']);
                $money -= 20 * $auto->未中奖局数;
                self::矿机生成($auto->user_id, $auto->未中奖局数);
                $user_fund = IdxUserFund::find($auto->user_id);
                $user_fund->USDT += $money;
                $user_fund->save();
                LogUserFund::create_data($auto->user_id, $money, 'USDT', '质押USDT结算', '质押USDT结算');
                $auto->status = 1;
                $auto->end_time = date("Y-m-d H:i:s", time());
                $auto->save();
            }
        }
    }

    public static function 升级(){
        //清空等级
        IdxUser::where('is_admin_up_level', 0)->update(['level'=> 0]);
        //获取全部会员
        $users = IdxUser::select();
        $users_data = [];
        foreach($users as $user){
            $users_data[$user->user_id] = ['user_id'=> $user->user_id, 'zjh'=> 0, 'zh'=> 0, 'jh'=> 0, 'th'=> 0, 'level'=> 0];
        }
        //获取合格人数
        foreach($users as &$user){
            if($user->usercount->today_date == date("Y-m-d", time())){
                $users_data[$user->user_id]['zjh'] = $user->usercount->今日我合格;
                $users_data[$user->user_id]['zh'] = $user->usercount->今日直推合格;
                $users_data[$user->user_id]['jh'] = $user->usercount->今日间推合格;
                $users_data[$user->user_id]['th'] = $user->usercount->今日团队合格;
                $down_users = IdxUser::field('user_id, top_id')->where('top_id', $user->user_id)->select();
                $users_data[$user->user_id]['xth'] = 0;
                if($down_users){
                    $max_team_number = 0;
                    foreach($down_users as $down_user){
                        $down_user_今日团队合格 = IdxUserCount::where('user_id', $down_user->user_id)->value('今日团队合格');
                        if($down_user_今日团队合格 > $max_team_number){
                            $max_team_number = $down_user_今日团队合格;
                        }
                        $users_data[$user->user_id]['xth'] += $down_user_今日团队合格;
                    }
                    $users_data[$user->user_id]['xth'] -= $max_team_number;
                }
                // if($user->usercount->今日局数 >= Cache::get('settings')['任务局数']){ //我合格
                //     $users_data[$user->user_id]['zjh'] = 1;
                //     if($user->top_id != 0){
                //         $top_user = IdxUser::find($user->top_id);
                //         $users_data[$user->top_id]['zh'] += 1;
                //         $users_data[$user->top_id]['th'] += 1;
                //         if($top_user->top_id != 0){
                //             $users_data[$top_user->top_id]['th'] += 1;
                //         }
                //     }
                // }
            }
        }
        //判断条件
        self::$cache_level = Cache::get('level');
        foreach($users_data as $user_data){
            foreach(self::$cache_level as $level){
                if($user_data['zjh'] >= 1 && $user_data['zh'] >= $level['直推人数'] && $user_data['th'] >= $level['团队人数'] && $user_data['xth'] >= $level['小区团队人数']){
                    $user_data['level'] = $level['level_id'];
                }
            }
            if($user_data['level'] != 0){
                $user = IdxUser::find($user_data['user_id']);
                if($user->is_admin_up_level == 0){
                    $user->level = $user_data['level'];
                    $user->save();
                }
            }
        }
        return $users_data;
    }

    //矿机  一天一次
    public function 矿机生产(){
        $users_count = IdxUserCount::where('今日我合格', 1)->select();
        foreach($users_count as $user_count){
            $矿机s = IdxUserMill::where('status', 0)->where('insert_date', '<>' ,date("Y-m-d", time()))->where('user_id', $user_count->user_id)->select();
            if(!$矿机s){
                continue;
            }
            $user_fund = IdxUserFund::find($user_count->user_id);
            foreach($矿机s as $矿机){
                $price = $矿机->price + ($矿机->price * SysLevel::where('level_id', $user_count->user->level)->value('矿机加速') * 0.01);
                $price = $矿机->all_price < $price ? $矿机->all_price : $price;
                $user_fund->TTP += $price;
                if($price > 0){
                    LogUserFund::create_data($矿机->user_id, $price, 'TTP', '矿机生产', '矿机生产');
                }
                //更新矿机
                $矿机->insert_date = date("Y-m-d", time());
                $矿机->all_price -= $price;
                if($矿机->all_price <= 0){
                    $矿机->status = 1;
                    $矿机->end_time = date("Y-m-d", strtotime($矿机->insert_date));
                }
                $矿机->当前周期 += 1;
                if($矿机->当前周期 >= SysSetting::where('sign', '收益周期')->value('value')){
                    $矿机->status = 1;
                    $矿机->end_time = date("Y-m-d", strtotime($矿机->insert_date));
                }
                if(strtotime(date('Y-m-d', strtotime($矿机->insert_time))) + (SysSetting::where('sign', '收益周期')->value('value') * 24 * 60 * 60) < strtotime(date("Y-m-d", time()))){
                    $矿机->status = 1;
                    $矿机->end_time = date("Y-m-d", strtotime($矿机->insert_date));
                }
                $矿机->save();
            }
            $user_fund->save();
        }
    }


    public static function 是否合格($user_id){
        $user_count = IdxUserCount::find($user_id);
        if($user_count->今日局数 >= SysSetting::where('sign', '任务局数')->value('value')){
            if($user_count->今日我合格 == 1){
                return;
            }
            $user_count->今日我合格 = 1;
            $user_count->save();
            $top_id = IdxUser::where('user_id', $user_id)->value('top_id');
            $i = 0;
            while($top_id != 0){
                $top_count = IdxUserCount::find($top_id);
                if($i == 0){
                    $top_count->今日直推合格 += 1;
                }
                if($i == 1){
                    $top_count->今日间推合格 += 1;
                }
                $top_count->今日团队合格 += 1;
                $top_count->save();
                $i++;
                $top_id = IdxUser::where('user_id', $top_id)->value('top_id');
            }
        }
    }

    public static function 矿机生成($user_id, $number){
        $all_tt = SysSetting::where('sign', '矿机价值')->value('value') / Cache::get('today_tt_price');
        $add_tt = $all_tt / SysSetting::where('sign', '收益周期')->value('value');
        $mill = new IdxUserMill;
        $list = [];
        while(count($list) < $number){
            $list[] = [
                'user_id'=> $user_id,
                'all_price'=> $all_tt,
                'price'=> $add_tt,
                'insert_date'=> date("Y-m-d", time()),
                'insert_time'=> date("Y-m-d H:i:s", time())
            ];
        }
        $mill->saveAll($list);
    }
}