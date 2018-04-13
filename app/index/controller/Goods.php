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
        $gid = input('gid', 1, 'intval');
        if($gid === 0){
            return '商品ID错误'; die;
        }
        // 1. 获取商品详情信息
        $goods = $this->getGoodsDetail($gid);
        // return dump($goods);
        $this->assign('goods', $goods);
        // 2. 获取默认收货地址
        

        // 3. 计算预计送达时间


        $this->assign('footer', ['status'=>false]);
        return $this->fetch();
    }

    # |=================================================================
    # | 
    # |
    # |
    # |
    # |=================================================================
    public function getGoodsDetail($gid=0){

        if($gid===0){
            return ['status'=>false];
        }else{
            // 1. 全部信息 + detail
            $goods = Db::name('goods') -> alias('a') 
                ->join('goods_detail b', 'a.id=b.gid', 'LEFT')
                ->field('a.id, a.catid, a.name, a.sub_name, a.key_words, a.brand, a.service, a.promotion, 
                    a.price, a.amount, a.sell_amount, a.img, a.weight, a.free_shipping, a.shipping_money, 
                    a.status, a.description, b.detail ')
                ->where(['a.status'=>1, 'a.id'=>$gid, 'b.gid'=>$gid])
                -> find();

            // 2. 图片信息
            $pictures = Db::name('goods_picture') -> where(['gid'=>$gid]) -> select();
            // 3. 规格信息
            $specs = Db::name('goods_spec') -> where(['gid'=>$gid]) ->field('gid, spec, num, price') -> select();
            
            $goods['pics'] = $pictures;
            $goods['specs'] = $specs;
            
            return ['status'=>true, 'goods'=>$goods];
        }
    }
}
