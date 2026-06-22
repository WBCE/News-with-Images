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

require_once __DIR__.'/functions.inc.php';

// Include WB admin wrapper script
$update_when_modified = false; // Tells script to update when this page was last updated
$admin_header = FALSE;
require WB_PATH.'/modules/admin.php';
if (!$admin->checkFTAN()){
    $admin->print_header();
    $admin->print_error($MESSAGE['GENERIC_SECURITY_ACCESS']
	 .' (FTAN) '.__FILE__.':'.__LINE__,
         ADMIN_URL.'/pages/index.php');
    $admin->print_footer();
    exit();
} else {
    if(!defined('CAT_PATH')) {
        $admin->print_header();
    }
}

// Validate all fields
if($admin->get_post('new_tag') == '' && $admin->get_post('tag_id') == '')
{
	$admin->print_error($MESSAGE['GENERIC_FILL_IN_ALL'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id.'&tab=s');
    $admin->print_footer();
    exit();
}
else
{
    if($admin->get_post('tag_id') != '') {
        $tag_id = intval($admin->get_post('tag_id'));
        $tag = $admin->get_post('tag');
    } else {
        $tag_id = null;
        $tag = $admin->get_post('new_tag');
    }
	$tag = mod_nwi_escapeString($tag);
	$tag = strip_tags($tag);
    // Validate as a safe CSS color first, then SQL-escape. mod_nwi_safe_css_color()
    // returns '' for anything that doesn't match a known-safe pattern (hex,
    // rgb/rgba/hsl/hsla, or a plain keyword), which neutralizes any attempt
    // to break out of the style attribute on the frontend.
    $tag_color = mod_nwi_escapeString(mod_nwi_safe_css_color((string)$admin->get_post('tag_color')));
    // Schriftfarbe analog zum Hintergrund validieren + escapen.
    $tag_text_color = mod_nwi_escapeString(mod_nwi_safe_css_color((string)$admin->get_post('tag_text_color')));
}

// make global
$tag_section_id = (int)$section_id;
if($admin->get_post('global_tag') == 'on') {
    $tag_section_id = 0;
}
// Update row
if(empty($tag_id)) {
    if(mod_nwi_tag_exists($tag_section_id,$tag)) {
        $admin->print_error($MOD_NEWS_IMG['TAG_EXISTS'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id.'&section_id='.$section_id.'&tab=s');
        exit();
    }
    $database->query("INSERT INTO `".TABLE_PREFIX."mod_news_img_tags` ( `tag`, `tag_color`, `tag_text_color` ) VALUES ('$tag','$tag_color','$tag_text_color')");
    if($database->is_error()) {
    	$admin->print_error($database->get_error(), ADMIN_URL.'/pages/modify.php?page_id='.$page_id.'&tab=s');
    } else {
        $tag_id = (int)$database->getLastInsertId();
        if(!empty($tag_id)) {
            $database->query(sprintf(
                "INSERT INTO `%smod_news_img_tags_sections` (`section_id`,`tag_id`) VALUES (%d, %d);",
                TABLE_PREFIX,
                $tag_section_id,
                $tag_id
            ));
        }
    }
} else {
    $tag_id = (int)$tag_id;
    $database->query(sprintf(
        "UPDATE `%smod_news_img_tags` SET `tag`='%s', `tag_color`='%s', `tag_text_color`='%s' WHERE `tag_id`=%d",
        TABLE_PREFIX,
        $tag,
        $tag_color,
        $tag_text_color,
        $tag_id
    ));
}

if($database->is_error()) {
	$admin->print_error($database->get_error(), ADMIN_URL.'/pages/modify.php?page_id='.$page_id.'&tab=s');
} else {
	$admin->print_success($TEXT['SUCCESS'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id.'&tab=s');
}

// Print admin footer
$admin->print_footer();

