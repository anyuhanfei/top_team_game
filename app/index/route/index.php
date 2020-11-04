<?php
use think\facade\Route;

Route::get('/test/批量增加会员', 'index/test/批量增加会员');
Route::get('/test/批量质押一次', 'index/test/批量质押一次');
Route::get('/test/批量质押到死', 'index/test/批量质押到死');


Route::get('/z/游戏', 'index/fund/游戏');
Route::get('/z/发放奖励', 'index/fund/发放奖励');
Route::get('/z/自动参与', 'index/fund/自动参与');
Route::get('/z/自动质押', 'index/fund/自动质押');
Route::post('/语言切换', 'index/base/语言切换');

// 首页
Route::get('/', 'index/index/index');
Route::get('/启动图', 'index/index/启动图');
Route::get('/更多', 'index/index/更多');
Route::get('/车房基金', 'index/index/车房基金');
Route::get('/非法操作', 'index/base/非法操作');

Route::post('/购买门票', 'index/index/购买门票');
Route::get('/游戏', 'index/index/游戏');
Route::post('/游戏/手动参与', 'index/index/手动参与');
Route::post('/游戏/自动参与', 'index/index/自动参与');
Route::get('/游戏记录', 'index/index/游戏记录');

//交易
Route::get('/deal', 'index/deal/交易大厅');
Route::get('/deal/交易单', 'index/deal/交易单');
Route::get('/deal/交易记录', 'index/deal/交易记录');
Route::post('/deal/挂单', 'index/deal/挂单');
Route::post('/deal/交易', 'index/deal/交易');
Route::post('/deal/撤销', 'index/deal/撤销');

// 登录
Route::get('/登录', 'index/login/登录');
Route::rule('/登录提交', 'index/login/登录提交');
Route::get('/注册', 'index/login/注册');
Route::rule('/注册提交', 'index/login/注册提交');
Route::get('/忘记密码', 'index/login/忘记密码');
Route::rule('/忘记密码提交', 'index/login/忘记密码提交');
Route::rule('/退出登录', 'index/login/退出登录');

//个人中心
Route::get('/me', 'index/me/index');
Route::get('/me/账户管理', 'index/me/账户管理');
Route::get('/me/客服', 'index/me/客服');
Route::post('/me/添加子账号', 'index/me/添加子账号');
Route::post('/me/切换账号', 'index/me/切换账号');
Route::get('/me/子资产管理', 'index/mefund/子资产管理');
Route::get('/me/钱包地址', 'index/me/钱包地址');
Route::post('/me/钱包地址提交', 'index/me/钱包地址提交');
Route::get('/me/安全', 'index/me/安全');
Route::get('/me/实名认证', 'index/me/实名认证');
Route::post('/me/实名认证提交', 'index/me/实名认证提交');
Route::get('/me/助记词', 'index/me/助记词');
Route::get('/me/忘记二级密码', 'index/me/忘记二级密码');
Route::post('/me/忘记二级密码提交', 'index/me/忘记二级密码提交');
Route::get('/me/修改密码', 'index/me/修改密码');
Route::post('/me/修改密码提交', 'index/me/修改密码提交');
Route::get('/me/团队', 'index/me/团队');
Route::get('/me/邀请', 'index/me/邀请');
Route::get('/me/关于我们', 'index/me/关于我们');
Route::get('/公告', 'index/index/公告');
Route::get('/公告详情', 'index/index/公告详情');
Route::get('/me/TTP销毁', 'index/me/ttp销毁');
Route::get('/me/百问百答', 'index/me/百问百答');
Route::get('/me/设置', 'index/me/设置');
Route::get('/me/修改昵称', 'index/me/修改昵称');
Route::post('/me/修改昵称提交', 'index/me/修改昵称提交');
Route::get('/me/语言切换', 'index/me/语言切换');
Route::post('/me/语言切换提交', 'index/me/语言切换提交');
Route::post('/me/生成助记词', 'index/me/生成助记词');

Route::get('/me/资产', 'index/mefund/资产');
Route::get('/me/coin/:coin_type', 'index/mefund/coin');
Route::get('/me/矿机', 'index/mefund/矿机');
Route::get('/me/矿机记录', 'index/mefund/矿机记录');
Route::get('/me/收益记录', 'index/mefund/收益记录');
Route::get('/me/兑换', 'index/mefund/兑换');
Route::post('/me/兑换提交', 'index/mefund/兑换提交');
Route::get('/me/充值', 'index/mefund/充值');
Route::get('/me/提现', 'index/mefund/提现');
Route::post('/me/提现提交', 'index/mefund/提现提交');
Route::get('/me/转账', 'index/mefund/转账');
Route::post('/me/转账提交', 'index/mefund/转账提交');
Route::get('/me/转入', 'index/mefund/转入');
Route::post('/me/转入提交', 'index/mefund/转入提交');
Route::rule('/me/归集', 'index/mefund/归集');