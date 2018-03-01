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

        $this->assign('address', ['id'=>1]);
        $this->assign('footer', false);
        return $this->fetch();
        
    }

    public function login(){

        
        
    }


}
