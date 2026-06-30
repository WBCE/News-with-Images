<?php

require_once __DIR__.'/../../config.php';

// Include WB functions file
require_once WB_PATH.'/framework/functions.php';

// Include the ordering class
require_once WB_PATH.'/framework/class.order.php';

// load module language file
require __DIR__ . '/languages/EN.php';
$lang = __DIR__ . '/languages/' . LANGUAGE . '.php';
if (file_exists($lang)) {
    require $lang;
}

global $allowed_suffixes;
$allowed_suffixes = array('jpg','jpeg','gif','png','webp');

$mod_nwi_file_dir = WB_PATH.MEDIA_DIRECTORY.'/.news_img/';
$mod_nwi_thumb_dir = WB_PATH.MEDIA_DIRECTORY.'/.news_img/thumb/';

/**
 **/
function mod_nwi_get_slide(int $sectionID, ?string $tpl = 'bs5carousel')
{
    $posts = mod_nwi_posts_getall($sectionID, false, '', true);
    if (empty($tpl)) {
        $tpl = 'bs5carousel';
    }
    if (!empty($posts)) {
        ob_start();
        include __DIR__.'/templates/slides/'.$tpl.'.txt';
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    } else {
        echo "no slides";
    }
}

// ========== URL helper =======================================================
/**
 * Build a URL by appending query parameters to a base URL.
 * Handles ?/& delimiter automatically and skips null/false/empty values.
 *
 * Returns a raw URL with '&' as the parameter separator (RFC 3986). Callers
 * that embed the result into an HTML attribute MUST run it through
 * htmlspecialchars() themselves. This keeps the URL usable for HTTP
 * Location headers and JavaScript redirects without literal "&amp;".
 *
 * @param string $base   Base URL (e.g. page URL or '' for relative query-only links)
 * @param array  $params Associative array of query parameters; null/false/'' values are omitted
 * @return string
 **/
function mod_nwi_build_url(string $base, array $params): string
{
    $filtered = array_filter($params, fn ($v) => $v !== null && $v !== false && $v !== '');
    if (empty($filtered)) {
        return $base;
    }
    $query = http_build_query($filtered, '', '&', PHP_QUERY_RFC3986);
    $sep = str_contains($base, '?') ? '&' : '?';
    return $base . $sep . $query;
}

/**
 * Parse the pipe-delimited group select value (format: "group_id|section_id|page_id").
 * Returns an associative array ['g', 's', 'p'] on success, null on invalid format.
 */
function mod_nwi_parse_group_param(string $group): ?array
{
    if (empty($group)) {
        return null;
    }
    $parts = explode('|', $group, 3);
    if (count($parts) !== 3) {
        return null;
    }
    return [
        'g' => intval($parts[0]),
        's' => intval($parts[1]),
        'p' => intval($parts[2]),
    ];
}

// ========== Tag Sorting helper ===============================================
/**
* sort an array
*
* @access public
* @param  array   $array          - array to sort
* @param  mixed   $index          - key to sort by
* @param  string  $order          - 'asc' (default) || 'desc'
* @param  boolean $natsort        - default: false
* @param  boolean $case_sensitive - sort case sensitive; default: false
*
**/
function mod_nwi_tag_sort($array, $index, $order = 'asc', $natsort = false, $case_sensitive = false)
{
    if (is_array($array) && count($array)) {
        foreach (array_keys($array) as $key) {
            $temp[$key] = $array[$key][$index];
        }
        if (!$natsort) {
            ($order == 'asc') ? asort($temp) : arsort($temp);
        } else {
            ($case_sensitive) ? natsort($temp) : natcasesort($temp);
            if ($order != 'asc') {
                $temp = array_reverse($temp, true);
            }
        }
        foreach (array_keys($temp) as $key) {
            (is_numeric($key)) ? $sorted[] = $array[$key] : $sorted[$key] = $array[$key];
        }
        return $sorted;
    }
    return $array;
}   // end function mod_nwi_tag_sort()

// ========== Groups ===========================================================

/**
 * SQL-Bedingung für die konsistente Durchsetzung des Gruppen-`active`-Flags
 * (Variante A): Ein Beitrag ist sichtbar, wenn er keiner Gruppe angehört
 * (group_id=0) ODER seine Gruppe aktiv ist. In allen Frontend-Abfragen
 * (Liste, Detail-Prev/Next, Droplet) verwenden, damit deaktivierte Gruppen
 * ihre Beiträge überall ausblenden.
 *
 * @param  string $alias  Tabellen-Alias der posts-Tabelle in der Query
 * @return string         führende " AND (...) "-Bedingung
 */
function mod_nwi_sql_group_active(string $alias = 't1'): string
{
    return " AND (`$alias`.`group_id` = 0 OR EXISTS ("
         . "SELECT 1 FROM `".TABLE_PREFIX."mod_news_img_groups` AS `gx` "
         . "WHERE `gx`.`group_id` = `$alias`.`group_id` AND `gx`.`active` = '1')) ";
}

/**
 * get groups for section with ID $section_id
 *
 * @param   int   $section_id
 * @return
 **/
function mod_nwi_get_group(int $group_id): array
{
    global $database;
    $query = $database->query(sprintf(
        "SELECT * FROM `%smod_news_img_groups` " .
        "WHERE `group_id`=%d",
        TABLE_PREFIX,
        $group_id
    ));
    if ($query->numRows() > 0) {
        return $query->fetchRow();
    }
    return array();
}   // end function mod_nwi_get_groups()

/**
 * get groups for section with ID $section_id
 *
 * @param   int   $section_id
 * @return
 **/
function mod_nwi_get_groups(int $section_id): array
{
    global $database, $admin;
    $groups = [];
    $query = $database->query(sprintf(
        "SELECT * FROM `%smod_news_img_groups` " .
        "WHERE `section_id`=%d ORDER BY `position` ASC",
        TABLE_PREFIX,
        $section_id
    ));
    if ($query->numRows() > 0) {
        // Loop through groups
        while ($group = $query->fetchRow()) {
            $group['id_key'] = $group['group_id'];
            $group['image'] = '';
            foreach (array_values(array('png','jpg','jpeg','gif','webp')) as $suffix) {
                if (file_exists(WB_PATH.MEDIA_DIRECTORY.'/.news_img/image'.$group['group_id'].'.'.$suffix)) {
                    $group['image'] = WB_URL.MEDIA_DIRECTORY.'/.news_img/image'.$group['group_id'].'.'.$suffix;
                }
            }
            $groups[] = $group;
        }
    }
    return $groups;
}   // end function mod_nwi_get_groups()

/**
 * get groups for all NWI sections on all pages
 *
 * result array:
 * page_id => array of groups
 *
 * @param   int   $section_id
 * @param   int   $page_id
 * @return  array
 **/
function mod_nwi_get_all_groups($section_id, $page_id)
{
    global $database, $admin;

    $groups = [];
    $pages = [];

    // get groups for this section
    if ($section_id != 0) {
        $groups[$page_id] = [];
        $groups[$page_id][$section_id] = mod_nwi_get_groups(intval($section_id));
    }

    // get all other NWI sections
    $sections = mod_nwi_sections();
    foreach ($sections as $sect) {
        if ($sect['section_id'] != $section_id) { // skip current
            $groups[$sect['page_id']] = [];
            // groups
            $groups[$sect['page_id']][$sect['section_id']] = mod_nwi_get_groups(intval($sect['section_id']));
            // get page details for the dropdown
            $pid = intval($sect['page_id']);
            $page_title = "";
            $page_details = "";
            if ($pid != 0) { // find out the page title and print separator line
                $page_details = $admin->get_page_details($pid);
                if (!empty($page_details)) {
                    $page_title = isset($page_details['page_title'])
                                ? $page_details['page_title']
                                : (isset($page_details['menu_title'])
                                    ? $page_details['menu_title']
                                    : "");
                }
                $pages[$pid] = $page_title;
            }
        }
    }
    return array($groups,$pages);
}   // end function mod_nwi_get_all_groups()

// ========== Tags =============================================================

/**
 *
 * @access
 * @return
 **/
function mod_nwi_get_tag($tag_id)
{
    global $database;
    $query_tags = $database->query(sprintf(
        "SELECT *, " .
        "(SELECT GROUP_CONCAT(`section_id`) FROM `%smod_news_img_tags_sections` AS `t2` WHERE `tag_id`=%d) as `sections` " .
        "FROM `%smod_news_img_tags` AS t1 ".
        "WHERE `tag_id`=%d",
        TABLE_PREFIX,
        intval($tag_id),
        TABLE_PREFIX,
        intval($tag_id)
    ));
    if (!empty($query_tags) && $query_tags->numRows() > 0) {
        return $query_tags->fetchRow();
    }
    return array();
}   // end function mod_nwi_get_tag()

/**
 * get existing tags for current section
 * @param  int   $section_id
 * @param  bool  $alltags
 * @return array
 **/
function mod_nwi_get_tags($section_id = null, $alltags = false)
{
    global $database;
    $tags = [];
    $where = "WHERE `section_id`=0";
    if (!empty($section_id)) {
        $section_id = intval($section_id);
        $where .= " OR `section_id` = '$section_id'";
    }
    if ($alltags === true) {
        $where = null;
    }
    $query_tags = $database->query(sprintf(
        "SELECT * FROM `%smod_news_img_tags` AS t1 " .
        "JOIN `%smod_news_img_tags_sections` AS t2 " .
        "ON t1.tag_id=t2.tag_id ".
        $where,
        TABLE_PREFIX,
        TABLE_PREFIX
    ));
    if (!empty($query_tags) && $query_tags->numRows() > 0) {
        while ($t = $query_tags->fetchRow()) {
            $tags[$t['tag_id']] = $t;
        }
    }
    return $tags;
}   // end function mod_nwi_get_tags()

/**
 * get tags for given post
 * @param  int   $post_id
 * @return array
 **/
function mod_nwi_get_tags_for_post($post_id)
{
    global $database;
    $tags = [];

    $query_tags = $database->query(sprintf(
        "SELECT  t1.*, t4.`page_id` " .
        "FROM `%smod_news_img_tags` AS t1 " .
        "JOIN `%smod_news_img_tags_posts` AS t2 " .
        "ON t1.`tag_id`=t2.`tag_id` ".
        "JOIN `%smod_news_img_posts` AS t3 ".
        "ON t2.`post_id`=t3.`post_id` ".
        "JOIN `%ssections` AS t4 ".
        "ON t3.`section_id`=t4.`section_id` ".
        "WHERE t2.`post_id`=%d",
        TABLE_PREFIX,
        TABLE_PREFIX,
        TABLE_PREFIX,
        TABLE_PREFIX,
        $post_id
    ));

    if (!empty($query_tags) && $query_tags->numRows() > 0) {
        while ($t = $query_tags->fetchRow()) {
            $tags[$t['tag_id']] = $t;
        }
    }
    $tags = mod_nwi_tag_sort($tags, 'tag', 'asc', true);
    return $tags;
}   // end function mod_nwi_get_tags_for_post()

/**
 * Batch-fetch tags for multiple posts in a single query.
 * Returns an array indexed by post_id, each value is the same format
 * as mod_nwi_get_tags_for_post() (tag_id => tag row, sorted).
 */
function mod_nwi_get_tags_for_posts(array $post_ids): array
{
    global $database;
    if (empty($post_ids)) {
        return [];
    }
    $ids = array_values(array_filter(array_map('intval', $post_ids), fn ($id) => $id > 0));
    if (empty($ids)) {
        return [];
    }
    $in = implode(',', $ids);
    $result = [];
    $query_tags = $database->query(sprintf(
        "SELECT t1.*, t2.`post_id`, t4.`page_id` " .
        "FROM `%smod_news_img_tags` AS t1 " .
        "JOIN `%smod_news_img_tags_posts` AS t2 ON t1.`tag_id`=t2.`tag_id` " .
        "JOIN `%smod_news_img_posts` AS t3 ON t2.`post_id`=t3.`post_id` " .
        "JOIN `%ssections` AS t4 ON t3.`section_id`=t4.`section_id` " .
        "WHERE t2.`post_id` IN (%s)",
        TABLE_PREFIX,
        TABLE_PREFIX,
        TABLE_PREFIX,
        TABLE_PREFIX,
        $in
    ));
    if (!empty($query_tags) && $query_tags->numRows() > 0) {
        while ($t = $query_tags->fetchRow()) {
            $pid = (int)$t['post_id'];
            $result[$pid][$t['tag_id']] = $t;
        }
    }
    foreach ($result as $pid => $tags) {
        $result[$pid] = mod_nwi_tag_sort($tags, 'tag', 'asc', true);
    }
    return $result;
}   // end function mod_nwi_get_tags_for_posts()

/**
 * check if tag is valid for given section
 * @param  int    $section_id
 * @param  string $tag
 * @return bool
 **/
function mod_nwi_tag_exists(int $section_id, string $tag)
{
    global $database;
    $sql   = sprintf(
        "SELECT * FROM `%smod_news_img_tags` AS t1 " .
        "JOIN `%smod_news_img_tags_sections` AS t2 " .
        "ON `t1`.`tag_id`=`t2`.`tag_id` " .
        "WHERE `tag`='%s' ".
        "AND (`t2`.`section_id`=%d OR `t2`.`section_id`=0)",
        TABLE_PREFIX,
        TABLE_PREFIX,
        mod_nwi_escapeString($tag),
        $section_id
    );
    $query = $database->query($sql);
    if (!empty($query) && $query->numRows() > 0) {
        return true;
    }
    return false;
}   // end function mod_nwi_tag_exists()

// ========== Users ============================================================
/**
 *
 * @access
 * @return
 **/
function mod_nwi_users_get()
{
    global $database;
    $users = [];
    $query_users = $database->query(sprintf(
        "SELECT `user_id`,`username`,`display_name`,`email` FROM `%susers`",
        TABLE_PREFIX
    ));
    if (!empty($query_users) && $query_users->numRows() > 0) {
        while ($user = $query_users->fetchRow()) {
            // Insert user info into users array
            $user_id = $user['user_id'];
            $users[$user_id]['username'] = $user['username'];
            $users[$user_id]['display_name'] = $user['display_name'];
            $users[$user_id]['email'] = $user['email'];
        }
    }
    return $users;
}   // end function mod_nwi_users_get()


// ========== Images ===========================================================

function mod_nwi_img_copy($source, $dest)
{
    // Refuse to follow symlinks — copying via a symlink could escape the
    // intended media directory (e.g. a symlink pointing at /etc).
    if (is_link($source)) {
        return;
    }
    if (is_dir($source)) {
        $dir_handle = opendir($source);
        while ($file = readdir($dir_handle)) {
            if ($file != "." && $file != "..") {
                $src_path = $source."/".$file;
                $dst_path = $dest."/".$file;
                // Skip any symlinks encountered while traversing.
                if (is_link($src_path)) {
                    continue;
                }
                if (is_dir($src_path)) {
                    if (!is_dir($dst_path)) {
                        mkdir($dst_path);
                    }
                    mod_nwi_img_copy($src_path, $dst_path);
                } else {
                    copy($src_path, $dst_path);
                }
            }
        }
        closedir($dir_handle);
    } else {
        if (file_exists($source)) {
            copy($source, $dest);
        }
    }
}

function mod_nwi_img_get($pic_id)
{
    global $database;
    $query_img = $database->query(sprintf(
        "SELECT * FROM `%smod_news_img_img` " .
        "WHERE `id` = %d",
        TABLE_PREFIX,
        intval($pic_id)
    ));
    if (!empty($query_img) && $query_img->numRows() > 0) {
        return $query_img->fetchRow();
    }
    return array();
}

function mod_nwi_img_makedir($dir, $with_thumb = true)
{
    if (make_dir($dir)) {
        // Add a index.php file to prevent directory spoofing
        $content = ''.
"<?php

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

header('Location: ../');
?>";
        $handle = fopen($dir.'/index.php', 'w');
        fwrite($handle, $content);
        fclose($handle);
        change_mode($dir.'/index.php', 'file');
    }
    if ($with_thumb) {
        $dir .= '/thumb';
        if (make_dir($dir)) {
            // Add a index.php file to prevent directory spoofing
            $content = ''.
"<?php

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

header('Location: ../');
?>";
            $handle = fopen($dir.'/index.php', 'w');
            fwrite($handle, $content);
            fclose($handle);
            change_mode($dir.'/index.php', 'file');
        }
    }
}

