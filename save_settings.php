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
$update_when_modified = true; // Tells script to update when this page was last updated
$admin_header = false;
require WB_PATH.'/modules/admin.php';

if (!defined('CAT_PATH')) {
    if (!$admin->checkFTAN()) {
        $admin->print_header();
        $admin->print_error(
            $MESSAGE['GENERIC_SECURITY_ACCESS']
             .' (FTAN) '.__FILE__.':'.__LINE__,
                 ADMIN_URL.'/pages/index.php'
            );
    } else {
        $admin->print_header();
    }
}

// change mode
if(isset($_POST['mode']) && in_array($_POST['mode'],['default','advanced'])) {
    $database->query(sprintf(
        "UPDATE `%smod_news_img_settings`"
        . " SET `mode`='%s' WHERE `section_id`=%d",
        TABLE_PREFIX, $_POST['mode'], $section_id
    ));
    if ($database->is_error()) {
        $admin->print_error($database->get_error(), ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
    } else {
        $admin->print_success($TEXT['SUCCESS'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
    }
    $admin->print_footer();
    exit;
}

// This code removes any <?php tags and adds slashes
$friendly = ['&lt;', '&gt;', '?php'];
$raw = ['<', '>', ''];

// get current settings
$settings = mod_nwi_settings_get($section_id);

// always there
// Hinweis: Werte werden hier ROH gehalten (nur ?php/<>-Filter). Das Escaping
// für die DB passiert gesammelt einmalig direkt vor dem UPDATE — so werden
// auch aus $settings bzw. View-/Galerie-Defaults stammende Werte sicher
// escaped und nichts doppelt escaped.
$header = str_replace($friendly, $raw, $_POST['header']);
$post_loop = str_replace($friendly, $raw, $_POST['post_loop']);
$view_order = intval($_POST['view_order']);
$footer = str_replace($friendly, $raw, $_POST['footer']);
$post_header = str_replace($friendly, $raw, $_POST['post_header']);
$post_content = str_replace($friendly, $raw, $_POST['post_content']);
$post_footer = str_replace($friendly, $raw, $_POST['post_footer']);
$posts_per_page = $_POST['posts_per_page'];
// Security: strip any character that is not a plain dir-name char (prevents path traversal)
$gallery = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($_POST['gallery'] ?? ''));
$use_second_block = (($_POST['use_second_block'] ?? '') === 'Y') ? 'Y' : 'N';
$show_settings_only_admins = (($_POST['show_settings_only_admins'] ?? '') === 'Y') ? 'Y' : 'N';

// ---------------------------------------------------------------------------
// default preview image — copy-on-save. Speichert eine eigenständige Kopie
// der Quelle (Upload ODER aus dem Galerie-Picker) als
// `default_<section_id>.<ext>` in media/.news_img/. Die Spalte
// default_preview_image enthält den Dateinamen; bleibt sie leer, fällt die
// Frontend-Kaskade direkt auf nopic.png durch.
//
// Priorität: Entfernen > Upload > Picker > Keep.
// ---------------------------------------------------------------------------
$default_preview_image = isset($settings['default_preview_image']) ? (string)$settings['default_preview_image'] : '';
$default_dir = WB_PATH.MEDIA_DIRECTORY.'/.news_img/';
$default_remove_old = function() use (&$default_preview_image, $default_dir) {
    if ($default_preview_image !== '' && file_exists($default_dir.$default_preview_image)) {
        @unlink($default_dir.$default_preview_image);
    }
    $default_preview_image = '';
};
$default_make_dest = function(string $ext) use ($section_id) {
    return 'default_'.(int)$section_id.'.'.strtolower($ext);
};

// 1) Entfernen
if (!empty($_POST['default_image_remove'])) {
    $default_remove_old();
}
// 2) Direkt-Upload
elseif (!empty($_FILES['default_image_upload']['tmp_name']) && is_uploaded_file($_FILES['default_image_upload']['tmp_name'])) {
    $up = $_FILES['default_image_upload'];
    $orig_ext = strtolower(pathinfo($up['name'], PATHINFO_EXTENSION));
    if (in_array($orig_ext, $allowed_suffixes, true)) {
        // Größe gegen den Sektion-Max-Wert (mit PHP-Limit als Obergrenze)
        $iniset = mod_nwi_return_bytes(ini_get('upload_max_filesize'));
        $section_max = (int)($settings['imgmaxsize'] ?? 0);
        $max_size = ($section_max > 0 && $section_max < $iniset) ? $section_max : $iniset;
        if ($up['size'] > 0 && $up['size'] <= $max_size) {
            $dest_name = $default_make_dest($orig_ext);
            $dest_path = $default_dir.$dest_name;
            if (!is_dir($default_dir)) {
                mod_nwi_img_makedir($default_dir, false);
            }
            if (move_uploaded_file($up['tmp_name'], $dest_path)) {
                // Gate: muss ein echtes Bild sein UND der erkannte Bildtyp muss
                // zur Endung passen (verhindert getarnte Nicht-Bilder/Polyglots,
                // da oben nur die Endung gegen die Whitelist geprüft wurde).
                $info = getimagesize($dest_path);
                $type_ext = [
                    IMAGETYPE_JPEG => ['jpg', 'jpeg'],
                    IMAGETYPE_PNG  => ['png'],
                    IMAGETYPE_GIF  => ['gif'],
                    IMAGETYPE_WEBP => ['webp'],
                ];
                $img_type = ($info !== false) ? (int)$info[2] : 0;
                if ($info === false || !isset($type_ext[$img_type]) || !in_array($orig_ext, $type_ext[$img_type], true)) {
                    @unlink($dest_path);
                } else {
                    // Immer neu kodieren (auch wenn schon klein genug) -> strippt
                    // eingebettete Payloads/EXIF. Auf Preview-Größe begrenzen.
                    list($pw, $ph,) = mod_nwi_get_sizes($section_id);
                    if (empty($pw)) { $pw = 150; }
                    if (empty($ph)) { $ph = 150; }
                    $crop = (($settings['crop_preview'] ?? 'N') === 'Y') ? 1 : 0;
                    if (mod_nwi_image_resize($dest_path, $dest_path, $pw, $ph, $crop, true) !== true) {
                        // Verarbeitung fehlgeschlagen -> nicht behalten
                        @unlink($dest_path);
                    } else {
                        // alte Datei nur löschen, falls die Endung gewechselt hat
                        if ($default_preview_image !== '' && $default_preview_image !== $dest_name) {
                            @unlink($default_dir.$default_preview_image);
                        }
                        $default_preview_image = $dest_name;
                    }
                }
            }
        }
    }
}
// 3) Picker: Galerie-Thumb als Quelle, kopieren
elseif (!empty($_POST['default_image_source'])) {
    $source_pic_id = (int)$_POST['default_image_source'];
    if ($source_pic_id > 0) {
        $check = $database->query(sprintf(
            "SELECT i.`picname`, i.`post_id` FROM `%smod_news_img_img` i "
            . "INNER JOIN `%smod_news_img_posts` p ON p.`post_id` = i.`post_id` "
            . "WHERE i.`id` = %d AND p.`section_id` = %d",
            TABLE_PREFIX, TABLE_PREFIX, $source_pic_id, (int)$section_id
        ));
        if ($check && $check->numRows() > 0) {
            $row = $check->fetchRow();
            // basename() als Defense-in-Depth, falls je ein DB-Datensatz mit
            // Pfadanteilen im picname existiert (kein Traversal im Quell-Pfad).
            $picname = basename((string)$row['picname']);
            $source_path = $default_dir.(int)$row['post_id'].'/thumb/'.$picname;
            if (file_exists($source_path)) {
                $src_ext = strtolower(pathinfo($picname, PATHINFO_EXTENSION));
                if (in_array($src_ext, $allowed_suffixes, true)) {
                    $dest_name = $default_make_dest($src_ext);
                    $dest_path = $default_dir.$dest_name;
                    if (@copy($source_path, $dest_path)) {
                        if ($default_preview_image !== '' && $default_preview_image !== $dest_name) {
                            @unlink($default_dir.$default_preview_image);
                        }
                        $default_preview_image = $dest_name;
                    }
                }
            }
        }
    }
}
// 4) Keep — kein Branch nötig, $default_preview_image bleibt unverändert.

// expert mode
if(isset($settings['mode']) && $settings['mode']=='advanced') {
    $image_loop = str_replace($friendly, $raw, $_POST['image_loop']);
    $gal_img_resize_width = $_POST['gal_img_resize_width'];
    $gal_img_resize_height = $_POST['gal_img_resize_height'];
    $gal_img_max_size = intval($_POST['gal_img_max_size'])*1024;
    // Security: strip any character that is not a plain dir-name char (prevents path traversal)
    $view = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($_POST['view'] ?? ''));
    $block2 = str_replace($friendly, $raw, $_POST['block2']);
    $thumbwidth = $_POST['thumb_width'];
    $thumbheight = $_POST['thumb_height'];
    // Zielverzeichnis der Access-Dateien: leer = automatisch aus Elternseite.
    // Wie bei view/gallery auf ein einzelnes Pfadsegment reduzieren (kein Traversal).
    $posts_dir = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($_POST['posts_dir'] ?? ''));
} else {
    $image_loop = $settings['image_loop'];
    $gal_img_resize_width = $settings['imgmaxwidth'];
    $gal_img_resize_height = $settings['imgmaxheight'];
    $gal_img_max_size = $settings['imgmaxsize'];
    $view = $settings['view'];
    $block2 = $settings['block2'];
    $posts_dir = $settings['posts_dir'] ?? '';
    list($previewwidth,
        $previewheight,
        $thumbwidth,
        $thumbheight
    ) = mod_nwi_get_sizes($section_id);
}

