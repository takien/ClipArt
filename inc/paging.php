<?php
/**
 * ClipArt paging
 * @author takien
 * @param <type> numrows 
 * @param <type> paged 
 * @param <type> perpage 
 * @param <type> page_query 
 * @param <type> class 
 * @param <type> currentclass 
 * @param <type> echo 
 */
defined('ABSPATH') or die();
function clipart_custom_paging($numrows,$paged,$perpage,$page_query='page',$class='paging',$currentclass='current',$echo=true){
parse_str($_SERVER['QUERY_STRING'],$current_query);

$max_pages		= ceil($numrows/$perpage);
$paged			= $paged ? $paged : 1;

if($max_pages <= 1) return false;

if($echo == false) {
	
}
else {
echo '<ul class="'.$class.'">';
?>
	<li><a title="First" href="<?php echo add_query_arg($page_query,1);?>">l<</a></li>
	<li><a title="Prev" href="<?php echo add_query_arg($page_query,$paged-1);?>"><</a></li>
<?php
	for($i=1;$i<=$max_pages;$i++){

		if(($i >= ($paged-3)) AND ($i <= ($paged+3))) { ?>
			<li <?php echo ($i == $paged) ? 'class="'.$currentclass.'"': '';?>>
				<a href="<?php echo add_query_arg($page_query,$i);?>"><?php echo $i;?></a></li>
			<?php
		}
	}
?>
	<li><a title="Next" href="<?php echo add_query_arg($page_query,$paged+1);?>">></a></li>
	<li><a title="Last" href="<?php echo add_query_arg($page_query,$max_pages);?>">>l</a></li>
<?php
echo '</ul>';
}
}