<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
if (version_compare(PHP_VERSION, '5.4.0', '<')){ //提示所需PHP版本必须为5.5.6以上
	die('require PHP > 5.4.0 !');
}
// 定义应用目录
define('APP_PATH', __DIR__ . '/../app/');
// 加载框架引导文件
define('APP_DEBUG', true);
//定义 【惯例配置】 文件目录
define('CONF_PATH', __DIR__ . '/../conf/');
require __DIR__ . '/../thinkphp/start.php'; //start.php 又引入了base.php