function mod_nwi_img_upload($post_id, $is_preview_image = false)
{
    global $database, $mod_nwi_file_dir, $allowed_suffixes;

    // upload.php = 'file'
    // modify_post.php (preview image) = 'postfoto'
    $key = 'file';
    if ($is_preview_image) {
        $key = 'postfoto';
    } else {
        $mod_nwi_file_dir .= "$post_id/";
    }
    $mod_nwi_thumb_dir = $mod_nwi_file_dir . "thumb/";
    $imageErrorMessage = null;

    // get section id
    $post_data = mod_nwi_post_get($post_id);
    $section_id = intval($post_data['section_id']);

    // get settings
    $settings = mod_nwi_settings_get($section_id);

    $settings['imgmaxsize'] = intval($settings['imgmaxsize']);
    $iniset = ini_get('upload_max_filesize');
    $iniset = mod_nwi_return_bytes($iniset);

    list($previewwidth,
        $previewheight,
        $thumbwidth,
        $thumbheight
    ) = mod_nwi_get_sizes($section_id);

    // gallery images size
    $imagemaxsize  = ($settings['imgmaxsize'] > 0 && $settings['imgmaxsize'] < $iniset)
        ? $settings['imgmaxsize']
        : $iniset;

    $imagemaxwidth  = $settings['imgmaxwidth'];
    $imagemaxheight = $settings['imgmaxheight'];
    $crop           = ($settings['crop_preview'] == 'Y') ? 1 : 0;

    // make sure the folder exists
    if (!is_dir($mod_nwi_file_dir)) {
        mod_nwi_img_makedir($mod_nwi_file_dir);
    }

    // handle upload
    if (isset($_FILES[$key]) && is_array($_FILES[$key])) {
        $picture = $_FILES[$key];
        if (isset($picture['name']) && $picture['name'] && (strlen($picture['name']) > 3)) {
            $pic_error = '';
            // change special characters
            $imagename = media_filename($picture['name']);
            // validate suffix
            $suffix = strtolower(pathinfo($imagename, PATHINFO_EXTENSION));
            if (!in_array($suffix, $allowed_suffixes)) {
                $imageErrorMessage = 'invalid file type';
            } else {
                // lowercase filename and find a free one
                $imagename = mod_nwi_find_free_filename($mod_nwi_file_dir, strtolower($imagename));
                $filepath = $mod_nwi_file_dir.$imagename;
                // check size limit
                if (empty($picture['size']) || $picture['size'] > $imagemaxsize) {
                    $imageErrorMessage .= $MOD_NEWS_IMG['IMAGE_LARGER_THAN'].mod_nwi_byte_convert($imagemaxsize).'<br />';
                } elseif (strlen($imagename) > '256') {
                    $imageErrorMessage .= $MOD_NEWS_IMG['IMAGE_FILENAME_ERROR'].'<br />';
                } else {
                    // move to media folder
                    if (true === move_uploaded_file($picture['tmp_name'], $filepath)) {
                        // preview images have a different size (smaller in most cases)
                        if ($is_preview_image) {
                            $imagemaxwidth  = $previewwidth;
                            $imagemaxheight = $previewheight;
                        }
                        // fix for empty max values
                        if (empty($imagemaxwidth)) {
                            $imagemaxwidth = 150;
                        }
                        if (empty($imagemaxheight)) {
                            $imagemaxheight = 150;
                        }

                        // resize image (if larger than max width and height)
                        if (list($w, $h) = getimagesize($mod_nwi_file_dir.$imagename)) {
                            if ($w > $imagemaxwidth || $h > $imagemaxheight) {
                                if (true !== ($pic_error = @mod_nwi_image_resize($mod_nwi_file_dir.$imagename, $mod_nwi_file_dir.$imagename, $imagemaxwidth, $imagemaxheight, $crop))) {
                                    $imageErrorMessage .= $pic_error.'<br />';
                                    @unlink($mod_nwi_file_dir.$imagename); // delete image (cleanup)
                                }
                            }
                        }
                        if ($is_preview_image) {
                            $database->query(sprintf(
                                "UPDATE `%smod_news_img_posts` SET `image`='%s' " .
                                "WHERE `post_id`=%d",
                                TABLE_PREFIX,
                                $imagename,
                                $post_id
                            ));
                            if ($database->is_error()) {
                                $imageErrorMessage .= $database->get_error()."<br />";
                            }
                        } else {
                            // create thumb
                            if (true !== ($pic_error = @mod_nwi_image_resize($mod_nwi_file_dir.$imagename, $mod_nwi_thumb_dir.$imagename, $thumbwidth, $thumbheight, $crop))) {
                                $imageErrorMessage .= $pic_error.'<br />';
                                @unlink($mod_nwi_file_dir.$imagename); // delete image (cleanup)
                            } else {
                                $pic_id = null;
                                // insert image into image table
                                $database->query(sprintf(
                                    "INSERT INTO `%smod_news_img_img` " .
                                    "(`picname`) " .
                                    "VALUES ('%s')",
                                    TABLE_PREFIX,
                                    $imagename
                                ));
                                if ($database->is_error()) {
                                    $imageErrorMessage .= $database->get_error()."<br />";
                                } else {
                                    $pic_id = $database->getLastInsertId();
                                }
                                // image position
                                $order = new order(TABLE_PREFIX.'mod_news_img_img', 'position', 'id', 'post_id');
                                $position = $order->get_new($post_id);
                                // connect with current post
                                $database->query(sprintf(
                                    "INSERT INTO `%smod_news_img_posts_img` " .
                                    "(`post_id`,`pic_id`,`position`) " .
                                    "VALUES ('%s',%d,%d)",
                                    TABLE_PREFIX,
                                    $post_id,
                                    $pic_id,
                                    $position
                                ));
                                if ($database->is_error()) {
                                    $imageErrorMessage .= $database->get_error()."<br />";
                                }
                            }
                        }
                    } else {
                        $imageErrorMessage .= "Unable to move uploaded image ".$picture['tmp_name']." to ".$mod_nwi_file_dir.$imagename."<br />";
                    }
                }
            }
        }
    }
    return $imageErrorMessage;
}

// ===== POSTS =================================================================

function mod_nwi_post_activate($value)
{
    global $database;
    $posts = [];
    if (isset($_POST['manage_posts']) && is_array($_POST['manage_posts'])) {
        $posts = $_POST['manage_posts'];
    } else {
        return false;
    }

    $value = (int)$value;

    $errors = 0;
    foreach ($posts as $post_id) {
        $post_id = (int)$post_id;
        if ($post_id <= 0) {
            continue;
        }

        // Update row
        $database->query(sprintf(
            "UPDATE `%smod_news_img_posts`"
            . " SET `active` = '%d' "
            . " WHERE `post_id` = %d",
            TABLE_PREFIX,
            $value,
            $post_id
        ));
        if ($database->is_error()) {
            $errors++;
        }

        $post = [];
        $page = [];
        $postQuery = sprintf("SELECT * from `%smod_news_img_posts` WHERE `post_id`=%d", TABLE_PREFIX, $post_id);
        $query_post = $database->query($postQuery);
        if ($query_post && $query_post->numRows() > 0) {
            $post = $query_post->fetchRow();
        }
        if (empty($post)) {
            $errors++;
            continue;
        }

        $pageQuery = sprintf("SELECT * from `%ssections` WHERE `section_id`=%d", TABLE_PREFIX, (int)$post['section_id']);
        $query_page = $database->query($pageQuery);
        if ($query_page && $query_page->numRows() > 0) {
            $page = $query_page->fetchRow();
        }
        if (empty($page)) {
            $errors++;
            continue;
        }

        if ($post['active'] == "0") {
            if (is_writable(WB_PATH.PAGES_DIRECTORY.$post['link'].PAGE_EXTENSION)) {
                unlink(WB_PATH.PAGES_DIRECTORY.$post['link'].PAGE_EXTENSION);
            }

        } else {
            $filename = WB_PATH.PAGES_DIRECTORY.$post['link'].PAGE_EXTENSION;
            mod_nwi_create_file($filename, '', $post_id, $post['section_id'], $page['page_id']);
        }

    }
    return ($errors > 0 ? false : true);
}


function mod_nwi_post_clear($value)
{
    global $database;
    $posts = [];
    if (isset($_POST['manage_posts']) && is_array($_POST['manage_posts'])) {
        $posts = $_POST['manage_posts'];
    } else {
        return false;
    }

    // hard whitelist of allowed column names
    if (!in_array($value, ['published_when', 'published_until'], true)) {
        return false;
    }

    $errors = 0;
    foreach ($posts as $post_id) {
        $post_id = (int)$post_id;
        if ($post_id <= 0) {
            continue;
        }

        // Update row
        $database->query(sprintf(
            "UPDATE `%smod_news_img_posts`"
            . " SET `%s` = '0' "
            . " WHERE `post_id` = %d",
            TABLE_PREFIX,
            $value,
            $post_id
        ));
        if ($database->is_error()) {
            $errors++;
        }

        $post = [];
        $page = [];
        $postQuery = sprintf("SELECT * from `%smod_news_img_posts` WHERE `post_id`=%d", TABLE_PREFIX, $post_id);
        $query_post = $database->query($postQuery);
        if ($query_post && $query_post->numRows() > 0) {
            $post = $query_post->fetchRow();
        }
        if (empty($post)) {
            $errors++;
            continue;
        }

        $pageQuery = sprintf("SELECT * from `%ssections` WHERE `section_id`=%d", TABLE_PREFIX, (int)$post['section_id']);
        $query_page = $database->query($pageQuery);
        if ($query_page && $query_page->numRows() > 0) {
            $page = $query_page->fetchRow();
        }
        if (empty($page)) {
            $errors++;
            continue;
        }

        // check if accessfile should be created...
        $createFile = true;										// by default: yes.
        if ($post['published_when'] != 0 && $post['published_when'] > time()) {
            $createFile = false;
        }    // no, because the post is not public yet.
        if ($post['published_until'] != 0 && $post['published_until'] < time()) {
            $createFile = false;
        }  // no, because the post is no longer public.
        if ($post['active'] != 1) {
            $createFile = false;
        }						// no, the post is created inactive.
        $filename = WB_PATH.PAGES_DIRECTORY.'/'.$post['link'].PAGE_EXTENSION;
        if (!file_exists($filename) && $createFile == true) {
            mod_nwi_create_file($filename, '', $post['post_id'], $post['section_id'], $page['page_id']);
        }
        // remove access file if it exists and it shouldn't - not sure if this can happen at all
        if (file_exists($filename) && $createFile == false && is_writable($filename)) {
            unlink($filename);
        }


    }
    return ($errors > 0 ? false : true);
}


/**
 * Resolves the copy target (section, page, group) from the POSTed 'group' parameter.
 * Redirects and exits if the parameter is malformed.
 *
 * @return array{section_id:int, page_id:int, group_id:int}
 */
function mod_nwi_post_copy_parse_target(int $section_id, int $page_id): array
{
    global $admin;

    $group_id = 0;
    $group = $admin->get_post_escaped('group');

    if (!empty($group)) {
        $values = mod_nwi_parse_group_param($group);
        if ($values === null) {
            header("Location: ".ADMIN_URL."/pages/index.php");
            exit(0);
        }
        if ($values['p'] != 0) {
            $group_id   = $values['g'];
            $section_id = $values['s'];
            $page_id    = $values['p'];
        }
    }

    return ['section_id' => $section_id, 'page_id' => $page_id, 'group_id' => $group_id];
}


/**
 * Computes the new post link from the original link and post ID,
 * then creates the access file on disk when the post is active.
 *
 * @return string  The new relative post link (without extension).
 */
function mod_nwi_post_make_link(string $link, int $post_id, int $section_id, int $page_id, string $active): string
{
    global $admin, $MESSAGE;

    $post_link = '/posts/' . page_filename(
        preg_replace('/^\/?posts\/?/s', '', preg_replace('/-[0-9]*$/s', '', $link, 1))
    );
    if (substr_compare($post_link, (string)$post_id, -(strlen((string)$post_id)), strlen((string)$post_id)) != 0) {
        $post_link .= PAGE_SPACER . $post_id;
    }

    make_dir(WB_PATH . PAGES_DIRECTORY . '/posts/');
    if (!is_writable(WB_PATH . PAGES_DIRECTORY . '/posts/')) {
        $admin->print_error($MESSAGE['PAGES_CANNOT_CREATE_ACCESS_FILE']);
    } elseif ($active == "1") {
        $filename = WB_PATH . PAGES_DIRECTORY . '/' . $post_link . PAGE_EXTENSION;
        mod_nwi_create_file($filename, '', (string)$post_id, (string)$section_id, (string)$page_id);
    }

    return $post_link;
}


/**
 * Creates the image directory for the new post, copies all images from the
 * original post, and duplicates the gallery rows in the database.
 */
function mod_nwi_post_copy_images(int $original_post_id, int $post_id): void
{
    global $mod_nwi_file_dir, $database;

    if (!is_dir($mod_nwi_file_dir)) {
        mod_nwi_img_makedir($mod_nwi_file_dir);
    }
    mod_nwi_img_copy(WB_PATH . MEDIA_DIRECTORY . '/.news_img/' . $original_post_id, $mod_nwi_file_dir);

    $database->query(sprintf(
        "INSERT INTO `%smod_news_img_img` (`picname`,`picdesc`,`post_id`,`position`) " .
        "SELECT `picname`,`picdesc`,%d,`position` " .
        "FROM `%smod_news_img_img` WHERE `post_id` = %d",
        TABLE_PREFIX,
        $post_id,
        TABLE_PREFIX,
        $original_post_id
    ));
}


/**
 * Copies tags from the original post to the new post.
 * When copying to a different section, missing tags are linked there first.
 *
 * Bug fix: the original condition `!$section_id != $old_section_id` was always
 * evaluating incorrectly; corrected to `$section_id != $old_section_id`.
 */
function mod_nwi_post_copy_tags(int $original_post_id, int $post_id, int $section_id, int $old_section_id): void
{
    global $database;

    $tags = mod_nwi_get_tags_for_post($original_post_id);
    if (empty($tags)) {
        return;
    }

    // Different target section: ensure every tag is linked there before assigning it to the post
    if ($section_id != $old_section_id) {
        $section_tags = mod_nwi_get_tags($section_id);
        foreach ($tags as $tag) {
            $tag_id = (int)$tag['tag_id'];
            if (!isset($section_tags[$tag_id]) || $section_tags[$tag_id]['section_id'] != 0) {
                $database->query(sprintf(
                    "INSERT IGNORE INTO `%smod_news_img_tags_sections` (`section_id`,`tag_id`) VALUES (%d,%d)",
                    TABLE_PREFIX,
                    $section_id,
                    $tag_id
                ));
            }
        }
    }

    foreach ($tags as $tag) {
        $database->query(sprintf(
            "INSERT IGNORE INTO `%smod_news_img_tags_posts` (`post_id`,`tag_id`) VALUES (%d,%d)",
            TABLE_PREFIX,
            $post_id,
            (int)$tag['tag_id']
        ));
    }
}


/**
 * Copies a single post (identified by $original_post_id) into the target section.
 *
 * @return bool  false on database error, true on success.
 */
function mod_nwi_post_copy_single(
    int $original_post_id,
    int $section_id,
    int $page_id,
    int $group_id,
    int $old_section_id,
    bool $with_tags,
    string $file_base
): bool {
    global $mod_nwi_file_dir, $database, $admin;

    // Get next position and insert a placeholder row to obtain a new post_id
    $order    = new order(TABLE_PREFIX . 'mod_news_img_posts', 'position', 'post_id', 'section_id');
    $position = $order->get_new($section_id);

    $database->query(sprintf(
        "INSERT INTO `%smod_news_img_posts` " .
        "(`section_id`,`group_id`,`position`,`link`,`content_short`,`content_long`,`content_block2`,`active`) " .
        "VALUES ('%d','%d','%d','','','','','0')",
        TABLE_PREFIX,
        $section_id,
        $group_id,
        $position
    ));
    $post_id = (int)$database->get_one("SELECT LAST_INSERT_ID()");

    // Point the global file-dir at the new post's directory (used by mod_nwi_post_copy_images)
    $mod_nwi_file_dir = "$file_base/$post_id/";

    // Fetch and escape original post content
    $src    = mod_nwi_post_get($original_post_id);
    $title  = mod_nwi_escapeString($src['title']);
    $link   = mod_nwi_escapeString($src['link']);
    $short  = mod_nwi_escapeString($src['content_short']);
    $long   = mod_nwi_escapeString($src['content_long']);
    $block2 = mod_nwi_escapeString($src['content_block2']);
    $image  = mod_nwi_escapeString($src['image']);
    $active = mod_nwi_escapeString($src['active']);
    $publishedwhen  = $src['published_when'];
    $publisheduntil = $src['published_until'];

    $post_link = mod_nwi_post_make_link($link, $post_id, $section_id, $page_id, $active);

    mod_nwi_post_copy_images($original_post_id, $post_id);

    $database->query(
        "UPDATE `" . TABLE_PREFIX . "mod_news_img_posts`" .
        " SET `section_id` = '$section_id'," .
        " `group_id` = '$group_id'," .
        " `title` = '$title'," .
        " `link` = '$post_link'," .
        " `content_short` = '$short'," .
        " `content_long` = '$long'," .
        " `content_block2` = '$block2'," .
        " `image` = '$image'," .
        " `active` = '$active'," .
        " `published_when` = '$publishedwhen'," .
        " `published_until` = '$publisheduntil'," .
        " `posted_when` = '" . time() . "'," .
        " `posted_by` = '" . $admin->get_user_id() . "'" .
        " WHERE `post_id` = '$post_id'"
    );

    if ($database->is_error()) {
        return false;
    }

    if ($with_tags) {
        mod_nwi_post_copy_tags($original_post_id, $post_id, $section_id, $old_section_id);
    }

    return true;
}


