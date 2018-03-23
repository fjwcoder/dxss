<?php
# ==================================================
# 该模块的公共方法，主要是验证权限
# create by fjw in 18.3.8 
# ==================================================
namespace app\index\controller;
// use app\model\model\Users;

use think\Controller;
use think\Config;
use think\Session;
use think\Db;
use think\Request;
use think\cache\driver\Redis;

class Common extends controller
{
    protected function _initialize(){
        
        // 定位
        // if(empty(session('LOCATION'))){
        //     $gaode = new Gaode();
        //     $gaode->IPLocation();
        // }
        Session::set(Config::get('USER_ID'), 1); // 测试账户
        #是否登录
        if( Session::get(Config::get('USER_ID')) ){
            //登陆后，每次跳转，都设置一下session，保持登录状态
            Session::set(Config::get('USER_ID'), Session::get(Config::get('USER_ID')));
            
            // $this->assign('cookie', decodecookie('user'));
        }else{
            session(null);
            return $this->redirect('/index/login/index');
            exit;
        }
    }



    

}
