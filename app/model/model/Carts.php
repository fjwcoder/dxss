<?php
namespace app\model\model;
use app\model\model\UserAddress;
use app\model\model\GoodsInfo;
use app\model\model\GoodsSpec;
use think\Db;
use think\Model;

class Carts extends Model
{

    //自定义初始化
    protected function initialize()
    {
        //需要调用`Model`的`initialize`方法
        parent::initialize();

        //TODO:自定义的初始化
        //初始化数据表名称，通常自动获取不需设置
        $this->table = 'dxss_carts';
        // $this->field = $this->db() ->getTableInfo('', 'fields');

        // //初始化数据表字段类型
        // $this->type = $this->db()->getTableInfo('', 'type'); 

        // //初始化数据表主键
        // $this->pk = $this->db()->getTableInfo('', 'pk');     
    }


    /**
     * 该方法用到的地方：1.购物车index方法  2.订单预览页面preview  
     * $isAddr : 是否查询地址
     * $list : 购物车ID链
     */
    public function getCartGoods($uid, $isAddr=false, $list=''){
        $where = " buyer_id=$uid and status=1 ";
        if(!empty($list)){
            $where .= " and id in ($list)";
        }
        // $cart = [];
        // $result = Carts::where($where) -> order('addtime desc') -> select();
        // foreach($result as $data){
        //     $cart[] = $data->getData();
        // }
        // return $cart;
        $cart = Carts::where($where) -> order('addtime desc') -> column('id, buyer_id, parent_id, 
            seller_id, goods_id, price, spec, num, addtime, status, addr_id');
        if($cart){
            if($isAddr){
                $model = new UserAddress();
                $allAddr = $model->getAddr();
            }

            $goods_list = [];
            $spec_list = [];
            foreach($cart as $k=>$v){
                $goods_list[] = $v['goods_id'];
                $spec_list[] = $v['spec'];
            }
            $goods_list = array_unique($goods_list); // 数组去重
            $goods_list = implode(",",$goods_list); // 数组转字符串
            $spec_list = array_unique($spec_list);
            $spec_list = implode(",",$spec_list);
            
            $model = new GoodsInfo();
            $goods = $model->getGoods($goods_list);
            $model = new GoodsSpec();
            $specs = $model->getSpec($spec_list);
            foreach($cart as $k=>$v){
                $cart[$k]['goods'] = $goods[$v['goods_id']];
                $cart[$k]['spec'] = $specs[$v['spec']];
                if($isAddr){
                    $cart[$k]['address'] = $allAddr[$v['addr_id']];
                }
                
            }

            return $cart;

        }else{
            return false;
        }
    }


}

?>