function mod_nwi_post_copy(int $section_id, int $page_id, bool $with_tags = false): bool
{
    global $mod_nwi_file_dir, $database;

    if (!isset($_POST['manage_posts']) || !is_array($_POST['manage_posts'])) {
        return false;
    }
    $posts = $_POST['manage_posts'];

    $old_section_id = $section_id;
    $file_base      = $mod_nwi_file_dir;

    $target     = mod_nwi_post_copy_parse_target($section_id, $page_id);
    $section_id = $target['section_id'];
    $page_id    = $target['page_id'];
    $group_id   = $target['group_id'];

    foreach ($posts as $pid) {
        $original_post_id = (int)$pid;
        if ($original_post_id === 0) {
            continue;
        }

        mod_nwi_post_copy_single(
            $original_post_id,
            $section_id,
            $page_id,
            $group_id,
            $old_section_id,
            $with_tags,
            $file_base
        );

        // Clean up ordering (relevant when moving posts across section borders)
        $order = new order(TABLE_PREFIX . 'mod_news_img_posts', 'position', 'post_id', 'section_id');
        $order->clean($old_section_id);

        if ($database->is_error()) {
            return false;
        }
    }

    return true;
}

/**
 *
 * @access
 * @return
 **/
function mod_nwi_posts_count(int $section_id)
{
    global $database;
    $query_extra = mod_nwi_get_query_extra();
    $t = time();
    $sql = sprintf(
        "SELECT count(`post_id`) AS `count` " .
        "FROM `%smod_news_img_posts` AS `t1` " .
        "WHERE `section_id`='$section_id' ".
        "AND `active` = '1' AND `title` != '' " .
        "AND (`published_when` = '0' OR `published_when` <= $t) " .
        "AND (`published_until` = '0' OR `published_until` >= $t) " .
        "$query_extra ",
        TABLE_PREFIX
    );
    $query_posts = $database->query($sql);
    if (!empty($query_posts) && $query_posts->numRows() > 0) {
        return $query_posts->fetchRow();
    }
    return 0;
}   // end function mod_nwi_posts_count()


function mod_nwi_post_delete($posts)
{
    global $database, $mod_nwi_file_dir, $section_id;

    if (!is_array($posts)) {
        return false;
    }

    //store this one for later use
    $mod_nwi_file_base = $mod_nwi_file_dir;

    $errors = 0;

    foreach ($posts as $post_id) {
        $post_id = (int)$post_id;
        if ($post_id <= 0) {
            continue;
        }

        // Get post details
        $get_details = mod_nwi_post_get($post_id);

        if (is_array($get_details) && count($get_details) > 0) {
            // Unlink post access file
            if (is_writable(WB_PATH.PAGES_DIRECTORY.$get_details['link'].PAGE_EXTENSION)) {
                unlink(WB_PATH.PAGES_DIRECTORY.$get_details['link'].PAGE_EXTENSION);
            }

            // delete images — build path from base each iteration (fixes
            // accumulating-path bug that occurred with '.=')
            $post_image_dir = $mod_nwi_file_base . $post_id;
            rm_full_dir($post_image_dir);
            $database->query(sprintf(
                "DELETE FROM `%smod_news_img_img` WHERE `post_id` = %d",
                TABLE_PREFIX,
                $post_id
            ));

            // Delete post
            $database->query(sprintf(
                "DELETE FROM `%smod_news_img_posts` WHERE `post_id` = %d LIMIT 1",
                TABLE_PREFIX,
                $post_id
            ));
            if ($database->is_error()) {
                $errors++;
            }

            // Clean up ordering
            $order = new order(TABLE_PREFIX.'mod_news_img_posts', 'position', 'post_id', 'section_id');
            $order->clean($section_id);
        }
    }
    return ($errors > 0 ? false : true);
}

