<?php
namespace app\admin\controller;
use app\model\model\Users;

use think\Controller;
use think\Config;
use think\Session;
use think\Db;
use think\Request;
use think\cache\driver\Redis;

class Index extends controller
{
    public function index(){
        
    }

    public function auth(){
        dump(config());
    }


}
