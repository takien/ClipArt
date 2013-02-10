<?php
/*
Plugin Name: ClipArt
Plugin URI: http://takien.com/
Description: ClipArt is a WordPress plugin to collect, organize, and insert clip art on your WordPress site. ClipArt also allow you to search online clipart from <a target="_blank" href="http://openclipart.org">Open Clip Art Library</a> and download them to your library.
Author: Takien
Version: 0.1
Author URI: http://takien.com/
*/
defined('ABSPATH') or die();
define('CLIPART_CACHE_DIR',dirname(__FILE__).'/cache');

//require_once(dirname(__FILE__).'/inc/takien-plugin-options.php');
require_once(dirname(__FILE__).'/inc/paging.php');

function clipart_admin_notice(){
	$error = false;
	if ( version_compare( $GLOBALS['wp_version'], 3.5, '<') ) {
		$error = '<p><strong>ClipArt</strong> is installed but not compatible with your current WordPress version. ClipArt requires at least WordPress version 3.5. Please <a href="plugins.php">deactivate ClipArt</a> plugin or update your <a href="update-core.php">WordPress</a>.</p>';
	}
	if(!function_exists('json_encode') OR !function_exists('file_put_contents') OR !function_exists('scandir') OR !function_exists('_update_generic_term_count') OR !function_exists('_update_post_term_count')) {
		$error .= '<p><strong>ClipArt</strong> is installed but some required functions are not available.</p>';
	}
	if(!is_writeable(CLIPART_CACHE_DIR)) {
		$error .= '<p><strong>ClipArt</strong> is installed, but <strong>'.CLIPART_CACHE_DIR.'</strong> is not writeable. Make sure you set permision to 777 or 775</p>';
	}
	if($error) {
		echo '<div class="error">'.$error.'</div>';
	}
}
add_action('admin_notices', 'clipart_admin_notice');

/**
 * Enqueue Scripts.
 * 
 */

function clipart_enqueue_script() {
	//foundation tabs.
	if(isset($_GET['insert_clipart_dialog'])) {
		wp_enqueue_script('foundation-tab',plugins_url('/js/jquery.foundation.tabs.js',__FILE__),array('jquery'),0.1);
	}
}
add_action( 'admin_enqueue_scripts' ,'clipart_enqueue_script' );
/**
 * Add ClipArt tags to the media library
 */

add_action( 'init', 'clipart_plugin_init' );

function clipart_plugin_init() {

	//register taxonomy
	$labels = array(
		'name'          => __('ClipArt Tags'),
		'singular_name' => 'clipart-tags',
	); 	
	
	register_taxonomy('clipart_tags',array('attachment'), array(
		'hierarchical'      => false,
		'update_count_callback'=> 'clipart_tags_count',
		'labels'            => $labels,
		'show_admin_column' => true,
		'query_var'         => false,
		'rewrite'           => false
	));
	
	//delete cache file
	if(isset($_GET['delete_clipart_files'])) {
		$dir = scandir(CLIPART_CACHE_DIR);
		foreach($dir as $file) {
			if(is_file(CLIPART_CACHE_DIR.'/'.$file) AND ($file !== 'index.html')) {
				@unlink(CLIPART_CACHE_DIR.'/'.$file);
			}
		}
	}
	//tinymce dialog
	if(isset($_GET['insert_clipart_dialog'])) {
		$plugin_url = plugins_url('/', __FILE__);
		include(dirname(__FILE__).'/clipart-dialog.php');
		exit;
	}
	
}

function clipart_tags_count($terms,$taxonomy) {
	_update_post_term_count( $terms, $taxonomy );
	_update_generic_term_count( $terms, $taxonomy );
}
/**
 * Add Insert ClipArt TinyMCE button.
 * 
 */
 
add_action('init', 'add_insert_clipart_button');

