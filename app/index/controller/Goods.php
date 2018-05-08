<?php
namespace app\index\controller;
use app\model\model\UserAddress;
use app\model\model\GoodsInfo;
use app\index\controller\Time as Time;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;
use think\Request;
use think\cache\driver\Redis;

class Goods extends controller
{
    
    /**
     * 未完成：
     * 1. 选择不同的地址后，预计送达时间要修改
     */
    public function index(){
        $user_id = 1;
        $gid = input('gid', 1, 'intval');
        if($gid === 0){
            return '商品ID错误'; die;
        }
        // 1. 获取商品详情信息
        $model = new GoodsInfo();
        $goods = $model->getGoodsDetail($gid);
        // dump($goods); die;
        $this->assign('goods', $goods['goods']);
        $this->assign('spec_num', count($goods['goods']['specs'])); // 计算规格数量

        // 2. 获取默认收货地址
        $model = new UserAddress();
        $allAddr = $model->getAddress(); // 不传参的话，查出全部的收货地址
        // dump($allAddr); die;
        $this->assign('address', $allAddr);
        // 3. 计算预计送达时间
        $timeObj = new Time();
        $shipping_time = $timeObj->calculate();
        $this->assign('shipping_time', $shipping_time);

        $this->assign('footer', ['status'=>false]);
        $this->assign('config', ['page_title'=>'商品详情']);
        return $this->fetch();
    }

    
}
