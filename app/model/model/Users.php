<?php
namespace app\model\model;
use think\Db;
use think\Model;

class Users extends Model
{

    //自定义初始化
    protected function initialize()
    {
        //需要调用`Model`的`initialize`方法
        parent::initialize();

        //TODO:自定义的初始化
        //初始化数据表名称，通常自动获取不需设置
        $this->table = 'dxss_users';
        // $this->field = $this->db() ->getTableInfo('', 'fields');

        // //初始化数据表字段类型
        // $this->type = $this->db()->getTableInfo('', 'type'); 

        // //初始化数据表主键
        // $this->pk = $this->db()->getTableInfo('', 'pk');     
    }

    /**
     * 获取用户的全部信息、数据
     * 
     */
    public function getInfoData($uid){
        $result = Db::name('users') ->alias('a')
        ->join('user_data b', 'a.id=b.id', 'LEFT')
        ->field('a.id, a.pid, a.id_list, a.name, a.realname, a.sex, a.mobile, a.level, 
        a.qq, a.email, a.id_num, a.id_card_path, a.birthday, a.headimgurl, a.regtime, 
        a.logintime, a.loginip, a.status, a.remark, a.subscribe, a.openid, a.nickname, 
        a.language, a.city, a.province, a.country, a.subscribe_time, a.unionid, a.groupid, 
        a.tagid_list, qr_code, qr_seconds, qr_ticket,
        
        b.point, b.balance, b.frozen') 
        -> where(['a.id'=>$uid]) -> find();
        return $result;
    }

    /**
     * 单独查询用户信息
     */
    public function getUserInfo($uid){
        //1.定义闭包函数  use($uid) 传参
        $closure = function ($query)use($uid){
            // $query -> field(['password', 'pay_code','salt',], true)  
            $query -> field(['','',], true)  
            //设置查询字段   
            -> where(['id'=>$uid]);      
            //设置查询主键  
        };      
        //2.执行查询,并获取原始数据  
        $result = Users::get($closure) -> getData();          
        return $result;
    }

    /**
     * 单独查询用户数据
     */
    public function getUserData($uid, $openid=''){
        $result = Db::name('user_data') -> where(['id'=>$uid]) -> find();
        return $result;
    }

}

?>