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

// Get id (Einzel-Toggle per GET-Link aus der Beitragsliste)
$post_id = filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$post_id) {
    header("Location: ".ADMIN_URL."/pages/index.php");
    exit(0);
}

// Include WB admin wrapper script
$update_when_modified = true; // Tells script to update when this page was last updated
$admin_header = false;
require WB_PATH.'/modules/admin.php';

// CSRF: Der Statuswechsel erfolgt über einen GET-Link -> FTAN-Token prüfen.
if (!defined('CAT_PATH')) {
    $admin->print_header();
    if (!$admin->checkFTAN('GET')) {
        $admin->print_error(
            $MESSAGE['GENERIC_SECURITY_ACCESS']
         .' (FTAN) '.__FILE__.':'.__LINE__,
             ADMIN_URL.'/pages/index.php'
        );
    }
}

// value: 1 = aktivieren (Default), 0 = deaktivieren
$value = 1;
$filter_value = filter_input(INPUT_GET, 'value', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1]]);
if ($filter_value === 0) {
    $value = 0;
}

// mod_nwi_post_activate() liest die ID-Liste aus $_POST['manage_posts'].
$_POST['manage_posts'] = [$post_id];
$result = mod_nwi_post_activate($value);

// Check if there is a db error, otherwise say successful
if ($result==false) {
    $admin->print_error($database->get_error(), ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
} else {
    $admin->print_success($TEXT['SUCCESS'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
}

// Print admin footer
$admin->print_footer();