function mod_nwi_post_get($post_id)
{
    global $database,$section_id;
    list($order_by, $direction) = mod_nwi_get_order($section_id);
    $filter_g = filter_input(INPUT_GET, 'g', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($filter_g) {
        $query_group = ' AND `group_id` = '.$filter_g.' ';
    } else {
        $query_group = '';
    }
    $t = time();
    $prev_dir = ($direction == 'DESC' ? 'ASC' : 'DESC');
    $sql = sprintf(
        "SELECT `t1`.*, " .
        "  (SELECT `link` FROM `%smod_news_img_posts` AS `t2` WHERE `t2`.`$order_by` > `t1`.`$order_by` AND `section_id`=$section_id AND (`published_when` = '0' OR `published_when` <= $t) AND (`published_until` = '0' OR `published_until` >= $t) AND `active`=1 $query_group ".mod_nwi_sql_group_active('t2')." ORDER BY `$order_by` $prev_dir LIMIT 1 ) as `prev_link`, ".
        "  (SELECT `post_id` FROM `%smod_news_img_posts` AS `t2b` WHERE `t2b`.`$order_by` > `t1`.`$order_by` AND `section_id`=$section_id AND (`published_when` = '0' OR `published_when` <= $t) AND (`published_until` = '0' OR `published_until` >= $t) AND `active`=1 $query_group ".mod_nwi_sql_group_active('t2b')." ORDER BY `$order_by` $prev_dir LIMIT 1 ) as `prev_id`, ".
        "  (SELECT `link` FROM `%smod_news_img_posts` AS `t3` WHERE `t3`.`$order_by` < `t1`.`$order_by` AND `section_id`=$section_id AND (`published_when` = '0' OR `published_when` <= $t) AND (`published_until` = '0' OR `published_until` >= $t) AND `active`=1 $query_group ".mod_nwi_sql_group_active('t3')." ORDER BY `$order_by` $direction LIMIT 1 ) as `next_link`, " .
        "  (SELECT `post_id` FROM `%smod_news_img_posts` AS `t3b` WHERE `t3b`.`$order_by` < `t1`.`$order_by` AND `section_id`=$section_id AND (`published_when` = '0' OR `published_when` <= $t) AND (`published_until` = '0' OR `published_until` >= $t) AND `active`=1 $query_group ".mod_nwi_sql_group_active('t3b')." ORDER BY `$order_by` $direction LIMIT 1 ) as `next_id` " .
        "FROM `%smod_news_img_posts` AS `t1` " .
        "WHERE `post_id`=%d",
        TABLE_PREFIX,
        TABLE_PREFIX,
        TABLE_PREFIX,
        TABLE_PREFIX,
        TABLE_PREFIX,
        $post_id
    );
    $query_content = $database->query($sql);
    if (!empty($query_content)) {
        $post = $query_content->fetchRow();

        // create accessfiles of prev/next items if missing
        // we need the page_id for the access file
        $sectionArray = mod_nwi_get_section_array($post['section_id']);
        $this_page_id = $sectionArray['page_id'];

        $filename_next = WB_PATH.PAGES_DIRECTORY.'/'.$post['next_link'].PAGE_EXTENSION;
        if ($post['next_link'] !== '' && !file_exists($filename_next)) {
            mod_nwi_create_file($filename_next, '', $post['next_id'], $post['section_id'], $this_page_id);
        }
        $filename_prev = WB_PATH.PAGES_DIRECTORY.'/'.$post['prev_link'].PAGE_EXTENSION;
        if ($post['prev_link'] !== '' && !file_exists($filename_prev)) {
            mod_nwi_create_file($filename_prev, '', $post['prev_id'], $post['section_id'], $this_page_id);
        }

        // get users
        $users = mod_nwi_users_get();
        // add "unknown" user
        $users[0] = array(
            'username' => 'unknown',
            'display_name' => 'unknown',
            'email' => ''
        );


        return mod_nwi_post_process($post, $section_id, $users);
    }
    return array();
}

/**
 *
 * @access
 * @return
 **/
function mod_nwi_post_list(int $section_id, bool $process = true)
{
    $query_extra = mod_nwi_get_query_extra();

    // ----- get posts ---------------------------------------------------------
    $posts =  mod_nwi_posts_getall($section_id, false, $query_extra, $process);

    return $posts;
}   // end function mod_nwi_post_list()


function mod_nwi_post_move($section_id, $page_id, $with_tags = false)
{
    global $database, $mod_nwi_file_dir, $admin;

    $group_id = 0;
    $old_section_id = $section_id;
    $old_page_id = $page_id;

    $group = $admin->get_post_escaped('group');

    if (!empty($group)) {
        $values = mod_nwi_parse_group_param($group);
        if ($values === null) {
            header("Location: ".ADMIN_URL."/pages/index.php");
            exit(0);
        }
        if ($values['p'] != 0) {
            $group_id   = $values['g'];
            $section_id = $values['s'];
            $page_id    = $values['p'];
        }
    }

    //store this one for later use
    $mod_nwi_file_base = $mod_nwi_file_dir;

    $posts = [];
    if (isset($_POST['manage_posts']) && is_array($_POST['manage_posts'])) {
        $posts = $_POST['manage_posts'];
    } else {
        return false;
    }
    foreach ($posts as $idx => $pid) {
        $post_id = intval($pid);

        if ($post_id != 0) {
            // Update row
            $database->query(sprintf(
                "UPDATE `%smod_news_img_posts` SET ".
                "`section_id` = '$section_id', `group_id` = '$group_id' ".
                "WHERE `post_id` = '$post_id'",
                TABLE_PREFIX
            ));
        }

        // Clean up ordering (e.g. if we were moving posts across section borders
        $order = new order(TABLE_PREFIX.'mod_news_img_posts', 'position', 'post_id', 'section_id');
        $order->clean($old_section_id);
        $order->clean($section_id);

        if ($database->is_error()) {
            return false;
        }

        // get post link
        $query_post = $database->query(sprintf(
            "SELECT `link` FROM `%smod_news_img_posts` WHERE `post_id`='$post_id'",
            TABLE_PREFIX
        ));
        $post = $query_post->fetchRow();
        $post_link = $post['link'];
        // We need to create a new file
        // First, delete old file if it exists
        if (file_exists(WB_PATH.PAGES_DIRECTORY.$post_link.PAGE_EXTENSION)) {
            $file_create_time = filemtime(WB_PATH.PAGES_DIRECTORY.$post_link.PAGE_EXTENSION);
            unlink(WB_PATH.PAGES_DIRECTORY.$post_link.PAGE_EXTENSION);
        }

        // Specify the filename
        $filename = WB_PATH.PAGES_DIRECTORY.'/'.$post_link.PAGE_EXTENSION;
        mod_nwi_create_file($filename, '', $post_id, $section_id, $page_id);
    }
    return true;
}

/**
 *
 * @access
 * @return
 **/
function mod_nwi_post_show(int $post_id)
{
    global $database, $admin, $section_id;

    $post_section = (
        defined('POST_SECTION') ?
        POST_SECTION :
        $section_id
    );

    // get settings
    $settings = mod_nwi_settings_get($post_section);

    // get users
    $users = mod_nwi_users_get();

    // add "unknown" user
    $users[0] = array(
        'username' => 'unknown',
        'display_name' => 'unknown',
        'email' => ''
    );

    // get post data
    $post = mod_nwi_post_get($post_id);
    if (empty($post) || !isset($post['section_id'])) {
        return false;
    }

    $page = [];
    $pageQuery = sprintf(
        "SELECT * from `%ssections` WHERE `section_id`=%d",
        TABLE_PREFIX,
        (int)$post['section_id']
    );
    $query_page = $database->query($pageQuery);
    if ($query_page && $query_page->numRows() > 0) {
        $page = $query_page->fetchRow();
    }

    // get group data
    $gid = $post['group_id'] ?? 0;
    if ($gid != 0) {
        $group = mod_nwi_get_group($gid);
        if (empty($group) || $group['active'] != 1) {
            return false;
        }
    }
    if ($post['active'] == 0) {
        if (is_writable(WB_PATH.PAGES_DIRECTORY.$post['link'].PAGE_EXTENSION)) {
            unlink(WB_PATH.PAGES_DIRECTORY.$post['link'].PAGE_EXTENSION);
        }
        return false;
    } else {
        if (empty($page)) {
            // No matching section row — cannot safely build the access
            // file (page_id missing); skip silently.
            return $post;
        }
        // Self-healing: nur anlegen, wenn die Access-Datei wirklich fehlt.
        // Beim normalen Beitrags-View IST sie die gerade laufende Skript-
        // datei, existiert also bereits. Sie hier bedingungslos via
        // mod_nwi_create_file() zu loeschen+neu zu schreiben ist unter
        // Windows fatal: unlink() der laufenden Datei greift, das fopen()
        // zum Neuanlegen scheitert aber (Name noch vom Prozess gehalten),
        // die Datei verschwindet -> naechster Direktaufruf liefert 404.
        $filename = WB_PATH.PAGES_DIRECTORY.$post['link'].PAGE_EXTENSION;
        if (!file_exists($filename)) {
            mod_nwi_create_file($filename, '', $post_id, $section_id, $page['page_id']);
        }
    }

    return $post;
}   // end function mod_nwi_post_show()

/**
 *
 * @access
 * @return
 **/
function mod_nwi_posts_getall(int $section_id, bool $is_backend, string $query_extra, bool $process = true)
{
    global $database, $admin, $TEXT;

    $posts    = [];
    $groups   = mod_nwi_get_groups($section_id);
    $t        = time();
    $limit    = '';
    $filter   = '';
    $active   = '';

    list($order_by, $direction) = mod_nwi_get_order($section_id);

    $settings = mod_nwi_settings_get($section_id);
    if ($settings['posts_per_page'] != 0) {
        $filter_p = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($filter_p !== null && $filter_p !== false) {
            $position = $filter_p;
        } else {
            $position = 0;
        }
        if (!$is_backend) {
            $limit = " LIMIT $position,".$settings['posts_per_page'];
        }
    }

    if (!$is_backend) {
        $query_extra .= " AND `active` = '1' AND `title` != '' "
                     .  "AND (`published_when`  = '0' OR `published_when`  <= $t) "
                     .  "AND (`published_until` = '0' OR `published_until` >= $t) "
                     .  mod_nwi_sql_group_active('t1');
        // gilt auch für die Prev/Next-Subqueries (Alias t3)
        $active       = 'AND `active`=1'.mod_nwi_sql_group_active('t3');
    }

    if (isset($_GET['tags']) && strlen($_GET['tags'])) {
        $filter_posts = [];
        $tags = mod_nwi_escape_tags($_GET['tags']);
        $r = $database->query(
            "SELECT `t2`.`post_id` FROM `".TABLE_PREFIX."mod_news_img_tags` as `t1` ".
            "JOIN `".TABLE_PREFIX."mod_news_img_tags_posts` AS `t2` ".
            "ON `t1`.`tag_id`=`t2`.`tag_id` ".
            "WHERE `tag` IN ('".implode("', '", $tags)."') ".
            "GROUP BY `t2`.`post_id`"
        );
        while ($row = $r->fetchRow()) {
            $filter_posts[] = $row['post_id'];
        }
        if (count($filter_posts) > 0) {
            $filter = " AND `t1`.`post_id` IN (".implode(',', array_values($filter_posts)).") ";
        } else {
            $filter = " AND `t1`.`post_id` = '-999'";
        }
    }

    $prev_dir = ($direction == 'DESC' ? 'ASC' : 'DESC');

    $sql = sprintf(
        "SELECT " .
        "  *, " .
        "  (SELECT `position`       FROM `%smod_news_img_groups`     AS `t4` WHERE `t4`.`group_id`=`t1`.`group_id`) as `gposition`, " .
        "  (SELECT COUNT(`post_id`) FROM `%smod_news_img_tags_posts` AS `t2` WHERE `t2`.`post_id`=`t1`.`post_id`) as `tags`, " .
        "  (SELECT `post_id` FROM `%smod_news_img_posts` AS `t3` WHERE `t3`.`$order_by` > `t1`.`$order_by` AND `section_id`='$section_id' $active ORDER BY `$order_by` $direction LIMIT 1 ) as `next`, ".
        "  (SELECT `post_id` FROM `%smod_news_img_posts` AS `t3` WHERE `t3`.`$order_by` < `t1`.`$order_by` AND `section_id`='$section_id' $active ORDER BY `$order_by` $prev_dir LIMIT 1 ) as `prev` " .
        "FROM `%smod_news_img_posts` AS `t1` WHERE `section_id`='$section_id' $filter ".
        "$query_extra ORDER BY `$order_by` $direction $limit",
        TABLE_PREFIX,
        TABLE_PREFIX,
        TABLE_PREFIX,
        TABLE_PREFIX,
        TABLE_PREFIX
    );

    $query_posts = $database->query($sql);

    if (!empty($query_posts) && $query_posts->numRows() > 0) {
        // map group index to title
        $group_map = [];
        foreach ($groups as $i => $g) {
            $group_map[$g['group_id']] = (empty($g['title']) ? $TEXT['NONE'] : $g['title']);
        }
        // get users
        $users = mod_nwi_users_get();
        // add "unknown" user
        $users[0] = array(
            'username' => 'unknown',
            'display_name' => 'unknown',
            'email' => ''
        );
        while ($post = $query_posts->fetchRow()) {
            if ($process === true) {
                $posts[] = mod_nwi_post_process($post, $section_id, $users);
            } else {
                $posts[] = $post;
            }
            $sectionArray = mod_nwi_get_section_array($post['section_id']);
            $filename = WB_PATH.PAGES_DIRECTORY.$post['link'].PAGE_EXTENSION;

            // check if accessfile should be created...
            $createFile = true;																				// by default: yes.
            if ($post['published_when'] != 0 && $post['published_when'] > time()) {
                $createFile = false;
            }    // no, because the post is not public yet.
            if ($post['published_until'] != 0 && $post['published_until'] < time()) {
                $createFile = false;
            }  // no, because the post is no longer public.
            if ($post['active'] != 1) {
                $createFile = false;
            }												// no, the post is created inactive.
            if (!file_exists($filename) && $createFile == true) {
                mod_nwi_create_file($filename, '', $post['post_id'], $post['section_id'], $sectionArray['page_id']);
            }
        }
    }



    return $posts;
}   // end function mod_nwi_posts_getall()

/**
 * Build pagination data (previous/next links, range labels) for a post list.
 *
 * @param  int         $section_id
 * @param  int         $position       Current offset (0-based start index)
 * @param  int         $posts_per_page 0 = unlimited / no paging
 * @param  string|null $tags_append    Active tag filter value for URL building
 * @return array{
 *     previous_link:               string,
 *     previous_page_link:          string,
 *     next_link:                   string,
 *     next_page_link:              string,
 *     out_of:                      string,
 *     of:                          string,
 *     display_previous_next_links: string
 * }
 **/
function mod_nwi_build_pagination(int $section_id, int $position, int $posts_per_page, ?string $tags_append): array
{
    global $TEXT;

    // No paging requested — return empty placeholders
    if ($posts_per_page === 0) {
        return [
            'previous_link'               => '',
            'previous_page_link'          => '',
            'next_link'                   => '',
            'next_page_link'              => '',
            'out_of'                      => '',
            'of'                          => '',
            'display_previous_next_links' => 'hidden',
        ];
    }

    $cnt       = mod_nwi_posts_count($section_id);
    $total_num = $cnt['count'];
    $filter_g  = filter_input(INPUT_GET, 'g', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($position > 0) {
        $prev_url           = mod_nwi_build_url('', ['p' => $position - $posts_per_page, 'tags' => $tags_append ?: null, 'g' => $filter_g]);
        $pl_prepend         = '<a href="' . htmlspecialchars($prev_url, ENT_QUOTES | ENT_HTML5) . '">';
        $pl_append          = '</a>';
        $previous_link      = $pl_prepend . $TEXT['PREVIOUS'] . $pl_append;
        $previous_page_link = $pl_prepend . $TEXT['PREVIOUS_PAGE'] . $pl_append;
    } else {
        $previous_link      = '';
        $previous_page_link = '';
    }

    if ($position + $posts_per_page >= $total_num) {
        $next_link      = '';
        $next_page_link = '';
    } else {
        $next_url       = mod_nwi_build_url('', ['p' => $position + $posts_per_page, 'tags' => $tags_append ?: null, 'g' => $filter_g]);
        $nl_prepend     = '<a href="' . htmlspecialchars($next_url, ENT_QUOTES | ENT_HTML5) . '"> ';
        $nl_append      = '</a>';
        $next_link      = $nl_prepend . $TEXT['NEXT'] . $nl_append;
        $next_page_link = $nl_prepend . $TEXT['NEXT_PAGE'] . $nl_append;
    }

    $num_of = min($position + $posts_per_page, $total_num);
    $range  = ($position + 1) . '-' . $num_of;

    return [
        'previous_link'               => $previous_link,
        'previous_page_link'          => $previous_page_link,
        'next_link'                   => $next_link,
        'next_page_link'              => $next_page_link,
        'out_of'                      => $range . ' ' . strtolower($TEXT['OUT_OF']) . ' ' . $total_num,
        'of'                          => $range . ' ' . strtolower($TEXT['OF'])     . ' ' . $total_num,
        'display_previous_next_links' => ($previous_link || $next_link) ? 'visible' : 'hidden',
    ];
}   // end function mod_nwi_build_pagination()


/**
 *
 * @access
 * @return
 **/
function mod_nwi_posts_render($section_id, $posts, $posts_per_page = 0)
{
    global $TEXT, $MOD_NEWS_IMG, $wb, $page_id;

    // if called by droplet
    if (!is_array($MOD_NEWS_IMG)) {
        require __DIR__ . '/languages/EN.php';
        $lang = __DIR__ . '/languages/' . LANGUAGE . '.php';
        if (file_exists($lang)) {
            require $lang;
        }
    }

    $list     = [];
    $settings = mod_nwi_settings_get($section_id);

    // position to start off (=offset)
    $filter_p = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $position = $filter_p ?: 0;

    // image sizes
    [$previewwidth, $previewheight, $thumbwidth, $thumbheight] = mod_nwi_get_sizes($section_id);

    // filter by tags
    $tags_header = null;
    $tags_append = null;

    if (isset($_GET['tags']) && strlen($_GET['tags'])) {
        $requested_tags = mod_nwi_escape_tags(mod_nwi_sanitize_input($_GET['tags'], 's{TRIM|STRIP|ENTITIES}'));
        foreach ($requested_tags as $tag_idx => $tag) {
            $requested_tags[$tag_idx] = "<span class=\"mod_nwi_tag\" id=\"mod_nwi_tag_" . $tag_idx . "\">" . $tag . "</span>";
        }
        $tags_header = implode("\n", $requested_tags);
        $tags_append = mod_nwi_sanitize_input($_GET['tags'], 's{TRIM|STRIP|ENTITIES}');
    }

    // Build pagination links and range labels
    $pagination = mod_nwi_build_pagination($section_id, $position, $posts_per_page, $tags_append);

    [$vars, $default_replacements] = mod_nwi_replacements();

    $tags_by_post = mod_nwi_get_tags_for_posts(array_column($posts, 'post_id'));

    // Closure to apply pagination placeholders to a template string
    $apply_pagination = function (string $template) use ($pagination): string {
        return str_replace(
            ['[NEXT_PAGE_LINK]', '[NEXT_LINK]', '[PREVIOUS_PAGE_LINK]',
             '[PREVIOUS_LINK]', '[OUT_OF]', '[OF]', '[DISPLAY_PREVIOUS_NEXT_LINKS]'],
            [
                $pagination['next_page_link'],
                $pagination['next_link'],
                $pagination['previous_page_link'],
                $pagination['previous_link'],
                $pagination['out_of'],
                $pagination['of'],
                $pagination['display_previous_next_links'],
            ],
            $template
        );
    };

    foreach ($posts as $post_idx => $post) {
        // tags
        $tags         = $tags_by_post[$post['post_id']] ?? [];
        $tagListArray = [];
        foreach ($tags as $tag_idx => $tag) {
            $tagListArray[] = $tag['tag'];
            $style_attr = mod_nwi_tag_style_attr($tag);
            $tag_id_attr = htmlspecialchars('mod_nwi_tag_' . (int)$post['post_id'] . '_' . (int)$tag_idx, ENT_QUOTES | ENT_HTML5);
            $tag_href    = htmlspecialchars(mod_nwi_build_url($wb->page_link($page_id), ['tags' => $tag['tag']]), ENT_QUOTES | ENT_HTML5);
            $tags[$tag_idx] = '<span class="mod_nwi_tag" id="' . $tag_id_attr . '"' . $style_attr . '>'
                . '<a href="' . $tag_href . '">'
                . htmlspecialchars($tag['tag'], ENT_QUOTES | ENT_HTML5)
                . '</a></span>';
        }

        // gallery images — relevant for "read more" link (SHOW_READ_MORE)
        $images       = mod_nwi_img_get_by_post($post['post_id'], false);
        $anz_post_img = count($images);

        // no "read more" link if neither long content nor gallery images exist
        $has_detail = (strlen($post['content_long']) >= 9) || ($anz_post_img >= 1);

        $post_href_link   = $has_detail ? 'href="' . $post['post_link'] . '"' : '';
        $post_a_open_tag  = $has_detail ? '<a ' . $post_href_link . '>' : '';
        $post_a_close_tag = $has_detail ? '</a>' : '';

        $post['content_short']  = str_replace('{SYSVAR:MEDIA_REL}', WB_URL . MEDIA_DIRECTORY, $post['content_short']);
        $post['content_long']   = str_replace('{SYSVAR:MEDIA_REL}', WB_URL . MEDIA_DIRECTORY, $post['content_long']);
        $post['content_block2'] = str_replace('{SYSVAR:MEDIA_REL}', WB_URL . MEDIA_DIRECTORY, $post['content_block2']);

        // set replacements for current post
        $replacements = array_merge(
            $default_replacements,
            $TEXT,
            $MOD_NEWS_IMG,
            array_change_key_case($post, CASE_UPPER),
            [
                'IMAGE'                        => $post['post_img'],
                'SHORT'                        => $post['content_short'],
                'LINK'                         => $post['post_link'],
                'HREF'                         => $post_href_link,
                'AOPEN'                        => $post_a_open_tag,
                'ACLOSE'                       => $post_a_close_tag,
                'MODI_DATE'                    => $post['post_date'],
                'MODI_TIME'                    => $post['post_time'],
                'TAGS'                         => implode(' ', $tags),
                'TAGLIST'                      => implode(',', $tagListArray),
                'SHOW_READ_MORE'               => $has_detail ? 'visible' : 'hidden',
                'DISPLAY_PREVIOUS_NEXT_LINKS'  => $pagination['display_previous_next_links'],
            ]
        );

        $list[] = preg_replace_callback(
            '~\[(' . implode('|', $vars) . ')+\]~',
            function ($match) use ($replacements) {
                return (isset($match[1]) && isset($replacements[$match[1]]))
                    ? $replacements[$match[1]]
                    : '';
            },
            $settings['post_loop']
        );
    }

    if (empty($list)) {
        $list[] = $TEXT['NONE_FOUND'];
    }

    return [
        'rendered_posts'  => $list,
        'prev_next_footer' => $apply_pagination($settings['footer']),
        'prev_next_header' => $apply_pagination($settings['header']),
    ];
}   // end function mod_nwi_posts_render()


/**
 *
 * @access
 * @return
 **/
function mod_nwi_post_process($post, $section_id, $users)
{
    global $MOD_NEWS_IMG, $TEXT, $admin;

    $filename = WB_PATH.PAGES_DIRECTORY.$post['link'].PAGE_EXTENSION;

    // get groups
    $groups = mod_nwi_get_groups(intval($section_id));

    // map group id to group data for easier handling
    $group_map = [];
    foreach ($groups as $i => $g) {
        $group_map[$g['group_id']] = $g;
    }

    $post['id_key'] = $post['post_id'];

    // this is for the backend only
    $icon = '';
    $t = time();
    if ($post['published_when'] <= $t && $post['published_until'] == 0) {
        $post['icon'] = '<span class="fa fa-fw fa-calendar-o" title="'.$MOD_NEWS_IMG['POST_ACTIVE'].'"></span>';
    } elseif (($post['published_when'] <= $t || $post['published_when'] == 0) && $post['published_until'] >= $t) {
        $post['icon'] = '<span class="fa fa-fw fa-calendar-check-o nwi-active" title="'.$MOD_NEWS_IMG['POST_ACTIVE'].'"></span>';
    } else {
        $post['icon'] = '<span class="fa fa-fw fa-calendar-times-o nwi-inactive" title="'.$MOD_NEWS_IMG['POST_INACTIVE'].'"></span>';
    }

    list($previewwidth,
        $previewheight,
        $thumbwidth,
        $thumbheight
    ) = mod_nwi_get_sizes($section_id);

    // posting (preview) image — Kaskade: post → group → default. Findet sich
    // keine Quelle, gibt es KEIN Bild mehr (Detail/Leseansicht) bzw. eine
    // Platzhalter-Kachel (Liste/Grid) — siehe Schritt 4 unten.
    // Alle Bildquellen werden in Preview-Größe ausgeliefert, damit das Layout
    // (Bild links, Teaser rechts) für alle Quellen identisch bleibt.
    $post_img_src = '';

    // 1) Beitragsbild
    if (!empty($post['image'])) {
        $post_img_src = WB_URL.MEDIA_DIRECTORY.'/.news_img/'.$post['image'];
    }

    // 2) Gruppenbild — Datei `image<group_id>.<ext>` durchprobieren
    if ($post_img_src === '' && !empty($post['group_id'])) {
        foreach (['png','jpg','jpeg','gif','webp'] as $ext) {
            $group_file = WB_PATH.MEDIA_DIRECTORY.'/.news_img/image'.(int)$post['group_id'].'.'.$ext;
            if (file_exists($group_file)) {
                $post_img_src = WB_URL.MEDIA_DIRECTORY.'/.news_img/image'.(int)$post['group_id'].'.'.$ext;
                break;
            }
        }
    }

    // 3) Section-Default — eigenständige Kopie unter media/.news_img/<file>.
    // Lebenszyklus ist von Posts entkoppelt; Datei verschwindet nur via
    // Settings ("entfernen" oder Überschreiben).
    if ($post_img_src === '') {
        $settings_section = mod_nwi_settings_get($section_id);
        $default_file = (string)($settings_section['default_preview_image'] ?? '');
        if ($default_file !== '' && file_exists(WB_PATH.MEDIA_DIRECTORY.'/.news_img/'.$default_file)) {
            $post_img_src = WB_URL.MEDIA_DIRECTORY.'/.news_img/'.$default_file;
        }
    }

    // 4) Kein echtes Bild gefunden — bewusst KEIN nopic-Pixel mehr, das nur
    //    einen leeren, auf Bildgroesse aufgeblasenen Bereich erzeugt.
    //    - Leseansicht (Detail, POST_ID gesetzt): gar kein Bild ausgeben,
    //      der Teasertext nutzt die volle Breite.
    //    - Listen-/Grid-Ansicht: schlichte Platzhalter-Kachel mit der
    //      Initiale des Titels (Tag-Palette-Navy), damit das Raster optisch
    //      gleichmaessig bleibt. Optik inline, damit es in allen Views ohne
    //      zusaetzliches CSS einheitlich aussieht.
    if ($post_img_src !== '') {
        $post['post_img'] = "<img src='".$post_img_src."' alt='".htmlspecialchars($post['title'], ENT_QUOTES | ENT_HTML401)."' />";
    } elseif (defined('POST_ID')) {
        $post['post_img'] = '';
    } else {
        $initial = mb_strtoupper(mb_substr(trim((string)$post['title']), 0, 1));
        $post['post_img'] = '<span class="mod_nwi_noimage" aria-hidden="true"'
            . ' style="display:flex;align-items:center;justify-content:center;'
            . 'width:100%;height:100%;min-height:80px;background:#314B68;'
            . 'color:#fff;font-size:2.5em;font-weight:500;border-radius:3px;">'
            . htmlspecialchars($initial, ENT_QUOTES | ENT_HTML5)
            . '</span>';
    }

    // post link
    $post['post_link'] = page_link($post['link']);
    $post['post_link_path'] = str_replace(WB_URL, WB_PATH, $post['post_link']);
    $post['next_link'] = (isset($post['next_link']) && strlen($post['next_link']) > 0 ? page_link($post['next_link']) : null);
    $post['prev_link'] = (isset($post['prev_link']) && strlen($post['prev_link']) > 0 ? page_link($post['prev_link']) : null);

    $filter_p = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $filter_g = filter_input(INPUT_GET, 'g', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    // mod_nwi_build_url returns raw '&'-separated URLs (RFC 3986). These
    // values are substituted into HTML templates as [LINK]/[NEXT_LINK]/
    // [PREVIOUS_LINK] without any further encoding, so we HTML-encode them
    // here once.
    $post['post_link'] = htmlspecialchars(mod_nwi_build_url($post['post_link'], ['p' => $filter_p, 'g' => $filter_g]), ENT_QUOTES | ENT_HTML5);
    $post['next_link'] = $post['next_link'] ? htmlspecialchars(mod_nwi_build_url($post['next_link'], ['g' => $filter_g]), ENT_QUOTES | ENT_HTML5) : null;
    $post['prev_link'] = $post['prev_link'] ? htmlspecialchars(mod_nwi_build_url($post['prev_link'], ['g' => $filter_g]), ENT_QUOTES | ENT_HTML5) : null;

    // publishing date
    if ($post['published_when'] === '0') {
        $post['published_when'] = time();
    }
    if (!defined('CAT_PATH')) {
        if ($post['published_when'] > $post['posted_when']) {
            $post['post_date'] = date(DATE_FORMAT, $post['published_when'] + TIMEZONE);
            $post['post_time'] = date(TIME_FORMAT, $post['published_when'] + TIMEZONE);
        } else {
            $post['post_date'] = date(DATE_FORMAT, $post['posted_when'] + TIMEZONE);
            $post['post_time'] = date(TIME_FORMAT, $post['posted_when'] + TIMEZONE);
        }
        $post['published_date'] = date(DATE_FORMAT, $post['published_when'] + TIMEZONE);
        $post['published_time'] = date(TIME_FORMAT, $post['published_when'] + TIMEZONE);

        $post['publishing_date'] = date(DATE_FORMAT, ($post['published_when'] == 0 ? time() + TIMEZONE : $post['published_when'] + TIMEZONE)) . ' ' . $post['published_time'];
        $post['publishing_end_date'] = ($post['published_until'] != 0 ? date(DATE_FORMAT, $post['published_until'] + TIMEZONE) : '');
        if (strlen($post['publishing_end_date']) > 0) {
            $post['publishing_end_date'] .= ' ' . date(TIME_FORMAT, $post['published_until'] + TIMEZONE);
        }

        if (file_exists($post['post_link_path'])) {
            $post['create_date'] = date(DATE_FORMAT, filemtime($post['post_link_path']) + TIMEZONE);
            $post['create_time'] = date(TIME_FORMAT, filemtime($post['post_link_path']) + TIMEZONE);
        } else {
            $post['create_date'] = $post['published_date'];
            $post['create_time'] = $post['published_time'];
        }
    } else {
        if ($post['published_when'] > $post['posted_when']) {
            $post['post_date'] = CAT_Helper_DateTime::getDate($post['published_when']);
            $post['post_time'] = CAT_Helper_DateTime::getTime($post['published_when']);
        } else {
            $post['post_date'] = CAT_Helper_DateTime::getDate($post['posted_when']);
            $post['post_time'] = CAT_Helper_DateTime::getTime($post['posted_when']);
        }
        $post['published_date'] = CAT_Helper_DateTime::getDate($post['published_when']);
        $post['published_time'] = CAT_Helper_DateTime::getTime($post['published_when']);
        $post['publishing_date'] = ($post['published_when'] == 0 ? CAT_Helper_Datetime::getDateTime() : CAT_Helper_Datetime::getDateTime($post['published_when']));
        $post['publishing_end_date'] = ($post['published_until'] == 0 ? '' : CAT_Helper_Datetime::getDateTime($post['published_until']));

        if (file_exists($post['post_link_path'])) {
            $post['create_date'] = CAT_Helper_DateTime::getDate(filemtime($post['post_link_path']));
            $post['create_time'] = CAT_Helper_DateTime::getTime($post['post_link_path']);
        } else {
            $post['create_date'] = $post['published_date'];
            $post['create_time'] = $post['published_time'];
        }
    }

    // Get group id, title, and image
    $group_id                = $post['group_id'];
    $post['group_title']     = (isset($group_map[$group_id]) ? $group_map[$group_id]['title'] : null);
    $post['group_image']     = (isset($group_map[$group_id]) ? $group_map[$group_id]['image'] : null);
    $post['group_image_url'] = WB_URL."/modules/news_img/images/nopic.png";
    $post['display_image']   = ($post['group_image'] == '') ? "none" : "inherit";
    $post['display_group']   = ($group_id == 0) ? 'none' : 'inherit';
    if ($post['group_image'] != "") {
        $post['group_image_url'] = $post['group_image'];
        $post['group_image'] = "<img class='mod_nwi_grouppic' src='".$post['group_image_url']."' alt='".htmlspecialchars($post['group_title'], ENT_QUOTES | ENT_HTML401)."' title='".htmlspecialchars($TEXT['GROUP'].": ".$post['group_title'], ENT_QUOTES | ENT_HTML401)."' />";
    }

    // Die volle Bild-Kaskade (post → group → default → nopic) sitzt jetzt
    // bereits in $post['post_img']; das Alt-Placeholder [POST_OR_GROUP_IMAGE]
    // bekommt dasselbe Resultat, damit Custom-Templates nicht hängenbleiben.
    $post['post_or_group_image'] = $post['post_img'];

    // user
    $post['display_name'] = isset($users[$post['posted_by']]) ? $users[$post['posted_by']]['display_name'] : '<i>'.$users[0]['display_name'] .'</i>';
    $post['username'] = isset($users[$post['posted_by']]) ? $users[$post['posted_by']]['username'] : '<i>'.$users[0]['username'] .'</i>';
    $post['email'] = isset($users[$post['posted_by']]) ? $users[$post['posted_by']]['email'] : '<i>'.$users[0]['email'] .'</i>';

    return $post;

}   // end function mod_nwi_post_process()


/**
 *
 * @access
 * @return
 **/
function mod_nwi_img_get_by_post(int $post_id, bool $render)
{
    global $database, $section_id;

    $settings = mod_nwi_settings_get($section_id);

    $query_img = $database->query(sprintf(
        "SELECT * FROM `%smod_news_img_img` " .
        "WHERE `post_id`=%d " .
        "ORDER BY `position`,`id` ASC",
        TABLE_PREFIX,
        intval($post_id)
    ));

    $thumbsizeraw = explode('x', (string)$settings['imgthumbsize']);

    $images = [];
    if (!empty($query_img) && $query_img->numRows() > 0) {
        while ($row = $query_img->fetchRow()) {
            if ($render === true) {
                $images[] = str_replace(
                    array(
                        '[IMAGE]',
                        '[DESCRIPTION]',
                        '[THUMB]',
                        '[THUMBWIDTH]',
                        '[THUMBHEIGHT]',
                        '[WB_URL]'
                    ),
                    array(
                        WB_URL.MEDIA_DIRECTORY.'/.news_img/'.POST_ID.'/'.$row['picname'],
                        $row['picdesc'],
                        WB_URL.MEDIA_DIRECTORY.'/.news_img/'.POST_ID.'/thumb/'.$row['picname'],
                        $thumbsizeraw[0],
                        $thumbsizeraw[1],
                        WB_URL
                    ),
                    $settings['image_loop']
                );
            } else {
                $images[] = $row;
            }
        }
    }

    return $images;
}

/**
 * Return all gallery images uploaded for any post in the given section.
 * Used e.g. by the "default preview image" picker in the section settings.
 *
 * @param  int $section_id
 * @return array  rows with: id, picname, picdesc, post_id, position, post_title
 */
function mod_nwi_img_get_by_section(int $section_id): array
{
    global $database;

    $images = [];
    $query = $database->query(sprintf(
        "SELECT i.`id`, i.`picname`, i.`picdesc`, i.`post_id`, i.`position`, "
        . "p.`title` AS `post_title` "
        . "FROM `%smod_news_img_img` i "
        . "INNER JOIN `%smod_news_img_posts` p ON p.`post_id` = i.`post_id` "
        . "WHERE p.`section_id` = %d "
        . "ORDER BY p.`post_id` ASC, i.`position` ASC, i.`id` ASC",
        TABLE_PREFIX,
        TABLE_PREFIX,
        $section_id
    ));
    if (!empty($query) && $query->numRows() > 0) {
        while ($row = $query->fetchRow()) {
            $images[] = $row;
        }
    }
    return $images;
}

// ========== Other ============================================================

/**
 *
 * @access
 * @return
 **/
function mod_nwi_sections()
{
    global $database;
    $sections = [];
    $query_sections = $database->query(sprintf(
        "SELECT `section_id`,`page_id` FROM `%ssections` " .
        "WHERE `module`='%s' ORDER BY `page_id`,`section_id` ASC",
        TABLE_PREFIX,
        'news_img'
    ));
    if ($query_sections->numRows() > 0) {
        while ($sect = $query_sections->fetchRow()) {
            $sections[] = $sect;
        }
    }
    return $sections;
}   // end function mod_nwi_sections()


/**
 *
 * @access
 * @return
 **/
function mod_nwi_settings_get($section_id, bool $bust_cache = false)
{
    global $database;
    static $cache = [];
    $key = (int)$section_id;
    if ($bust_cache) {
        unset($cache[$key]);
    }
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    $query_content = $database->query(sprintf(
        "SELECT * FROM `%smod_news_img_settings` WHERE `section_id`=%d",
        TABLE_PREFIX,
        $key
    ));
    // Only cache rows we actually retrieved. Caching '[]' on a failed
    // query or a missing row would lock subsequent calls into a broken
    // state and spam notices on every field access.
    if ($query_content && $query_content->numRows() > 0) {
        $cache[$key] = $query_content->fetchRow();
        return $cache[$key];
    }
    return [];
}   // end function mod_nwi_settings_get()

/**
 *
 * @access
 * @return
 **/
function mod_nwi_get_dates()
{
    global $admin;

    // get publishedwhen and publisheduntil
    $publishedwhen = jscalendar_to_timestamp(mod_nwi_escapeString($admin->get_post('publishdate')));
    if ($publishedwhen == '' || $publishedwhen < 1) {
        $publishedwhen = 0;
    } else {
        if (!defined('CAT_PATH')) {
            $publishedwhen -= TIMEZONE;
        }
    }

    $publisheduntil = jscalendar_to_timestamp(mod_nwi_escapeString($admin->get_post('enddate')), $publishedwhen);
    if ($publisheduntil == '' || $publisheduntil < 1) {
        $publisheduntil = 0;
    } else {
        if (!defined('CAT_PATH')) {
            $publisheduntil -= TIMEZONE;
        }
    }
    return array($publishedwhen, $publisheduntil);
}   // end function mod_nwi_get_dates()


/**
 *
 * @access
 * @return
 **/
function mod_nwi_get_order(int $section_id)
{
    $settings = mod_nwi_settings_get($section_id);
    $order_by  = 'position'; // default
    $direction = 'DESC';
    switch ($settings['view_order']) {
        case 1:
            $order_by = "published_when";
            $direction = 'DESC';
            break;
        case 2:
            $order_by = "published_until";
            $direction = 'DESC';
            break;
        case 3:
            $order_by = "posted_when";
            $direction = 'DESC';
            break;
        case 4:
            $order_by = "post_id";
            $direction = 'DESC';
            break;
        case 5:
            $order_by = "published_until";
            $direction = 'ASC';
            break;
    }
    return array($order_by,$direction);
}   // end function mod_nwi_get_order()

/**
 *
 * @access
 * @return
 **/
function mod_nwi_get_query_extra()
{
    global $database;
    $query_extra = '';

    // ----- filter by group? --------------------------------------------------
    $filter_g = filter_input(INPUT_GET, 'g', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($filter_g) {
        $query_extra = " AND group_id = ".$filter_g;
    }

    // ----- filter by date?  --------------------------------------------------
    $filter_m      = filter_input(INPUT_GET, 'm', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1,    'max_range' => 12]]);
    $filter_y      = filter_input(INPUT_GET, 'y', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1970, 'max_range' => 2100]]);
    $filter_method = filter_input(INPUT_GET, 'method', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0,    'max_range' => 1]]);
    if ($filter_m && $filter_y && $filter_method !== null && $filter_method !== false) {
        $startdate = mktime(0, 0, 0, $filter_m, 1, $filter_y);
        $enddate   = mktime(0, 0, 0, $filter_m + 1, 1, $filter_y);
        switch ($filter_method) {
            case 0:
                $date_option = "posted_when";
                break;
            case 1:
                $date_option = "published_when";
                break;
        }
        $query_extra .= " AND ".$date_option." >= '$startdate' AND ".$date_option." < '$enddate'";
    }

    // ----- filter by tags? ---------------------------------------------------
    if (isset($_GET['tags']) && strlen($_GET['tags'])) {
        $filter_posts = [];
        $tags = mod_nwi_escape_tags($_GET['tags']);
        $r = $database->query(
            "SELECT `t2`.`post_id` FROM `".TABLE_PREFIX."mod_news_img_tags` as `t1` ".
            "JOIN `".TABLE_PREFIX."mod_news_img_tags_posts` AS `t2` ".
            "ON `t1`.`tag_id`=`t2`.`tag_id` ".
            "WHERE `tag` IN ('".implode("', '", $tags)."') ".
            "GROUP BY `t2`.`post_id`"
        );
        while ($row = $r->fetchRow()) {
            $filter_posts[] = $row['post_id'];
        }
        if (count($filter_posts) > 0) {
            $query_extra .= " AND `t1`.`post_id` IN (".implode(',', array_values($filter_posts)).") ";
        }
    }

    return $query_extra;
}   // end function mod_nwi_get_query_extra()

