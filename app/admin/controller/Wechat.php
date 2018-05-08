<?php
# +------------------------------------------------------------------------------------
# | index：
# | valid：验证
# | checkSignature：签名验证
# | access_token：获取access_token
# | jsapi_ticket：获取jsapi_ticket
# | responseMsg：回复消息
# | handleEvent：处理事件消息
# | handleText：处理文本消息
# | transmitNews：回复图文消息
# | transmitService：回复多客服消息
# | transmitImage：回复图片消息
# | transmitText：回复文本消息
# | getMediaId：获取素材id
# | getTempMaterial：获取临时素材
# | uploadTempMaterial: 上传临时素材
# |
# |
# |
# |
# | sceneQRCode：生成带场景值参数的二维码信息
# +------------------------------------------------------------------------------------
namespace app\admin\controller;
define('COMPANY_SUBSCRIBE', '顶鲜蔬蔬\n');
// define('SUCAI_COUNT', 'https://api.weixin.qq.com/cgi-bin/material/get_materialcount?access_token=');//素材数量
// define('FOREVER_SUCAI', 'https://api.weixin.qq.com/cgi-bin/material/get_material?access_token='); //获取永久素材
// define('SUCAI_LIST', 'https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=');//素材列表
use app\common\controller\Common;
use app\index\controller\Register as Register;
use app\index\controller\Login as Login;
use think\Controller;
use think\Session;
use think\Cookie;
use think\Config;
use think\Request;
use think\Db;
use think\Cache;

class Wechat extends Controller
{   
    # +------------------------------------------------------------------------------------
    # | 验证信息
    # |
    # +------------------------------------------------------------------------------------
    public function index(){
        if(!isset($_GET['echostr'])){
			$this -> responseMsg();
		}else{
			$this -> valid();//验证key
		}
    }

    # +------------------------------------------------------------------------------------
    # | 
    # |
    # +------------------------------------------------------------------------------------
    public function valid()
    {
       
        $echoStr = $_GET['echostr'];
        if($this->checkSignature()){//调用验证签名checkSignature函数
        	echo $echoStr;
        	exit;
        }
    }

