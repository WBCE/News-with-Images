<?php


header('Content-type:application/json;charset=utf-8');

require_once('../../../config.php');
// check if user has permissions to access the news_img module
require_once(WB_PATH.'/framework/class.admin.php');
$admin = new admin('Pages', 'pages_modify', false, false);
if (!($admin->is_authenticated() && $admin->get_permission('news_img', 'module'))) {
    throw new RuntimeException('insuficcient rights');
}

// CSRF: Upload-Endpunkt -> FTAN-Token (per Query-String) prüfen.
if (!$admin->checkFTAN('GET')) {
    throw new RuntimeException('insufficient rights (FTAN)');
}

$post_id = filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$post_id) {
    throw new RuntimeException('missing or invalid post_id parameter');
}

require_once __DIR__.'/../functions.inc.php';

// get section id
$query_content = $database->query("SELECT `section_id` FROM `".TABLE_PREFIX."mod_news_img_posts` WHERE `post_id` = '$post_id'");
$fetch_content = $query_content->fetchRow();
$section_id = intval($fetch_content['section_id']);


// fetch settings
$query_content = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_img_settings` WHERE `section_id` = '$section_id'");
$fetch_content = $query_content->fetchRow();
if (!$fetch_content) {
    throw new RuntimeException('section settings not found');
}

$fetch_content['imgmaxsize'] = intval($fetch_content['imgmaxsize']);
$iniset = ini_get('upload_max_filesize');
$iniset = mod_nwi_return_bytes($iniset);

$previewwidth = $previewheight = $thumbwidth = $thumbheight = '';
if(substr_count((string)($fetch_content['resize_preview'] ?? ''),'x')>0) {
    list($previewwidth,$previewheight) = explode('x',$fetch_content['resize_preview'],2);
}
if(substr_count((string)($fetch_content['imgthumbsize'] ?? ''),'x')>0) {
    list($thumbwidth,$thumbheight) = explode('x',$fetch_content['imgthumbsize'],2);
}

$imageErrorMessage = '';
$imagemaxsize  = ($fetch_content['imgmaxsize']>0 && $fetch_content['imgmaxsize'] < $iniset)
    ? $fetch_content['imgmaxsize']
    : $iniset;

// Dimensionen als positive Ints; 0/leer -> großzügiger Fallback. mod_nwi_image_resize
// skaliert kleinere Bilder nicht hoch, kodiert sie aber (mit force) neu.
$imagemaxwidth  = (int)$fetch_content['imgmaxwidth']  ?: 99999;
$imagemaxheight = (int)$fetch_content['imgmaxheight'] ?: 99999;
$thumbwidth     = (int)$thumbwidth  ?: 100;
$thumbheight    = (int)$thumbheight ?: 100;
$crop           = (($fetch_content['crop_preview'] ?? '') == 'Y') ? 1 : 0;

try {
    if (
        !isset($_FILES['file']['error']) ||
        is_array($_FILES['file']['error'])
    ) {
        throw new RuntimeException('Invalid parameters.');
    }

    switch ($_FILES['file']['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            throw new RuntimeException('No file sent.');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('Exceeded filesize limit.');
        default:
            throw new RuntimeException('Unknown errors.');
    }

    $imageErrorMessage = '';

    $mod_nwi_file_dir .= "$post_id/";
    $mod_nwi_thumb_dir = $mod_nwi_file_dir . "thumb/";

    $filepath="";


    // post images (gallery images)
    if (isset($_FILES["file"])) {

	// make sure the folder exists
	if(!is_dir($mod_nwi_file_dir)) {
            mod_nwi_img_makedir($mod_nwi_file_dir);
	}
	// 2014-04-10 by BlackBird Webprogrammierung:
	//            image position (order)
	$picture = $_FILES["file"];
        if (isset($picture['name']) && $picture['name'] && (strlen($picture['name']) > 3))
        {
            $pic_error = '';
            //change special characters
            $imagename = media_filename($picture['name']);
            //small characters
            $imagename = strtolower($imagename) ;

            // Nur erlaubte Bild-Endungen zulassen (Whitelist). media_filename
            // behält die Endung, daher hier gegen $allowed_suffixes prüfen.
            $ext = strtolower(pathinfo($imagename, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_suffixes, true)) {
                throw new RuntimeException(($MOD_NEWS_IMG['GENERIC_FILE_TYPE'] ?? 'Invalid file type').' JPG, JPEG, PNG, GIF, WEBP');
            }

            //            if file exists, find new name by adding a number
            if (file_exists($mod_nwi_file_dir.$imagename)) {
                $num = 1;
                $f_name = pathinfo($mod_nwi_file_dir.$imagename, PATHINFO_FILENAME);
                $suffix = pathinfo($mod_nwi_file_dir.$imagename, PATHINFO_EXTENSION);
                while (file_exists($mod_nwi_file_dir.$f_name.'_'.$num.'.'.$suffix)) {
                    $num++;
                }
                $imagename = $f_name.'_'.$num.'.'.$suffix;
            }
            $filepath=$mod_nwi_file_dir.$imagename;
            // check
            if (empty($picture['size']) || $picture['size'] > $imagemaxsize) {
                $imageErrorMessage .= $MOD_NEWS_IMG['IMAGE_LARGER_THAN'].mod_nwi_byte_convert($imagemaxsize).'<br />';
            } elseif (strlen($imagename) > 256) {
                $imageErrorMessage .= $MOD_NEWS_IMG['IMAGE_FILENAME_ERROR'].'1<br />';
            } else {
                // move to media folder
                if(true===move_uploaded_file($picture['tmp_name'], $filepath)) {
                    // Gate: muss ein echtes Bild sein UND der erkannte Bildtyp muss
                    // zur Endung passen (verhindert getarnte Nicht-Bilder/Polyglots).
                    $info = @getimagesize($filepath);
                    $type_ext = [
                        IMAGETYPE_JPEG => ['jpg', 'jpeg'],
                        IMAGETYPE_PNG  => ['png'],
                        IMAGETYPE_GIF  => ['gif'],
                        IMAGETYPE_WEBP => ['webp'],
                    ];
                    $img_type = ($info !== false) ? (int)$info[2] : 0;
                    if ($info === false || !isset($type_ext[$img_type]) || !in_array($ext, $type_ext[$img_type], true)) {
                        @unlink($filepath);
                        throw new RuntimeException(($MOD_NEWS_IMG['GENERIC_FILE_TYPE'] ?? 'Invalid image file').' JPG, JPEG, PNG, GIF, WEBP');
                    }

                    // Immer neu kodieren (strippt eingebettete Payloads/EXIF) und
                    // auf die Maximalmaße begrenzen.
                    if (true !== ($pic_error = @mod_nwi_image_resize($filepath, $filepath, $imagemaxwidth, $imagemaxheight, $crop, true))) {
                        $imageErrorMessage .= $pic_error.'<br />';
                        @unlink($filepath); // delete image (cleanup)
                    } elseif (true !== ($pic_error = @mod_nwi_image_resize($filepath, $mod_nwi_thumb_dir.$imagename, $thumbwidth, $thumbheight, $crop, true))) {
                        // create thumb
                        $imageErrorMessage.=$pic_error.'<br />';
                        @unlink($filepath); // delete image (cleanup)
                    } else {
                        //            image position
                        $order = new order(TABLE_PREFIX.'mod_news_img_img', 'position', 'id', 'post_id');
                        $position = $order->get_new($post_id);
                        $database->query("INSERT INTO ".TABLE_PREFIX."mod_news_img_img (picname, post_id, position) VALUES ('".mod_nwi_escapeString($imagename)."', ".(int)$post_id.", ".(int)$position.')');
                    }
                } else {
                    $imageErrorMessage .= "Unable to move uploaded image ".$picture['tmp_name']." to ".$mod_nwi_file_dir.$imagename."<br />";
                }
            }
        }
    }

    if($imageErrorMessage!=""){
	throw new RuntimeException($imageErrorMessage);
    }


    // All good, send the response
    echo json_encode([
        'status' => 'ok',
        'path' => $filepath
    ]);

} catch (RuntimeException $e) {
	// Something went wrong, send the err message as JSON
	http_response_code(400);

	echo json_encode([
		'status' => 'error',
		'message' => $e->getMessage()
	]);
}