/**
 *
 * @access
 * @return
 **/
function mod_nwi_get_sizes($section_id)
{
    $settings = mod_nwi_settings_get($section_id);
    // preview images size
    $previewwidth = $previewheight = $thumbwidth = $thumbheight = '';
    if (substr_count($settings['resize_preview'], 'x') > 0) {
        list($previewwidth, $previewheight) = explode('x', $settings['resize_preview'], 2);
    }
    if (substr_count($settings['imgthumbsize'], 'x') > 0) {
        list($thumbwidth, $thumbheight) = explode('x', $settings['imgthumbsize'], 2);
    }
    return array(
        $previewwidth,
        $previewheight,
        $thumbwidth,
        $thumbheight
    );
}   // end function mod_nwi_get_sizes()



// if file exists, find new name by adding a number
function mod_nwi_find_free_filename($mod_nwi_file_dir, $imagename)
{
    if (file_exists($mod_nwi_file_dir.$imagename)) {
        $num = 1;
        $f_name = pathinfo($mod_nwi_file_dir.$imagename, PATHINFO_FILENAME);
        $suffix = pathinfo($mod_nwi_file_dir.$imagename, PATHINFO_EXTENSION);
        while (file_exists($mod_nwi_file_dir.$f_name.'_'.$num.'.'.$suffix)) {
            $num++;
        }
        $imagename = $f_name.'_'.$num.'.'.$suffix;
    }
    return $imagename;
}

function mod_nwi_byte_convert($bytes)
{
    $symbol = array(' bytes', ' KB', ' MB', ' GB', ' TB');
    $exp = 0;
    $converted_value = 0;
    // log(0) is -INF and log() of a negative number is NaN; guard against
    // both. Negative input is clamped to 0 so the function returns
    // "0.00 bytes" rather than producing a fatal/warning.
    $bytes = (float)$bytes;
    if ($bytes > 0) {
        $exp = (int)min(floor(log($bytes) / log(1024)), count($symbol) - 1);
        $converted_value = $bytes / pow(1024, $exp);
    }
    return sprintf('%.2f '.$symbol[$exp], $converted_value);
}   // end function mod_nwi_byte_convert()

function mod_nwi_escapeString($string)
{
    global $database;
    if ($string === null) {
        return '';
    }
    if (method_exists($database, 'escapeString')) {
        return $database->escapeString($string);
    }
    if (defined('CAT_PATH')) {
        $quoted = $database->conn()->quote($string);
        $quoted = substr_replace($quoted, '', 0, 1);
        $quoted = substr_replace($quoted, '', -1, 1);
        return $quoted;
    }
    // Last-resort fallback so we never return null and corrupt a query.
    return addslashes((string)$string);
}

/**
 * Allow only well-formed CSS color values for inline use in a style attribute.
 * Returns '' for anything that doesn't match a known-safe pattern, so callers
 * can simply skip emitting the style="…" entirely when the value is unsafe.
 *
 * Accepted formats:
 *   - hex:           #rgb, #rgba, #rrggbb, #rrggbbaa
 *   - functional:    rgb()/rgba()/hsl()/hsla() with only digits, commas,
 *                    dots, spaces, percent signs and slashes
 *   - keyword:       plain letter sequences up to 32 chars (e.g. "red",
 *                    "transparent"); we do not enumerate the full CSS list,
 *                    but the character class blocks any quote/angle break
 */
function mod_nwi_safe_css_color(?string $color): string
{
    if ($color === null) {
        return '';
    }
    $color = trim($color);
    if ($color === '') {
        return '';
    }
    if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $color)) {
        return $color;
    }
    if (preg_match('/^(rgb|rgba|hsl|hsla)\(\s*[0-9,.\s%\/]+\)$/', $color)) {
        return $color;
    }
    if (preg_match('/^[a-zA-Z]{3,32}$/', $color)) {
        return $color;
    }
    return '';
}

/**
 * Pick a readable text color (black/white) for a given background color.
 * Only hex colors are evaluated; for anything else '' is returned (the
 * caller then leaves the text color to CSS).
 */
function mod_nwi_contrast_color(string $color): string
{
    $hex = ltrim(trim($color), '#');
    if (preg_match('/^[0-9a-fA-F]{3}$/', $hex)) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
        return '';
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    // perceived brightness (YIQ): >=140 -> dark text, else light text
    $yiq = ($r * 299 + $g * 587 + $b * 114) / 1000;
    return $yiq >= 140 ? '#000000' : '#ffffff';
}

/**
 * Build the inline style="" attribute for a tag span from its optional
 * background (`tag_color`) and text (`tag_text_color`) colors. If a
 * background is set but no text color, a readable contrast color is chosen
 * automatically. Returns '' when nothing is set. Result is HTML-escaped.
 */
function mod_nwi_tag_style_attr(array $tag): string
{
    $styles = [];
    $bg = mod_nwi_safe_css_color((string)($tag['tag_color'] ?? ''));
    if ($bg !== '') {
        $styles[] = 'background-color:' . $bg;
    }
    $fg = mod_nwi_safe_css_color((string)($tag['tag_text_color'] ?? ''));
    if ($fg === '' && $bg !== '') {
        $fg = mod_nwi_contrast_color($bg);
    }
    if ($fg !== '') {
        $styles[] = 'color:' . $fg;
    }
    if (empty($styles)) {
        return '';
    }
    return ' style="' . htmlspecialchars(implode(';', $styles), ENT_QUOTES | ENT_HTML5) . '"';
}

function mod_nwi_return_bytes($val)
{
    $val  = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    $val  = intval($val);
    switch ($last) {
        case 'g':
            $val *= 1024;
            // no break
        case 'm':
            $val *= 1024;
            // no break
        case 'k':
            $val *= 1024;
    }

    return $val;
}

