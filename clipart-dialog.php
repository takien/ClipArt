<?php
defined('ABSPATH') or die();
header('Content-Type: text/html; charset=' . get_bloginfo('charset'));
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
<title><?php _e('Insert ClipArt','clipart'); ?></title>


<script type="text/javascript" src="<?php echo includes_url('/js/tinymce/tiny_mce_popup.js');?>"></script>
<link type="text/css" rel="stylesheet" href="<?php echo $plugin_url.'css/foundation.min.css';?>"/>
<link type="text/css" rel="stylesheet" href="<?php echo $plugin_url.'css/clipart-dialog.css';?>"/>
<script type="text/javascript" src="http://code.jquery.com/jquery-1.8.3.min.js"></script>
<?php
wp_enqueue_script('jquery');
?>
<script type="text/javascript" src="<?php echo $plugin_url.'js/jquery.foundation.tabs.js';?>"></script>
	
</head>
<body>
	
<div class="static-top">
	<dl class="tabs three-up">
		<dd class="<?php echo isset($_GET['clipart_remote_get']) ? '' : 'active';?>"><a href="#library"><?php _e('Library','clipart'); ?></a></dd>
		<dd class="<?php echo isset($_GET['clipart_remote_get']) ? 'active' : '';?>"><a href="#remote"><?php _e('Download ClipArt','clipart'); ?></a></dd>
		<dd><a href="#about"><?php _e('About','clipart'); ?></a></dd>
	</dl>
</div>

