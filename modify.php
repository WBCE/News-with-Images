<?php
/**
 *
 * @category        modules
 * @package         news_img
 * @author          WBCE Community
 * @copyright       2004-2009, Ryan Djurovich
 * @copyright       2009-2010, Website Baker Org. e.V.
 * @copyright       2019-, WBCE Community
 * @link            https://www.wbce.org/
 * @license         http://www.gnu.org/licenses/gpl.html
 * @platform        WBCE
 *
 */

// Must include code to stop this file being access directly
if(!defined('WB_PATH')) { exit("Cannot access this file directly"); }

// check if module language file exists for the language set by the user (e.g. DE, EN)
if(!file_exists(WB_PATH .'/modules/news_img/languages/'.LANGUAGE .'.php')) {
	// no module language file exists for the language set by the user, include default module language file EN.php
	require_once WB_PATH .'/modules/news_img/languages/EN.php';
} else {
	// a module language file exists for the language defined by the user, load it
	require_once WB_PATH .'/modules/news_img/languages/'.LANGUAGE .'.php';
}

$database->query("DELETE FROM `".TABLE_PREFIX."mod_news_img_posts`  WHERE `page_id` = '$page_id' and `section_id` = '$section_id' and `title`=''");
$database->query("DELETE FROM `".TABLE_PREFIX."mod_news_img_groups`  WHERE `page_id` = '$page_id' and `section_id` = '$section_id' and `title`=''");

//overwrite php.ini on Apache servers for valid SESSION ID Separator
if(function_exists('ini_set')) {
	ini_set('arg_separator.output', '&amp;');
}

?>
<div class="mod_news_img">
    <input type="button" class="mod_img_news_add" value="<?php echo $MOD_NEWS['ADD_POST']; ?>" onclick="javascript: window.location = '<?php echo WB_URL; ?>/modules/news_img/add_post.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>';"  />
    <input  class="mod_img_news_options" type="button" value="<?php echo $MOD_NEWS['OPTIONS']; ?>" onclick="javascript: window.location = '<?php echo WB_URL; ?>/modules/news_img/modify_settings.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>';"  />
    <input  class="mod_img_news_add_group" type="button" value="<?php echo $MOD_NEWS['ADD_GROUP']; ?>" onclick="javascript: window.location = '<?php echo WB_URL; ?>/modules/news_img/add_group.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>';"  />
    <br />

    <h2><?php echo $TEXT['MODIFY'].'/'.$TEXT['DELETE'].' '.$TEXT['POST']; ?></h2>

<?php
    // Get settings
    $query_settings = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_img_settings` WHERE `section_id` = '$section_id'");
    if($query_settings->numRows() > 0)
    {
        $fetch_settings = $query_settings->fetchRow();
        $setting_view_order = ($fetch_settings['view_order']);
    } else {
	$setting_view_order = 0;
    }
    
    $order_by = "position";
    if($setting_view_order==1) $order_by = "published_when"; 
    if($setting_view_order==2) $order_by = "published_until"; 
    if($setting_view_order==3) $order_by = "posted_when"; 
    if($setting_view_order==4) $order_by = "post_id"; 

    // map order to lang string
    $lang_map = array(
        0 => $TEXT['CUSTOM'],
        1 => $TEXT['PUBL_START_DATE'],
        2 => $TEXT['PUBL_END_DATE'],
        3 => $TEXT['SUBMITTED'],
        4 => $TEXT['SUBMISSION_ID']
    );
?>

    <div style="text-align:right;font-style:italic"><?php echo $MOD_NEWS['ORDERBY'], ": <span class=\"\" title=\"", $MOD_NEWS['ORDER_CUSTOM_INFO'] ,"\">", $lang_map[$setting_view_order] ?></span></div>

<?php
    // Loop through existing posts
    $query_posts = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_img_posts` WHERE `section_id` = '$section_id' ORDER BY `$order_by` DESC");
    if($query_posts->numRows() > 0) {
    	$num_posts = $query_posts->numRows();
    	$row = 'a';
?>
    	<table>
            <tbody>
    	<?php
    	while($post = $query_posts->fetchRow()) {
    		?>
    		<tr>
    			<td style="width:20px">
    				<a href="<?php echo WB_URL; ?>/modules/news_img/modify_post.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;post_id=<?php echo $post['post_id']; ?>" title="<?php echo $TEXT['MODIFY']; ?>">
    					<img src="<?php echo THEME_URL; ?>/images/modify_16.png" border="0" alt="Modify - " />
    				</a>
    			</td>
    			<td>
    				<a href="<?php echo WB_URL; ?>/modules/news_img/modify_post.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;post_id=<?php echo $post['post_id']; ?>">
    					<?php echo ($post['title']); ?>
    				</a>
    			</td>
    			<td>
    				<?php echo $TEXT['GROUP'].': ';
    				// Get group title
    				$query_title = $database->query("SELECT `title` FROM `".TABLE_PREFIX."mod_news_img_groups` WHERE `group_id` = '".$post['group_id']."'");
    				if($query_title->numRows() > 0) {
    					$fetch_title = $query_title->fetchRow();
    					echo ($fetch_title['title']);
    				} else {
    					echo $TEXT['NONE'];
    				}
    				?>
    			</td>
    			<td>
    				<?php echo $TEXT['COMMENTS'].': ';
    				// Get number of comments
    				$query_title = $database->query("SELECT `title` FROM `".TABLE_PREFIX."mod_news_img_comments` WHERE `post_id` = '".$post['post_id']."'");
    				echo $query_title->numRows();
    				?>
    			</td>
    			<td style="width:120px">
    				<?php echo $TEXT['ACTIVE'].': '; if($post['active'] == 1) { echo $TEXT['YES']; } else { echo $TEXT['NO']; } ?>
    			</td>
    			<td style="width:20px">
<?php
	$start = $post['published_when'];
	$end = $post['published_until'];
	$t = time();
	$icon = '';
	if($start<=$t && $end==0)
		$icon=THEME_URL.'/images/noclock_16.png';
	elseif(($start<=$t || $start==0) && $end>=$t)
		$icon=THEME_URL.'/images/clock_16.png';
	else
		$icon=THEME_URL.'/images/clock_red_16.png';
?>
    			<a href="<?php echo WB_URL; ?>/modules/news_img/modify_post.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;post_id=<?php echo $post['post_id']; ?>" title="<?php echo $TEXT['MODIFY']; ?>">
    				<img src="<?php echo $icon; ?>" border="0" alt="" />
    			</a>
    			</td>
    			<td style="width:20px">
    			<?php if(($post['position'] != $num_posts)&&($setting_view_order == 0)) { ?>
    				<a href="<?php echo WB_URL; ?>/modules/news_img/move_down.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;post_id=<?php echo $post['post_id']; ?>" title="<?php echo $TEXT['MOVE_UP']; ?>">
    					<img src="<?php echo THEME_URL; ?>/images/up_16.png" border="0" alt="^" />
    				</a>
    			<?php } ?>
    			</td>
    			<td style="width:20px">
    			<?php if(($post['position'] != 1)&&($setting_view_order == 0)) { ?>
    				<a href="<?php echo WB_URL; ?>/modules/news_img/move_up.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;post_id=<?php echo $post['post_id']; ?>" title="<?php echo $TEXT['MOVE_DOWN']; ?>">
    					<img src="<?php echo THEME_URL; ?>/images/down_16.png" border="0" alt="v" />
    				</a>
    			<?php } ?>
    			</td>
    			<td style="width:20px">
    				<a href="javascript: confirm_link('<?php echo $TEXT['ARE_YOU_SURE']; ?>', '<?php echo WB_URL; ?>/modules/news_img/delete_post.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;post_id=<?php echo $post['post_id']; ?>');" title="<?php echo $TEXT['DELETE']; ?>">
    					<img src="<?php echo THEME_URL; ?>/images/delete_16.png" border="0" alt="X" />
    				</a>
    			</td>
    		</tr>
		<?php
	}
	?>
        </tbody>
        </table>
	<?php
} else {
	echo $TEXT['NONE_FOUND'];
}

