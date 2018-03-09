<?php
namespace app\index\controller;
// use app\model\model\Users;

use think\Controller;
use think\Config;
use think\Session;
use think\Db;
use think\Request;
use think\cache\driver\Redis;

class Cart extends controller
{
    public function index(){

        $this->assign('cart', ['id'=>1]);
        $this->assign('footer', false);
        return $this->fetch();
        
    }

    public function orderPreview(){
        dump(request()->post());
        die;
    }



    

}