    # +------------------------------------------------------------------------------------
    # | 验证签名
    # |
    # +------------------------------------------------------------------------------------		
	private function checkSignature()
	{
        $signature = $_GET['signature'];
        $timestamp = $_GET['timestamp'];
        $nonce = $_GET['nonce'];	
        	
		$token = wxConfig('TOKEN');
		$tmpArr = array($token['value'], $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
    
    # +------------------------------------------------------------------------------------
    # | 获取access_token: by fjw in 17.10.13
    # |
    # +------------------------------------------------------------------------------------
    public function access_token() {
        
        $res = db("wechat_config", [], false) -> where(array("name"=>'ACCESS_TOKEN')) -> find();
        if($res['endtime'] > time()){ //没过期
            return $res['value'];
        }else{
            $wxconf = wxConfig();
            $url = $wxconf['ACCESS_TOKEN_URL']['value'].$wxconf['APPID']['value']."&secret=".$wxconf['APPSECRET']['value'];
            $response = httpsGet($url);
            $res = json_decode($response, true);
            if(!empty($res['access_token'])){
                $data['value'] = $res['access_token'];
                $data['exprire'] = intval($res['expires_in'])-100;
                #endtime 是到期时间
                $data['edittime'] = time();
                $data['endtime'] = $data['edittime']+$data['exprire'];
                db('wechat_config') -> where(array('name'=>'ACCESS_TOKEN')) -> update($data);
                return $res['access_token'];
            }
        }

    }

    # +------------------------------------------------------------------------------------
    # | 获取jsapi_ticket: by fjw in 17.12.10
    # |
    # +------------------------------------------------------------------------------------
    public function jsapi_ticket(){
        $res = db("wechat_config", [], false) -> where(array("name"=>'JSAPI_TICKET')) -> find();
        if($res['endtime'] > time()){ //没过期
            return $res['value'];
        }else{
            $wxconf = wxConfig();
            $url = $wxconf['JSAPI_URL']['value'].$this->access_token()."&type=jsapi";//.$wxconf['APPID']['value']."&secret=".$wxconf['APPSECRET']['value'];
            $response = httpsGet($url);
            $res = json_decode($response, true);
            // return dump($res);
            if( (!empty($res['ticket'])) && ($res['errmsg']=='ok')){
                $data['value'] = $res['ticket'];
                $data['exprire'] = intval($res['expires_in'])-100;
                #endtime 是到期时间
                $data['edittime'] = time();
                $data['endtime'] = $data['edittime']+$data['exprire'];
                db('wechat_config') -> where(array('name'=>'JSAPI_TICKET')) -> update($data);
                return $res['ticket'];
            }
        }
    }

    #获取素材
    // public function sucaiList(){
    //     $token = $this->access_token();
    //     $url = SUCAI_LIST.$token;
    //     $comm = new Common();
    //     $data = '{
    //         "type":"news",
    //         "offset": 4,
    //         "count":1,
    //     }';
    //     $post = httpsPost($url, $data);
    //     return $post;
    // }


    
    # +------------------------------------------------------------------------------------
    # | 响应公众号信息、事件
    # | 
    # +------------------------------------------------------------------------------------
    public function responseMsg()
	{
		$postStr = file_get_contents('php://input');
		if (!empty($postStr))
		{
			$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
			$RX_TYPE = trim($postObj -> MsgType);
			switch($RX_TYPE)
			{
				case 'event':
					$resultStr = $this -> handleEvent($postObj);
				break;
				case 'text':
					$resultStr = $this -> handleText($postObj);
				break;
				default:
					$resultStr = 'Unknow msg type: '.$RX_TYPE;
				break;
			}
			echo $resultStr;
		}else{
			echo "no user's post data";
		}
	}

    # +------------------------------------------------------------------------------------
    # | 接收事件消息
    # | 
    # +------------------------------------------------------------------------------------
    public function handleEvent($object){
        $openid = strval($object->FromUserName);
        $wxconf = wxConfig();
        $register = new Register();
        $access_token = $this->access_token();
        // $deal = true; // 是否做统一处理：$content is array 发送图文 $content is string 发送消息
        $content = "";
        switch ($object->Event){
            case "subscribe":
                
                $user_url = $wxconf['USER_BASEINFO']['value'].$access_token.'&openid='.$openid.'&lang=zh_CN';
                $user_res = httpsGet($user_url);
                $user_arr = json_decode($user_res, true);//获取到的用户信息
                $content .= "欢迎关注".COMPANY_SUBSCRIBE;
                if(empty($object->EventKey)){ //不带场景值,直接注册 或者 重新关注
                    $regist = $register->subscribe($user_arr); //参数为用户数据
					$content .= $regist['content'];

                }else{ //带场景值
                    #判断绑定 or 推荐
                    $scene = explode('_', $object->EventKey);
                    unset($scene[0]);
                    if(count($scene) == 1){
                        $param['uid'] = $scene[1];
                    }else{
                        foreach($scene as $k=>$v){
                            $scene[$k] = explode('=', $v);
                            $param[$scene[$k][0]] = $scene[$k][1];
                        }
                    }
                    
                    
                    $regist = $register->scanQRCode($user_arr, $param);
                    $content .= $regist['content'];

                    // $content .= "==".json_encode($param)."==";


                }
                $content .= "详情请关注自定义菜单";

                
                break;
            case "CLICK":
                switch($object->EventKey){
                    case "my_qrcode":
                        # 图文模式 , 不可删除, 与图片模式只能留一个
                        $user = Db::name('users') -> where(['openid'=>$openid, 'subscribe'=>1,'status'=>1]) -> find();
                        if(!empty($user)){
                            $view_url = 'http://www.6rmh.com/index/register/myqrcode/id/'.$user['id'];
                            $content = array(); // 写成这种方式，是为了多图文消息
                            $content[] = [
                                'Title'=>'我的推广二维码', 
                                'Description'=>$user['nickname'].'的推广二维码', 
                                'PicUrl'=>$user['qr_code'], 
                                'Url'=>$view_url
                            ];
                        }else{
                            $content .= '未关注公众号或者用户已锁定';
                        }

                        # 图片模式
                        // $media = $this->getTempMaterial($openid);
						
                        // if($media['status']){
                        //     $result = $this->transmitImage($object, $media['media_id']);
                        //     $deal = false;
                        // }else{
                        //     $content .= $media['content'];
                        // }
                        
                    break;
                    default: 
                        $content .= " unknown ";
                    break;
                }
            break;
            case "VIEW":
            
                $content .= "跳转链接 ".$object->EventKey;
            break;
            case "SCAN": 
                $content .= "扫描场景 ".$object->EventKey;
            break;
            case "LOCATION":
                $content .= "上传位置：纬度 ".$object->Latitude.";经度 ".$object->Longitude;
            break;
            case "scancode_waitmsg":
                if ($object->ScanCodeInfo->ScanType == "qrcode"){
                    $content .= "扫码带提示：类型 二维码 结果：".$object->ScanCodeInfo->ScanResult;
                }else if ($object->ScanCodeInfo->ScanType == "barcode"){
                    $codeinfo = explode(",",strval($object->ScanCodeInfo->ScanResult));
                    $codeValue = $codeinfo[1];
                    $content .= "扫码带提示：类型 条形码 结果：".$codeValue;
                }else{
                    $content .= "扫码带提示：类型 ".$object->ScanCodeInfo->ScanType." 结果：".$object->ScanCodeInfo->ScanResult;
                }
            break;
            case "scancode_push":
                $content .= "扫码推事件";
            break;
            case "pic_sysphoto":
                $content .= "系统拍照";
            break;
            case "pic_weixin":
                $content .= "相册发图：数量 ".$object->SendPicsInfo->Count;
            break;
            case "pic_photo_or_album":
                $content .= "拍照或者相册：数量 ".$object->SendPicsInfo->Count;
            break;
            case "location_select":
                $content .= "发送位置：标签 ".$object->SendLocationInfo->Label;
            break;
            default:
                $content .= "receive a new event: ".$object->Event;
            break;
        }
        if($deal){
            if(is_array($content)){
                $result = $this->transmitNews($object, $content);
            }else{
                $result = $this->transmitText($object, $content);
            }
        }
        return $result;
    }

    //文本消息处理函数
	private function handleText($object)
	{
        $keyword = trim($object->Content);
        if(strstr($keyword, '呼叫客服') || strstr($keyword, '在线客服')|| strstr($keyword, '客服') ){
            $result = $this->transmitService($object);
            return $result;
        }else{
            $result = $this->transmitText($object, "系统收到信息，客服人员正在处理，请稍后……");
		    return $result;
        } 
	}

    //回复多客服消息
    private function transmitService($object)
    {
        $xmlTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[transfer_customer_service]]></MsgType>
            </xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }
    
    //回复文本消息
    private function transmitText($object, $content)
    {
        if (!isset($content) || empty($content)){
            return "";
        }
        
        $xmlTpl = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[text]]></MsgType>
						<Content><![CDATA[%s]]></Content>
			       </xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);