<ul class="tabs-content">
	<li class="<?php echo isset($_GET['clipart_remote_get']) ? '' : 'active';?>" id="libraryTab">
		<div class="row">
			<div class="columns twelve">
				<form action="<?php echo admin_url('?insert_clipart_dialog=1');?>">
				<input type="hidden" name="insert_clipart_dialog" value="1" />
					<div class="row collapse">
						<div class="ten columns">
							<input type="text" name="library_query" value="<?php echo (isset($_GET['library_query']) ? trim(strip_tags($_GET['library_query'])) : '');?>" placeholder="Enter keyword to search"/>
						</div>
						<div class="two columns">
							<input  class="button expand postfix" type="submit" value="Search"/>
						</div>
					</div>
				</form>
			</div>
		</div>
		
		<?php
		$args  = Array(
		'hide_empty' => 0,
		'orderby'=>'name'
		);
		$clipart_tags =  get_terms( 'clipart_tags', $args );
		$paged = isset($_GET['clip-paged']) ? (int)$_GET['clip-paged'] : 1;
		$args = Array(
			'posts_per_page' => 8,
			'post_type'      =>'attachment',
			'post_parent'    => '',
			'paged'          => $paged,
			'post_status'	 => 'any',
			's'              => isset($_GET['library_query']) ? $_GET['library_query'] : '',
			'tax_query'      => Array(
				array(
				'taxonomy' => 'clipart_tags',
				'field'    => 'slug',
				'terms'    => isset($_GET['clipart_tags']) ? trim(strip_tags($_GET['clipart_tags'])) : 'clipart'
				)
			)
		);
		
		$query       = new WP_Query($args);
		$numrows     = $query->found_posts;
		$max_page    = $query->max_num_pages;
		$attachments = $query->query($args);
		?>

		<div class="row">
			<div class="columns two">
			
				<h2>Tags</h2>
				<?php
					$library_base_url = remove_query_arg(array('clipart_remote_get','remote_page','tab'));
					echo '<ul class="clipart_tags">';
					echo '<li><a href="'.add_query_arg(Array('clipart_tags'=>'clipart','clip-paged'=>1),$library_base_url).'">All</a></li>';
					foreach($clipart_tags as $ct) {
						if($ct->count > 0) {
							$current = (isset($_GET['clipart_tags']) AND $_GET['clipart_tags'] == $ct->slug) ? ' class="current" ' : '';
							echo '<li '.$current.' ><a href="'.add_query_arg(Array('clipart_tags'=>$ct->slug,'clip-paged'=>1),$library_base_url).'">'.$ct->name.'</a></li>';
						}
					}
					echo '</ul>';
					?>
			</div>
			<div class="columns ten">
				<div id="cliparts" class="clipart-wrap">
					<?php
						if(empty($attachments)) { 
							if(isset($_GET['library_query']) OR isset($_GET['clipart_tags'])) { ?>
							<div class="attention">
								<?php _e('Your search return empty, please try different search term.','clipart');?>
							</div>
							<?php }
							else { ?>
							<div class="attention">
								<?php printf(__('You don\'t have any ClipArt in your Library. <a href="%s">Click here to search ClipArt online</a>.','clipart'), '#remote');?>
							</div>
							
						<?php }
						}
						else {
						foreach((array)$attachments as $atts ) {
							$img      = wp_get_attachment_image_src($atts->ID,'thumbnail');
							$img_full = wp_get_attachment_image_src($atts->ID,'full');
							$tags     = get_the_terms( $atts->ID, 'clipart_tags' );
							$img_tag  = Array();
							if($tags) {
								foreach((array)$tags as $tag) {
									$img_tag[] = $tag->slug;
								}
							}
							if($img[0]) {
							
							$clipart_info = Array(
								'url'          => get_permalink($atts->ID),
								'full'         => $img_full[0],
								'title'        => $atts->post_title,
								'tags'         => implode(',',$img_tag),
								'description'  => $atts->post_content
							);
							?>
							
							<div class="library-clipart-item clipart-item" data-clipart_info='<?php echo json_encode($clipart_info);?>'>
								<div class="fake-check"></div>
								<img alt="<?php echo esc_attr($atts->post_title);?>" src="<?php echo $img[0];?>" />
								</div>
							<?php
							}
						}
						}
					?>
				</div>
			</div>
		</div>
		<div class="static-bottom" id="library-toolbox">
		<div class="pagination-wrap">
		<?php
			clipart_custom_paging($numrows,$paged,8,'clip-paged','pagination','current',true);
		?>
		</div>
		
			<div class="row">
				
				<div class="four columns offset-by-eight text-right">
					<input id="insert_to_post" type="submit" class="button disabled" value="Insert to Post"/>
				</div>
			</div>
		</div><!--LIBRARY-toolbox-->
		
		
	</li><!--tabs1-->
	<li class="<?php echo isset($_GET['clipart_remote_get']) ? 'active' : '';?>" id="remoteTab">
		
		<div class="row">
			<div class="columns twelve">
				<form action="">
				<input type="hidden" value="1" name="remote_page" id="remote_page"/>
				<input type="hidden" value="1" name="clipart_remote_get" />
				<input type="hidden" value="1" name="insert_clipart_dialog" />
					<div class="row collapse">
						<div class="ten columns">
							<input type="text" name="clipart_query" id="search_remote" value="<?php echo isset($_GET['clipart_query']) ? trim(strip_tags($_GET['clipart_query'])) : '';?>" placeholder="Enter keyword to search"/>
						</div>
						<div class="two columns">
							<input  class="button expand postfix" type="submit" value="Search"/>
						</div>
					</div>
				</form>
			</div>
		</div>
		
		<div id="remote-wrap" class="clipart-wrap">
		<?php
			if(!isset($_GET['clipart_query'])) { ?>
				<div class="attention">
					<?php _e('Use search form above to find ClipArt from http://openclipart.org','clipart');?>
				</div>
			<?php }
			else if(empty($_GET['clipart_query'])) { ?>
				<div class="attention">
					<?php _e('Please enter a search query.','clipart');?>
				</div>
			<?php }
			else {
			$clipart_query = isset($_GET['clipart_query']) ? $_GET['clipart_query'] : 'christmast';
			$remote_page   = isset($_GET['remote_page']) ? (int)$_GET['remote_page'] : '1';
			$json          = get_clipart_from_cache("http://openclipart.org/search/json/?query=$clipart_query&page=$remote_page");
			$obj = json_decode($json);
			if($obj) {
				if($obj->msg == 'success') {
					foreach($obj->payload as $key=>$val) { 
					$clipart_info = Array(
						'url'          => $val->svg->png_thumb,
						'title'        => $val->title,
						'tags'         => $val->tags,
						'detail_link'  => $val->detail_link,
						'id'           => md5($val->detail_link),
						'description'  => $val->description,
						'uploader'     => $val->uploader,
						'drawn_by'     => $val->drawn_by
					);
					?>
						<div class="remote-clipart-item clipart-item <?php echo (is_clipart_already_exists($clipart_info['id']) ? 'saved' : '');?>" id="<?php echo $clipart_info['id'];?>" data-clipart_info='<?php echo json_encode($clipart_info);?>'>
						<div class="fake-check"></div>
						<img alt="<?php echo $val->title;?>" src="<?php echo $val->svg->png_thumb;?>" />
						</div>
					<?php } 
				}
				else { ?>
					<div class="attention">
						<?php _e('Something goes wrong, try again later.','clipart');?>
					</div>
				<?php }
			}
			else { ?>
				<div class="attention">
					<?php printf(__('Failed to retrieve remote data, please try different search term, or <a href="%s">reload this page.</a>.','clipart'), add_query_arg('remote_page',$remote_page));?>
				</div>
			<?php }
			}
		?>
		</div>
		<div class="static-bottom" id="remote-toolbox">
			<div class="row">
				<div class="twelve columns">
					<?php  if(isset($obj)) {
						if($remote_page < $obj->info->pages ) { ?>
						<div class="pagination-wrap">
							<ul id="remote_page_nav" class="pagination">
								<li><a title="First" href="<?php echo add_query_arg('remote_page',1);?>">l<</a></li>
								<li><a title="Prev" href="<?php echo add_query_arg('remote_page',$remote_page-1);?>"><</a></li>
								<?php for($i=1;$i<=$obj->info->pages;$i++) {
									if(($i >= ($remote_page-3)) AND ($i <= ($remote_page+3))) {
									?>
									<li <?php echo ($i == $remote_page) ? 'class="current"': '';?>><a href="<?php echo add_query_arg('remote_page',$i);?>"><?php echo $i;?></a></li>
									<?php }
									}
								?>
								<li><a title="Next" href="<?php echo add_query_arg('remote_page',$remote_page+1);?>">></a></li>
								<li><a title="Last" href="<?php echo add_query_arg('remote_page',$obj->info->pages);?>">>l</a></li>
							</ul>
						</div>
						<?php }
					} ?>
				</div>
			</div>
			<div class="row">
				<div class="columns eight">
					<p class="clipart-note"><strong>Note: </strong>ClipArt bordered with gray is indicated that ClipArt is already saved to Library.</p>
				</div>
				<div class="four columns text-right">
					<input id="save_to_library" type="submit" class="button disabled" value="Save to Library"/>
				</div>
			</div>
		</div><!--remote-toolbox-->
		
	</li><!--tabs2-->
	<li id="aboutTab">
		<div class="wrap">
			<h2><?php _e('About ClipArt','clipart'); ?></h2>
			<p><img class="clipart-logo" src="<?php echo plugins_url('images/clipart-logo.png',__FILE__);?>" alt=""/><?php printf(__('ClipArt is a WordPress plugin to collect, organize, and insert clip art on your WordPress site. ClipArt also allow you to search online clipart from <a target="_blank" href="%s">Open Clip Art Library</a> and download them to your library.','clipart'),'http://openclipart.org');?></p>
			<h3><?php _e('About','clipart'); ?> Open Clip Art Library (OCAL)</h3>
			<p><img class="clipart-logo" src="<?php echo plugins_url('images/openclipart-logo.jpg',__FILE__);?>" alt=""/><?php printf(__('Open Clip Art Library (OCAL) is the largest collaborative community that creates, shares and remixes clipart. All clipart is released to the public domain and may be used in any project for free with no restrictions. Open Clip Art Library website is <a href="%s">here</a>','clipart'),'http://openclipart.org');?></p>
			<h3><?php _e('Support','clipart'); ?></h3>
			<p><?php printf(__('Please visit my website <a href="%s" target="_blank">http://takien.com</a> to get support of this plugin.','clipart'),'http://takien.com');?></p>
			<h3>Cache</h3>
			<p>ClipArt use cache to store search result from http://openclipart.org. If you think this waste your space, you can delete those files.</p>
			<p>
				<form action="<?php echo admin_url('/');?>">
				<input type="hidden" value="1" name="insert_clipart_dialog" />
				<input type="hidden" value="1" name="delete_clipart_files" />
				<input type="hidden" value="about" name="tab" />
				<input type="submit" class="button" value="Delete cache files"/></form>
			</p>
		</div>
	</li><!--tabs3-->
