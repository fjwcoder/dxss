<?php
namespace app\index\controller;
// use app\index\controller\Common;
use app\model\model\Users;

use think\Controller;
use think\Config;
use think\Session;
use think\Db;
use think\Request;
use think\cache\driver\Redis;

class Index extends controller
{
    public function index(){

        if(isMobile()){
            
            $this->assign('footer', ['status'=>true, 'name'=>'home']);
            
            return $this->fetch();
        }else{
            return $this->redirect('https://www.baidu.com');
        }
        
    }

    

    ##########################thinkphp5 模型学习############################3

    public function model3(){
        //all 多条查询
        $where['id'] = ['<', 10];
        $model = new Users();
        $result = $model ->all($where);
        dump($result);
    }


    public function model2(){
        $model = new Users();
        $result = $model->where(['id'=>1]) ->field(['id','name', 'password']) -> find() ->getData();
        return dump($result);
    }
    public function model1(){
        //闭包查询
        $closer = function($query){
            $query->field('id, name, password') -> where('id<10');
        };
        $result = Users::get($closer);//->getData();
        dump($result);


        // $model = new Users(); 
        // $result = $model->get(['name'=>'fjw'])->getData();
        // dump($result);
    }
    public function model0(){
        // $module = new Users();
        //1.创建$data数组  
        $data['id'] = 10;      
        $data['name'] = 'ThinkPHP';      
        $data['version'] = '5.0.3';      
        //2. 用$data数组为参数实例化Staff类  
        //此时获取到的模型对象，就可以认为是数据对象 
        // $model = new Users($data);
        $model = new Users();  
        $model->data($data);   
        $model->haha = 'houhou';
        $model->name = 'fjw';
        //3.查看$model对象  
        $result = $model->getData('haha');
        dump($result);
    }
}