        return $result;
    }

    //回复图文消息
    private function transmitNews($object, $newsArray)
    {
        if(!is_array($newsArray)){
            return "";
        }
        $itemTpl = "        <item>
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            <PicUrl><![CDATA[%s]]></PicUrl>
            <Url><![CDATA[%s]]></Url>
        </item>";
        $item_str = "";
        foreach ($newsArray as $item){
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        }
        $xmlTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[news]]></MsgType>
            <ArticleCount>%s</ArticleCount>
            <Articles>$item_str</Articles>
        </xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));
        return $result;
    }	

    //回复图片消息
	private function transmitImage($object, $media_id){
		if(!isset($object)){
			return "error";
		}
		if(empty($media_id)){
			return "error";
		}
		
		$xmlTpl = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[image]]></MsgType>
						<Image>
							<MediaId><![CDATA[%s]]></MediaId>
						</Image>
				</xml>";
		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $media_id);
		return $result;
	}

    

    


    #==========================================================
    # 生成带场景值的二维码
    # $id 用户ID
    # $user 用户信息
    # $command 强制更新
    # $action 整型还是字符串型，默认字符串
    # $limit 是否永久，默认否
    #==========================================================
    public function sceneQRCode($id, $user=[], $command=false, $action="QR_STR_SCENE", $limit=false, $expire=2590000){
        
        if(session("USER_ID") ){ //登录
            $user = decodeCookie('user');
        }else{ //没有登录
            if(empty($user)){
                $user = Db::name('users') -> where(['id'=>$id ,'status'=>1]) -> find();
            }
        }
        #强制更新
        if($command){
            $user = Db::name('users') -> where(['id'=>$id ,'status'=>1]) -> find();
        }
        if(empty($user['qr_ticket']) || (time() >= $user['qr_seconds']) || $command == true){  //不存在或者超时
            
            $scene = [
                'expire_seconds'=>$expire, 'action_name'=>$action,
                'action_info'=>[]
            ];
            
            if($action==="QR_STR_SCENE"){ // 生成推广二维码
                $scene['action_info']['scene']['scene_str'] = "uid=".$id."_subscribe=".$user['subscribe']."_pid=$user[pid]";;
            }elseif($action==="QR_SCENE"){ // 生成绑定二维码
                $scene['action_info']['scene']['scene_id'] = $id;
            }

            $wxconf = wxConfig();
            $url = $wxconf['PARAM_QRCODE']['value'].$this->access_token();
            $response = httpsPost($url, json_encode($scene));
            $result = json_decode($response, true);
            // return dump($result);
            $data = ['qr_code'=>$wxconf['SHOW_QRCODE']['value'].urlencode($result['ticket']), 
                'qr_seconds'=>intval(time())+intval($result['expire_seconds']), 
                'qr_ticket'=>$result['ticket']];
            $update = Db::name('users') -> where(['id'=>$id]) -> update($data);

            $user = Db::name('users') -> where(['id'=>$id ,'status'=>1]) -> find();

            return $user;

        }else{
            return $user;
        }
        
    }





