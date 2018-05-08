<?php
namespace app\index\controller;
// use app\model\model\Region;

use think\Controller;
use think\Config;
use think\Session;
use think\Db;
use think\Request;
use think\cache\driver\Redis;

class Login extends controller
{
    public function index(){

        // $this->assign('address', ['id'=>1]);
        $this->assign('footer', ['status'=>false]);
        $this->assign('config', ['page_title'=>'用户登录']);
        return $this->fetch();
        
    }


    public function login(){
        $wxcode = '';
        $field = "id, name, password, mobile";
        if(empty($wxcode)){ // 账号密码登录
            $login['account'] = input('account', '', 'htmlspecialchars,trim');
            $login['password'] = input('password', '', 'htmlspecialchars,trim');
            if(empty($login['account'])){
                return '账号为空'; die;
            }
            if(empty($login['password'])){
                return '密码为空'; die;
            }

            $user = Db::name('users') -> where('name|mobile', '=', $login['account']) ->field($field) -> find();

            if($user['password'] === md5($login['password'])){ // 密码一致，登录成功
                Session::set(Config::get('USER_ID'), $user['id']);  // 测试

                return $this->redirect('/index/index/index'); // 跳转到首页

            }
            

            
        }else{ //微信登录

        }
        
        
    }

    # |================================================
    # | $openid 微信登录
    # | $login  账号密码登录
    # |================================================
    public function checkUser($openid, $login){
        if(empty($openid)){ // 账号密码登录

        }else{ // 微信登录  

        }
        
    }

}
