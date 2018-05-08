<?php
namespace app\index\controller;
use app\model\model\Users;
// use app\model\model\UserAddress;
use app\model\model\GoodsInfo;
use app\model\model\GoodsSpec;
use app\model\model\Carts;

use think\Controller;
use think\Config;
use think\Session;
use think\Db;
use think\Request;
use think\cache\driver\Redis;

class Cart extends controller
{
    public function index(){
        $uid = session(config('USER_ID'));
        $cart = [];
        // 1.查询购物车内的商品
        $model = new Carts();
        $cart = $model->getCartGoods($uid, true);

        if($cart){
            // 2.查出用户信息
            // $model = new Users();
            // $users = $model->getInfoData($uid);
            $this->assign('cart', $cart);

        }else{
            $this->assign('cart', []);
        }

        
        
        $this->assign('footer', ['status'=>false]);
        $this->assign('config', ['page_title'=>'购物车']);
        return $this->fetch();
        
    }

    

    /**
     * ajax 购物车增加商品数量
     */
    public function ajaxChangeNum(){
        if(request()->isAjax()){
            
            $data['num'] = input('num', 0, 'intval');
            $data['id'] = input('id', 0, 'intval');
            if($data['id'] !== 0){
                $result = Carts::update($data);
                echo json_encode($result);
            }else{
                echo json_encode('购物车ID错误');
            }
            
        }else{
            echo json_encode(['status'=>false, 'info'=>'post error']);
        }
    }

    /**
     * 目前所有的功能都基本写完了，需要完善的地方：
     * 1.   
     * 2.第3步中，计算各种价格的，需要等有该功能以后再添加，目前只有原价
     * 3.添加失败的跳转页面
     * 
     * end 18.5.5:19:00
     */
    public function add(){
        $uid = session(config('USER_ID'));
        // 1.接收数据
        $data['goods_id'] = input('gid', 0, 'intval');
        $data['spec'] = input('spec', 0, 'intval');
        $data['num'] = input('num', 0, 'intval');
        $data['addr_id'] = 0; //input('addr', 0, 'intval'); 暂时不开放该功能
        
        // 2. 查询该商品信息
        $model = new GoodsInfo();
        $goods = $model->getGoodsDetail($data['goods_id'], true); // true 不查询图片
        $goods = $goods['goods'];
        // 3.查询价格是否有变化：原价，会员价，折扣价，批发价等等
        foreach($goods['specs'] as $k=>$v){
            if($v['id'] == $data['spec']){
                $goods['specs'] = $v;
            }
        }
        $goods['price'] = isset($goods['specs']['price'])?$goods['specs']['price']:$goods['price'];

        // 4. 查询购物车中是否存在 该商品、该卖家、该规格、该价格、该地址
        $condition = ['buyer_id'=>$uid, 'goods_id'=>$data['goods_id'], 'seller_id'=>$goods['user_id'], 
            'spec'=>$data['spec'], 'price'=>$goods['price']];// 这个先不作为开放的条件  ,'addr_id'=>$data['addr_id']

        $cart = Db::name('cart') -> where($condition) -> find();

        if($cart){ // 增加数量
            $result = Db::name('cart') -> where($condition) -> setInc('num', $data['num']);
        }else{ // 重新生成订单
            $data['parent_id'] = 0;
            $data['seller_id'] = $goods['user_id'];
            $data['buyer_id'] = $uid;
            $data['price'] = $goods['price'];
            $data['addtime'] = time();
            $data['status'] = 1;

            $result = Db::name('cart') -> insert($data);
        }

        if($result){
            return $this->redirect('/index/cart/index');
        }else{
            die('添加失败');
        }
    }




    

}
