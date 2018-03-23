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

class Order extends Common
{
    public function index(){
        
        $this->assign('orderlist', ['id'=>1]); // 订单列表
        $this->assign('footer', ['status'=>false]); // 公共底部导航栏
        return $this->fetch();
        
    }

    public function preview(){
        
        $this->assign('footer', ['status'=>false]);
        return $this->fetch();
    }

    public function detail(){
        
        $this->assign('footer', ['status'=>false]);
        return $this->fetch();
    }

    

}
