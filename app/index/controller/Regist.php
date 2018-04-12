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



}