// if the user chooses a new view, the settings are overwritten by the
// defaults of this view!
if(!empty($view) && $view != $settings['view'] && preg_match('/^[a-zA-Z0-9_-]+$/', $view)) {
    include __DIR__.'/views/'.$view.'/config.php';
}

$resize_preview = '';
$crop = 'N';

// resize_width/height werden nur gerendert, wenn GD verfügbar ist -> ?? ''.
$width = $_POST['resize_width'] ?? '';
$height = $_POST['resize_height'] ?? '';
$thumbsize = "100x100"; // default

$crop = $_POST['crop_preview'] ?? 'N';
if (is_numeric($width) && is_numeric($height)) {
    if ($height>0 && $width>0) {
        $resize_preview = $width.'x'.$height;
    }
}
if (is_numeric($thumbwidth) && is_numeric($thumbheight)) {
    if ($thumbheight>0 && $thumbwidth>0) {
        $thumbsize = $thumbwidth.'x'.$thumbheight;
    }
}
if ($crop=='on') {
    $crop = 'Y';
} else {
    $crop = 'N';
}

// leeres posts_per_page (= unbegrenzt) wird beim (int)-Cast vor dem UPDATE zu 0.

// if the gallery setting changed, load default settings.
// Der bisherige Wert steht bereits in $settings (geladen oben) -> keine
// zweite Query nötig.
if (($settings['gallery'] ?? '') != $gallery && preg_match('/^[a-zA-Z0-9_-]+$/', $gallery)) {
    include WB_PATH.'/modules/news_img/js/galleries/'.$gallery.'/settings.php';
}

