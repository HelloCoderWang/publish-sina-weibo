<?php
/**
	define( "WB_AKEY" , '151401374' );
	define( "WB_SKEY" , '250f12c6800ad4914b23fa68912c3ade' );
	define( "WB_CALLBACK_URL" , 'http://42.96.197.42/wp-content/plugins/publish_sina_weibo/save_token.php' );
 */
$wb_akey = get_option('_king_wb_key');
$wb_skey = get_option('_king_wb_skey');
$wb_callback_url = get_option('_king_callback_url');
if($wb_akey && $wb_skey && $wb_callback_url){
	define( "WB_AKEY" , $wb_akey );
	define( "WB_SKEY" , $wb_skey);
	define( "WB_CALLBACK_URL" , $wb_callback_url);
}


$config['record_table'] = 'sina_weibo_post_record';
