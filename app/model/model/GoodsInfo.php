<?php
namespace app\model\model;
use think\Db;
use think\Model;

class GoodsInfo extends Model
{

    //自定义初始化
    protected function initialize()
    {
        //需要调用`Model`的`initialize`方法
        parent::initialize();

        //TODO:自定义的初始化
        //初始化数据表名称，通常自动获取不需设置
        $this->table = 'dxss_goods';
        // $this->field = $this->db() ->getTableInfo('', 'fields');

        // //初始化数据表字段类型
        // $this->type = $this->db()->getTableInfo('', 'type'); 

        // //初始化数据表主键
        // $this->pk = $this->db()->getTableInfo('', 'pk');     
    }

    # |=================================================================
    # | 获取单个商品的详细信息 
    # | 
    # | $nopic: 是否不查询图片
    # |
    # |=================================================================
    public function getGoodsDetail($gid=0, $nopic=false){

        if($gid===0){
            return ['status'=>false];
        }else{
            if($nopic){ // 不查询图片
                $goods = Db::name('goods_info') -> where(['status'=>1, 'id'=>$gid]) -> find();
                $specs = Db::name('goods_spec') -> where(['gid'=>$gid]) ->field('id, gid, spec, num, price') -> select();
                $goods['specs'] = $specs;
            }else{ // 全部查询
                // 1. 全部信息 + detail
                $goods = Db::name('goods_info') -> alias('a') 
                ->join('goods_detail b', 'a.id=b.gid', 'LEFT')
                ->field('a.id, a.cat_id, a.name, a.sub_name, a.key_words, a.brand, a.service, a.promotion, 
                    a.price, a.amount, a.sell_amount, a.img, a.weight, a.free_shipping, a.shipping_money, 
                    a.status, a.description, b.detail ')
                ->where(['a.status'=>1, 'a.id'=>$gid, 'b.gid'=>$gid])
                -> find();
                

                // 2. 图片信息
                $pictures = Db::name('goods_picture') -> where(['gid'=>$gid]) -> select();
                // 3. 规格信息
                $specs = Db::name('goods_spec') -> where(['gid'=>$gid]) ->field('id, gid, spec, num, price, weight') -> select();
                
                $goods['pics'] = $pictures;
                $goods['specs'] = $specs;
            }
            
            
            return ['status'=>true, 'goods'=>$goods];
        }
    }

    /**
     * 获取多个商品的粗略信息
     * 信息、规格
     * 购物车、订单、订单预览页/详情页 等用到
     */
    public function getGoods($goods_list, $isspec=false,$ispic=false){
        $result = GoodsInfo::where('id in ('.$goods_list.')') -> column('id, user_id, name, sub_name, 
            price, img, weight, description');
        if($isspec){
            $specs = Db::name('goods_spec') -> where('gid in ('.$goods_list.')') -> select();
            foreach($specs as $v){
                $result[$v['gid']]['spec'][] = $v;
            }
        }
        
        if($ispic){ // 把图片查出来

        }
        return $result;

    }

}

?>