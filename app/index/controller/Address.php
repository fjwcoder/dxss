<?php
namespace app\index\controller;
use app\model\model\UserAddress;
use app\index\controller\Common as Common;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;
use think\Request;
use think\cache\driver\Redis;

class Address extends Common
{
    public function index(){
        $address = $this->getAddress(); // 参数为空，获取全部地址
        $this->assign('address', $address);
        $this->assign('footer', ['status'=>false]);
        $this->assign('config', ['page_title'=>'收货地址']);
        return $this->fetch();
        
    }

    //获取用户的收货地址
    // $id : 获取的哪一条地址
    // $default = true : 只显示默认地址
    public function getAddress($default=false,$id=0){
        if($id===0){
            $id = input('id', 0, 'intval');
        }
        
        $address_model = new UserAddress();
        $address = $address_model->getAddress($default, $id);
        return $address;

    }

    public function setDefaultAddress($id=0){
        if(request()->isAjax()){
            $isajax = true;
            $id = input('id', 0, 'intval');
        }else{
            $isajax = false;
        }

        $uid = session(config('USER_ID'));
        $status = false; // 默认失败
        Db::startTrans();
        try{
            $refresh = Db::name('user_address')->where(['userid'=>$uid]) -> update(['type'=>0]); 
            $default = Db::name('user_address') -> where(['userid'=>$uid, 'id'=>$id]) -> update(['type'=>1]);
            if($default){
                Db::commit(); 
                $status = true;
                
            }else{
                Db::rollback(); 
            }
        }catch (\Exception $e) {
            Db::rollback();
        }
        if($status){
            if($isajax){
                echo json_encode(['status'=>true, 'id'=>$id]);
            }else{
                return $this->redirect('/index/address/index'); // 非ajax方法还没有用到
            }
            
        }

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
        $this->assign('config', ['page_title'=>'新增地址']);
        return $this->fetch();
        
    }

    // 保存新增的地址
    public function addSave(){
        $address['name'] = input('name', '', 'htmlspecialchars,trim');
        $address['mobile'] = input('mobile', '', 'htmlspecialchars,trim');
        $address['province'] = input('province', '', 'htmlspecialchars,trim');
        $address['city'] = input('city', '', 'htmlspecialchars,trim');
        $address['area'] = input('area', '', 'htmlspecialchars,trim');
        $address['address'] = input('address', '', 'htmlspecialchars,trim');
        $address['community'] = input('community', '', 'htmlspecialchars,trim');
        $address['station'] = input('station', '', 'htmlspecialchars,trim');

        if(!isMobileNumber($address['mobile'])){
            return '手机号格式错误'; die;
        }

        // $province = $this->getProvince();
        // $city = $this->getCity($address['province'], true);
        // $area = $this->getArea($address['city'], true);
        
        // $address['province'] = $province[$address['province']]['title'];
        // $address['city'] = $city[$address['city']]['title'];
        // $address['area'] = $area[$address['area']]['title'];
        $address['userid'] = Session::get(Config::get('USER_ID'));
        $address['type'] = 0;
        $add = Db::name('user_address') -> insert($address);
        if($add){
            return $this->redirect('/index/address/index');
        }


    }

    public function edit(){
        $id = input('id', 0, 'intval');
        $address = $this->getAddress(false, $id);

// return dump($address);
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


        $this->assign('address', $address);
        $this->assign('footer', ['status'=>false]);
        $this->assign('config', ['page_title'=>'编辑地址']);
        return $this->fetch();
        
    }

    public function editSave(){
        $addressid = input('addressid', 0, 'intval');

        $address['name'] = input('name', '', 'htmlspecialchars,trim');
        $address['mobile'] = input('mobile', '', 'htmlspecialchars,trim');
        $address['province'] = input('province', '', 'htmlspecialchars,trim');
        $address['city'] = input('city', '', 'htmlspecialchars,trim');
        $address['area'] = input('area', '', 'htmlspecialchars,trim');
        $address['address'] = input('address', '', 'htmlspecialchars,trim');
        $address['community'] = input('community', '', 'htmlspecialchars,trim');
        $address['station'] = input('station', '', 'htmlspecialchars,trim');

        if($addressid === 0){
            return '参数错误'; die;
        }
        if(!isMobileNumber($address['mobile'])){
            return '手机号格式错误'; die;
        }

        $update = Db::name('user_address') -> where(['id'=>$addressid]) ->update($address);
        if($update){
            return $this->redirect('/index/address/index');
        }
    }

    // 获取省份
    public function getProvince(){
        $province = cache('REGION_PROVINCE');
        if($province){
            return $province;
        }else{

            $province = Db::name('region') -> where(['type'=>1,'active'=>1]) ->order('id') -> column('title, id, pid, name, type, data, link');
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
            $title = input('title', '', 'htmlspecialchars,trim'); // 省级title
            $province = $this->getProvince();
            $pid = $province[$title]['id'];
        }

        if($pid === 0){
            return 'province参数错误'; die;
        }

        $city = cache('REGION_CITY');
        if(empty($city)){

            $city = Db::name('region') -> where(['pid'=>$pid, 'type'=>2,'active'=>1]) -> column('title, id, pid, name,type');
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
            
            $title = input('title', '', 'htmlspecialchars,trim'); // 市级的title
            $city = Db::name('region') -> where(['active'=>1, 'type'=>2, 'title'=>$title]) -> column('id');
            $pid = $city[0];
        }

        if($pid === 0){
            return 'city参数错误'; die;
        }

        $area = cache('REGION_AREA');
        if(empty($area)){
            $area = Db::name('region') -> where(['pid'=>$pid, 'type'=>3,'active'=>1]) -> column('title, id, pid');
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
            $title = input('title', '', 'htmlspecialchars,trim'); // 市级的title
            $area = Db::name('region') -> where(['active'=>1,'type'=>3, 'title'=>$title]) -> column('id');
            $pid = $area[0];
        }

        if($pid === 0){
            return 'city参数错误'; die;
        }

        $community = cache('REGION_COMMUNITY');
        if(empty($community)){
            $community = Db::name('community') -> where(['pid'=>$pid, 'status'=>1]) -> column('title, id, pid');
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
            $title = input('title', '', 'htmlspecialchars,trim'); // 市级的title
            $area = Db::name('region') -> where(['active'=>1,'type'=>3, 'title'=>$title]) -> column('id');
            $pid = $area[0];
        }

        if($pid === 0){
            return 'city参数错误'; die;
        }

        $station = cache('REGION_STATION');
        if(empty($station)){
            $station = Db::name('station') -> where(['pid'=>$pid, 'status'=>1]) -> column('title, id, pid');
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

    public function delAddress(){
        $id = input('id', 0, 'intval');
        $del = Db::name('user_address') -> where(['userid'=>session(config('USER_ID')), 'id'=>$id]) -> delete();
        if($del){
            return $this->redirect('/index/address/index');
        }
    }

}
