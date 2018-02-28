<?php
namespace app\index\controller;
// use app\model\model\Users;

use think\Controller;
use think\Config;
use think\Session;
use think\Db;
use think\Request;
use think\cache\driver\Redis;

class Goods extends controller
{
    public function index(){

        return $this->fetch();
        
    }

}
