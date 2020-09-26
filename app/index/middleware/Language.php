<?php
namespace app\index\middleware;

use think\facade\Cookie;

use app\admin\model\IdxUser;


class Language{
    public function handle($request, \Closure $next){
        $language = $request->param('language', '');
        $url = $request->url();

        if($language != ''){
            if($language != Cookie::get('think_lang')){
                Cookie::set('think_lang', $language);
                return redirect($url);
            }
        }
        if(is_null(Cookie::get('think_var'))){
            Cookie::set('think_lang', 'zh-cn');
        }
        return $next($request);
    }
}