<?php
namespace app\model\model;
use think\Db;
use think\Model;

class GoodsSpec extends Model
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


    /**
     * 获取多个商品的粗略信息
     * 信息、规格
     * 购物车、订单、订单预览页/详情页 等用到
     */
    public function getSpec($spec_list){
        $result = GoodsSpec::where('id in ('.$spec_list.')') -> column('id, gid, spec, num, 
            price, weight');
        
        return $result;

    }

}

?>