function add_insert_clipart_button() {
	if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
	return;
	
	// Add only in Rich Editor mode
	if ( get_user_option('rich_editing') == 'true') {
		add_filter("mce_external_plugins", "add_insert_clipart_tinymce_plugin");
		add_filter('mce_buttons', 'register_insert_clipart_button');
	}
}

function register_insert_clipart_button($buttons) {
	array_push($buttons, "|", "insertclipart");
	return $buttons;
}

function add_insert_clipart_tinymce_plugin($plugin_array) {
	$plugin_array['insertclipart'] = plugins_url('/js/editor_plugin.js', __FILE__);
	return $plugin_array;
}


add_action('wp_ajax_clipart_save', 'clipart_save_callback');

/**
 * Save ClipArt to library include appropriate data.
 * 
 */
function clipart_save_callback() {
	//extract $_POST into variable, append it with clipart
	extract($_POST, EXTR_PREFIX_ALL, 'clipart');
	$result = Array(
		'code'         => 0,
		'message'      => 'failed'
	);
	$remote = wp_remote_get($clipart_url);
	//make sure response is OK
	if(!is_object($remote)) {
		$content_type = $remote['headers']['content-type'];
		$filenames    = explode('.',end(explode('/',$clipart_url)));
		$ext          = end($filenames);
		$filename     = str_ireplace('.'.$ext,'',$filenames[0]);
		$filename     = 'clipart-'.sanitize_title_with_dashes($filename).'.'.$ext;
		$file_content = wp_remote_retrieve_body($remote);
		
		$paths 		= wp_upload_dir(date("Y-m-d H:i:s"));
		$path 		= $paths['path'];
		$att_url	= $paths['url'];
		
		//create unique filename
		$filename   = wp_unique_filename( $path, $filename );
		if(file_put_contents($path.'/'.$filename,$file_content)){
			  require_once(ABSPATH . 'wp-admin/includes/image.php');
				
				$attachment = array(
					'post_type'      => 'attachment',
					'post_title'     => $clipart_title,
					'post_content'   => $clipart_description,
					'post_mime_type' => $content_type
				);
				$attach_id   = wp_insert_attachment( $attachment, $path.'/'.$filename );
				if($attach_id) {
					$attach_data = wp_generate_attachment_metadata( $attach_id, $path.'/'.$filename );
					$attach_data['clipart_guid'] = $clipart_id;
					$attach_data['uploader']     = $clipart_uploader;
					$attach_data['drawn_by']     = $clipart_drawn_by;
					//update attachment metadata, include clipart_guid
					wp_update_attachment_metadata( $attach_id,$attach_data );
					//set clipart tags
					wp_set_post_terms( $attach_id, 'clipart,'.$clipart_tags, 'clipart_tags' );
					
					//update attachment alt
					update_post_meta( $attach_id, '_wp_attachment_image_alt', $clipart_title );
					$result['code']    = 1;
					$result['message'] = 'success';
					$result['id']      = $clipart_id;
				}
		}
	}
	header('content-type: application/json; charset=utf-8');
	echo json_encode($result);
	exit;
}

function is_clipart_already_exists($clipart_id='') {
	global $wpdb;
	$query = "SELECT meta_value FROM `$wpdb->postmeta` WHERE meta_value LIKE '%$clipart_id%'";
	if($wpdb->get_row($query)) {
		return true;
	}
	else {
		return false;
	}
	
}

/**
 * Get ClipArt info from cache, or update if cache not available
 * 
 * @param string url 
 */

function get_clipart_from_cache($url) {
	if(!$url) return false;
	$cache_path = CLIPART_CACHE_DIR.'/'.md5($url);
	if(is_readable($cache_path) AND (filemtime($cache_path) > (time() - 86400)) AND (filesize($cache_path) > 1)) {
		return file_get_contents($cache_path);
	}
	else {
		$remote = wp_remote_retrieve_body( wp_remote_get($url) );
		file_put_contents($cache_path,$remote);
		return $remote;
	}
}
