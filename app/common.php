<?php
// use think\Controller;
use think\Session;
use think\Cookie;
use think\Config;
use think\Request;
use think\Db;
use think\Cache;
use think\cache\driver\Redis;
// use think\File;
// use think\Upload;

// | fjw:应用公共（函数）文件
// +----------------------------------------------------------------------
// | Author: fjw <fjwcoder@gmail.com>
// +----------------------------------------------------------------------
// | （fjw: 测试用）
// | msg: 页面跳转
// | isMobile：是否是移动端
// | httpsPost：post请求
// | httpsGet：get请求
// | cryptCode：加解密方法
// | ksetcookie: 设置加密cookie
// | kgetcookie: 获取解密后的cookie
// | getField: 弥补tp3.2的getField方法
// | webConfig：网站配置缓存文件
// | getUserInfo: 获取用户的基本信息
// | arrayDeepVal: 获取数组X深度的值
// | //updateAll 批量更新: tp5修改的不支持批量更新了，烦人！！！
// | getAdminBranch 获取部门
// | getAdminLevel 获取级别
// | uploadImg 图片上传
// | getAdminNode 获取用户节点
// | getOrderID 获取唯一的订单号
// | clientIP: 获取IP地址
// | getTerm: 获取当前期的信息
// | getWxConf: 获取微信配置
// |
// | // start wxapp
// | getSeparate: 获取分表信息
// | getDataSep: 获取用户分数表
// | getLinkSep: 获取用户关系表
// | getGlobalConfig: 获取全局配置
// |
// |  
// |
// |
// +----------------------------------------------------------------------



function randomFromDev($len=6)
{
    $fp = @fopen('/dev/urandom','rb');
    $result = '';
    if ($fp !== FALSE) {
        $result .= @fread($fp, $len);
        @fclose($fp);
    }
    else
    {
        trigger_error('Can not open /dev/urandom.');
    }
    // convert from binary to string
    $result = base64_encode($result);
    // remove none url chars
    $result = strtr($result, '+/', '-_');
    // Remove = from the end
    $result = str_replace('=', '', $result);
    return $result.time();
}



function getSeparate(){
    if(cache('SEPARATE')){
        $separate = cache('SEPARATE');
    }else{
        $data = Db::name('region') -> field(['id', 'name', 'title', 'data', 'link']) 
            -> where(['type'=>1])-> select();
        $separate = getField($data, 'name');
        cache('SEPARATE', $separate);
    }

    return $separate;
}




// +----------------------------------------------------
// |页面跳转
// |
// |
// +----------------------------------------------------
function msg($url,$str="", $type='')
{
	if($url=="-1"){
		if($url && !$str){
			echo "<script>history.go(-1);</script>";
		}
		if($url && $str){
			echo "<script>alert('$str');history.go(-1);</script>";
		}
	}else{
        if($type==='iframe'){
            if($url && !$str){
			    echo "<script>window.parent.location='$url';</script>";
		    }
            if($url && $str){
                echo "<script>alert('$str');window.parent.location='$url';</script>";
            }
        }else{
            if($url && !$str){
			    echo "<script>window.location='$url';</script>";
		    }
            if($url && $str){
                echo "<script>alert('$str');window.location='$url';</script>";
            }	
        }
		
	}
	die;
}


// +----------------------------------------------------
// |判断是否是手机端登录
// |应用实例：1.【后台Index控制器】Index/index
// |
// +----------------------------------------------------
function isMobile(){  
    // if(session('ISMOBILE')){
    //     return session('ISMOBILE');
    // }else{

    
        $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';  
            $mobile_browser = '0';  
        if(preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT'])))  
            $mobile_browser++;  
        if((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') !== false))  
            $mobile_browser++;  
        if(isset($_SERVER['HTTP_X_WAP_PROFILE']))  
            $mobile_browser++;  
        if(isset($_SERVER['HTTP_PROFILE']))  
            $mobile_browser++;  
        $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,4));  
        $mobile_agents = array(  
            'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',  
            'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',  
            'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',  
            'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',  
            'newt','noki','oper','palm','pana','pant','phil','play','port','prox',  
            'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',  
            'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',  
            'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',  
            'wapr','webc','winw','winw','xda','xda-' 
        );  
        if(in_array($mobile_ua, $mobile_agents))
            $mobile_browser++;  
        if(strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false)  
            $mobile_browser++;  
        // Pre-final check to reset everything if the user is on Windows  
        if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false)  
            $mobile_browser=0;  
        // But WP7 is also Windows, with a slightly different characteristic  
        if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false)  
            $mobile_browser++; 
        if($mobile_browser>0)  
            return true;  
        else 
            return false; 
    // }
}

