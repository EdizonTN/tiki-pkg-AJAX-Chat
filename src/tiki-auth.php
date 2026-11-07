<?php
/*
 * TikiWiki chat authentication endpoint.
 * This endpoint runs inside Tiki's authenticated PHP session and issues a
 * short-lived signed token. It never accepts a username or password.
 */

ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

function ajax_chat_auth_error($status, $code) {
	http_response_code($status);
	echo json_encode(array('ok' => false, 'error' => $code));
	exit;
}

$configFile = dirname(__DIR__, 3) . '/temp/ajax-chat/Chat-config.php';
if (!is_file($configFile) || !is_readable($configFile)) {
	ajax_chat_auth_error(500, 'chat_config_missing');
}
$config = require($configFile);
if (!is_array($config)) {
	ajax_chat_auth_error(500, 'chat_config_invalid');
}
require_once(__DIR__.'/lib/class/AjaxChatAuth.php');

$secret = isset($config['ajaxChatSharedSecret']) ? $config['ajaxChatSharedSecret'] : '';
if (!is_string($secret) || strlen($secret) < 32 || strpos($secret, 'REPLACE_WITH_') === 0) {
	ajax_chat_auth_error(500, 'chat_auth_not_configured');
}

$paths = isset($config['paths']) && is_array($config['paths']) ? $config['paths'] : array();
require_once(__DIR__.'/lib/tiki-bootstrap.php');
try {
    AjaxChatBootstrapTiki($config);
} catch (Throwable $e) {
    error_log('Ajax Chat auth Tiki bootstrap: '.$e->getMessage());
    ajax_chat_auth_error(500, 'tiki_database_unavailable');
}

global $user, $tiki_p_admin;
if (empty($user) || $user === 'Anonymous') {
	ajax_chat_auth_error(401, 'tiki_login_required');
}

$userLib = TikiLib::lib('user');
$tikiLib = TikiLib::lib('tiki');
$userInfo = $userLib->get_user_info($user);
$userId = (int)$tikiLib->get_user_id($user);
if (!is_array($userInfo) || $userId < 1 || !empty($userInfo['userBanned']) || !empty($userInfo['waiting'])) {
	ajax_chat_auth_error(401, 'tiki_user_unavailable');
}

$groups = $tikiLib->get_user_groups($user);
$isAdmin = in_array('Admins', $groups, true) || (isset($tiki_p_admin) && $tiki_p_admin === 'y');
global $prefs;
$moderatorGroupsRaw = isset($prefs['tp_edizontn__ajaxchat__moderator_groups'])
    ? (string) $prefs['tp_edizontn__ajaxchat__moderator_groups'] : '';
$moderatorGroups = array_values(array_filter(array_map('trim', explode(',', $moderatorGroupsRaw)), 'strlen'));
$chatEnabled = !isset($prefs['tp_edizontn__ajaxchat__chat_enabled'])
    || $prefs['tp_edizontn__ajaxchat__chat_enabled'] === 'y';
if (!$chatEnabled) {
    ajax_chat_auth_error(403, 'chat_disabled');
}
$adminStealth = isset($prefs['tp_edizontn__ajaxchat__admin_stealth_in_private_rooms'])
    && $prefs['tp_edizontn__ajaxchat__admin_stealth_in_private_rooms'] === 'y';
$isModerator = count(array_intersect($moderatorGroups, $groups)) > 0;

if ($isAdmin) {
	$chatRole = 3;
} elseif ($isModerator) {
	$chatRole = 2;
} else {
	$chatRole = 1;
}

// The Tiki login is unique and is therefore the safest chat display name.
$displayName = (string)$user;
$now = time();
$claims = array(
	'uid' => $userId,
	'login' => (string)$user,
	'name' => $displayName,
	'role' => $chatRole,
    'adminStealth' => $isAdmin && $adminStealth,
	'iat' => $now,
	'exp' => $now + 120,
	'nonce' => bin2hex(random_bytes(16))
);

try {
	$token = AjaxChatAuth::createToken($claims, $secret);
} catch (Throwable $e) {
	ajax_chat_auth_error(500, 'token_issue_failed');
}

echo json_encode(array(
	'ok' => true,
	'token' => $token,
	'expires' => $claims['exp']
));
