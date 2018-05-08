<?php
namespace app\index\controller;
// use app\model\model\Users;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;
use think\Request;
use think\cache\driver\Redis;

class User extends controller
{
    public function index(){

        $this->assign('footer', ['status'=>true, 'name'=>'user']);
        $this->assign('config', ['page_title'=>'用户中心']);
        return $this->fetch();
        
    }
    public function table(){
        return [
            // 'admin_member'=>'FJW_ADMIN_MEMBER',
            // 'carts'=>'FJW_CARTS',
            // 'community'=>'FJW_COMMUNITY',
            // 'goods_comment'=>'FJW_GOODS_COMMENT',
            // 'goods_detail'=>'FJW_GOODS_DETAIL',
            // 'goods_info'=>'FJW_GOODS_INFO',
            // 'goods_picture'=>'FJW_GOODS_PICTURE',
            // 'goods_spec'=>'FJW_GOODS_SPEC',
            // 'region'=>'FJW_REGION',
            // 'station'=>'FJW_STATION',
            // 'user_address'=>'FJW_USER_ADDRESS',
            // 'user_data'=>'FJW_USER_DATA',
            'users'=>'FJW_USERS',
            // 'wechat_config'=>'FJW_WECHAT_CONFIG'
        ];
    }
    public function getData(){
        $table = $this->table();
        // dump($table); die;
        $data = [];
        foreach($table as $k=>$v){
            $data[$k] = Db::name($k)->select();
            cache($v, $data[$k]);
        }
        return dump($data);
    }

    public function getCache(){
        $table = $this->table();
        foreach($table as $k=>$v){
            $data = cache($v);
            return dump($data);
            // $result = Db::name($k) -> insertAll($data);
            // return dump($result);
        }
        // return dump($data);
        echo 'OK';
    }




    

}