#https POST 请求处理函数 
function httpsPost($url, $data = null){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    if(!empty($data)){
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}

// 是否是手机号码
function isMobileNumber($phonenumber){
    if(preg_match("/^1[34578]{1}\d{9}$/",$phonenumber)){  
        return true; 
    }else{  
        return false; 
    } 
}
#https   GET请求处理函数
function httpsGet($url){
    $oCurl = curl_init();
    if(stripos($url, 'https://')!==FALSE){
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($oCurl, CURLOPT_SSLVERSION, 1);
    }
    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
    $sContent = curl_exec($oCurl);
    $aStatus = curl_getinfo($oCurl);
    curl_close($oCurl);
    if(intval($aStatus['http_code'])==200){
        return $sContent;
    }else{
        return false;
    }
}

function cryptCode($data='', $operation='ENCODE', $key=''){
    $key = md5($key);
    $x = 0;
    $char = '';
    $str = '';
    if($operation === 'ENCODE'){
        $len = strlen($data);
        $l = strlen($key);
        for ($i = 0; $i < $len; $i++)  {  
            if ($x == $l)   {  
                $x = 0;  
            }  
            $char .= $key{$x};  
            $x++;  
        }  
        for ($i = 0; $i < $len; $i++)  {  
            $str .= chr(ord($data{$i}) + (ord($char{$i})) % 256);  
        }  
        $result = base64_encode($str);
    }else{
        $data = base64_decode($data);  
        $len = strlen($data);  
        $l = strlen($key);  
        for ($i = 0; $i < $len; $i++) {  
            if ($x == $l)   {  
                $x = 0;  
            }  
            $char .= substr($key, $x, 1);  
            $x++;  
        }  
        for ($i = 0; $i < $len; $i++) {  
            if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1)))  {  
                $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));  
            }  
            else  {  
                $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));  
            }  
        }  
        $result = $str;
    }

    return $result;
}

// +----------------------------------------------------
// |加密解密的方法，存在有效时间
// |$string: 加解密字段
// |$operation: 加解密操作
// |$crypt: 加解密秘钥
// |$expriy: 密文有效期
// +----------------------------------------------------
function authCode($string, $operation = 'DECODE', $crypt ='', $expiry = 0)
{
	$ckey_length = 4;
    if(empty($crypt)){
        $crypt = substr(md5($string), 0, 4);
    }
    $key = md5(md5($crypt).$_SERVER['HTTP_USER_AGENT']);

    // $keya 会参与加解密
	$keya = md5(substr($key, 0, 16));
    // $keyb 用来做数据完整性验证
	$keyb = md5(substr($key, 16, 16));
    // $keyc用于变化生成的密文   
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
	$cryptkey = $keya.md5($keya.$keyc);
	$key_length = strlen($cryptkey);
	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	$string_length = strlen($string);
	$result = '';
	$box = range(0, 255);
	$rndkey = array();
	for($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}
	for($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}
	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}
	if($operation == 'DECODE') {
		if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		return $keyc.str_replace('=', '', base64_encode($result));
	}
}

//后台设置cookie
function dsetcookie($array, $key){
    foreach($array as $k=>$v){
        $array[$k] = authCode($v, 'ENCODE');
    }
    cookie($key, $array);
    return true;
}

//后台获取cookie
function dgetcookie($key){
    $cookie = cookie($key);

    foreach($cookie as $k=>$v){
        $cookie[$k] = authCode($v, 'DECODE');
    }

    return $cookie;
}

function encodeCookie($array, $key){
    // if(isset($array['level'])){
    //     switch($array['level']){
    //         case 1:
    //             $array['levelname'] = 'FRESHMAN-VIP';
    //         break;
    //         case 2:
    //             $array['levelname'] = 'SOPHOMORE-VIP';
    //         break;
    //         case 3:
    //             $array['levelname'] = 'JUNIOR-VIP';
    //         break;
    //         case 4:
    //             $array['levelname'] = 'SENIOR-VIP';
    //         break;
    //         default:
    //             $array['levelname'] = 'TOP-VIP';
    //         break;
    //     }
    // }
    foreach($array as $k=>$v){
        $array[$k] = authCode($v, 'ENCODE', $k);
    }
    
    cookie($key, $array);
    return true;
}

function decodeCookie($key){
    $cookie = cookie($key);
    if(!empty($cookie)){
        foreach($cookie as $k=>$v){
            $cookie[$k] = authCode($v, 'DECODE', $k);
        }
        return $cookie;
    }else{
        return [];
    }
    
}





#数组处理函数，弥补3.2的getField();
function getField($array, $field='name'){
    foreach($array as $k=>$v){
        $result[$v[$field]] = $v;
        unset($array[$k]);
    }
    return $result;
}


