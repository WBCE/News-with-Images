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

// Get id (Einzel-Löschung per GET-Link aus der Beitragsliste)
$post_id = filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$post_id) {
	header("Location: ".ADMIN_URL."/pages/index.php");
	exit(0);
}

// Include WB admin wrapper script
$update_when_modified = true; // Tells script to update when this page was last updated
$admin_header = FALSE;
require WB_PATH.'/modules/admin.php';

// CSRF: Die Löschung erfolgt über einen GET-Link -> FTAN-Token prüfen.
if (!defined('CAT_PATH')) {
    $admin->print_header();
    if (!$admin->checkFTAN('GET')) {
        $admin->print_error($MESSAGE['GENERIC_SECURITY_ACCESS']
		 .' (FTAN) '.__FILE__.':'.__LINE__,
             ADMIN_URL.'/pages/index.php');
    }
}

mod_nwi_post_delete([$post_id]);

// Check if there is a db error, otherwise say successful
if($database->is_error()) {
	$admin->print_error($database->get_error(), ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
} else {
	$admin->print_success($TEXT['SUCCESS'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
}

// Print admin footer
$admin->print_footer();
