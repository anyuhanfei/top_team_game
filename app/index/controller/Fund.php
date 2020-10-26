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
        self::结算();
        //计算今日所有奖金
        $data = SysData::find(1);
        $data->昨日推广分红 = 0;
        $data->昨日团队分红 = 0;
        $data->昨日创世节点分红 = 0;
        $money = $data->推广玩家收益;
        //创世节点分发奖金
        $创世节点_money = self::$cache_settings['创世节点分红PCT'] * 0.01 * $money;
        $data->昨日创世节点分红 = $创世节点_money;

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
            $data->昨日团队分红 += $总奖励;
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
                $data->昨日团队分红 += $全网分红奖励;
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
     * 每分钟执行一次
     */
    public static function 自动参与(){
        sleep(random_int(1, 3));
        $质押s = GameAuto::where('质押日期', date("Y-m-d", time()))->select();
        foreach($质押s as $质押){
            if($质押->可玩局数 > $质押->已玩局数){
                // if(random_int(1, 10) >= 5){
                //     continue;
                // }
                // if(GameQueue::where('user_id', $质押->user_id)->where('is_pop', 0)->find()){
                //     continue;
                // }
                GameQueue::create(['user_id'=> $质押->user_id, 'is_auto'=> $质押->id]);
                $质押->已玩局数 += 1;
                $质押->save();
            }
        }
    }

    /**
     * 每分钟执行一次
     */
    public static function 游戏(){
        $sleep_time = 3;
        self::$cache_settings['游戏房间玩家数量'] = self::$cache_settings['游戏房间玩家数量'] > 10 ? 10 : self::$cache_settings['游戏房间玩家数量'];
        // if(self::$cache_settings['每日游戏开始时间'] > date("H:i", time()) || self::$cache_settings['每日游戏结束时间'] < date("H:i", time())){
        //     return;
        // }
        while(true){
            $queue = GameQueue::where('is_pop', 0)->order('id asc')->limit(self::$cache_settings['游戏房间玩家数量'])->select();
            if(count($queue) != self::$cache_settings['游戏房间玩家数量']){
                break;
            }
            $create_array = ['insert_time'=> date("Y-m-d H:i:s", time()), 'id'=> create_captcha(9)];
            $number_array = ['one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten'];
            $i = 0;
            foreach($queue as $v){
                $v->is_pop = 1;
                $v->save();
                $create_array['player_id_' . $number_array[$i]] = $v->user_id;
                $create_array['is_auto_' . $number_array[$i]] = $v->is_auto;
                $i++;
            }
            GameInning::create($create_array);
        }
        sleep($sleep_time);
        $inning = GameInning::where('end_time', NULL)->select();
        $number_array = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten'];
        $s_time = time();
        foreach($inning as $v){
            if($s_time + 40 < time()){
                break;
            }
            for($i = 1; $i <= self::$cache_settings['中奖人数']; $i++){
                //中奖
                $nowin = random_int(1, self::$cache_settings['游戏房间玩家数量']);
                $is_win_field = 'is_win_' . $number_array[$nowin];
                while($v->$is_win_field != 0){  //说明这个人已经有结果了, 换一个没结果的
                    $nowin = random_int(1, self::$cache_settings['游戏房间玩家数量']);
                    $is_win_field = 'is_win_' . $number_array[$nowin];
                }
                $player_id_field = 'player_id_' . $number_array[$nowin];
                $is_auto_field = 'is_auto_' . $number_array[$nowin];
                //中奖奖励
                $v->$is_win_field = 1;
                if($v->$is_auto_field != 0){ //是自动, 仅添加记录
                    $auto = GameAuto::find($v->$is_auto_field);
                    $auto->中奖局数 += 1;
                    $auto->save();
                }else{ //直接发钱
                    $user_fund = IdxUserFund::find($v->$player_id_field);
                    $user_fund->USDT += self::$cache_settings['下注金额'] + self::$cache_settings['中奖打赏金额'] - self::$cache_settings['中奖支付矿工费'];
                    $user_fund->save();
                    LogUserFund::create_data($v->$player_id_field, self::$cache_settings['下注金额'] + self::$cache_settings['中奖打赏金额'], 'USDT', '游戏中奖', '游戏中奖');
                    LogUserFund::create_data($v->$player_id_field, '-' . self::$cache_settings['中奖支付矿工费'], 'USDT', '游戏中奖支付矿工费', '游戏中奖支付矿工费');
                }
            }
            for($i = 1; $i <= self::$cache_settings['游戏房间玩家数量']; $i++){
                $player_id_field = 'player_id_' . $number_array[$i];
                $is_auto_field = 'is_auto_' . $number_array[$i];
                $is_win_field = 'is_win_' . $number_array[$i];
                if($v->$is_win_field == 0){// 未中奖
                    $v->$is_win_field = 2;
                    if($v->$is_auto_field != 0){ //是自动, 仅添加记录
                       $auto = GameAuto::find($v->$is_auto_field);
                       $auto->未中奖局数 += 1;
                       $auto->save();
                    }else{ //直接发矿机
                        self::矿机生成($v->$player_id_field);
                    }
                }
            }
            $v->end_time = date("Y-m-d H:i:s", time());
            $v->save();
            //统计
            $sys_data = SysData::find(1);
            $sys_data->推广玩家收益 += self::$cache_settings['中奖人数'] * self::$cache_settings['中奖支付矿工费'] + self::$cache_settings['中奖打赏金额'];
            $sys_data->累计推广玩家收益 += self::$cache_settings['中奖人数'] * self::$cache_settings['中奖支付矿工费'] + self::$cache_settings['中奖打赏金额'];
            $sys_data->save();
        }

        $autos = GameAuto::where('status', 0)->select();
        foreach($autos as $auto){
            if(strtotime($auto->insert_time) + 5000 < time()){
                $auto->中奖局数 += 1;
                $auto->save();
            }
            if($auto->可玩局数 == $auto->已玩局数 && $auto->已玩局数 <= ($auto->中奖局数 + $auto->未中奖局数)){
                $money = $auto->质押USDT;
                $money += $auto->中奖局数 * (self::$cache_settings['中奖打赏金额'] - self::$cache_settings['中奖支付矿工费']);
                for($i = 0; $i < $auto->未中奖局数; $i++){
                    $money -= 20;
                    self::矿机生成($auto->user_id);
                }
                $user_fund = IdxUserFund::find($auto->user_id);
                $user_fund->USDT += $money;
                $user_fund->save();
                LogUserFund::create_data($auto->user_id, $money, 'USDT', '质押USDT结算', '质押USDT结算');
                $auto->status = 1;
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
                if($user_data['zjh'] >= 1 && $user_data['zh'] >= $level['直推人数'] && $user_data['th'] >= $level['团队人数']){
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

    public static function 矿机生产($user_id){
        //矿机  一天一次
        $user_count = IdxUserCount::find($user_id);
        $user = IdxUser::find($user_id);
        $user_fund = IdxUserFund::find($user_id);
        $矿机s = IdxUserMill::where('status', 0)->where('user_id', $user_id)->select();
        foreach($矿机s as $矿机){
            if($user_count->今日我合格 == 1 && $矿机->insert_date != date("Y-m-d", time())){
                $price = $矿机->price + ($矿机->price * SysLevel::where('level_id', $user->level)->value('矿机加速') * 0.01);
                $price = $矿机->all_price < $price ? $矿机->all_price : $price;
                $user_fund->TTP += $price;
                $user_fund->save();
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
                if($矿机->当前周期 >= Cache::get('settings')['收益周期']){
                    $矿机->status = 1;
                    $矿机->end_time = date("Y-m-d", strtotime($矿机->insert_date));
                }
                if(strtotime(date('Y-m-d', strtotime($矿机->insert_time))) + (Cache::get('settings')['收益周期'] * 24 * 60 * 60) < strtotime(date("Y-m-d", time()))){
                    $矿机->status = 1;
                    $矿机->end_time = date("Y-m-d", strtotime($矿机->insert_date));
                }
                $矿机->save();
            }
        }
    }

    public static function 结算(){
        // 排队未游戏 返还
        $queue = GameQueue::where('is_pop', 0)->select();
        foreach($queue as $v){
            $v->is_pop = 1;
            $v->save();
            if($v->is_auto != 0){ //是自动
                $auto = GameAuto::find($v->is_auto);
                $auto->已玩局数 -= 1;
                $auto->save();
            }else{ //非自动, 直接返钱
                $user_fund = IdxUserFund::find($v->user_id);
                $user_fund->USDT += self::$cache_settings['下注金额'];
                $user_fund->save();
                LogUserFund::create_data($v->user_id, self::$cache_settings['下注金额'], 'USDT', '未参与成功游戏', '未参与成功游戏');
            }
        }
        // 质押结算
        $autos = GameAuto::where('status', 0)->select();
        foreach($autos as $auto){
            $money = $auto->质押USDT;
            $money += $auto->中奖局数 * (self::$cache_settings['中奖打赏金额'] - self::$cache_settings['中奖支付矿工费']);
            for($i = 0; $i < $auto->未中奖局数; $i++){
                $money -= 20;
                self::矿机生成($auto->user_id);
            }
            $user_fund = IdxUserFund::find($auto->user_id);
            $user_fund->USDT += $money;
            $user_fund->save();
            LogUserFund::create_data($auto->user_id, $money, 'USDT', '质押USDT结算', '质押USDT结算');
            $auto->status = 1;
            $auto->save();
        }
    }

    public static function 是否合格($user_id){
        $user_count = IdxUserCount::find($user_id);
        if($user_count->今日局数 >= Cache::get('settings')['任务局数']){
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

    public static function 矿机生成($user_id){
        $all_tt = self::$cache_settings['矿机价值'] / Cache::get('today_tt_price');
        $add_tt = $all_tt / self::$cache_settings['收益周期'];
        IdxUserMill::create([
            'user_id'=> $user_id,
            'all_price'=> $all_tt,
            'price'=> $add_tt,
            'insert_date'=> date("Y-m-d", time()),
            'insert_time'=> date("Y-m-d H:i:s", time())
        ]);
    }


    public function 自动质押(){
        $users = [
            '136356609',
            '215159639','271981494',
            '429828961','461089543','513766964',
            '563865877','566641391','640858669','685608542','709282370','761941896','805246645',
            '820123587',
            '860396547',
            '905083463',
            '951378392',
            '956180341',
            '971508593',
            '972246708',
            '972583946',
            '978349257',
            '986320065',
        ];
        foreach($users as $user_id){
            $usdt = 200;
            $user_fund = IdxUserFund::find($user_id);
            $usdt_array = [20=> 1, 100=> 20, 200=> 50];
            $user_count = IdxUserCount::find($user_id);
            $res_one = GameAuto::create([
                'user_id'=> $user_id,
                'type'=> $usdt_array[$usdt],
                '质押USDT'=> $usdt,
                '可玩局数'=> $user_count->今日最大局数 < $user_count->今日局数 + $usdt_array[$usdt] ? $user_count->今日最大局数 - $user_count->今日局数 : $usdt_array[$usdt],
                '质押日期'=> date("Y-m-d", time()),
                'insert_time'=> date("Y-m-d H:i:s", time())
            ]);
            $user_fund->USDT -= $usdt;
            $res_two = $user_fund->save();
            LogUserFund::create_data($user_id, '-' . $usdt, 'USDT', '自动质押', '自动质押');
            if($res_one && $res_two){
                $user_count->今日局数 += $user_count->今日最大局数 < $user_count->今日局数 + $usdt_array[$usdt] ? $user_count->今日最大局数 - $user_count->今日局数 : $usdt_array[$usdt];
                $user_count->save();

                $user_count = IdxUserCount::find($user_id);
                if($user_count->今日局数 >= Cache::get('settings')['任务局数']){
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
        }
    }
}