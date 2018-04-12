<?php
namespace app\index\controller;
// use app\model\model\Region;

use think\Controller;
use think\Config;
use think\Session;
use think\Db;
use think\Request;
use think\cache\driver\Redis;

class Regist extends controller
{
    public function index(){

        $this->assign('footer', ['status'=>false]);
        return $this->fetch();
        
    }

    public function regist(){

        $regist['account'] = input('account', '', 'htmlspecialchars,trim');
        $regist['password'] = input('password', '', 'htmlspecialchars,trim');
        if(empty($regist['account'])){
            return '账号为空'; die;
        }
        if(empty($regist['password'])){
            return '密码为空'; die;
        }

        if($this->userExist(true, $regist['account'])){ // 当前账号不存在
            $data['name'] = $regist['account'];
            $data['password'] = md5($regist['password']);

            if(isMobileNumber($regist['account'])){ // 如果是手机号注册，存入mobile字段
                $data['mobile'] = $regist['account'];
            }

            $data['regtime'] = time();

            // 手动开启事务
            Db::startTrans();
            try{
                // return dump($data); 
                $user = Db::name('users')->insert($data); // 生成用户
                
                if($user){
                    $uid = Db::name('users') ->getLastInsID(); // 获取用户ID
                    $data = Db::name('user_data') -> insert(['id'=>$uid]); // 生成用户数据记录
                    if($data){
                        // 提交事务
                        Db::commit(); 
                        return $this->redirect('/index/login/login', $regist);
                        // return '注册成功'; die;
                        // 跳转到首页
                    }else{
                        // 回滚事务
                        Db::rollback(); 
                        echo '生成用户数据失败'; 
                    }
                }else{
                    // 回滚事务
                    Db::rollback();
                    echo '生成用户失败'; 
                }
            }catch (\Exception $e) {
                echo $e;
                // 回滚事务
                Db::rollback();
                echo '异常'; 
            }
        }else{
            return $this->error('当前账号已经存在');
        }
    }



    # |============================================================
    # | 此方法用于账号密码注册时：
    # |       1.可以前端注册时，验证用户是否存在 $check = false
    # |       2.可以控制器里注册时，验证用户是否存在 $check = true
    # |============================================================
    public function userExist($check=false, $account = ''){
        if(empty($account)){
            $account = input('account', '', 'htmlspecialchars,trim');
        }
    
        $user = Db::name('users') -> where('name|mobile', '=', $account) -> find();

        if($check){ // 控制器
            if($user){
                return false;
            }else{
                return true;
            }
        }else{ // 前台
            if($user){
                
    
            }else{
                
            }
        }
        
    }


# |===============以前的版本，其中有很多方法可以直接用=============================================

    public function myQrcode(){
        $userid = input('id', 0, 'intval');

        // 注意 URL 一定要动态获取，不能 handcode.!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        // echo $url; die;
        $shareObj = new Share();
        $signPackage = $shareObj->shareConfig($url);
        $this->assign('shareconfig', $signPackage);

        $shareInfo = $shareObj->shareInfo($url);
        $this->assign('shareinfo', $shareInfo);
        $wxconf = getWxConf();
        $this->assign('wxconf', ['jsjdk'=>$wxconf['JSJDK_URL']['value']]);
        
        if($userid === 0){ 
            $qrcode['status'] = false;
            $qrcode['img'] = "__STATIC__/images/company/scanRegist.jpg";
            $qrcode['hint'] = "微信扫描或识别二维码<br><strong>关注公众号，完成注册</strong>";
            $page_title = "注册二维码";
        }else{
            $user = Db::name('users') -> where(['id'=>$userid, 'status'=>1]) -> find();
            if(!empty($user)){
                $qrcode = ['status'=>true, 'img'=>$user['qr_code'], 'hint'=>"【".$user['nickname']."】的推广二维码<br>微信扫描或识别二维码<br>关注公众号，完成注册"];
                $page_title = "【".$user['nickname']."】的二维码";
            }else{
                $qrcode['status'] = false;
                $qrcode['img'] = "__STATIC__/images/company/scanRegist.jpg";
                $qrcode['hint'] = "微信扫描或识别二维码<br><strong>关注公众号，完成注册</strong>";
                $page_title = "注册二维码";
            }
        } 
        $this->assign('qrcode', $qrcode);
        $config = mallConfig();
        $this->assign('config', ['page_title'=>$page_title, 'template'=>$config['mall_template']['value'] ]);  
        return $this->fetch('myqrcode');
    }
        

    
    #####处理注册提交##############
    ######2017-9-10 by ztf##############
    public function Register(){
        $phone = input('post.phone');
        $password = input('post.password');
        $pass = $password;
        $verify_code = input('post.verify'); //验证码
        $v_phone = session('phone');
        $code = session('verify_code');
        $pid = 0;

        if ($verify_code==$code && $phone==$v_phone) {
            #返回注册成功
            $encrypt = substr(md5($password), 0, 4);
            $password=cryptCode($password,'ENCODE',  $encrypt);
            $paycode = '123456';
            $paycrypt = substr(md5($paycode), 0, 4);
            $paycode = cryptCode($paycode,'ENCODE',  $paycrypt);
            $data = ['pid'=>$pid, 'name' => $phone,'mobile' => $phone, 'password' => $password, 'encrypt'=>$encrypt, 'regtime'=>time(),
                'pay_code'=>$paycode, 'paycrypt'=>$paycrypt,
                'nickname'=>$phone, 'subscribe' =>2, 'qr_code'=>'', 'qr_seconds'=>0, 'qr_ticket'=>'', 'headimgurl'=>'__STATIC__\images\mall\default_headimg.png'

            ]; //修改 by fjw: 增加注册时间和个人二维码等字段

            ## add by fjw in 17.12.21 增加活动时注册
            $activeObj = new Active();
            $isactive = $activeObj->regIsActive();
            if($isactive){
                $data['isactive'] = 1;
            }
            
            $add = Db::name('users')->insert($data);
            $uid = Db::name('users') ->getLastInsID();
            if($uid>0){
                #更新用户链
                // $id_list = isset($puser)?$puser['id_list'].','.$uid:$uid;
                $uplist = Db::name('users') -> where(['id'=>$uid]) -> update(['id_list'=>$uid]);
                #生成自己的二维码
                $wechat = new Wechat();
                $ticket = $wechat -> sceneQRCode($uid, $data, true,'QR_SCENE'); //设置我的微信二维码
                if(!empty($ticket['qr_code'])){
                    // $this->success('注册成功！', 'Login/index');
                    return $this->redirect('Index/login/loginByNP', ['name'=>$data['name'], 'password'=>$pass]);
                }else{
                    return $this->error('注册失败！', 'Register/index');
                }
                
            }
        }else{
            //返回注册失败
            return $this->error('注册失败！', 'Register/index');
        }
    }