/**
 * Convenience-Wrapper um mod_nwi_create_file(): nimmt ein Post-Array (mit
 * mindestens post_id + link) und die Section/Page-IDs, sichert ab, dass das
 * Parent-Verzeichnis existiert, ruft mod_nwi_create_file() und prüft, ob die
 * Datei wirklich geschrieben wurde.
 *
 * Wird vom Refresh-Access-Files-Button, vom Demo-Import und von den
 * regulären add/save/move/import-Pfaden genutzt — damit gibt es genau eine
 * Stelle, die das "Access-Datei anlegen"-Verhalten implementiert.
 *
 * @param array       $post        ['post_id'=>int, 'link'=>string]
 * @param int         $section_id
 * @param int         $page_id
 * @param string|null $filetime    optionaler mtime-Wert (für Umbenennungs-Pfade)
 * @return bool       true wenn die Datei nach dem Aufruf existiert
 */
function mod_nwi_post_refresh_access_file(array $post, int $section_id, int $page_id, ?string $filetime = null): bool
{
    if (empty($post['post_id']) || empty($post['link'])) {
        return false;
    }
    $filename = WB_PATH.PAGES_DIRECTORY.$post['link'].PAGE_EXTENSION;
    $parent   = dirname($filename);
    if (!is_dir($parent)) {
        make_dir($parent);
    }
    if (!is_writable($parent)) {
        return false;
    }
    mod_nwi_create_file(
        $filename,
        $filetime,
        (string)$post['post_id'],
        (string)$section_id,
        (string)$page_id
    );
    return is_file($filename);
}

function mod_nwi_create_file(string $filename, ?string $filetime = null, ?string $postID = null, ?string $sectionID = null, ?string $pageID = null)
{
    global $page_id, $section_id, $post_id;

    if (!empty($postID)) {
        $post_id = $postID;
    }
    // on copy/move, the ID of the new sections is passed; in all other cases
    // we use the ID of the current section
    if (empty($sectionID)) {
        $sectionID = $section_id;
    }
    if (empty($pageID)) {
        $pageID = $page_id;
    }

    // Hard cast all values that will be written into PHP source — these
    // come from many callers (some passing strings from DB rows), and any
    // non-numeric value here would be a PHP code injection.
    $safe_page_id    = (int)$pageID;
    $safe_section_id = (int)$sectionID;
    $safe_post_id    = (int)$post_id;

    // Determine the timestamp to keep: preserve the existing file's mtime
    // if present, otherwise use "now". No upfront unlink here — the old
    // file stays in place until a complete new one is ready (see below).
    if (file_exists($filename)) {
        $filetime = !empty($filetime) ? $filetime : filemtime($filename);
    } else {
        $filetime = !empty($filetime) ? $filetime : time();
    }
    // The depth of the page directory in the directory hierarchy
    // '/pages' is at depth 1
    $pages_dir_depth = count(explode('/', PAGES_DIRECTORY)) - 1;
    // Work-out how many ../'s we need to get to the index page
    $index_location = '../';
    for ($i = 0; $i < $pages_dir_depth; $i++) {
        $index_location .= '../';
    }

    // Write to the filename
    $content = sprintf(
'<?php
$page_id = %d;
$section_id = %d;
$post_id = %d;

define("POST_SECTION", $section_id);
define("POST_ID", $post_id);
require("%sconfig.php");
require(WB_PATH."/index.php");
?>',
        $safe_page_id,
        $safe_section_id,
        $safe_post_id,
        addslashes($index_location)
    );
    // Atomic write: build the full file in a temp file in the same
    // directory first, then move it into place. This guarantees we never
    // leave a missing or half-written access file — the original is only
    // replaced once a complete new file exists on disk.
    $dir = dirname($filename);
    $tmp = @tempnam($dir, 'nwi');
    if ($tmp === false) {
        return;
    }

    $written = false;
    if ($handle = fopen($tmp, 'wb')) {
        $bytes = fwrite($handle, $content);
        fflush($handle);
        fclose($handle);
        $written = ($bytes !== false && $bytes === strlen($content));
    }
    if (!$written) {
        @unlink($tmp);
        return;
    }

    // Move the temp file into place. rename() replaces atomically on POSIX;
    // on Windows it fails if the target already exists, so fall back to
    // unlink()+rename() there — but only now that a complete temp file is
    // guaranteed, so a failure can at worst lose the temp file, never the
    // freshly written content before it is in place.
    if (!@rename($tmp, $filename)) {
        if (file_exists($filename)) {
            @unlink($filename);
        }
        if (!@rename($tmp, $filename)) {
            @unlink($tmp);
            return;
        }
    }

    if ($filetime) {
        touch($filename, $filetime);
    }
    change_mode($filename);
}

/**
 *
 * @access
 * @return
 **/
function mod_nwi_escape_tags($tags)
{
    global $database;
    if (empty($tags)) {
        return array();
    }
    $tags = explode(",", $tags);
    foreach ($tags as $i => $tag) {
        $tags[$i] = mod_nwi_escapeString($tag);
    }
    return $tags;
}   // end function mod_nwi_escape_tags()


/**
 * resize image
 *
 * return values:
 *    true - ok
 *    1    - image is smaller than new size
 *    2    - invalid type (unable to handle)
 *
 * @param $src    - image source
 * @param $dst    - save to
 * @param $width  - new width
 * @param $height - new height
 * @param $crop   - 0=no, 1=yes
 **/
function mod_nwi_image_resize($src, $dst, $width, $height, $crop = 0, $force_reencode = false)
{
    //var_dump($src);
    if (!list($w, $h) = getimagesize($src)) {
        return 2;
    }
    $type = strtolower(pathinfo($src, PATHINFO_EXTENSION));
    if ($type == 'jpeg') {
        $type = 'jpg';
    }
    switch ($type) {
        case 'gif':
            if (!function_exists('imagecreatefromgif')) {
                return 2;
            } else {
                try {
                    $img = imagecreatefromgif($src);
                } catch (\Exception $e) {
                    return 2;
                }
            }
            break;
        case 'jpg':
            if (!function_exists('imagecreatefromjpeg')) {
                return 2;
            } else {
                try {
                    $img = imagecreatefromjpeg($src);
                } catch (\Exception $e) {
                    return 2;
                }
            }
            break;
        case 'png':
            if (!function_exists('imagecreatefrompng')) {
                return 2;
            } else {
                try {
                    $img = imagecreatefrompng($src);
                } catch (\Exception $e) {
                    return 2;
                }
            }
            break;
        case 'webp':
            if (!function_exists('imagecreatefromwebp')) {
                return 2;
            } else {
                try {
                    $img = imagecreatefromwebp($src);
                } catch (\Exception $e) {
                    return 2;
                }
            }
            break;
        default: return 2;
    }

    // resize
    if ($crop) {
        if ($w < $width or $h < $height) {
            // Bild kleiner als Crop-Box. Mit $force_reencode trotzdem in
            // Originalgröße neu kodieren (strippt eingebettete Payloads),
            // sonst wie bisher: nichts schreiben.
            if (!$force_reencode) {
                return 1;
            }
            $width = $w;
            $height = $h;
            $x = 0;
        } else {
            $ratio = max($width / $w, $height / $h);
            $h = $height / $ratio;
            $x = ($w - $width / $ratio) / 2;
            $w = $width / $ratio;
        }
    } else {
        if ($w < $width and $h < $height) {
            if (!$force_reencode) {
                return 1;
            }
            $width = $w;
            $height = $h;
            $x = 0;
        } else {
            $ratio = min($width / $w, $height / $h);
            $width = $w * $ratio;
            $height = $h * $ratio;
            $x = 0;
        }
    }

    $new = imagecreatetruecolor($width, $height);

    // preserve transparency
    if ($type == "gif" or $type == "png") {
        imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
        imagealphablending($new, false);
        imagesavealpha($new, true);
    }

    imagecopyresampled($new, $img, 0, 0, $x, 0, $width, $height, $w, $h);

    switch ($type) {
        case 'gif': imagegif($new, $dst);
            break;
        case 'jpg': imagejpeg($new, $dst);
            break;
        case 'png': imagepng($new, $dst);
            break;
        case 'webp': imagewebp($new, $dst);
            break;
    }
    return true;
}


function mod_nwi_replacements()
{
    // all known placeholders
    $vars = array(
        'BACK',                         // back to list link
        'CONTENT',                      // content_short + content_long
        'CONTENT_BLOCK2',               // optional block 2
        'CONTENT_LONG',                 // long content
        'CONTENT_SHORT',                // short content (teaser)
        'CREATED_DATE',                 // post added
        'CREATED_TIME',                 // post added time
        'DISPLAY_GROUP',                // wether to show the group name
        'DISPLAY_IMAGE',                // wether to show the preview image
        'DISPLAY_NAME',                 // user's (who posted) display name
        'DISPLAY_PREVIOUS_NEXT_LINKS',  // wether to show prev/next
        'EMAIL',                        // user's (who posted) email address
        'GROUP_ID',                     // ID of the group the post is linked to
        'GROUP_IMAGE',                  // image of the group
        'GROUP_IMAGE_URL',              // image url
        'GROUP_TITLE',                  // group title
        'IMAGE',                        // preview image
        'IMAGE_URL',					// URL of preview image without <img src>
        'IMAGES',                       // gallery images
        'LINK',                         // "read more" link
        'HREF',							// link to post detail including href=""
        'AOPEN',						// complete <a href=""> if long text or gallery exists
        'ACLOSE',						// closing </a> if long text or gallery exists
        'MODI_DATE',                    // post modification date
        'MODI_TIME',                    // post modification time
        'NEXT_LINK',                    // next link
        'NEXT_PAGE_LINK',               // next page link
        'OF',                           // text "of" ("von")
        'OUT_OF',                       // text "out of" ("von")
        'PAGE_TITLE',                   // page title
        'POST_ID',                      // ID of the post
        'POST_OR_GROUP_IMAGE',          // alias for IMAGE — full cascade post→group→default→nopic
        'PREVIOUS_LINK',                // prev link
        'PREVIOUS_PAGE_LINK',           // prev page link
        'PUBLISHED_DATE',               // published date
        'PUBLISHED_TIME',               // published time
        'SHORT',                        // alias for CONTENT_SHORT
        'SHOW_READ_MORE',               // wether to show "read more" link
        'TAGS',                         // tags
        'TAGLIST',						// tags without formatting
        'TEXT_AT',                      // text for "at" ("um")
        'TEXT_BACK',                    // text for "back" ("zurück")
        'TEXT_LAST_CHANGED',            // text for "last changed" ("zuletzt geändert")
        'TEXT_NEXT_POST',                // text for "next post" ("nächster Beitrag")
        'TEXT_O_CLOCK',                 // text for "o'clock" ("Uhr")
        'TEXT_ON',                      // text for "on" ("am")
        'TEXT_POSTED_BY',               // text for "posted by" ("verfaßt von")
        'TEXT_PREV_POST',               // text for "previous post" ("voriger Beitrag")
        'TEXT_READ_MORE',               // text for "read more" ("Weiterlesen")
        'TITLE',                        // post title (heading)
        'USER_ID',                      // user's (who posted) ID
        'USERNAME',                     // user's (who posted) username
    );
    $default_replacements = array(

    );
    return array($vars,$default_replacements);
}

function mod_nwi_display_news_items(
    $group_id = 0,                  // IDs of news to show, matching defined $group_id_type (default:=0, all news, 0..N, or array(2,4,5,N) to limit news to IDs matching $group_id_type)
    $max_news_items = 10,           // maximum number of news shown (default:= 10, min:=1, max:= 999)
    $max_news_length = -1,          // maximum length of the short news text shown (default:=-1 => full news length)
    $lang_id = 'AUTO',              // language file to load and lang_id used if $lang_filer = true (default:= auto, examples: AUTO, DE, EN)
    $strip_tags = true,             // true:=remove tags from short and long text (default:=true); false:=don´t strip tags
    $allowed_tags = '<p><a><img>',  // tags not striped off (default:='<p><a><img>')
    $custom_placeholder = false,    // false:= none (default), array('MY_VAR_1' => '%TAG%#', ... 'MY_VAR_N' => '#regex_N#' ...)
    $sort_by = 1,                   // 1:=position (default), 2:=posted_when, 3:=published_when, 4:=random order 5:=published_until
    $sort_order = 1,                // 1:=descending (default), 2:=ascending
    $not_older_than = 0,            // 0:=disabled (default), 0-999 (only show news `published_when` date <=x days; 12 hours:=0.5)
    $is_not_older_than = 0,         // alias for not_older_than
    $group_id_type = 'group_id',    // type used by group_id to extract news entries (supported: 'group_id', 'page_id', 'section_id', 'post_id')
    $lang_filter = false,           // flag to enable language filter (default:= false, show only news from a news page, which language fits $lang_id)
    $skip = null,
    $tags = null,
    $groups_on_tags = false,        // wether to use the group_id if $skip or $tags is set
    $view = null,                   // CSS view to use
    $aslist = false
) {
    $output = mod_nwi_get_news_items(
        $options = array(
            'group_id_type'      => $group_id_type,
            'group_id'           => $group_id,
            'max_news_items'     => $max_news_items,
            'max_news_length'    => $max_news_length,
            'strip_tags'         => $strip_tags,
            'allowed_tags'       => $allowed_tags,
            'custom_placeholder' => $custom_placeholder,
            'sort_by'            => $sort_by,
            'sort_order'         => $sort_order,
            'not_older_than'     => $not_older_than,
                'is_not_older_than'  => $is_not_older_than,
            'lang_id'            => $lang_id,
            'lang_filter'        => $lang_filter,
                'skip'               => $skip,
            'tags'               => $tags,
                    'groups_on_tags'     => $groups_on_tags,
                    'view'               => $view,
                    'aslist'             => $aslist,
        )
    );
    echo $output;
}

