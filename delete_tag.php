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
require(WB_PATH.'/modules/admin.php');

$tag_id = filter_input(INPUT_GET, 'tag_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$section_id = filter_input(INPUT_GET, 'section_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!$tag_id || !$section_id) {
    $admin->print_error($MESSAGE['GENERIC_SECURITY_ACCESS']
	 .' (tag_id) '.__FILE__.':'.__LINE__,
         ADMIN_URL.'/pages/index.php');
    $admin->print_footer();
    exit();
}



// get tag
$tag = mod_nwi_get_tag($tag_id);

// remove tag-to-posts-mappings
$database->query(sprintf(
    "DELETE FROM `%smod_news_img_tags_posts` WHERE `tag_id`=%d",
    TABLE_PREFIX, $tag_id
));

$sections = explode(",",$tag['sections']);

// if it's a global tag...
// (in fact, if this is the case, there should be only one item in the $sections
// array)
if(in_array('0',$sections)) {
    // remove all tag-to-section-mappings
    $database->query(sprintf(
        "DELETE FROM `%smod_news_img_tags_sections` WHERE `tag_id`=%d",
        TABLE_PREFIX, $tag_id
    ));
    // remove tag
    $database->query(sprintf(
        "DELETE FROM `%smod_news_img_tags` WHERE `tag_id`=%d",
        TABLE_PREFIX, $tag_id
    ));
} else {
    // remove the local tag
    $database->query(sprintf(
        "DELETE FROM `%smod_news_img_tags_sections` WHERE `section_id`=%d AND `tag_id`=%d",
        TABLE_PREFIX, $section_id, $tag_id
    ));
    $database->query(sprintf(
        "DELETE FROM `%smod_news_img_tags` WHERE `tag_id`=%d",
        TABLE_PREFIX, $tag_id
    ));
}

// Check if there is a db error, otherwise say successful
if($database->is_error()) {
	$admin->print_error($database->get_error(), ADMIN_URL.'/pages/modify.php?page_id='.$page_id.'&tab=s');
} else {
	$admin->print_success($TEXT['SUCCESS'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id.'&tab=s');
}

// Print admin footer
$admin->print_footer();
