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
 * Verschiebt die Page-Access-Dateien aller Beiträge der aktuellen Section in
 * das aktuell konfigurierte Zielverzeichnis (Elternseite oder Override, s.
 * mod_nwi_posts_dir()) und schreibt die `link`-Spalte entsprechend um.
 *
 * Wartungs-Aktion — verschiebt vorhandene /pages/<link>.php-Dateien und passt
 * die DB-Links an. Aufgerufen über den "Beiträge verschieben"-Button in
 * modify.phtml. Ergänzt refresh_access_files.php: Refresh schreibt am
 * bestehenden Link neu, diese Aktion ändert das Verzeichnis.
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

// Aktuell wirksames Zielverzeichnis der Section ermitteln und sicherstellen.
$posts_dir = mod_nwi_posts_dir((int)$section_id, (int)$page_id);
make_dir(WB_PATH.PAGES_DIRECTORY.'/'.$posts_dir.'/');

$q = $database->query(sprintf(
    "SELECT `post_id`, `link` FROM `%smod_news_img_posts` WHERE `section_id`=%d",
    TABLE_PREFIX, (int)$section_id
));

$moved   = 0; // Datei tatsächlich verschoben
$skipped = 0; // lag bereits im Zielverzeichnis
$fail    = 0;
$fail_links = [];

if ($q && $q->numRows() > 0) {
    while ($post = $q->fetchRow()) {
        $old_link = (string)$post['link'];
        if ($old_link === '') {
            continue;
        }
        // Neues Ziel: gleicher Dateiname, nur anderes Verzeichnis.
        $new_link = '/'.$posts_dir.'/'.basename($old_link);

        if ($new_link === $old_link) {
            $skipped++;
            continue;
        }

        $old_file = WB_PATH.PAGES_DIRECTORY.$old_link.PAGE_EXTENSION;
        $had_file = file_exists($old_file);
        $filetime = $had_file ? (string)filemtime($old_file) : null;

        // 1) DB-Link umschreiben (kanonischer Pfad, auch für inaktive Beiträge
        //    ohne Datei — beim Aktivieren wird dann im neuen Verzeichnis erzeugt).
        $database->query(sprintf(
            "UPDATE `%smod_news_img_posts` SET `link`='%s' WHERE `post_id`=%d",
            TABLE_PREFIX, mod_nwi_escapeString($new_link), (int)$post['post_id']
        ));
        if ($database->is_error()) {
            $fail++;
            $fail_links[] = $old_link;
            continue;
        }

        // 2) Datei nur verschieben, wenn am alten Ort vorhanden. So bleibt die
        //    Sichtbarkeit erhalten (inaktive Beiträge haben keine Datei).
        if ($had_file) {
            $ok = mod_nwi_post_refresh_access_file(
                ['post_id' => (int)$post['post_id'], 'link' => $new_link],
                (int)$section_id,
                (int)$page_id,
                $filetime
            );
            if ($ok) {
                if (is_writable($old_file)) {
                    unlink($old_file);
                }
                $moved++;
            } else {
                $fail++;
                $fail_links[] = $old_link;
            }
        } else {
            // kein Handlungsbedarf an der Datei; Link ist bereits angepasst
            $skipped++;
        }
    }
}

$tmpl_success = $MOD_NEWS_IMG['MIGRATE_POSTS_DIR_SUCCESS'] ?? '%d Beitrag/Beiträge verschoben';
$tmpl_failed  = $MOD_NEWS_IMG['MIGRATE_POSTS_DIR_FAILED']  ?? '%d Beitrag/Beiträge konnten nicht verschoben werden';

$msg = sprintf($tmpl_success, $moved);
if ($skipped > 0) {
    $tmpl_skipped = $MOD_NEWS_IMG['MIGRATE_POSTS_DIR_SKIPPED'] ?? '%d bereits im Zielverzeichnis';
    $msg .= '; '.sprintf($tmpl_skipped, $skipped);
}

if ($fail === 0) {
    $admin->print_success($msg, ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
} else {
    $msg .= '; '.sprintf($tmpl_failed, $fail);
    if (!empty($fail_links)) {
        $msg .= ': '.htmlspecialchars(implode(', ', $fail_links), ENT_QUOTES | ENT_HTML5);
    }
    $admin->print_error($msg, ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
}

$admin->print_footer();
