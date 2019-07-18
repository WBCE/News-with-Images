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

// Get id
if((!isset($_GET['post_id']))AND(!isset($_POST['manage_posts']))) {
	header("Location: ".ADMIN_URL."/pages/index.php");
	exit(0);
}

$update_when_modified = true;
$admin_header = FALSE;
// Include WB admin wrapper script
require WB_PATH.'/modules/admin.php';

if ( isset($_POST['manage_posts']) && is_array($_POST['manage_posts']) && !$admin->checkFTAN()){
    $admin->print_header();
    $admin->print_error($MESSAGE['GENERIC_SECURITY_ACCESS']
	 .' (FTAN) '.__FILE__.':'.__LINE__,
         ADMIN_URL.'/pages/index.php');
    $admin->print_footer();
    exit();
} else $admin->print_header();

$post_id = $admin->checkIDKEY('post_id', 0, 'GET');
if(defined('WB_VERSION') && (version_compare(WB_VERSION, '2.8.3', '>'))) {
    $post_id = intval($_GET['post_id']);
}
if (!$post_id && isset($_GET['post_id'])) {
    $admin->print_error($MESSAGE['GENERIC_SECURITY_ACCESS']
	 .' (IDKEY) '.__FILE__.':'.__LINE__,
         ADMIN_URL.'/pages/index.php');
    $admin->print_footer();
    exit();
}

$value=1;
if(isset($_GET['value']) && ($_GET['value']==0)) $value=0;

$posts=array();
if (isset($_GET['post_id'])){    
    $posts = array($post_id);
} else {
    if(isset($_POST['manage_posts'])&&is_array($_POST['manage_posts'])) 
        $posts=$_POST['manage_posts'];
} 

// Include the ordering class
require WB_PATH.'/framework/class.order.php';

// store this one for later use
$mod_nwi_file_base=$mod_nwi_file_dir; 

foreach($posts as $post_id) {
    // Update row
    $database->query(sprintf(
        "UPDATE `%smod_news_img_posts`"
    . " SET `active` = '$value' "
        . " WHERE `post_id` = '$post_id'",
        TABLE_PREFIX
    ));
    if($database->is_error()) break;
}

if($database->is_error()) {
	$admin->print_error($database->get_error(), ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
} else {
	$admin->print_success($TEXT['SUCCESS'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
}

$admin->print_footer();
