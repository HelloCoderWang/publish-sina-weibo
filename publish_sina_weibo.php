<?php
/**
 Plugin Name: Publish Sina Weibo
 Plugin URI:
 Author: King
 Description: 自动发布文章内容到新浪微博
 Version: 1.0
 */
require 'weibo.lib.php';


function init_sina_weibo()
{
	require_once ABSPATH.'wp-admin/includes/upgrade.php';
	global $wpdb;
	$prefix = $wpdb->prefix;
	$tableName = $prefix."sina_weibo_post_record";
	$sql="CREATE TABLE IF NOT EXISTS $tableName(
			`wbId` BIGINT NOT NULL AUTO_INCREMENT, 
			`postId` BIGINT NOT NULL,
			`weiboId` BIGINT NOT NULL,
			`createTime` DATETIME, 
			PRIMARY KEY (`wbId`)
		) ENGINE=INNODB CHARSET=utf8;";

	dbDelta($sql);
}

add_action('publish_post','publish_sina_weibo',10);
add_action('get_sina_weibo_token','get_weibo_auth_token');
add_action('wp_dashboard_setup','add_dashboard_weibo_widget');
add_action('admin_menu','add_weibo_page_menu');
register_activation_hook(__FILE__,'init_sina_weibo');