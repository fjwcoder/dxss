<?php
/**
* 	配置账号信息
*/

class WxPayConfig
{
	//=======【基本信息设置】=====================================
	//
	/**
	 * TODO: 修改这里配置为您自己申请的商户信息
	 * 微信公众号信息配置
	 * 
	 * APPID：绑定支付的APPID（必须配置，开户邮件中可查看）
	 * 
	 * MCHID：商户号（必须配置，开户邮件中可查看）
	 * 
	 * KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
	 * 设置地址：https://pay.weixin.qq.com/index.php/account/api_cert
	 * 
	 * APPSECRET：公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置），
	 * 获取地址：https://mp.weixin.qq.com/advanced/advanced?action=dev&t=advanced/dev&token=2005451881&lang=zh_CN
	 * @var string
	 */
	const APPID = 'wx6daa65cc6fc26c29';
	const APPSECRET = '5ed4ad3a2bbfca9f7e3654252e4c4bed';//公众号SECRET
	const MCHID = '1446652202';
	const KEY = 'X2m5c0TMoh0vrhMrKanQvhWhpd08n6ji';//商户key(手动设置的)
	
	
	//=======【证书路径设置】=====================================
	/**
	 * TODO：设置商户证书路径
	 * 证书路径,注意应该填写绝对路径（仅退款、撤销订单时需要，可登录商户平台下载，
	 * API证书下载地址：https://pay.weixin.qq.com/index.php/account/api_cert，下载之前需要安装商户操作证书）
	 * @var path
	 */
	// const SSLCERT_PATH = '../wxcert/apiclient_cert.pem';
	// const SSLKEY_PATH = '../wxcert/apiclient_key.pem';
	// const CAINFO_PATH = '../wxcert/rootca.pem'; 
	// $document_root = request()->server();
	const SSLCERT_PATH = "H:/PHP_Develop/WWW/sixer/vendor/wxcert/apiclient_cert.pem";
	const SSLKEY_PATH = 'H:/PHP_Develop/WWW/sixer/vendor/wxcert/apiclient_key.pem';
	const CAINFO_PATH = 'H:/PHP_Develop/WWW/sixer/vendor/wxcert/rootca.pem';

	const RSAPUBLIC_PATH = 'H:/PHP_Develop/WWW/sixer/vendor/wxcert/rsa_public_cert.pem';
	
	
	
	//=======【curl代理设置】===================================
	/**
	 * TODO：这里设置代理机器，只有需要代理的时候才设置，不需要代理，请设置为0.0.0.0和0
	 * 本例程通过curl使用HTTP POST方法，此处可修改代理服务器，
	 * 默认CURL_PROXY_HOST=0.0.0.0和CURL_PROXY_PORT=0，此时不开启代理（如有需要才设置）
	 * @var unknown_type
	 */
	const CURL_PROXY_HOST = "0.0.0.0";//"10.152.18.220";
	const CURL_PROXY_PORT = 0;//8080;
	
	//=======【上报信息配置】===================================
	/**
	 * TODO：接口调用上报等级，默认紧错误上报（注意：上报超时间为【1s】，上报无论成败【永不抛出异常】，
	 * 不会影响接口调用流程），开启上报之后，方便微信监控请求调用的质量，建议至少
	 * 开启错误上报。
	 * 上报等级，0.关闭上报; 1.仅错误出错上报; 2.全量上报
	 * @var int
	 */
	const REPORT_LEVENL = 1;
}
