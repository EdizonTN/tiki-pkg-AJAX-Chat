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
$ajaxChatInstaller->ensureAdminNavigation();
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



$ajaxChatSettingValues = $ajaxChatSettings->getAll();
$ajaxChatSettingTabs = array();
foreach ($ajaxChatSettings->getDefinitions() as $fullName => $definition) {
    $key = substr($fullName, strlen(AjaxChatSettingsStore::PREFIX));
    $definition['key'] = $key;
    $definition['value'] = isset($ajaxChatSettingValues[$key]) ? $ajaxChatSettingValues[$key] : $definition['default'];
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
