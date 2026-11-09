<?php
function tra($value)
{
    return $value;
}

require_once __DIR__ . '/../lib/SettingsDefinitions.php';
$runtimeConfig = require __DIR__ . '/../src/lib/Chat-config.php.example';
$groups = array('Admins', 'Registered', 'Editors');
$definitions = ajax_chat_setting_definitions($runtimeConfig, $groups);
$prefix = 'tp_edizontn__ajaxchat__';

$required = array(
    'chat_enabled', 'lang_default', 'style_default',
    'socket_server_enabled', 'socket_server_host', 'socket_server_ip', 'socket_server_port', 'socket_server_chat_id',
    'logs_purge_logs', 'logs_user_access', 'session_cookie_secure', 'logs_request_messages_time_diff',
    'logs_request_messages_limit', 'logs_first_year', 'logs_purge_time_diff', 'session_cookie_lifetime',
    'opening_hour', 'closing_hour', 'opening_week_days', 'time_zone_offset', 'request_messages_time_diff',
    'request_messages_limit', 'max_users_logged_in', 'default_ban_time', 'inactive_timeout',
    'inactive_check_interval', 'allow_nick_change', 'user_name_max_length', 'allow_user_message_delete',
    'admin_stealth_in_private_rooms', 'moderator_groups', 'chat_closed', 'ip_check',
    'message_text_max_length', 'max_message_rate', 'allow_private_rooms', 'allow_private_channels',
    'allow_private_messages', 'show_channel_messages', 'request_messages_prior_channel_enter',
    'default_channel_name', 'default_channel_id', 'max_private_rooms_per_user',
    'private_room_max_name_length', 'private_room_prefix', 'private_room_suffix',
    'private_channel_prefix', 'private_channel_suffix', 'changed_nick_prefix', 'changed_nick_suffix',
    'chatbot_name', 'limit_channel_list', 'request_messages_prior_channel_enter_list',
    'logs_user_access_channel_list'
);
foreach ($required as $key) {
    if (!isset($definitions[$prefix . $key])) {
        fwrite(STDERR, "Missing setting definition: {$key}\n");
        exit(1);
    }
}

$check = function ($key, $property, $expected) use ($definitions, $prefix) {
    $actual = $definitions[$prefix . $key][$property] ?? null;
    if ($actual !== $expected) {
        fwrite(STDERR, "Unexpected {$property} for {$key}: " . var_export($actual, true) . "\n");
        exit(1);
    }
};

$check('logs_purge_time_diff', 'min', 0);
$check('logs_purge_time_diff', 'max', 1460);
$check('session_cookie_lifetime', 'max', 1460);
$check('private_room_max_name_length', 'max', 40);
$check('message_text_max_length', 'max', 10000);
$check('max_users_logged_in', 'max', 10000);
$check('user_name_max_length', 'max', 64);
$check('private_room_prefix', 'maxlength', 16);
$check('chatbot_name', 'maxlength', 255);

$langs = array_column($definitions[$prefix . 'lang_default']['options'], 'value');
$styles = array_column($definitions[$prefix . 'style_default']['options'], 'value');
$days = array_column($definitions[$prefix . 'opening_week_days']['options'], 'value');
$moderatorGroups = array_column($definitions[$prefix . 'moderator_groups']['options'], 'value');
if (!in_array('sk', $langs, true) || !in_array('prosilver', $styles, true)
    || $days !== array('0', '1', '2', '3', '4', '5', '6')
    || !in_array('Editors', $moderatorGroups, true)) {
    fwrite(STDERR, "Dynamic language/style/group options or weekday mapping are invalid.\n");
    exit(1);
}

echo "AJAX Chat admin settings catalogue checks passed.\n";