function mod_nwi_get_news_items($options = array())
{
    global $wb, $database, $LANG;

    // default settings
    $defaults = array(
        'group_id_type' => 'group_id',    // type used by group_id to extract news entries (supported: 'group_id', 'page_id', 'section_id', 'post_id')
        'group_id' => 0,                  // IDs of news to show, matching defined $group_id_type (default:=0, all news, 0..N, or array(2,4,5,N) to limit news to IDs matching $group_id_type)
        'start_news_item' => 0,           // start showing news from the Nth news item onwards (default:= 0, min:=-999, max:= 999); Note: -1: last item, -2: 2nd last etc.
        'max_news_items' => 10,           // maximum number of news shown (default:= 10, min:=1, max:= 999)
        'max_news_length' => -1,          // maximum length of the short news text shown (default:=-1 => full news length)
        'strip_tags' => true,             // true:=remove tags from short and long text (default:=true); false:=dont strip tags
        'allowed_tags' => '<p><a><img>',  // tags not striped off (default:='<p><a><img>')
        'sort_by' => 1,                   // 1:=position (default), 2:=posted_when, 3:=published_when, 4:=random order 5:=published_until
        'sort_order' => 1,                // 1:=descending (default), 2:=ascending
        'not_older_than' => 0,            // 0:=disabled (default), 0-999 (only show news `published_when` date <=x days; 12 hours:=0.5)
        'is_not_older_than' => 0,
        'lang_id' => 'AUTO',              // language file to load and lang_id used if $lang_filer = true (default:= auto, examples: AUTO, DE, EN)
        'lang_filter' => false,	          // flag to enable language filter (default:= false, show only news from a news page, which language fits $lang_id)
        'skip' => null,                   // do not show posts with the given list of tags (default:=none)
        'tags' => null,                   // show posts with only the given list of tags
        'taglist' => null,				  // show tags as simple list
        'groups_on_tags' => false,        // wether to use the group_id if $skip or $tags is set
        'view' => 'default',              // use css from subfolder ('default','grid','avatar',...)
        'aslist' => false                 // unordered list of titles
    );

    // merge defaults and options array and remove unsupported keys
    $settings = array_merge($defaults, $options);
    foreach ($settings as $key => $value) {
        if (!array_key_exists($key, $defaults)) {
            unset($settings[$key]);
        }
    }

    // explicit assignment instead of extract()
    $group_id_type    = $settings['group_id_type'];
    $group_id         = $settings['group_id'];
    $start_news_item  = $settings['start_news_item'];
    $max_news_items   = $settings['max_news_items'];
    $max_news_length  = $settings['max_news_length'];
    $strip_tags       = $settings['strip_tags'];
    $allowed_tags     = $settings['allowed_tags'];
    $sort_by          = $settings['sort_by'];
    $sort_order       = $settings['sort_order'];
    $not_older_than   = $settings['not_older_than'];
    $is_not_older_than = $settings['is_not_older_than'];
    $lang_id          = $settings['lang_id'];
    $lang_filter      = $settings['lang_filter'];
    $skip             = $settings['skip'];
    $tags             = $settings['tags'];
    $taglist          = $settings['taglist'];
    $groups_on_tags   = $settings['groups_on_tags'];
    $view             = $settings['view'];
    $aslist           = $settings['aslist'];

    /**
     * Sanitize user specified function parameters
     */
    mod_nwi_sanitize_input($group_id, 'i{0;0;999}');
    mod_nwi_sanitize_input($start_news_item, 'i{0;-999;999}');
    mod_nwi_sanitize_input($max_news_items, 'i{10;1;999}');
    mod_nwi_sanitize_input($max_news_length, 'i{-1;0;250}');
    mod_nwi_sanitize_input($strip_tags, 'b');
    mod_nwi_sanitize_input($allowed_tags, 's{TRIM}');
    mod_nwi_sanitize_input($sort_by, 'i{1;1;5}');
    mod_nwi_sanitize_input($sort_order, 'i{1;1;2}');
    mod_nwi_sanitize_input($not_older_than, 'd{0;0;999}');
    mod_nwi_sanitize_input($is_not_older_than, 'd{0;0;999}');
    mod_nwi_sanitize_input($group_id_type, 'l{group_id;group_id;page_id;section_id;post_id}');
    mod_nwi_sanitize_input($lang_filter, 'b');
    mod_nwi_sanitize_input($skip, 's{TRIM|STRIP|ENTITIES}');
    mod_nwi_sanitize_input($tags, 's{TRIM|STRIP|ENTITIES}');
    mod_nwi_sanitize_input($groups_on_tags, 'b');
    mod_nwi_sanitize_input($view, 's{TRIM|STRIP|ENTITIES}');

    $sql_group_id = null;
    $sql_not_older_than = null;
    $sql_lang_filter = null;
    $sql_sort_order = null;
    $sql_limit = null;

    // the "tags" param may be passend by a page link
    if (!strlen($tags) && isset($_GET['tags'])) {
        $tags = htmlspecialchars($_GET['tags']);
        mod_nwi_sanitize_input($tags, 's{TRIM|STRIP|ENTITIES}');
    }

    // ---------- group handling -----------------------------------------------

    // show all news items if 0 is contained in group_id array
    if (is_array($group_id) && in_array(0, $group_id)) {
        $group_id = 0;
    }

    // check for multiple groups or single group values
    if (is_array($group_id)) {
        // SQL query for multiple groups
        $sql_group_id = "t1.`$group_id_type` IN (" . implode(',', $group_id) . ")";
    } else {
        // SQL query for single or empty groups
        $sql_group_id = ($group_id) ? "t1.`$group_id_type` = '$group_id'" : '1';
    }

    // ---------- time filter --------------------------------------------------
    $server_time = time();
    $sql_not_older_than = '1';
    if ($is_not_older_than > 0) {
        $not_older_than = $is_not_older_than;
    }
    if ($not_older_than > 0) {
        $sql_not_older_than = ' (t1.`published_when` >= \'' . ($server_time - ($not_older_than * 24 * 60 * 60)) . '\')';
    }

    // ---------- language filter ----------------------------------------------
    $sql_lang_filter = '1';
    if ($lang_filter) {
        if (!defined('CAT_PATH')) {
            $page_ids = getPageIdsByLanguage($lang_id);
        } else {
            $pages = CAT_Helper_Page::getPagesForLang($lang_id);
            $page_ids = [];
            foreach ($pages as $i => $pg) {
                $page_ids[] = $pg['page_id'];
            }
        }
        if (count($page_ids) > 0) {
            $sql_lang_filter = 't1.`page_id` in (' . implode(',', $page_ids) . ')';
        }
    }

    // ---------- tag filter ---------------------------------------------------
    $filter_posts = [];
    $sql_filter_posts = null;
    if (!empty($skip)) {
        $skip_tags = explode(",", urldecode($skip));
        foreach ($skip_tags as $i => $t) {
            $skip_tags[$i] = mod_nwi_escapeString($t);
        }
        $r = $database->query(
            "SELECT `t2`.`post_id` FROM `".TABLE_PREFIX."mod_news_img_tags` as `t1` ".
            "JOIN `".TABLE_PREFIX."mod_news_img_tags_posts` AS `t2` ".
            "ON `t1`.`tag_id`=`t2`.`tag_id` ".
            "WHERE `tag` IN ('".implode("', '", $skip_tags)."') ".
            "GROUP BY `t2`.`post_id`"
        );
        while ($row = $r->fetchRow()) {
            $filter_posts[] = $row['post_id'];
        }
        if (count($filter_posts) > 0) {
            $sql_filter_posts = " AND `t1`.`post_id` NOT IN (".implode(',', array_values($filter_posts)).") ";
        }
    }
    if (!empty($tags)) {
        $tags = explode(",", urldecode($tags));
        foreach ($tags as $i => $t) {
            $tags[$i] = mod_nwi_escapeString($t);
        }
        $r = $database->query(
            "SELECT `t2`.`post_id` FROM `".TABLE_PREFIX."mod_news_img_tags` as `t1` ".
            "JOIN `".TABLE_PREFIX."mod_news_img_tags_posts` AS `t2` ".
            "ON `t1`.`tag_id`=`t2`.`tag_id` ".
            "WHERE `tag` IN ('".implode("', '", $tags)."') ".
            "GROUP BY `t2`.`post_id`"
        );
        while ($row = $r->fetchRow()) {
            $filter_posts[] = $row['post_id'];
        }
        if (count($filter_posts) > 0) {
            $sql_filter_posts = " AND `t1`.`post_id` IN (".implode(',', array_values($filter_posts)).") ";
        } else { // no posts for any tag
            $sql_filter_posts = " AND `t1`.`post_id`='-1'";
        }
        if ($groups_on_tags == false) {
            $sql_group_id = '1';
        }
    }

    // ---------- sort order ---------------------------------------------------
    $order_by_options = array('t1.`position`', 't1.`posted_when`', 't1.`published_when`', 'RAND()', 't1.`published_until`');
    $sql_order_by = $order_by_options[$sort_by - 1];
    $sql_sort_order = ($sort_order == 1) ? 'DESC' : 'ASC';

    $sql_group_active = mod_nwi_sql_group_active('t1');
    $sql = "SELECT t1.*
		FROM `%smod_news_img_posts` as t1
		WHERE t1.`active`='1'
		AND $sql_group_id
		AND $sql_lang_filter
		AND (t1.`published_when` = '0' or t1.`published_when` <= '$server_time')
		AND (t1.`published_until` = '0' OR t1.`published_until` >= '$server_time')
		AND $sql_not_older_than
		$sql_group_active
        $sql_filter_posts
		GROUP BY t1.`post_id`
		ORDER BY $sql_order_by $sql_sort_order
	";

    // ---------- limit --------------------------------------------------------
    // start from N-th last news item if $start_news_items is negative
    if ($start_news_item < 0) {
        // find total news items matching SQL query
        $results = $database->query(sprintf($sql, TABLE_PREFIX));
        $total_news = ($results) ? $results->numRows() : 0;
        // adjust start_news_item to the N-th last news item
        $start_news_item = $total_news + $start_news_item;
        if ($start_news_item < 0) {
            $start_news_item = 0;
        }
    }

    $sql .= "
		LIMIT $start_news_item, $max_news_items
	";

    $query_posts = $database->query(sprintf($sql, TABLE_PREFIX));

    if (!empty($query_posts) && $query_posts->numRows() > 0) {
        // map group index to title
        list($groups, $pages) = mod_nwi_get_all_groups(0, 0);
        $group_map = [];
        foreach ($groups as $pg => $sections) {
            foreach ($sections as $section_id => $grps) {
                foreach ($grps as $i => $g) {
                    $group_map[$g['group_id']] = (empty($g['title']) ? $TEXT['NONE'] : $g['title']);
                }
            }
        }
        // get users
        $users = mod_nwi_users_get();
        // add "unknown" user
        $users[0] = array(
            'username' => 'unknown',
            'display_name' => 'unknown',
            'email' => ''
        );
        $posts = [];
        while ($post = $query_posts->fetchRow()) {
            $post['content_short'] = ($strip_tags) ? strip_tags($post['content_short'], $allowed_tags) : $post['content_short'];
            $post['content_long'] = ($strip_tags) ? strip_tags($post['content_long'], $allowed_tags) : $post['content_long'];
            // shorten news text to defined news length (-1 for full text length)
            if ($max_news_length != -1 && strlen($post['content_short']) > $max_news_length) {
                // truncate text if user asked for using CakePHP truncate function
                $post['content_short'] = nia_truncate($post['content_short'], $max_news_length);
            }
            // gallery images - wichtig für link "weiterlesen"  SHOW_READ_MORE
            $images = mod_nwi_img_get_by_post($post['post_id'], false);
            $anz_post_img = count($images);
            $post_href_link = 'href="'. $post['post_link'].'"';
            $post_a_open_tag = '<a '.$post_href_link.'>';
            $post_a_close_tag = '</a>';
            // no "read more" link if no long content
            if ((strlen($post['content_long']) < 9) && ($anz_post_img < 1)) {
                $post['post_link'] = '#" onclick="javascript:void(0);return false;" style="cursor:no-drop;';
                $post_href_link = '';
                $post_a_open_tag = '';
                $post_a_close_tag = '';
            }
            $posts[] = mod_nwi_post_process($post, $post['section_id'], $users);
        }
    }

    if (!empty($settings['aslist']) && $settings['aslist'] == true) {
        $output = array('<ul class="nia_posts">');
        foreach ($posts as $p) {
            $output[] = '<li class="nia_post"><a href="'.WB_URL.PAGES_DIRECTORY.$p['link'].PAGE_EXTENSION.'">'.$p['title'].'</a></li>';
        }
        $output[] = '</ul>';
        return implode("\n", $output);
    }

    $tpl = '/templates/default/view.phtml';

    $tpl_data = mod_nwi_posts_render($section_id, $posts, 0);

    ob_start();
    include __DIR__.$tpl;
    $content = ob_get_contents();
    ob_end_clean();

    if (defined('CAT_PATH')) {
        $sql = 'SELECT `drop_file` FROM `%smod_droplets_extension` WHERE  `drop_droplet_name`="%s" AND `drop_page_id`=%d';
        $stmt = $database->query(sprintf($sql, TABLE_PREFIX, 'getnewsitems', PAGE_ID));
        $data = $stmt->fetchAll();
        if (is_array($data)) {
            foreach ($data as $i => $d) {
                if (pathinfo($d['drop_file'], PATHINFO_BASENAME) == 'frontend.css') {
                    if (pathinfo(pathinfo($d['drop_file'], PATHINFO_DIRNAME), PATHINFO_BASENAME) != $view) {
                        CAT_Helper_Droplet::unregister_droplet_css('getNewsItems', PAGE_ID);
                    }
                }
            }
        }
        if (!CAT_Helper_Droplet::is_registered_droplet_css('getNewsItems', PAGE_ID)) {
            CAT_Helper_Droplet::register_droplet_css('getNewsItems', PAGE_ID, 'news_img', 'views/'.$view.'/frontend.css');
        }
    }

    return $content;
}

/**
 * Function to sanitize function input parameters
*/
function mod_nwi_sanitize_input(&$input, $filter)
{
    // $input...        input variable to filter
    // $filter...       filter to apply for input variable
    //	Numeric filter: b|i¦d{default;min;max}
    //	List filter:    l{default;string1;string2;..;stringN}
    //	String filter:  s{TRIM|STRIP|ENTITIES}

    // check if a valid filter was supplied
    if (!preg_match('#(b|i|d|s|l)#i', $filter, $match)) {
        echo 'Filter: <b>' . htmlentities($filter) . '</b> is no valid filter expression.';
        die;
    }

    // convert user input to array (allows to handle single values and array identical)
    $temp = is_array($input) ? $input : array($input);

    // evaluate filter expressions
    $filter_type = strtolower($match[1]);
    switch ($filter_type) {
        case 'b': case 'i': case 'd': // numeric filter ($input can be single value or array)
            // check if optional filter values are supplied (default, min, max)
            $advanced_filter = (preg_match('#(i|d)\{([-.0-9]+);([-.0-9]+);([-.0-9]+)\}#i', $filter, $match));

            // loop over input values
            foreach ($temp as $key => $value) {
                // force type casting to either integer or double
                if ($filter_type == 'b') {
                    $temp[$key] = (bool) $temp[$key];
                }
                if ($filter_type == 'i') {
                    $temp[$key] = (int) $temp[$key];
                }
                if ($filter_type == 'd') {
                    $temp[$key] = (float) $temp[$key];
                }

                // check if value is within min/max range, if not use default value
                if ($advanced_filter) {
                    $temp[$key] = ($temp[$key] >= $match[3] && $temp[$key] <= $match[4]) ? $temp[$key] : $match[2];
                }
            }
            break;

        case 'l': // list filter
            // check for correct list filter: l{default;list1;list2;..;listN}
            if (!preg_match('#(l)\{([^;]+?);(.+)\}#i', $filter, $match)) {
                echo 'List filter: <b>' . htmlentities($filter) . '</b> invalid. Usage: <b>l{default;list1;list2;..listN}</b>.';
                die;
            }

            // create array with list values from regular expression
            $list_values = explode(';', $match[3]);

            // loop over input values
            foreach ($temp as $key => $value) {
                // check if value is in list (return default value if not in list)
                $temp[$key] = (in_array($value, $list_values) ? $value : $match[2]);
            }
            break;

        case 's': // string filter
            // check for correct string filter: s{TRIM|STRIP|ENTITIES}
            if (!preg_match('#(s)\{(.+)\}#i', $filter, $match)) {
                echo 'String filter: <b>' . htmlentities($filter) . '</b> invalid. Usage: <b>s{STRIP;TRIM;ENTITIES}</b>.';
                die;
            }

            // get filter options from regular expression
            $filter_options = strtoupper($match[2]);

            // loop over input values
            foreach ($temp as $key => $value) {
                // check if value is in list (return default value if not in list)
                if (strpos($filter_options, 'STRIP') !== false) {
                    $temp[$key] = strip_tags($temp[$key]);
                }
                if (strpos($filter_options, 'TRIM') !== false) {
                    $temp[$key] = trim($temp[$key]);
                }
                if (strpos($filter_options, 'ENTITIES') !== false) {
                    $temp[$key] = htmlentities($temp[$key]);
                }
            }
            break;
    }

    // revert user input back to array or single value
    $input = is_array($input) ? $temp : $temp[0];
}


// ========== Demo data ========================================================

/**
 * Liefert die verfügbaren Demo-Daten-Packs aus modules/news_img/demodata/.
 * Ein Pack ist ein Unterordner, der mindestens meta.json und data.php enthält.
 *
 * Sortierreihenfolge:
 *   1. Packs der aktuellen Backend-Sprache zuerst (LANGUAGE-Konstante)
 *   2. Innerhalb derselben Sprache alphabetisch nach Pack-Name
 *
 * @return array<string,array>  pack name => meta data
 */
function mod_nwi_demodata_list(): array
{
    $packs = [];
    $base  = __DIR__ . '/demodata';
    if (!is_dir($base)) {
        return $packs;
    }
    foreach (scandir($base) as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        // Sicherheitsnetz: nur einfache Ordnernamen zulassen, kein Pfad-Trickserei
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $entry)) {
            continue;
        }
        $dir = $base . '/' . $entry;
        if (!is_dir($dir) || !is_file($dir.'/meta.json') || !is_file($dir.'/data.php')) {
            continue;
        }
        $meta = json_decode((string)file_get_contents($dir.'/meta.json'), true);
        if (!is_array($meta)) {
            continue;
        }
        $packs[$entry] = [
            'name'        => isset($meta['name']) ? (string)$meta['name'] : $entry,
            'description' => isset($meta['description']) ? (string)$meta['description'] : '',
            'language'    => isset($meta['language']) ? strtoupper((string)$meta['language']) : '',
            'author'      => isset($meta['author']) ? (string)$meta['author'] : '',
            'version'     => isset($meta['version']) ? (string)$meta['version'] : '',
        ];
    }

    // Sortieren: aktuelle Sprache zuerst, dann alphabetisch nach Anzeigename
    $current_lang = defined('LANGUAGE') ? strtoupper((string)LANGUAGE) : '';
    uasort($packs, function ($a, $b) use ($current_lang) {
        $a_curr = ($a['language'] === $current_lang) ? 0 : 1;
        $b_curr = ($b['language'] === $current_lang) ? 0 : 1;
        if ($a_curr !== $b_curr) {
            return $a_curr - $b_curr;
        }
        // gleiche „Aktuell-Sprache"-Klasse → nach Sprachcode, dann Name
        $lang_cmp = strcmp($a['language'], $b['language']);
        if ($lang_cmp !== 0) {
            return $lang_cmp;
        }
        return strcasecmp($a['name'], $b['name']);
    });

    return $packs;
}

/**
 * Demo-Import nur erlauben, wenn die Section noch ohne Beiträge UND ohne
 * Gruppen ist — sonst würden importierte Daten in eine bereits begonnene
 * Konfiguration grätschen.
 */
function mod_nwi_demodata_can_import(int $section_id): bool
{
    global $database;
    $r1 = $database->query(sprintf(
        "SELECT COUNT(*) AS c FROM `%smod_news_img_posts` WHERE `section_id`=%d",
        TABLE_PREFIX, $section_id
    ));
    $r2 = $database->query(sprintf(
        "SELECT COUNT(*) AS c FROM `%smod_news_img_groups` WHERE `section_id`=%d",
        TABLE_PREFIX, $section_id
    ));
    $posts  = ($r1 && $r1->numRows() > 0) ? (int)$r1->fetchRow()['c'] : 0;
    $groups = ($r2 && $r2->numRows() > 0) ? (int)$r2->fetchRow()['c'] : 0;
    return ($posts === 0 && $groups === 0);
}