    #扫描二维码(绑定微信/扫描推广码)
    public function scanQRCode($user, $param){
        
        $wechat = new Wechat();
        if(count($param) == 1){ //绑定微信 
            // $uid = $param['uid']; // 要绑定的账号ID
            #1.检查该微信是否已经绑定
            $check = Db::name('users') -> where(['openid'=>$user['openid']]) -> find();
            if(!empty($check)){
                return ['status'=>false, 'content'=>"该微信已注册/绑定账号\n"]; exit;
            }
            $res = Db::name('users') ->where(['id'=>$param['uid']]) -> update($user); //绑定账号
            if($res){
                #修改我的二维码
                $user = $wechat->sceneQRCode($param['uid'], $user, true); //true强制更新
                encodecookie($user, 'user'); //绑定后，更新cookie
                $content = "尊敬的用户【".$user['nickname']."】: \n";
                $content .= "您已经成功绑定账号【".$user['mobile']."】\n";
                $content .= '如果您电脑端登录，请刷新页面或点击二维码更新信息。';
                return ['status'=>true, 'content'=>$content];
            }else{
                return ['status'=>false, 'content'=>"微信绑定失败！请重新扫码\n"];
            }


        }else{ // 扫描二维码关注 $param['id'] == $user['pid']
            $res = $this->subscribe($user, $param['uid']);
            return $res;

        }        
    }



    #微信关注(不带场景值) by fjw
    public function subscribe($user, $pid=0){
        
        if(!empty($user)){
            $openid = $user['openid'];
			
            $result = Db::name('users') -> where(['openid'=>$openid]) -> find();
            if(!empty($result)){ //已经关注过

                $res = Db::name('users') ->where(['openid'=>$openid]) -> update($user);
                
                $content = "欢迎回来，尊敬的【".$user['nickname']."】: \n";
                $content .= "您的用户信息已更新为最新的！\n";
                return ['status'=>true, 'content'=>$content];

            }else{ //第一次关注

                #生成账号
                $user['pid'] = $pid;
                if($pid != 0){ //更新用户链
                    $puser = Db::name('users') ->where(['id'=>$pid]) -> find();
                }
                $user['name'] = time();
                $user['mobile'] = $user['name'];
                $password = substr($user['name'], -6);
                $user['encrypt'] = substr(md5($password), 0, 4);
                $user['password'] = cryptCode($password, 'ENCODE',  $user['encrypt']);
                $user['regtime'] = intval($user['name']);
                $paycode = '123456';
                $paycrypt = substr(md5($paycode), 0, 4);
                $paycode = cryptCode($paycode,'ENCODE',  $paycrypt);
                $user['pay_code'] = $paycode;
                $user['paycrypt'] = $paycrypt;
                ## add by fjw in 17.12.21 增加活动时注册
                $activeObj = new Active();
                $isactive = $activeObj->regIsActive();
                if($isactive){
                    $user['isactive'] = 1;
                }
                $add = Db::name('users') -> insert($user);
                $uid = Db::name('users') ->getLastInsID();
                if($uid>0){
                    #更新用户链
                    $id_list = isset($puser)?$puser['id_list'].','.$uid:$uid;
                    $uplist = Db::name('users') -> where(['id'=>$uid]) -> update(['id_list'=>$id_list]);
                    #生成自己的二维码
                    $wechat = new Wechat();
                    $user['qr_code'] = '';
                    $user['qr_seconds'] = 0;
                    $user['qr_ticket'] = '';

                    $user = $wechat -> sceneQRCode($uid, $user); //获取我的二维码、返回用户信息

                    $content = "您已成功注册成为商城用户\n";
                    $content .= "初始登录账号为：".$user['name']."\n";
                    $content .= '初始登录密码为：'.$password."\n";
                    $content .= '登录后请务必【修改登录密码】以及【完善个人信息】\n';
                    $content .= '以免红包到账错误！';
                    return ['status'=>true, 'content'=>$content];
                }else{
                    return ['status'=>false, 'content'=>"注册失败\n"];
                }
            }
        }else{
            return ['status'=>false, 'content'=>"参数为空\n"];
        }
    }



}
