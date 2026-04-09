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
require WB_PATH.'/modules/admin.php';

// Get id
$id = filter_input(INPUT_GET, 'group_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id) {
    $id_field = 'group_id';
    $table    = TABLE_PREFIX.'mod_news_img_groups';
} else {
    $id = filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) {
        header("Location: index.php");
        exit(0);
    }
    $id_field = 'post_id';
    $table    = TABLE_PREFIX.'mod_news_img_posts';
}

// Create new order object an reorder
$order = new order($table, 'position', $id_field, 'section_id');
if($order->move_up($id)) {
	$admin->print_success($TEXT['SUCCESS'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
} else {
	$admin->print_error($TEXT['ERROR'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
}

// Print admin footer
$admin->print_footer();
