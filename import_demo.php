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
 * Import-Endpoint für vorgefertigte Demo-Daten-Packs (siehe modules/news_img/demodata/).
 * Wird vom "Demo-Daten importieren"-Panel in modify.phtml aufgerufen und ist nur
 * erlaubt, wenn die Section noch ohne Beiträge UND ohne Gruppen ist (die Eligibility
 * wird sowohl in der UI als auch hier serverseitig geprüft).
 */

require_once __DIR__.'/functions.inc.php';

// Include WB admin wrapper script
$update_when_modified = true;
require WB_PATH.'/modules/admin.php';

// FTAN gegen CSRF (admin.php holt das Token aus dem POST)
if (!$admin->checkFTAN()) {
    $admin->print_header();
    $admin->print_error(
        $MESSAGE['GENERIC_SECURITY_ACCESS'].' (FTAN) '.__FILE__.':'.__LINE__,
        ADMIN_URL.'/pages/index.php'
    );
    $admin->print_footer();
    exit();
}

// Section / Page IDs sind via admin.php gesetzt; defensive Validierung
if (empty($section_id) || empty($page_id)) {
    $admin->print_header();
    $admin->print_error(
        $MESSAGE['GENERIC_SECURITY_ACCESS'].' (params) '.__FILE__.':'.__LINE__,
        ADMIN_URL.'/pages/index.php'
    );
    $admin->print_footer();
    exit();
}

$pack_name = filter_input(INPUT_POST, 'pack', FILTER_DEFAULT, ['flags' => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH]);
$pack_name = (string)$pack_name;
if ($pack_name === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $pack_name)) {
    $admin->print_header();
    $admin->print_error(
        $MESSAGE['GENERIC_SECURITY_ACCESS'].' (pack) '.__FILE__.':'.__LINE__,
        ADMIN_URL.'/pages/modify.php?page_id='.$page_id
    );
    $admin->print_footer();
    exit();
}

$posted_by = method_exists($admin, 'get_user_id') ? (int)$admin->get_user_id() : 1;
if ($posted_by <= 0) {
    $posted_by = 1;
}

$result = mod_nwi_demodata_import($pack_name, (int)$section_id, (int)$page_id, $posted_by);

if (!$result['success']) {
    $msg = $MOD_NEWS_IMG['DEMODATA_IMPORT_FAILED'] ?? 'Demo-Daten-Import fehlgeschlagen';
    if (!empty($result['errors'])) {
        $msg .= ': '.htmlspecialchars(implode(' / ', $result['errors']), ENT_QUOTES | ENT_HTML5);
    }
    $admin->print_error($msg, ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
} else {
    $imp = $result['imported'];
    $parts = [];
    if (!empty($imp['groups'])) { $parts[] = $imp['groups'].' '.$MOD_NEWS_IMG['GROUPS']; }
    if (!empty($imp['tags']))   { $parts[] = $imp['tags'].' '.$MOD_NEWS_IMG['TAGS']; }
    if (!empty($imp['posts']))  { $parts[] = $imp['posts'].' '.$MOD_NEWS_IMG['POSTS']; }
    $msg = ($MOD_NEWS_IMG['DEMODATA_IMPORT_SUCCESS'] ?? 'Demo-Daten importiert').(empty($parts) ? '' : ': '.implode(', ', $parts));
    $admin->print_success($msg, ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
}

$admin->print_footer();
