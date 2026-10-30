<?php
/*
 * Mikrozone chat authentication endpoint.
 * This endpoint runs inside Tiki's authenticated PHP session and issues a
 * short-lived signed token. It never accepts a username or password.
 */

ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

function mz_chat_auth_error($status, $code) {
	http_response_code($status);
	echo json_encode(array('ok' => false, 'error' => $code));
	exit;
}

$configFile = __DIR__.'/lib/config.php';
if (!is_file($configFile) || !is_readable($configFile)) {
	mz_chat_auth_error(500, 'chat_config_missing');
}
require($configFile);
require_once(__DIR__.'/lib/class/MZChatAuth.php');

$secret = isset($config['mzChatSharedSecret']) ? $config['mzChatSharedSecret'] : '';
if (!is_string($secret) || strlen($secret) < 32 || strpos($secret, 'REPLACE_WITH_') === 0) {
	mz_chat_auth_error(500, 'chat_auth_not_configured');
}

$tikiRoot = isset($config['tikiRootPath']) ? $config['tikiRootPath'] : dirname(__DIR__).DIRECTORY_SEPARATOR;
$tikiSetup = rtrim($tikiRoot, '/\\').DIRECTORY_SEPARATOR.'tiki-setup.php';
if (!is_file($tikiSetup)) {
	mz_chat_auth_error(500, 'tiki_setup_not_found');
}

// Tiki is installed one directory above _CHAT/. Set the working directory and
// virtual script path to the Tiki root so its relative includes and session
// cookie path are calculated as they are for a normal Tiki request.
$tikiRootReal = realpath($tikiRoot);
if($tikiRootReal === false || !is_dir($tikiRootReal) || !chdir($tikiRootReal)) {
	mz_chat_auth_error(500, 'tiki_root_unavailable');
}
$scriptName = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '/_CHAT/tiki-auth.php';
$tikiWebPath = rtrim(dirname(dirname($scriptName)), '/');
if($tikiWebPath === '.') {
	$tikiWebPath = '';
}
$tikiIndexPath = $tikiWebPath.'/index.php';
$_SERVER['SCRIPT_NAME'] = $tikiIndexPath;
$_SERVER['PHP_SELF'] = $tikiIndexPath;
$_SERVER['REQUEST_URI'] = $tikiIndexPath;
$_SERVER['SCRIPT_FILENAME'] = $tikiRootReal.DIRECTORY_SEPARATOR.'index.php';

require_once($tikiSetup);

global $user, $tiki_p_admin;
if (empty($user) || $user === 'Anonymous') {
	mz_chat_auth_error(401, 'tiki_login_required');
}

$userLib = TikiLib::lib('user');
$tikiLib = TikiLib::lib('tiki');
$userInfo = $userLib->get_user_info($user);
$userId = (int)$tikiLib->get_user_id($user);
if (!is_array($userInfo) || $userId < 1 || !empty($userInfo['userBanned']) || !empty($userInfo['waiting'])) {
	mz_chat_auth_error(401, 'tiki_user_unavailable');
}

$groups = $tikiLib->get_user_groups($user);
$isAdmin = in_array('Admins', $groups, true) || (isset($tiki_p_admin) && $tiki_p_admin === 'y');
$moderatorGroups = isset($config['tikiChatModeratorGroups']) && is_array($config['tikiChatModeratorGroups'])
	? $config['tikiChatModeratorGroups']
	: array();
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
	'iat' => $now,
	'exp' => $now + 120,
	'nonce' => bin2hex(random_bytes(16))
);

try {
	$token = MZChatAuth::createToken($claims, $secret);
} catch (Throwable $e) {
	mz_chat_auth_error(500, 'token_issue_failed');
}

echo json_encode(array(
	'ok' => true,
	'token' => $token,
	'expires' => $claims['exp']
));
