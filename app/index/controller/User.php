<?php
namespace app\index\controller;
// use app\model\model\Users;

use think\Controller;
use think\Config;
use think\Session;
use think\Db;
use think\Request;
use think\cache\driver\Redis;

class User extends controller
{
    public function index(){

        $this->assign('footer', ['status'=>true, 'name'=>'user']);
        return $this->fetch();
        
    }




    

}
