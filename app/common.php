<?php
// 应用公共文件

use think\facade\Env;
use app\qiniu;

/**
 * 前后端数据传输格式
 *
 * @param int $code 状态,1成功2失败
 * @param int|array $data 数据
 * @param string $msg 提示信息
 * @param string $operation 日志内容
 * @return void
 */
function return_data($code, $data, $msg, $operation = ''){
    return json_encode(array('code'=>$code, 'data'=>$data, 'msg'=>$msg, 'operation'=> $operation));
}

/**
 * 生成简单验证码
 *
 * @param [type] $number 验证码位数
 * @param string $type 验证码内容类型
 * @return void
 */
function create_captcha($number, $type = 'figure'){
    $array_figure = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0'];
    $array_lowercase = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
    $array_uppercase = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
    switch($type){
        case 'lowercase':
            $res_array = $array_lowercase;
            break;
        case 'uppercase':
            $res_array = $array_uppercase;
            break;
        case 'lowercase+figure':
            $res_array = array_merge($array_lowercase, $array_figure);
            break;
        case 'uppercase+figure':
            $res_array = array_merge($array_uppercase, $array_figure);
            break;
        case 'lowercase+uppercase':
            $res_array = array_merge($array_lowercase, $array_uppercase);
            break;
        case 'lowercase+uppercase+figure':
            $res_array = array_merge(array_merge($array_lowercase, $array_uppercase), $array_figure);
            break;
        default:
            $res_array = $array_figure;
            break;
    }
    $resstr = '';
    shuffle($res_array);
    foreach(array_rand($res_array, $number) as $v){
        $resstr .= $res_array[$v];
    }
    return $resstr;
}

/**
 * 文件上传
 *
 * @param file $file 文件资源句柄
 * @param string $save_path 文件保存子文件夹
 * @param array $file_validate 文件上传验证
 * @return void
 */
function file_upload($file, $save_path, $file_validate = array('size'=>156780000, 'ext'=>'jpg,png,gif')){
    if(!$file){
        return array('status'=>2, 'file_path'=>'', 'error'=>'请上传文件');
    }
    // 验证
    try{
        validate(['file'=>[
            'fileSize'=> $file_validate['size'],
            'fileExt'=> $file_validate['ext']
        ]])->check(['file'=> $file]);
    }catch(\think\exception\ValidateException $e){
        return array('status'=>2, 'file_path'=>'', 'error'=>'图片审核未通过');
    }
    // 图片上传至服务器
    $info = str_replace('\\', '/', \think\facade\Filesystem::disk('public')->putFile($save_path, $file));
    if($info){
        // 图片添加水印(可将图片中的php木马去除)
        $image = \think\Image::open('./storage' . '/' . $info);
        $image->water('./static/watermark.png')->save('./storage' . '/' . $info);
        // 上传七牛云判断
        if(Env::get('QINIU.UPLOAD2QINIU') == true){
            //上传
            $qiniu = new qiniu;
            list($res, $file_path) = $qiniu->upload('./storage' . '/' . $info, $info);
            //结果判断
            if($res == false){
                $res = array('status'=>2, 'file_path'=>'', 'error'=>'七牛云上传失败');
            }else{
                $res = array('status'=>1, 'file_path'=> $file_path, 'error'=>'');
            }
            //删除服务器上的文件
            delete_image('./storage' . '/' . $info);
        }else{
            $res = array('status'=>1, 'file_path'=>'/storage' . '/' . $info, 'error'=>'');
        }
    }else{
        $res = array('status'=>2, 'file_path'=>'', 'error'=>$file->getError());
    }
    unset($file);
    return $res;
}

/**
 * 删除图片
 *
 * @param [type] $oldImg 旧图片路径
 * @param boolean $is_full 是否是完整路径
 * @return void
 */
function delete_image($oldImg, $is_full = false){
    if($oldImg != ''){
        if($is_full == false){
            $path = app()->getRootPath() . '/public' . $oldImg;
        }else{
            $path = $oldImg;
        }
        if ($path != '/public/') {
            if(is_file($path) == true) {
                unlink($path);
            }
        }
    }
}

/**
 * 二维码生成
 *
 * @param [type] $url
 * @param [type] $phone
 * @return void
 */
function png_erwei($url, $phone)
{
   include_once '../extend/phpqrcode/phpqrcode.php';//放在extend中
    //vendor('phpqrcode.phpqrcode');//放在vender中
    $errorCorrectionLevel = 'H';//容错级别
    $matrixPointSize = 5;//图片大小慢慢自己调整，只要是int就行
    $path = '../public/storage/qrcode/';
    $QR = $QRB = $path . $phone . ".png";
    \QRcode::png($url, $QR, $errorCorrectionLevel, $matrixPointSize, 2);
    if(file_exists($path . $phone . ".png")){
        return "/storage/qrcode/" . $phone . ".png";
    }else{
        return false;
    }
}

/**
 * 随机生成订单号
 *
 * @return void
 */
function order_sn(){
    return date('ymdhis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * 获取用户IP
 *
 * @return string
 */
function get_ip()
{
    $ip = 'xxxx';
    if(!empty($_SERVER['HTTP_CLIENT_IP']))
    {
        return is_ip($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : $ip;
    }
    elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
    {
        return is_ip($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $ip;
    }
    else
    {
        return is_ip($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : $ip;
    }
}


/**
 * 判断是否是IP地址
 *
 * @param $str
 * @return bool|int
 */
function is_ip($str)
{
    $ip = explode('.', $str);
    for($i=0; $i<count($ip); $i++)
    {
        if($ip[$i]>255)
        {
            return false;
        }
    }
    return preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $str);
}

/**
 * 创建助记词
 *
 * @return void
 */
function read_word(){
    $filename = "./static/word.txt";
    $handle = fopen($filename, "r");
    $number = 0;
    $res = '';
    while(!feof($handle) && $number < 12){
        if(create_captcha(4) < 500){
            $a = nl2br(fgets($handle));
            $res .= str_replace(array(" ","　","\t","\n","\r", '<br/>', '<br />'), '', $a) . ($number < 11 ? ' ' : '');
            $number++;
        }else{
            fgets($handle);
        }
    }
    fclose($handle);
    return $res;
}