/**
 * Wendet Settings aus dem "settings"-Block der meta.json eines Demo-Packs auf
 * mod_news_img_settings für die gegebene Section an. Strenge Allowlist + per-
 * Key-Validierung, damit man sich nicht mit einem unsauberen Pack das Modul
 * zerschießen kann.
 *
 * Auto-Verhalten: hat der Pack Tags und ist mode nicht explizit gesetzt, wird
 * mode auf "advanced" angehoben — sonst werden Tags im Backend nicht angezeigt
 * und können nicht bearbeitet werden.
 *
 * @return array{applied:array<string,mixed>, skipped:string[]}
 */
function mod_nwi_demodata_apply_settings(int $section_id, array $settings, bool $has_tags): array
{
    global $database;
    $applied = [];
    $skipped = [];

    // Validatoren pro erlaubtem Key. Null = abweisen.
    $is_yn      = function ($v) { return in_array($v, ['Y', 'N'], true) ? $v : null; };
    $is_slug    = function ($v) { return preg_match('/^[a-zA-Z0-9_-]+$/', $v) ? $v : null; };
    $is_int     = function ($v) { return is_numeric($v) ? (string)(int)$v : null; };
    $is_size    = function ($v) { return preg_match('/^\d+x\d+$/', (string)$v) ? (string)$v : null; };
    $is_string  = function ($v) { return is_string($v) || is_numeric($v) ? (string)$v : null; };
    $is_mode    = function ($v) { return in_array($v, ['default', 'advanced'], true) ? $v : null; };

    $validators = [
        'mode'                      => $is_mode,
        'view'                      => $is_slug,
        'gallery'                   => $is_slug,
        'crop_preview'              => $is_yn,
        'use_second_block'          => $is_yn,
        'show_settings_only_admins' => $is_yn,
        'view_order'                => $is_int,
        'posts_per_page'            => $is_int,
        'imgmaxwidth'               => $is_int,
        'imgmaxheight'              => $is_int,
        'imgmaxsize'                => $is_int,
        'resize_preview'            => $is_size,
        'imgthumbsize'              => $is_size,
        'header'                    => $is_string,
        'footer'                    => $is_string,
        'block2'                    => $is_string,
        'post_loop'                 => $is_string,
        'post_header'               => $is_string,
        'post_content'              => $is_string,
        'image_loop'                => $is_string,
        'post_footer'               => $is_string,
    ];

    foreach ($settings as $key => $value) {
        $key = (string)$key;
        if (!isset($validators[$key])) {
            $skipped[] = $key;
            continue;
        }
        $clean = $validators[$key]($value);
        if ($clean === null) {
            $skipped[] = $key;
            continue;
        }
        $applied[$key] = $clean;
    }

    // Auto-advanced wenn Tags vorhanden und mode nicht explizit gesetzt.
    // Direkt per SQL gegen die DB, NICHT mod_nwi_settings_get — dessen Cache
    // dürfen wir hier nicht mit Pre-Update-Werten vergiften.
    if ($has_tags && !isset($applied['mode'])) {
        $q = $database->query(sprintf(
            "SELECT `mode` FROM `%smod_news_img_settings` WHERE `section_id`=%d",
            TABLE_PREFIX, $section_id
        ));
        $cur_mode = ($q && $q->numRows() > 0) ? (string)$q->fetchRow()['mode'] : 'default';
        if ($cur_mode !== 'advanced') {
            $applied['mode'] = 'advanced';
        }
    }

    if (!empty($applied)) {
        $sets = [];
        foreach ($applied as $col => $val) {
            $sets[] = sprintf("`%s`='%s'", $col, mod_nwi_escapeString((string)$val));
        }
        $database->query(sprintf(
            "UPDATE `%smod_news_img_settings` SET %s WHERE `section_id`=%d",
            TABLE_PREFIX, implode(', ', $sets), $section_id
        ));
        // Cache leeren, damit nachfolgende Resize-Calls (preview/gallery) die
        // frischen Werte lesen.
        mod_nwi_settings_get($section_id, true);
    }

    return ['applied' => $applied, 'skipped' => $skipped];
}

/**
 * Demo-Pack importieren. Legt Gruppen, Tags, Posts (samt Page-Datei), Tag-
 * Mappings und ggf. Vorschau-/Galerie-Bilder in der angegebenen Section an.
 *
 * @return array{success:bool,imported:array,errors:array}
 */
function mod_nwi_demodata_import(string $pack_name, int $section_id, int $page_id, int $posted_by): array
{
    global $database;

    $result = ['success' => false, 'imported' => [], 'errors' => []];

    // --- Pack-Pfad validieren (kein Path-Traversal)
    if (!preg_match('/^[A-Za-z0-9_-]+$/', $pack_name)) {
        $result['errors'][] = 'invalid pack name';
        return $result;
    }
    $pack_dir = __DIR__ . '/demodata/' . $pack_name;
    if (!is_dir($pack_dir) || !is_file($pack_dir.'/data.php')) {
        $result['errors'][] = 'pack not found';
        return $result;
    }

    // --- Eligibility-Recheck (defensive: UI könnte stale sein)
    if (!mod_nwi_demodata_can_import($section_id)) {
        $result['errors'][] = 'section not empty';
        return $result;
    }

    // --- Pack-Daten laden
    $data = require $pack_dir.'/data.php';
    if (!is_array($data)) {
        $result['errors'][] = 'pack data invalid';
        return $result;
    }

    $images_dir = $pack_dir.'/images';

    // --- Settings aus data.php['settings'] anwenden (vor allem anderen, damit
    //     nachfolgende Image-Resize-Calls schon die neuen Werte sehen).
    //     Wenn der Pack Tags enthält und mode nicht explizit gesetzt wurde,
    //     schalten wir auf "advanced" — sonst sind die Tags im Backend nicht
    //     bearbeitbar.
    $pack_settings = (isset($data['settings']) && is_array($data['settings'])) ? $data['settings'] : [];
    $pack_has_tags = !empty($data['tags']);
    $apply_result  = mod_nwi_demodata_apply_settings($section_id, $pack_settings, $pack_has_tags);
    if (!empty($apply_result['applied'])) {
        $result['imported']['settings'] = count($apply_result['applied']);
    }
    if (!empty($apply_result['skipped'])) {
        $result['errors'][] = 'settings ignored (unknown or invalid): '.implode(', ', $apply_result['skipped']);
    }

    // --- Groups: title -> group_id
    $group_map = [];
    foreach ((array)($data['groups'] ?? []) as $g) {
        $title    = mod_nwi_escapeString((string)($g['title'] ?? ''));
        $active   = (int)($g['active'] ?? 1);
        $position = (int)($g['position'] ?? 0);
        if ($title === '') { continue; }
        $database->query(sprintf(
            "INSERT INTO `%smod_news_img_groups` (`section_id`,`active`,`position`,`title`) "
            . "VALUES (%d, %d, %d, '%s')",
            TABLE_PREFIX, $section_id, $active, $position, $title
        ));
        if ($database->is_error()) {
            $result['errors'][] = 'group "'.$g['title'].'": '.$database->get_error();
            continue;
        }
        $group_map[(string)$g['title']] = (int)$database->getLastInsertId();
        $result['imported']['groups'] = ($result['imported']['groups'] ?? 0) + 1;
    }

    // --- Tags: name -> tag_id. Globale Tags (section_id=0); falls bereits
    //     vorhanden, wieder verwenden statt duplizieren.
    $tag_map = [];
    foreach ((array)($data['tags'] ?? []) as $t) {
        $name  = (string)($t['tag'] ?? '');
        $color = mod_nwi_safe_css_color((string)($t['tag_color'] ?? ''));
        $text_color = mod_nwi_safe_css_color((string)($t['tag_text_color'] ?? ''));
        if ($name === '') { continue; }
        $esc_name = mod_nwi_escapeString($name);
        $q = $database->query(sprintf(
            "SELECT `tag_id` FROM `%smod_news_img_tags` WHERE `tag`='%s' LIMIT 1",
            TABLE_PREFIX, $esc_name
        ));
        if ($q && $q->numRows() > 0) {
            $tag_id = (int)$q->fetchRow()['tag_id'];
        } else {
            $database->query(sprintf(
                "INSERT INTO `%smod_news_img_tags` (`tag`,`tag_color`,`tag_text_color`) VALUES ('%s','%s','%s')",
                TABLE_PREFIX, $esc_name, $color, $text_color
            ));
            $tag_id = (int)$database->getLastInsertId();
            $result['imported']['tags'] = ($result['imported']['tags'] ?? 0) + 1;
        }
        $tag_map[$name] = $tag_id;
        // Tag-Section-Mapping (idempotent dank UNIQUE KEY)
        $database->query(sprintf(
            "INSERT IGNORE INTO `%smod_news_img_tags_sections` (`section_id`,`tag_id`) VALUES (%d, %d)",
            TABLE_PREFIX, $section_id, $tag_id
        ));
    }

    // --- Posts
    foreach ((array)($data['posts'] ?? []) as $p) {
        $slug     = (string)($p['slug'] ?? '');
        $title    = mod_nwi_escapeString((string)($p['title'] ?? ''));

        // Link auf WBCE-Konvention /posts/<slug> normalisieren — Access-Dateien
        // landen sonst direkt unter pages/ statt unter pages/posts/. Wer in
        // einem Pack bereits explizit /posts/<slug> schreibt, bleibt unbehelligt.
        $raw_link = (string)($p['link'] ?? '');
        if ($raw_link !== '' && strpos($raw_link, '/posts/') !== 0) {
            $raw_link = '/posts/'.ltrim($raw_link, '/');
        }
        $link     = mod_nwi_escapeString($raw_link);

        $group_id = isset($group_map[(string)($p['group'] ?? '')]) ? $group_map[(string)$p['group']] : 0;
        $active   = (int)($p['active'] ?? 1);
        $content_short  = mod_nwi_escapeString((string)($p['content_short'] ?? ''));
        $content_long   = mod_nwi_escapeString((string)($p['content_long'] ?? ''));
        $content_block2 = mod_nwi_escapeString((string)($p['content_block2'] ?? ''));
        $published_when  = (int)($p['published_when'] ?? 0);
        $published_until = (int)($p['published_until'] ?? 0);

        if ($title === '' || $link === '' || $slug === '') {
            $result['errors'][] = 'post: missing title/link/slug';
            continue;
        }

        // Position innerhalb der Section
        $order    = new order(TABLE_PREFIX.'mod_news_img_posts', 'position', 'post_id', 'section_id');
        $position = $order->get_new($section_id);

        $database->query(sprintf(
            "INSERT INTO `%smod_news_img_posts` "
            . "(`section_id`,`group_id`,`active`,`position`,`title`,`link`,`image`,"
            . " `content_short`,`content_long`,`content_block2`,"
            . " `published_when`,`published_until`,`posted_when`,`posted_by`) "
            . "VALUES (%d, %d, %d, %d, '%s', '%s', '', '%s', '%s', '%s', %d, %d, %d, %d)",
            TABLE_PREFIX, $section_id, $group_id, $active, $position,
            $title, $link, $content_short, $content_long, $content_block2,
            $published_when, $published_until, time(), $posted_by
        ));
        if ($database->is_error()) {
            $result['errors'][] = 'post "'.$p['title'].'": '.$database->get_error();
            continue;
        }
        $post_id = (int)$database->getLastInsertId();

        // Page-Access-Datei anlegen — mit dem normalisierten Link, sonst landet
        // die Datei am Original-Pfad statt unter /posts/.
        mod_nwi_post_refresh_access_file(
            ['post_id' => $post_id, 'link' => $raw_link],
            $section_id,
            $page_id
        );

        // Tag-Mappings
        foreach ((array)($p['tags'] ?? []) as $tag_name) {
            if (isset($tag_map[(string)$tag_name])) {
                $database->query(sprintf(
                    "INSERT IGNORE INTO `%smod_news_img_tags_posts` (`post_id`,`tag_id`) VALUES (%d, %d)",
                    TABLE_PREFIX, $post_id, $tag_map[(string)$tag_name]
                ));
            }
        }

        // Bilder kopieren — Quelle: images/<slug>/<file>
        $post_image_dir = WB_PATH.MEDIA_DIRECTORY.'/.news_img/'.$post_id.'/';
        $slug_dir       = $images_dir.'/'.$slug;
        $preview_name   = (string)($p['preview_image'] ?? '');
        $gallery_names  = (array)($p['images'] ?? []);

        if ($preview_name !== '' || !empty($gallery_names)) {
            if (!is_dir($post_image_dir)) {
                mod_nwi_img_makedir($post_image_dir);
            }
        }

        // Vorschaubild — flach in media/.news_img/, gleiche Konvention wie
        // mod_nwi_img_upload($post_id, true). Per find_free_filename Kollisionen
        // (z.B. zwei Packs mit preview.jpg) auflösen, Vorschau auf
        // resize_preview-Größe runterrechnen.
        if ($preview_name !== '' && is_dir($slug_dir)) {
            $src = $slug_dir.'/'.basename($preview_name);
            if (is_file($src) && in_array(strtolower(pathinfo($src, PATHINFO_EXTENSION)), $GLOBALS['allowed_suffixes'], true)) {
                $flat_dir = WB_PATH.MEDIA_DIRECTORY.'/.news_img/';
                $safe_name = mod_nwi_find_free_filename($flat_dir, strtolower(basename($preview_name)));
                $dst = $flat_dir.$safe_name;
                if (@copy($src, $dst)) {
                    list($pw, $ph,) = mod_nwi_get_sizes($section_id);
                    if (empty($pw)) { $pw = 150; }
                    if (empty($ph)) { $ph = 150; }
                    $crop_p = (mod_nwi_settings_get($section_id)['crop_preview'] ?? 'N') === 'Y' ? 1 : 0;
                    if (list($w, $h) = getimagesize($dst)) {
                        if ($w > $pw || $h > $ph) {
                            @mod_nwi_image_resize($dst, $dst, $pw, $ph, $crop_p);
                        }
                    }
                    $database->query(sprintf(
                        "UPDATE `%smod_news_img_posts` SET `image`='%s' WHERE `post_id`=%d",
                        TABLE_PREFIX, mod_nwi_escapeString($safe_name), $post_id
                    ));
                }
            }
        }

        // Galerie-Bilder (inkl. Thumb-Resize über vorhandenen Pfad)
        if (!empty($gallery_names) && is_dir($slug_dir)) {
            list(, , $tw, $th) = mod_nwi_get_sizes($section_id);
            $tw = empty($tw) ? 100 : (int)$tw;
            $th = empty($th) ? 100 : (int)$th;
            $crop = (mod_nwi_settings_get($section_id)['crop_preview'] ?? 'N') === 'Y' ? 1 : 0;
            $img_order = new order(TABLE_PREFIX.'mod_news_img_img', 'position', 'id', 'post_id');

            foreach ($gallery_names as $fname) {
                $src = $slug_dir.'/'.basename($fname);
                if (!is_file($src)) { continue; }
                $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
                if (!in_array($ext, $GLOBALS['allowed_suffixes'], true)) { continue; }
                $safe_name = strtolower(basename($fname));
                $dst = $post_image_dir.$safe_name;
                if (!@copy($src, $dst)) { continue; }
                // Thumb
                if (!is_dir($post_image_dir.'thumb')) {
                    mod_nwi_img_makedir($post_image_dir, true);
                }
                @mod_nwi_image_resize($dst, $post_image_dir.'thumb/'.$safe_name, $tw, $th, $crop);
                // DB-Eintrag + Mapping
                $database->query(sprintf(
                    "INSERT INTO `%smod_news_img_img` (`picname`,`post_id`,`position`) VALUES ('%s', %d, %d)",
                    TABLE_PREFIX, mod_nwi_escapeString($safe_name), $post_id, $img_order->get_new($post_id)
                ));
                $pic_id = (int)$database->getLastInsertId();
                $database->query(sprintf(
                    "INSERT IGNORE INTO `%smod_news_img_posts_img` (`post_id`,`pic_id`,`position`) VALUES (%d, %d, %d)",
                    TABLE_PREFIX, $post_id, $pic_id, 0
                ));
            }
        }

        $result['imported']['posts'] = ($result['imported']['posts'] ?? 0) + 1;
    }

    $result['success'] = empty($result['errors']);
    return $result;
}

// =============================================================================

if (!function_exists('mod_nwi_get_section_array')) {
    /**
     * @brief  Get Array with all the details of a section by using the section_id.
     *
     * @param integer $iSectionID
     * @return array
     */
    function mod_nwi_get_section_array($iSectionID)
    {
        $aSection = [];
        if (isset($iSectionID) && $iSectionID > 0) {
            global $database;
            $sSql = 'SELECT * FROM `{TP}sections` WHERE `section_id`=%d';
            if ($rSections = $database->query(sprintf($sSql, (int)$iSectionID))) {
                $aSection = $rSections->fetchRow(MYSQLI_ASSOC);
            }
        }
        return $aSection;
    }
}
