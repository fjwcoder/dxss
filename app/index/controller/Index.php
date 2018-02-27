<?php
namespace app\index\controller;
use app\common\controller\Common; 
use think\Controller;
use think\Config;
use think\Session;
use think\Db;
use think\Request;
use think\cache\driver\Redis;

class Index extends controller
{
    public function index(){


        return $this->fetch();
        

    }
}
