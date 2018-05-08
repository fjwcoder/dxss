<?php
namespace app\index\controller;
// use app\model\model\Users;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;
use think\Request;
use think\cache\driver\Redis;

class Time extends controller
{
    public $period_end = 10;
    public $shipping_time = 17;

    public function calculate(){
        $now = time();
        $show = [
            'day'=>'今天',
            'time'=> strval($this->period_end.':00'),
            'date'=>date('m-d', $now)
        ];
        
        $today = strtotime(date('Y-m-d', $now)); // 今天的凌晨
        $today_end = $today+ $this->period_end * 60 * 60; // 本周期结束
        $tomorrow_end = $today_end+24*60*60; 
        if($now > $today_end && $now < $tomorrow_end){
            $show['day'] = '明天';
            $show['date'] = date('m-d', $tomorrow_end);
        }
        return $show; 
        
    }




    

}