?>

    <h2><?php echo $TEXT['MODIFY'].'/'.$TEXT['DELETE'].' '.$TEXT['GROUP']; ?></h2>

<?php

// Loop through existing groups
$query_groups = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_img_groups` WHERE `section_id` = '$section_id' ORDER BY `position` ASC");
if($query_groups->numRows() > 0) {
	$num_groups = $query_groups->numRows();
	$row = 'a';
	?>
	<table>
	<?php
	while($group = $query_groups->fetchRow()) {
		?>
		<tr>
			<td style="width:20px">
				<a href="<?php echo WB_URL; ?>/modules/news_img/modify_group.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;group_id=<?php echo $group['group_id']; ?>" title="<?php echo $TEXT['MODIFY']; ?>">
					<img src="<?php echo THEME_URL; ?>/images/modify_16.png" border="0" alt="Modify - " />
				</a>
			</td>		
			<td>
				<a href="<?php echo WB_URL; ?>/modules/news_img/modify_group.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;group_id=<?php echo $group['group_id']; ?>">
					<?php echo $group['title']; ?>
				</a>
			</td>
			<td style="width:150px">
				<?php echo $TEXT['ACTIVE'].': '; if($group['active'] == 1) { echo $TEXT['YES']; } else { echo $TEXT['NO']; } ?>
			</td>
			<td style="width:20px">
			<?php if($group['position'] != 1) { ?>
				<a href="<?php echo WB_URL; ?>/modules/news_img/move_up.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;group_id=<?php echo $group['group_id']; ?>" title="<?php echo $TEXT['MOVE_UP']; ?>">
					<img src="<?php echo THEME_URL; ?>/images/up_16.png" border="0" alt="^" />
				</a>
			<?php } ?>
			</td>
			<td style="width:20px">
			<?php if($group['position'] != $num_groups) { ?>
				<a href="<?php echo WB_URL; ?>/modules/news_img/move_down.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;group_id=<?php echo $group['group_id']; ?>" title="<?php echo $TEXT['MOVE_DOWN']; ?>">
					<img src="<?php echo THEME_URL; ?>/images/down_16.png" border="0" alt="v" />
				</a>
			<?php } ?>
			</td>
			<td style="width:20px">
				<a href="javascript: confirm_link('<?php echo $TEXT['ARE_YOU_SURE']; ?>', '<?php echo WB_URL; ?>/modules/news_img/delete_group.php?page_id=<?php echo $page_id; ?>&amp;group_id=<?php echo $group['group_id']; ?>');" title="<?php echo $TEXT['DELETE']; ?>">
					<img src="<?php echo THEME_URL; ?>/images/delete_16.png" border="0" alt="X" />
				</a>
			</td>
		</tr>
<?php
	}
?>
	</table>
	<?php
} else {
	echo $TEXT['NONE_FOUND'];
}
