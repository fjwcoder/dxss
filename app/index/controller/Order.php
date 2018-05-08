<?php
namespace app\index\controller;
use app\model\model\Carts;
use app\model\model\UserAddress;
use app\index\controller\Common as Common;
// use app\index\controller\Cart as Cart;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;
use think\Request;
use think\cache\driver\Redis;

class Order extends Common
{
    public function index(){
        
        $this->assign('order', []);
        $this->assign('footer', ['status'=>false]);
        $this->assign('config', ['page_title'=>'我的订单']);
        return $this->fetch();
        
    }

    public function create(){
        $post = request()->Post();

        // $post = array(5) {
        //     ["address"] => string(2) "58"
        //     ["payment"] => string(1) "1"
        //     ["prior_balance"] => string(1) "1"
        //     ["prior_point"] => string(1) "1"
        //     ["cartList"] => array(2) {
        //       [0] => string(3) "153"
        //       [1] => string(3) "152"
        //     }
        //   }
        
    }

    public function preview(){
        $list = request()->Post();
        $uid = session(config('USER_ID'));
        $list = $list['cartList'];
        $list = implode(',', $list);
        $model = new Carts();
        $list = $model->getCartGoods($uid, false, $list);
        $this->assign('list', $list);

        $model = new UserAddress();
        $allAddr = $model->getAddress();
        if(empty($allAddr)){
            $this->assign('address', false);
        }else{
            $this->assign('address', $allAddr);
        }
        
        $total = ['weight'=>0, 'money'=>0.00];
        //计算商品 总重量 总价格
        foreach($list as $k=>$v){
            $total['weight'] += $v['num']*$v['spec']['weight'];
            $total['money'] += $v['num']*$v['spec']['price'];
        }
        $total['money'] = sprintf("%.2f",$total['money']);
        $this->assign('total', $total);


        $this->assign('footer', ['status'=>false]);
        $this->assign('config', ['page_title'=>'订单预览']);
        return $this->fetch();
    }

    
    public function detail(){
        
        $this->assign('footer', ['status'=>false]);
        $this->assign('config', ['page_title'=>'订单详情']);
        return $this->fetch();
    }

    

    

}