</ul>
	

<script type="text/javascript">
jQuery(document).ready(function($){
	$('a[href="'+window.location.hash+'"]').trigger('click');
	<?php
		if(isset($_GET['tab'])) { ?>
			$('a[href="#<?php echo $_GET['tab'];?>"]').trigger('click');
		<?php }
	?>
	$(window).bind('hashchange', function() {
		$('a[href="'+window.location.hash+'"]').trigger('click');
	});
	
	/* check remote clipart*/
	$('body').on('click','.clipart-item',function(e) {
		if($(this).is('.saved')) {
			return false;
		}
		$(this).addClass('checked');
		activate_button();
		
	});
	$('body').on('click','.clipart-item.checked',function(e) {
		if($(this).is('.saved')) {
			return false;
		}
		
		$(this).removeClass('checked');
		activate_button();
	});
	function activate_button() {
		if($('.remote-clipart-item.checked').not('.saved').length > 0) {
			$('#save_to_library').removeClass('disabled');
		}
		else {
			$('#save_to_library').addClass('disabled');
		}

		if($('.library-clipart-item.checked').length > 0) {
			$('#insert_to_post').removeClass('disabled');
		}
		else {
			$('#insert_to_post').addClass('disabled');
		}
	}
	$('#save_to_library').click(function(){
		save_to_library();
	});
	
	function save_to_library() {
		var ajaxurl = '<?php echo admin_url('admin-ajax.php');?>';
		if($('.remote-clipart-item.checked').not('.saved').first().length) {
			var data = $('.remote-clipart-item.checked').not('.saved').first().data().clipart_info;
			data.action = 'clipart_save';
			$.post(ajaxurl,data,function(response){
				if( $.isPlainObject( response ) ){
					if(response.id != undefined){
						$('#'+response.id).addClass('saved');
					}
					save_to_library();
				}
				else {
					alert('Some of files failed to download. Please try again.');
				}
				
			});
		}
		activate_button();
	}
	
	$('#insert_to_post').click(function() {
		$('.library-clipart-item.checked').each(function(){
			var img = $(this).data().clipart_info;
			var toinsert = '<img src="'+img.full+'" alt="'+img.title+'" />';
			tinyMCEPopup.editor.execCommand('mceInsertContent', false, toinsert);
		});
	});
});
</script>
</body>
</html>