<?php
/**
 * AJAX Chat package administration page.
 *
 * Loaded by Tiki's native extension admin-page dispatcher.
 */
$access->check_permission('tiki_p_admin');
$admintitle = tra('Ajax Chat settings');
$description = tra('Configure and maintain the AJAX Chat extension.');

// Tiki's breadcrumb help link prefixes helpUrl with prefs.helpurl.
// Override the base for this request only so the page help icon opens the project wiki.
$prefs['helpurl'] = 'https://github.com/Frug/AJAX-Chat/';

require_once dirname(__DIR__) . '/lib/PackageInstaller.php';
require_once dirname(__DIR__) . '/lib/SettingsStore.php';

$ajaxChatInstaller = new AjaxChatPackageInstaller(dirname(__DIR__));
$ajaxChatInstaller->ensureAdminNavigation();

// The bootstrap helper may have been loaded before ensureAdminNavigation refreshed it.
// Update the current request's admin navigation entry as well.
if (isset($admin_icons['tp_edizontn_ajaxchat'])) {
    $admin_icons['tp_edizontn_ajaxchat']['help'] = 'wiki';
}

$runtimeConfig = require_once dirname(__DIR__) . '/src/lib/Chat-config.php.example';
if (!is_array($runtimeConfig)) {
    throw new RuntimeException('AJAX Chat default configuration could not be loaded.');
}
$userLib = TikiLib::lib('user');
$groupResult = $userLib->get_groups(0, -1, 'groupName_asc', '', '', 'n');
$ajaxChatGroupNames = array();
if (isset($groupResult['data']) && is_array($groupResult['data'])) {
    foreach ($groupResult['data'] as $group) {
        if (isset($group['groupName'])) {
            $ajaxChatGroupNames[] = (string) $group['groupName'];
        }
    }
}
$ajaxChatSettings = new AjaxChatSettingsStore($runtimeConfig, $ajaxChatGroupNames);
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
$ajaxChatSettingTabs = array();
foreach ($ajaxChatSettings->getDefinitions() as $fullName => $definition) {
    $key = substr($fullName, strlen(AjaxChatSettingsStore::PREFIX));
    $definition['key'] = $key;
    $definition['value'] = isset($ajaxChatSettingValues[$key]) ? $ajaxChatSettingValues[$key] : $definition['default'];
    if ($key === 'moderator_groups' && strpos($definition['value'], ',') !== false) {
        $legacyGroups = array_map('trim', explode(',', $definition['value']));
        $definition['value'] = '';
        foreach ($legacyGroups as $legacyGroup) {
            if (in_array($legacyGroup, $ajaxChatGroupNames, true)) {
                $definition['value'] = $legacyGroup;
                break;
            }
        }
    }
    if ($definition['type'] === 'multi') {
        $definition['selected_values'] = $definition['value'] === '' ? array() : explode(',', $definition['value']);
    } else {
        $definition['selected_values'] = array();
    }
    if (isset($definition['options']) && is_array($definition['options'])) {
        foreach ($definition['options'] as &$option) {
            $option['selected'] = ($definition['type'] === 'multi')
                ? in_array((string) $option['value'], $definition['selected_values'], true)
                : ((string) $option['value'] === (string) $definition['value']);
        }
        unset($option);
    }
    $tab = $definition['tab'];
    $section = $definition['section'];
    if (!isset($ajaxChatSettingTabs[$tab])) {
        $ajaxChatSettingTabs[$tab] = array();
    }
    if (!isset($ajaxChatSettingTabs[$tab][$section])) {
        $ajaxChatSettingTabs[$tab][$section] = array();
    }
    $ajaxChatSettingTabs[$tab][$section][] = $definition;
}
$smarty->assign('ajax_chat_tabs', $ajaxChatSettingTabs);
$smarty->assign('ajax_chat_groups', $ajaxChatGroupNames);
