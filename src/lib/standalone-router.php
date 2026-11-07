<?php
/**
 * Standalone URL dispatcher for AJAX Chat.
 *
 * Included from Tiki 30.1's _custom/shared/custom.php after Tiki setup.
 * It only handles the configured exact path and leaves all other Tiki
 * requests untouched.
 */

if (PHP_SAPI === 'cli' || !isset($prefs) || !is_array($prefs)) {
    return;
}

$ajaxChatEnabledPref = 'tp_edizontn__ajax_chat__chat_enabled';
$ajaxChatUrlPref = 'tp_edizontn__ajax_chat__chat_url';
if (!isset($prefs[$ajaxChatEnabledPref]) || $prefs[$ajaxChatEnabledPref] !== 'y') {
    return;
}

$chatPath = isset($prefs[$ajaxChatUrlPref]) ? trim((string) $prefs[$ajaxChatUrlPref]) : '/chat';
if (
    $chatPath === ''
    || $chatPath[0] !== '/'
    || strpos($chatPath, '//') !== false
    || strpos($chatPath, '..') !== false
    || !preg_match('~^/[A-Za-z0-9._~/-]+$~D', $chatPath)
) {
    return;
}
$chatPath = rtrim($chatPath, '/');
if ($chatPath === '') {
    return;
}

$requestPath = isset($_SERVER['REQUEST_URI']) ? parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
if (!is_string($requestPath) || $requestPath === '') {
    return;
}

// Tiki's front controller is the script being executed after URL rewriting.
// Its directory is the installation's URL base path, including subdirectory installs.
$scriptName = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']) : '';
$basePath = rtrim(dirname($scriptName), '/');
if ($basePath === '.' || $basePath === '/') {
    $basePath = '';
}
$expectedPath = $basePath . $chatPath;

if ($requestPath !== $expectedPath && $requestPath !== $expectedPath . '/') {
    return;
}
if ($requestPath === $expectedPath . '/') {
    $query = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '';
    header('Location: ' . $expectedPath . $query, true, 301);
    exit;
}

$chatEntryPoint = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'index.php';
if (!is_file($chatEntryPoint) || !is_readable($chatEntryPoint)) {
    http_response_code(503);
    exit('AJAX Chat package is installed but its entry point is unavailable.');
}

require $chatEntryPoint;
exit;
