<?php
/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @author Philip Nicolcev
 * @copyright (c) Sebastian Tschan
 * @license Modified MIT License
 * @link https://blueimp.net/ajax/
 */

// Suppress errors:
ini_set('display_errors', 0);

// Load the installer-generated configuration and use its stored chat filesystem path.
$configFile = __DIR__.'/lib/Chat-config.php';
if (!is_file($configFile) || !is_readable($configFile)) {
	http_response_code(500);
	exit('Chat is not configured. Run install.php first.');
}
$chatConfig = require($configFile);
if (!is_array($chatConfig) || empty($chatConfig['paths']['chatRootPath']) || empty($chatConfig['paths']['chatUrlPath'])) {
	http_response_code(500);
	exit('Chat paths are not configured. Run install.php first.');
}
$GLOBALS['mzChatConfig'] = $chatConfig;
define('AJAX_CHAT_PATH', rtrim($chatConfig['paths']['chatRootPath'], '/\\').DIRECTORY_SEPARATOR);
$chatScheme = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') ? 'https://' : 'http://';
$chatHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
define('AJAX_CHAT_URL', $chatScheme.$chatHost.$chatConfig['paths']['chatUrlPath']);

// Include custom libraries and initialization code:
require(AJAX_CHAT_PATH.'lib/custom.php');

// Include Class libraries:
require(AJAX_CHAT_PATH.'lib/classes.php');

// Initialize the chat:
$ajaxChat = new CustomAJAXChat();
