<?php
require_once dirname(__FILE__).'/weibo.cfg.php';

if (!class_exists('SaeTOAuthV2'))
	require dirname(__FILE__).'/saetv2.ex.class.php';
if(defined('WB_AKEY')){
	$so = new SaeTOAuthV2(WB_AKEY, WB_SKEY);
}else{
	$so = null;
}



function get_weibo_auth_token()
{
	global $so;
	if(!defined('WB_CALLBACK_URL')) return false;

	$get_auth_url = $so->getAuthorizeURL(WB_CALLBACK_URL);
	header("Location:$get_auth_url");
}

/**
 * @abstract 获得操作新浪client对象
 * @return Object
 */
function get_weibo_client()
{
	static $client = null;
	if(is_null($client)){
		$access_token = get_option('sina_weibo_token');
		if(!$access_token) return false;
		$access_token = unserialize($access_token);
		
		$client = new SaeTClientV2(WB_AKEY, WB_SKEY, $access_token['access_token']);
	}
		
	return $client;
}

function get_post_featured_image_path($postID)
{
	if(has_post_thumbnail($postID)){
		$thumbID =get_post_thumbnail_id($postID);
		$imgPath = wp_get_attachment_thumb_file($thumbID);
		return $imgPath;
	}else{
		return null;
	}
}

/**
 * 发布微博
 * @param  int $postID 文章id
 * @return null
 */
function publish_sina_weibo($postID)
{
	if(check_post_has_publish_weibo($postID)){
		return ;
	}
	$post = get_post($postID);
	$featuredImagePath = get_post_featured_image_path($postID);
	$client = get_weibo_client();
	//获得客户端失败
	if(!$client) return false;

	$img_src = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), "Full");
	if(has_excerpt($postID)){	//文章摘要
		$excerpt = $post->post_excerpt;
	}else{	//自动生成摘要
		$excerpt = strip_tags($post->post_content);
		$len = mb_strlen($excerpt);
		if($len>90){
			$excerpt = mb_substr($excerpt,0,80);
			$excerpt.='...';
		}		
	}
	$weibo_content = $post->post_title.":".$excerpt;
	$url = get_permalink($postID);
	$weibo_content.=$url;	
	$imagepath='';
	if($img_src){
		$imagepath = get_image_path_by_url($img_src[0]);
	}	

	if($imagepath){
		$weibo_data = $client->upload($weibo_content,$imagepath);
	}else{
		$weibo_data = $client->update($weibo_content);
	}
	
	$weibo_data = (array)$weibo_data;
	
	$data['postId']=$postID;
	$data['weiboId']= $weibo_data['id'];
	publish_weibo_record($data);
}

/**
 * @abstract 记录发布微博
 * @param unknown_type $data
 */
function publish_weibo_record($data)
{
	global $wpdb,$config;
	$tableName = $wpdb->prefix.$config['record_table'];
	$insertSql = "INSERT INTO `$tableName` (`postId`,`weiboId`,`createTime`) VALUES(".$data['postId'].",".$data['weiboId'].",now());";
	$wpdb->query($insertSql);
}

/**
 * @abstract 检测文章是否已经进行过发布微博
 * @param int $postId
 * @return boolean
 */
function check_post_has_publish_weibo($postId)
{
	global $wpdb,$config;
	$tableName = $wpdb->prefix.$config['record_table'];
	$sql = "SELECT COUNT(1) recordTimes FROM `$tableName` WHERE `postId`=$postId;";
	$result = $wpdb->get_row($sql,ARRAY_A);
	return $result['recordTimes']>0;
}



function get_image_path_by_url($imgUrl)
{
	$siteurl = get_option('siteurl')."/";
	$path = str_replace($siteurl,ABSPATH,$imgUrl);
	return realpath($path);
}

/**
 * 添加Dashboard微博挂件
 */
function add_dashboard_weibo_widget()
{
	wp_add_dashboard_widget('dashboard_weibo_show',"微博信息",'create_dashboard_weibo_widget');
}

/**
 * 创建Dashboard微博挂件
 * @return string
 */
function create_dashboard_weibo_widget()
{
	$tokenInfo = get_option('sina_weibo_token');
	$content = '';
	if(!empty($tokenInfo)){
		$tokenInfo = unserialize($tokenInfo);
		$expiresTime = date("Y-m-d H:i:s",$tokenInfo['expires_in']+$tokenInfo['last_request_time']);
		$content.='过期时间：'.$expiresTime."<br>\r\n";;
	}
	$content.='获得授权或者更新授权信息点<a href="'.admin_url().'weibo_auth.php">这里</a>';
	echo $content;
}

/**
 * 添加微博设置页面
 */
function add_weibo_page_menu()
{
	add_options_page('新浪微博', '新浪微博', 'manage_options', 'set_weibo_config', 'show_weibo_option_page' );
}

/**
 * 微博展示显示页面
 * @return void
 */
function show_weibo_option_page()
{
	global $wb_akey,$wb_skey,$wb_callback_url;
	if(isset($_POST['wp_publish_weibo_option'])){
		if(isset($_POST['wb_key'])){
			$wb_akey =trim($_POST['wb_key']);
			update_option( '_king_wb_key', $wb_akey);
		}
		if(isset($_POST['wb_skey'])){
			$wb_skey = trim($_POST['wb_skey']);
			update_option( '_king_wb_skey', $wb_skey);
		}
		if(isset($_POST['wb_callback_url'])){
			$wb_callback_url = trim($_POST['wb_callback_url']);
			update_option( '_king_callback_url', $wb_callback_url);
		}
	}
?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"><br></div>
	<h2>新浪微博参数设置</h2>
	<form method="post" action="">
		<input type="hidden" name="wp_publish_weibo_option" value="1"/>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">App Key</th>
				<td><input type="text" name="wb_key"  value="<?php echo $wb_akey; ?>"></td>
			</tr>
			<tr valign="top">
				<th scope="row">App Secret</th>
				<td><input type="text" name="wb_skey" size=40 value="<?php echo $wb_skey; ?>"></td>
			</tr>
			<tr valign="top">
				<th scope="row">授权回调页</th>
				<td><input type="text" name="wb_callback_url" size=40 value="<?php echo $wb_callback_url; ?>">
					<p>插件授权回调信息处理在/wp-content/plugins/publish_sina_weibo/save_token.php文件中，因此回调页面的设置请按照要求在此地址前面加上域名即可，此地址为相对site_url，完整的路径请加上网站的site_url.</p>
				</td>
			</tr>			
		</table>
		<p class="submit" >
			<input type="submit" value="保存更改" />
		</p>
	</form>
</div>
<?php 	
}

function save_option_change()
{
	
}