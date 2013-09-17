<?php

require_once dirname(__FILE__).'/../../../wp-load.php';
require_once 'weibo.lib.php';


if (isset($_REQUEST['code'])) {
	$code = $_REQUEST['code'];
	$_SESSION['code']=$code;	
	$sendData['code'] = $code;
	$sendData['redirect_uri'] = WB_CALLBACK_URL;
	$token = $so->getAccessToken('code',$sendData);
	$token['last_request_time'] = mktime(); 
	$token = serialize($token);	
	if(get_option('sina_weibo_token'))
	{
		update_option('sina_weibo_token', $token);
	}else{
		add_option('sina_weibo_token',$token);
	}
	echo "<script type='text/javascript'>window.location.href='/wp-admin/index.php';</script>";
}