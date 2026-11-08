<?php
/**
 * AJAX Chat package administration page.
 *
 * Loaded by Tiki's native extension admin-page dispatcher.
 */
$access->check_permission('tiki_p_admin');
$admintitle = tra('Ajax Chat settings');
$description = tra('Configure and maintain the AJAX Chat extension.');

require_once dirname(__DIR__) . '/lib/PackageInstaller.php';
require_once dirname(__DIR__) . '/lib/SettingsStore.php';

$ajaxChatInstaller = new AjaxChatPackageInstaller(dirname(__DIR__));
$ajaxChatInstaller->ensureAdminMenuLink();
$ajaxChatSettings = new AjaxChatSettingsStore();
$ajaxChatSettings->ensureTable();
$ajaxChatSettings->migratePreferences();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_chat_action']) && $_POST['ajax_chat_action'] === 'save_settings') {
    if (!$access->checkCsrf()) {
        Feedback::error(tra('The security ticket is missing or invalid. No settings were saved.'));
    } else {
        try {
            $ajaxChatSettings->save(isset($_POST['ajax_chat_settings']) && is_array($_POST['ajax_chat_settings']) ? $_POST['ajax_chat_settings'] : array());
            Feedback::success(tra('AJAX Chat settings have been saved.'));
        } catch (Throwable $e) {
            error_log('AJAX Chat settings: ' . $e->getMessage());
            Feedback::error($e->getMessage());
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_chat_action']) && $_POST['ajax_chat_action'] !== 'save_settings') {
    if (!$access->checkCsrf()) {
        Feedback::error(tra('The security ticket is missing or invalid. No action was performed.'));
    } else {
        try {
            $action = (string) $_POST['ajax_chat_action'];
            if ($action === 'install') {
                $ajaxChatInstaller->install();
                Feedback::success(tra('AJAX Chat is installed. The /chat entry point and database tables are ready.'));
            } elseif ($action === 'uninstall') {
                if (!isset($_POST['confirmation']) || trim((string) $_POST['confirmation']) !== 'DELETE AJAX CHAT DATA') {
                    throw new RuntimeException(tra('The confirmation phrase does not match. No data was deleted.'));
                }
                $ajaxChatInstaller->uninstall();
                Feedback::success(tra('AJAX Chat database tables and package-managed files were removed.'));
            } else {
                throw new RuntimeException(tra('Unknown AJAX Chat administration action.'));
            }
        } catch (Throwable $e) {
            error_log('AJAX Chat package administration: ' . $e->getMessage());
            Feedback::error($e->getMessage());
        }
    }
}

try {
    $smarty->assign('ajax_chat_schema_status', $ajaxChatInstaller->status());
} catch (Throwable $e) {
    error_log('AJAX Chat schema status: ' . $e->getMessage());
    $smarty->assign('ajax_chat_schema_status', 'unknown');
}
$smarty->assign('ajax_chat_admin_url', 'tiki-admin.php?page=tp_edizontn_ajaxchat');
$smarty->assign('ajax_chat_chat_url', TikiLib::tikiUrl('chat/'));
$smarty->assign('ajax_chat_chat_ready', $ajaxChatInstaller->isReady());
$smarty->assign('ajax_chat_uninstall_confirmation', 'DELETE AJAX CHAT DATA');

$ajaxChatSettingValues = $ajaxChatSettings->getAll();
$ajaxChatSettingRows = array();
foreach ($ajaxChatSettings->getDefinitions() as $fullName => $definition) {
    $key = substr($fullName, strlen(AjaxChatSettingsStore::PREFIX));
    $definition['key'] = $key;
    $definition['value'] = isset($ajaxChatSettingValues[$key]) ? $ajaxChatSettingValues[$key] : $definition['default'];
    $ajaxChatSettingRows[] = $definition;
}
$smarty->assign('ajax_chat_settings', $ajaxChatSettingRows);
