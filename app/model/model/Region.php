<?php
namespace app\model\model;

use think\Model;

class Region extends Model
{

    //自定义初始化
    protected function initialize()
    {
        //需要调用`Model`的`initialize`方法
        parent::initialize();

        //TODO:自定义的初始化
        //初始化数据表名称，通常自动获取不需设置
        $this->table = 'dxss_region';
        // $this->field = $this->db() ->getTableInfo('', 'fields');

        // //初始化数据表字段类型
        // $this->type = $this->db()->getTableInfo('', 'type'); 

        // //初始化数据表主键
        // $this->pk = $this->db()->getTableInfo('', 'pk');     
    }
}

?>