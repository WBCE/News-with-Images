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

$post_id = filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$post_id) {
    $admin->print_error($MESSAGE['GENERIC_SECURITY_ACCESS'], ADMIN_URL.'/pages/index.php');
}

$FTAN = $admin->getFTAN();
// FTAN als Query-String-Fragment (formtoken=...) für die GET-Aktionen dieser
// Seite (Vorschaubild löschen, Galeriebild löschen, Umsortieren).
$FTAN_GET = $admin->getFTAN(false);
$post_id_key = $post_id;

// get post
$post_data = mod_nwi_post_get($post_id);
$post_data['content_short']=str_replace('{SYSVAR:MEDIA_REL}',WB_URL.MEDIA_DIRECTORY,$post_data['content_short']);
$post_data['content_long']=str_replace('{SYSVAR:MEDIA_REL}',WB_URL.MEDIA_DIRECTORY,$post_data['content_long']);
$post_data['content_block2']=str_replace('{SYSVAR:MEDIA_REL}',WB_URL.MEDIA_DIRECTORY,$post_data['content_block2']);

if(method_exists($admin, 'setViewUrl')) {
    $admin->setViewUrl(WB_URL.'/pages/posts/'.$post_data['link'].'.php');
}

// ----- delete previewimage ---------------------------------------------------
if (isset($_GET['post_img'])) {
    // CSRF: Löschung erfolgt per GET -> FTAN-Token prüfen.
    if (!$admin->checkFTAN('GET')) {
        $admin->print_error($MESSAGE['GENERIC_SECURITY_ACCESS']
             .' (FTAN) '.__FILE__.':'.__LINE__, ADMIN_URL.'/pages/index.php');
    }
    $post_img = basename($post_data['image']);
    $database->query(sprintf(
        "UPDATE `%smod_news_img_posts` SET `image`='' WHERE `post_id`=%d",
        TABLE_PREFIX, intval($post_id)
    ));
    if ($post_img && file_exists($mod_nwi_file_dir.$post_img)) {
        unlink($mod_nwi_file_dir.$post_img);
    }
    $post_data['image'] = null;
}   //end delete preview image

$mod_nwi_file_dir .= "$post_id/";
$mod_nwi_thumb_dir = $mod_nwi_file_dir . "thumb/";

// ----- delete gallery image --------------------------------------------------
if (isset($_GET['img_id'])) {
    // CSRF: Löschung erfolgt per GET -> FTAN-Token prüfen.
    if (!$admin->checkFTAN('GET')) {
        $admin->print_error($MESSAGE['GENERIC_SECURITY_ACCESS']
             .' (FTAN) '.__FILE__.':'.__LINE__, ADMIN_URL.'/pages/index.php');
    }

    $img_id = (int) filter_input(INPUT_GET, 'img_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $row = mod_nwi_img_get($img_id);
 
    if (!$row) {
        echo "Datei existiert nicht!";
    } else {
        $picname = basename($row['picname']);
        if ($picname) {
            if (file_exists($mod_nwi_file_dir.$picname)) {
                unlink($mod_nwi_file_dir.$picname);
            }
            if (file_exists($mod_nwi_thumb_dir.$picname)) {
                unlink($mod_nwi_thumb_dir.$picname);
            }
        }
    }
    $database->query(sprintf(
        "DELETE FROM `%smod_news_img_img` WHERE `id` = %d AND `post_id` = %d",
        TABLE_PREFIX, $img_id, $post_id
    ));
}   //end delete gallery image

// re-order images
if (isset($_GET['id']) && (isset($_GET['up']) || isset($_GET['down']))) {
    // CSRF: Umsortieren erfolgt per GET -> FTAN-Token prüfen.
    if (!$admin->checkFTAN('GET')) {
        $admin->print_error(
            $MESSAGE['GENERIC_SECURITY_ACCESS']
         .' (FTAN) '.__FILE__.':'.__LINE__,
                 ADMIN_URL.'/pages/index.php'
        );
    }
    $order = new order(TABLE_PREFIX.'mod_news_img_img', 'position', 'id', 'post_id');
    $id = (int) filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (isset($_GET['up'])) {
        $order->move_up($id);
    } else {
        $order->move_down($id);
    }
}

$settings = mod_nwi_settings_get($section_id);

// ----- make sure we have a WYSIWYG editor ------------------------------------
if (!defined('WYSIWYG_EDITOR') or WYSIWYG_EDITOR=="none" or !file_exists(WB_PATH.'/modules/'.WYSIWYG_EDITOR.'/include.php')) {
    function show_wysiwyg_editor($name, $id, $content, $width, $height)
    {
        echo '<textarea name="'.$name.'" id="'.$id.'" rows="10" cols="1" style="width: '.$width.'; height: '.$height.';">'.$content.'</textarea>';
    }
} else {
    $id_list=["short","long"];
    if ($settings['use_second_block']=='Y') {
        $id_list[]="block2";
    }
    require(WB_PATH.'/modules/'.WYSIWYG_EDITOR.'/include.php');
}

// split link
$link = $post_data['link'];
$parts = explode('/', $link);
$link = array_pop($parts);
$linkbase = implode('/', $parts);
if(strlen(PAGE_SPACER)) {
    $parts = explode(PAGE_SPACER, $link);
    array_pop($parts);
    $link = implode(PAGE_SPACER, $parts);
}
$assigned = [];
$tags = mod_nwi_get_tags($section_id);
$tags = mod_nwi_tag_sort($tags, 'tag', 'asc', true);

$assigned_tags = $database->query(sprintf(
    "SELECT * FROM `%smod_news_img_tags_posts` WHERE `post_id`=%d",
    TABLE_PREFIX, $post_id
));

while ($a=$assigned_tags->fetchRow()) {
    $assigned[$a['tag_id']] = 1;
}

// Create new order object and reorder
$order = new order(TABLE_PREFIX.'mod_news_img_img', 'position', 'id', 'post_id');
$order->clean($post_id);

// get images
$postimg = mod_nwi_img_get_by_post($post_id,false);
$images = [];
$seenimg = [];

if (count($postimg)>0) {
    $i=1;
    foreach ($postimg as $row) {
        $row['id_key'] = $row['id'];
        $row['up'] = '<span style="display:inline-block;width:20px;"></span>';
        $row['down'] = $row['up'];
        if ($i>1) { // not first
            $row['up'] = '<a href="'.WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id.'&post_id='. $post_id_key.'&id='.$row['id_key'] .'&up=1&'.$FTAN_GET.'">'
                . '<img src="'.THEME_URL.'/images/up_16.png"  class="mod_news_img_arrow" /></a>';
        }
        if ($i != (count($postimg)-1)) { // not last
            $row['down'] = '<a href="'.WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id.'&post_id='. $post_id_key.'&id='.$row['id_key'] .'&down=1&'.$FTAN_GET.'">'
                  . '<img src="'.THEME_URL.'/images/down_16.png"  class="mod_news_img_arrow" /></a>';
        }
        $i++;
        $images[] = $row;
        $seenimg[$row['picname']]=1;
    }
}

// $settings ist oben bereits geladen.
$imgmaxsize = $settings['imgmaxsize'];

list($groups,$pages) = mod_nwi_get_all_groups($section_id, $page_id);

if(!defined('CAT_PATH')) {
    include __DIR__.'/config/wbce_config.php';
} else {
    include __DIR__.'/config/bc_config.php';
}

include __DIR__.'/js/datetimepickers/'.$datetimepicker.'/include.phtml';
include __DIR__.'/templates/default/modify_post.phtml';

// Print admin footer
$admin->print_footer();