#############################################################################################################################################
# 暂时用不到的方法
#############################################################################################################################################


    # +------------------------------------------------------------------------------------
    # | 上传临时素材
    # | 
    # +------------------------------------------------------------------------------------
    public function uploadTempMaterial($openid, $path='', $type='image'){
        $wxconf = wxConfig();
        $url = $wxconf['UPLOAD_TEMP_MATERIAL']['value'].$this->access_token().'&type='.$type;
        if(class_exists('\CURLFile')){
            $fileObj = new \CURLFile($path);
            // $fileObj->setMimeType('image/jpeg');
            $data = array('media'=>$fileObj);
        }else{
            $data = array('media'=>'@'.$path);
        }
        $res = httpsPost($url, $data);
        $res = json_decode($res, true);
        if(isset($res['media_id'])){ //
            $res['created_at'] = intval($res['created_at'])+3600*23*3;
            Db::name('users') -> where(['openid'=>$openid]) -> update(['temp_material'=>json_encode($res)]); 
            return $res; //数组形势
        }else{
            return false;
        }  
    }

    # +------------------------------------------------------------------------------------
    # | 获取临时素材
    # | 
    # +------------------------------------------------------------------------------------
    public function getTempMaterial($openid){
        $user = Db::name('users') -> where(['openid'=>$openid, 'subscribe'=>1,'status'=>1]) -> find();

        if(empty($user)){
            return ['status'=>false, 'content'=>'账号尚未关注或已锁定']; exit;
        }

        if(empty($user['spread_img'])){
            return ['status'=>false, 'content'=>'尚未设置推广图片']; exit;
        }

        if(empty($user['temp_material'])){
            # 上传最新的图片
            $media= $this->uploadTempMaterial($openid, $_SERVER['DOCUMENT_ROOT'].$user['spread_img']);
            if($media != false){
                return ['status'=>true, 'media_id'=>$media['media_id']]; exit;
            }
        }

        $material = json_decode($user['temp_material'], true);
		
        if($material['created_at'] < time()){

            # 上传最新的图片
            $media = $this->uploadTempMaterial($openid, $_SERVER['DOCUMENT_ROOT'].$user['spread_img']);
            if($media != false){
                return ['status'=>true, 'media_id'=>$media['media_id']]; exit;
            }
        }else{

            return ['status'=>true, 'media_id'=>$material['media_id']]; exit;
        }
    }

    // public function getMediaId($content){
	// 	$find = M('config') -> where(array('remark'=>"$content")) -> find();
	// 	if(empty($find)){
	// 		return "";
	// 	}else{
	// 		$media_id = $find['tvalue'];
	// 		if(empty($media_id)){
	// 			$count_json = httpsGet(SUCAI_COUNT.$this->access_token());
	// 			$count_arr = json_decode($count_json, true);//素材总数
	// 			$img_count = $count_arr['image_count'];

	// 			$post_arr = array("type"=>"image", "offset"=>0, "count"=>$img_count);
	// 			$post_json = json_encode($post_arr);
	// 			$url = SUCAI_LIST.$this->access_token();
	// 			$list_json = httpsPost($url, $post_json);
	// 			$list_arr = json_decode($list_json, true);
	// 			\Think\Log::write(var_export($list_arr), true);//写入日志
	// 			foreach($list_arr as $v){
	// 				if($v['name'] === 'share.jpg' ){
	// 					$save_media_id = M("config") -> where(array("name"=>"SHARE_ID")) -> setField("tvalue", $v['media_id']);
	// 					if($save_media_id){
	// 						return $v['media_id'];
	// 						break;
	// 					}
	// 				}
	// 			}
	// 		}else{
	// 			return $media_id;
	// 		}
	// 	}
	// }



}// 类结束