$gal_img_max_size = intval($gal_img_max_size);
$gal_img_resize_width = intval($gal_img_resize_width);
$gal_img_resize_height = intval($gal_img_resize_height);

// if the "use block 2" setting changed...
if($settings['use_second_block'] != $use_second_block) {
    // from on to off: delete content
    if($use_second_block == 'N') {
        $block2 = '';
    }
}


// Freitext-Felder hier EINMALIG für die DB escapen. Bis zu diesem Punkt sind
// alle Werte roh — egal ob aus $_POST, aus $settings (Nicht-Advanced-Modus)
// oder aus den View-/Galerie-Defaults. So wird konsistent genau einmal
// escaped und keine Quelle bleibt unescaped in der Query.
$header        = mod_nwi_escapeString($header);
$post_loop     = mod_nwi_escapeString($post_loop);
$footer        = mod_nwi_escapeString($footer);
$post_header   = mod_nwi_escapeString($post_header);
$post_content  = mod_nwi_escapeString($post_content);
$post_footer   = mod_nwi_escapeString($post_footer);
$block2        = mod_nwi_escapeString($block2);
$image_loop    = mod_nwi_escapeString($image_loop);
$posts_per_page = (int) $posts_per_page;

// Update settings
$database->query(
    "UPDATE `".TABLE_PREFIX."mod_news_img_settings`"
    . " SET"
    . " `header` = '$header',"
    . " `post_loop` = '$post_loop',"
    . " `view_order` = '$view_order',"
    . " `footer` = '$footer',"
    . " `block2` = '$block2',"
    . " `posts_per_page` = '$posts_per_page',"
    . " `post_header` = '$post_header',"
    . " `post_content` = '$post_content',"
    . " `image_loop` = '$image_loop',"
    . " `post_footer` = '$post_footer',"
    . " `resize_preview` = '$resize_preview',"
    . " `crop_preview` = '$crop',"
    . " `gallery` = '$gallery',"
    . " `imgmaxsize`='$gal_img_max_size',"
    . " `imgmaxwidth`='$gal_img_resize_width',"
    . " `imgmaxheight`='$gal_img_resize_height',"
    . " `imgthumbsize`='$thumbsize',"
    . " `use_second_block`='$use_second_block',"
	. " `show_settings_only_admins`='$show_settings_only_admins',"
    // $default_preview_image ist serverkontrolliert ('' oder
    // default_<int>.<whitelist-ext>), nie roher User-Input -> SQL-safe.
    . " `default_preview_image`='$default_preview_image',"
    // $posts_dir ist serverseitig auf [a-zA-Z0-9_-] reduziert -> SQL-safe.
    . " `posts_dir`='$posts_dir',"
    . " `view`='$view'"
    . " WHERE `section_id` = '$section_id'"
);

// Check result
if ($database->is_error()) {
    $admin->print_error($database->get_error(), ADMIN_URL.'/pages/modify_settings.php?page_id='.$page_id);
} else {
    $admin->print_success($TEXT['SUCCESS'], ADMIN_URL.'/pages/modify_settings.php?page_id='.$page_id);
}

// Print admin footer
$admin->print_footer();
