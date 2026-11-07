<?php
/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @copyright (c) Sebastian Tschan
 * @license Modified MIT License
 * @link https://blueimp.net/ajax/
 */

// Fail-closed placeholder. Mikrozone authentication will be provided by the
// TikiWiki bridge; never ship the upstream admin/admin demo credentials.
$users = array();

// Guest record required by the upstream class, but guest login is disabled
// in config.php.example and this account cannot authenticate.
$users[0] = array();
$users[0]['userRole'] = AJAX_CHAT_GUEST;
$users[0]['userName'] = null;
$users[0]['password'] = null;
$users[0]['channels'] = array(0);
