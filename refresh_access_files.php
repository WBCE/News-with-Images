<?php
/**
 *
 * @category        modules
 * @package         news_img
 * @author          WBCE Community
 * @copyright       2019-, WBCE Community
 * @link            https://www.wbce.org/
 * @license         http://www.gnu.org/licenses/gpl.html
 * @platform        WBCE
 *
 * Erzeugt die Page-Access-Dateien für alle Beiträge der aktuellen Section neu.
 * Wartungs-Aktion — destruktiv nur für die /pages/<link>.php-Dateien, nicht für
 * die DB. Aufgerufen über den "Access-Dateien erneuern"-Button in modify.phtml.
 */

require_once __DIR__.'/functions.inc.php';

$update_when_modified = true;
require WB_PATH.'/modules/admin.php';

if (!$admin->checkFTAN()) {
    $admin->print_header();
    $admin->print_error(
        $MESSAGE['GENERIC_SECURITY_ACCESS'].' (FTAN) '.__FILE__.':'.__LINE__,
        ADMIN_URL.'/pages/index.php'
    );
    $admin->print_footer();
    exit();
}

if (empty($section_id) || empty($page_id)) {
    $admin->print_header();
    $admin->print_error(
        $MESSAGE['GENERIC_SECURITY_ACCESS'].' (params) '.__FILE__.':'.__LINE__,
        ADMIN_URL.'/pages/index.php'
    );
    $admin->print_footer();
    exit();
}

// /posts/-Subdir vorsorglich sicherstellen — viele Bestands-Links liegen dort
make_dir(WB_PATH.PAGES_DIRECTORY.'/posts/');

$q = $database->query(sprintf(
    "SELECT `post_id`, `link` FROM `%smod_news_img_posts` WHERE `section_id`=%d",
    TABLE_PREFIX, (int)$section_id
));

$ok = 0;
$fail = 0;
$fail_links = [];

if ($q && $q->numRows() > 0) {
    while ($post = $q->fetchRow()) {
        if (mod_nwi_post_refresh_access_file($post, (int)$section_id, (int)$page_id)) {
            $ok++;
        } else {
            $fail++;
            $fail_links[] = $post['link'];
        }
    }
}

$tmpl_success = $MOD_NEWS_IMG['REFRESH_ACCESS_FILES_SUCCESS'] ?? '%d Access-Datei(en) erneuert';
$tmpl_failed  = $MOD_NEWS_IMG['REFRESH_ACCESS_FILES_FAILED']  ?? '%d Access-Datei(en) konnten nicht erneuert werden';

if ($fail === 0) {
    $admin->print_success(sprintf($tmpl_success, $ok), ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
} else {
    $msg = sprintf($tmpl_success, $ok).'; '.sprintf($tmpl_failed, $fail);
    if (!empty($fail_links)) {
        $msg .= ': '.htmlspecialchars(implode(', ', $fail_links), ENT_QUOTES | ENT_HTML5);
    }
    $admin->print_error($msg, ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
}

$admin->print_footer();
