<?php
namespace app\index\controller;
// use app\model\model\Users;
use app\index\controller\Common as Common;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;
use think\Request;
use think\cache\driver\Redis;

class User extends Common
{
    public function index(){
        
        $this->assign('user', []);
        $this->assign('footer', ['status'=>true, 'id'=>3]);
        return $this->fetch();
        
    }


    

}
