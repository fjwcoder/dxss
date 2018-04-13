<?php
namespace app\index\controller;
use app\model\model\Region;

use think\Controller;
use think\Config;
use think\Session;
use think\Db;
use think\Request;
use think\cache\driver\Redis;

class Address extends controller
{
    public function index(){

        $this->assign('address', ['id'=>1]);
        $this->assign('footer', ['status'=>false]);
        return $this->fetch();
        
    }

    //获取用户的收货地址
    public function getAddress(){
        
    }

    public function add(){
        // 1. 获取省份
        $province = $this->getProvince();

        // 2. 获取城市
        $currentP = current($province); // 取当前数组值
        $pid = $currentP['id'];
        $city = $this->getCity($pid, true);

        // 3. 获取区县
        $currentC = current($city);
        $cid = $currentC['id'];
        $area = $this->getArea($cid, true);
        
        $currentA = current($area);
        $aid = $currentA['id'];

        $this->assign('province', $province);
        $this->assign('city', $city);
        $this->assign('area', $area);
        $this->assign('community', $this->getCommunity($aid, true));
        $this->assign('station', $this->getStation($aid, true));
        
        $this->assign('footer', ['status'=>false]);
        return $this->fetch();
        
    }

    // 保存新增的地址
    public function addSave(){
        $address['name'] = input('name', '', 'htmlspecialchars,trim');
        $address['mobile'] = input('mobile', '', 'htmlspecialchars,trim');
        $address['province'] = input('province', 0, 'intval');
        $address['city'] = input('city', 0, 'intval');
        $address['area'] = input('area', 0, 'intval');
        $address['address'] = input('address', '', 'htmlspecialchars,trim');
        $address['community'] = input('community', '', 'htmlspecialchars,trim');
        $address['station'] = input('station', '', 'htmlspecialchars,trim');

        if(!isMobileNumber($address['mobile'])){
            return '手机号格式错误'; die;
        }

        $province = $this->getProvince();
        $city = $this->getCity($address['province'], true);
        $area = $this->getArea($address['city'], true);
        
        $address['province'] = $province[$address['province']]['title'];
        $address['city'] = $city[$address['city']]['title'];
        $address['area'] = $area[$address['area']]['title'];
        $address['userid'] = 1;//Session::get(Config::get('USER_ID'));
        $address['type'] = 0;
        $add = Db::name('user_address') -> insert($address);
        if($add){
            return $this->redirect('/index/address/index');
        }


    }

    public function edit(){

        $this->assign('footer', ['status'=>false]);
        return $this->fetch();
        
    }

    public function editSave(){

    }

    // 获取省份
    public function getProvince(){
        $province = cache('REGION_PROVINCE');
        if($province){
            return $province;
        }else{

            $province = Db::name('region') -> where(['type'=>1,'active'=>1]) ->order('id') -> column('id, pid, name, title, type, data, link');
            // $province = Db::name('region') -> where(['type'=>1,'active'=>1]) ->order('id') -> select();
            if($province){
                // cache('REGION_PROVINCE', $province);
                return $province;
            }else{
                return [];
            }
            
        }
    }

    // $pid 上级ID
    // $controller true: 控制器调用，返回数组； false: 前台调用，返回json
    public function getCity($pid=0, $controller=false){
        if($pid === 0){
            $pid = input('pid', 0, 'intval');
            
        }

        if($pid === 0){
            return 'province参数错误'; die;
        }

        $city = cache('REGION_CITY');
        if(empty($city)){

            $city = Db::name('region') -> where(['pid'=>$pid, 'type'=>2,'active'=>1]) -> column('id, pid, name, title,type');
            if($city){
                // cache('REGION_CITY', $city);
            }
        }
        if($controller){ // true: 控制器内调用； false: ajax调用
            return $city;
        }else{
            return json_encode($city, JSON_UNESCAPED_UNICODE);
            
        }

    }

    public function getArea($pid=0, $controller=false){
        if($pid === 0){
            $pid = input('pid', 0, 'intval');
        }

        if($pid === 0){
            return 'city参数错误'; die;
        }

        $area = cache('REGION_AREA');
        if(empty($area)){
            $area = Db::name('region') -> where(['pid'=>$pid, 'type'=>3,'active'=>1]) -> column('id, pid, title');
            if($area){
                // cache('REGION_AREA', $area);
            }
        }
        if($controller){ // true: 控制器内调用； false: ajax调用
            return $area;
        }else{
            return json_encode($area, JSON_UNESCAPED_UNICODE);
        }
    }


    public function getCommunity($pid=0, $controller=false){
        if($pid === 0){
            $pid = input('pid', 0, 'intval');
        }

        if($pid === 0){
            return 'city参数错误'; die;
        }

        $community = cache('REGION_COMMUNITY');
        if(empty($community)){
            $community = Db::name('community') -> where(['pid'=>$pid, 'status'=>1]) -> column('id, pid, title');
            if($community){
                // cache('REGION_COMMUNITY', $community);
            }
        }
        if($controller){ // true: 控制器内调用； false: ajax调用
            return $community;
        }else{
            $station = $this->getStation($pid, true);
            return json_encode(['community'=>$community, 'station'=>$station], JSON_UNESCAPED_UNICODE);
        }
    }

    public function getStation($pid, $controller=false){
        if($pid === 0){
            $pid = input('pid', 0, 'intval');
        }

        if($pid === 0){
            return 'city参数错误'; die;
        }

        $station = cache('REGION_STATION');
        if(empty($station)){
            $station = Db::name('station') -> where(['pid'=>$pid, 'status'=>1]) -> column('id, pid, title');
            if($station){
                // cache('REGION_STATION', $community);
            }
        }
        if($controller){ // true: 控制器内调用； false: ajax调用
            return $station;
        }else{
            return json_encode($station, JSON_UNESCAPED_UNICODE);
        }
    }

}