// +----------------------------------------------------
// | 图片上传方法，只支持上传到本地服务器
// +----------------------------------------------------

#多图片上传
function uploadImg($dir='', $param=''){
    $static = DS.'upload'.DS.$dir.DS;
    $upurl = $_SERVER['DOCUMENT_ROOT'].DS.'static'.$static;
    
    if (! file_exists ( $upurl )) {
        mkdir ( "$upurl", 0777, true );
    }
    $path = [];

    $keys = array_keys($_FILES);
    foreach($keys as $key){
        $files = request()->file($key);
        if(!empty($files)){
            foreach($files as $key=>$file){
                $info = $file -> move($upurl);

                if($info){

                    $path[] = '__STATIC__'.$static.$info->getSaveName();
                }else{
                    // 上传失败获取错误信息
                    return ['status'=>false, 'error'=>$file->getError()]; exit; //只要有一张上传失败，都算失败
                }
            }
        }
    }
    return ['status'=>true, 'path'=>$path]; 
}
#单图片上传，头像上传(layui)
function uploadHeadImg($dir='', $param = ''){
    $static = DS.'upload'.DS.$dir.DS;
    $upurl = $_SERVER['DOCUMENT_ROOT'].DS.'static'.$static;
    
    if (! file_exists ( $upurl )) {
        mkdir ( "$upurl", 0777, true );
    }
    $path = [];
    // $file = $_FILES['file'];
    $file = request()->file('file');
    // file_put_contents('test.txt', $file);
    if(!empty($file)){
        $info = $file->move($upurl);
        if($info){
            
            $path[] = DS.'static'.$static.$info->getSaveName();
            // $path[] = '__STATIC__'.$static.$info->getSaveName();
        }else{
            // 上传失败获取错误信息
            return ['status'=>false, 'error'=>$file->getError()]; exit; 
        }
    }
    return ['status'=>true, 'path'=>$path]; 
}

// #头像上传
// function uploadHeadImg($dir='', $param = ''){
//     $static = DS.'upload'.DS.$dir.DS;
//     $upurl = $_SERVER['DOCUMENT_ROOT'].DS.'static'.$static;
    
//     if (! file_exists ( $upurl )) {
//         mkdir ( "$upurl", 0777, true );
//     }
//     $path = [];

//     $keys = array_keys($_FILES);

//     foreach($keys as $key){
//         $files = request()->file($key);
//         if(!empty($files)){
            
//                 $info = $files -> move($upurl);
                
//                 if($info){

//                     $path[] = '__STATIC__'.$static.$info->getSaveName();
//                 }else{
//                     // 上传失败获取错误信息
//                     return ['status'=>false, 'error'=>$file->getError()]; exit; 
//                 }
//         }
//     }
//     return ['status'=>true, 'path'=>$path]; 
// }


#获取订单号
function setOrderID(){
    $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
    $orderSn = $yCode[intval(date('Y')) - 2011] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    return $orderSn;
}

//获取当前客户端的IP地址
function clientIP() { 
    if(getenv('HTTP_CLIENT_IP')){ 
        $client_ip = getenv('HTTP_CLIENT_IP'); 
    } elseif(getenv('HTTP_X_FORWARDED_FOR')) { 
        $client_ip = getenv('HTTP_X_FORWARDED_FOR'); 
    } elseif(getenv('REMOTE_ADDR')) {
        $client_ip = getenv('REMOTE_ADDR'); 

    } else {
        $client_ip = $_SERVER['REMOTE_ADDR'];
    } 
    return $client_ip; 
}



function wxConfig($param = ''){
    if(cache('WX_CONFIG')){
        $wxconf = cache('WX_CONFIG');
    }else{
        $wxconf = Db::name('wechat_config')-> where(['status'=>1]) -> select();
        $wxconf = getField($wxconf, 'name');
        //cache('WX_CONFIG', $wxconf); //缓存注释
    }

    return empty($param)?$wxconf:$wxconf[$param];

}

#商城配置文件缓存
function mallConfig(){
    if(cache('MALL_CONFIG')){
        $config = cache('MALL_CONFIG');
    }else{
        $config = db('mall_config', [], false) -> where(array('status'=>1)) -> select();
        $config = getField($config);
        $config = array_merge(webConfig(), $config);
        cache('MALL_CONFIG', $config); //缓存注释
    }

    return $config;
}

#网站配置文件缓存
function webConfig($type = 0){
    if(cache('WEB_CONFIG')){
        $config = cache('WEB_CONFIG');
    }else{
        $config = db('web_config', [], false) -> where(array('status'=>1)) -> select();
        $config = getField($config);
        cache('WEB_CONFIG', $config); //缓存注释
    }
    return $config;
}

