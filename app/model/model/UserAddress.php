<?php
namespace app\model\model;

use think\Model;

class UserAddress extends Model
{

    //自定义初始化
    protected function initialize()
    {
        //需要调用`Model`的`initialize`方法
        parent::initialize();

        //TODO:自定义的初始化
        //初始化数据表名称，通常自动获取不需设置
        $this->table = 'dxss_user_address';
        // $this->field = $this->db() ->getTableInfo('', 'fields');

        // //初始化数据表字段类型
        // $this->type = $this->db()->getTableInfo('', 'type'); 

        // //初始化数据表主键
        // $this->pk = $this->db()->getTableInfo('', 'pk');     
    }

    //获取用户的收货地址
    // $id : 获取的哪一条地址
    // $default = true : 只显示默认地址
    /**
     * key   0，1，2，3
     */
    public function getAddress($default=false,$id=0){
        // if($id===0){
            // $id = input('id', 0, 'intval');
            // return false;
        // }
        // $where['userid'] = 999;
        $where['userid'] = session(config('USER_ID'));
        if($default){  // 获取默认地址
            $where['type'] = 1;
        }else{ 
            if($id !== 0){ // 获取指定地址
                $where['id'] = $id;
            }
        }
        $address = [];
        $result = UserAddress::where($where) -> order('type desc') -> select();
        
        foreach($result as $data){
            $address[] = $data->getData();
        }
        
        if($default || $id !== 0){
            return $address[0];
        }else{
            return $address;
        }

    }

    /**
     * 索引问题
     * key   以ID开始
     */

    public function getAddr($default=false,$id=0){
        // if($id===0){
            // $id = input('id', 0, 'intval');
            // return false;
        // }
        
        $where['userid'] = session(config('USER_ID'));
        if($default){  // 获取默认地址
            $where['type'] = 1;
        }else{ 
            if($id !== 0){ // 获取指定地址
                $where['id'] = $id;
            }
        }
        $result = UserAddress::where($where) -> order('type desc') -> column('id, userid, province, city, area, 
         address, community, station, name, mobile, type');

        if($default || $id !== 0){
            reset($result);
            return current($result);
        }else{
            return $result;
        }

    }
}

?>