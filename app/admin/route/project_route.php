<?php
use think\facade\Route;


Route::rule('app/质押列表', 'app/质押列表');
Route::rule('app/游戏日志', 'app/游戏日志');
Route::rule('app/交易列表', 'app/交易列表');
Route::rule('app/等级', 'app/等级');
Route::rule('app/等级/update', 'app/等级_update');
Route::rule('app/等级/update/submit', 'app/等级_update_submit');
Route::rule('app/tt价格', 'app/tt价格');
Route::rule('app/tt价格/add/submit', 'app/tt价格_add_submit');
Route::rule('app/矿机管理', 'app/矿机管理');
Route::rule('app/门票列表', 'app/门票列表');

Route::get('user/pan/:id', 'admin/user/user_pan');


Route::rule('user/log/recharge', 'admin/app/recharge_log');
Route::rule('user/address', 'admin/app/address');
Route::rule('user/log/withdraw', 'admin/app/withdraw_log');
Route::rule('user/withdraw/submit/:swift_no', 'admin/app/withdraw_submit');
Route::rule('user/fund/link', 'admin/app/user_fund_link');
Route::rule('user/fund/fee/submit', 'admin/app/fee_submit');
Route::rule('user/fund/cc/submit', 'admin/app/cc_submit');