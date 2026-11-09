<?php
/**
 * AJAX Chat settings catalogue.
 *
 * Each setting carries its presentation metadata so the package admin page
 * can render native Tiki-style label/help and value columns.
 */
function ajax_chat_setting_definitions(array $runtimeConfig = array(), array $groups = array())
{
    $tabs = array(
        'General Preferences' => array(
            'Enable Ajax Chat' => array('chat_enabled', 'checkbox', 'Allow authenticated Tiki users to access AJAX Chat.', 'y'),
            'Default language' => array('lang_default', 'select', 'Default language used by AJAX Chat.', 'sk'),
            'Default chat style' => array('style_default', 'select', 'Default visual style used by AJAX Chat.', 'prosilver'),
        ),
        'General Settings' => array(),
        'Users/Group Settings' => array(),
        'Chat Settings' => array(),
    );

    $definitions = array();
    $add = function ($tab, $section, $label, $key, $type, $description, $default, $extra = array()) use (&$definitions) {
        $definition = array(
            'name' => tra($label),
            'description' => tra($description),
            'type' => $type,
            'default' => (string) $default,
            'tab' => $tab,
            'section' => $section,
        );
        foreach ($extra as $name => $value) {
            $definition[$name] = $value;
        }
        $definitions['tp_edizontn__ajaxchat__' . $key] = $definition;
    };

    foreach ($tabs['General Preferences'] as $label => $item) {
        $key = $item[0];
        $extra = array();
        if ($key === 'lang_default') {
            $extra['options'] = array();
            foreach ((isset($runtimeConfig['langAvailable']) ? $runtimeConfig['langAvailable'] : array()) as $code) {
                $extra['options'][] = array('value' => $code, 'label' => isset($runtimeConfig['langNames'][$code]) ? html_entity_decode($runtimeConfig['langNames'][$code], ENT_QUOTES, 'UTF-8') : $code);
            }
        } elseif ($key === 'style_default') {
            $extra['options'] = array();
            foreach ((isset($runtimeConfig['styleAvailable']) ? $runtimeConfig['styleAvailable'] : array()) as $style) {
                $extra['options'][] = array('value' => $style, 'label' => $style);
            }
        }
        $add('General Preferences', 'General Preferences', $label, $key, $item[1], $item[2], $item[3], $extra);
    }

    $add('General Preferences', 'Socket Server', 'Enable AJAX Chat socket server', 'socket_server_enabled', 'checkbox', 'Requires a separately running socket server. Keep disabled on shared hosting unless you operate one.', 'n');
    $add('General Preferences', 'Socket Server', 'Socket server hostname', 'socket_server_host', 'url', 'Optional full URL of the socket server, for example https://chat.example.com. Leave blank to use the current host.', '');
    $add('General Preferences', 'Socket Server', 'Socket server IP address', 'socket_server_ip', 'ip', 'IP address used by the server when broadcasting socket messages.', '127.0.0.1');
    $add('General Preferences', 'Socket Server', 'Socket server port', 'socket_server_port', 'number', 'TCP port used by the socket server.', '1935', array('min' => 1, 'max' => 65535));
    $add('General Preferences', 'Socket Server', 'Socket server chat ID', 'socket_server_chat_id', 'number', 'Identifier for this chat installation when several installations share one socket server.', '0', array('min' => 0, 'max' => 2147483647));

    $add('General Settings', 'Logs', 'Purge old chat logs', 'logs_purge_logs', 'checkbox', 'Automatically delete messages older than the configured log retention period.', 'n');
    $add('General Settings', 'Logs', 'Allow users to view chat logs', 'logs_user_access', 'checkbox', 'Allow registered users and moderators to view chat logs. Administrators always have access.', 'n');
    $add('General Settings', 'Logs', 'Require HTTPS for chat cookies', 'session_cookie_secure', 'checkbox', 'Enable for HTTPS sites. Disable only for local HTTP development.', 'y');
    $add('General Settings', 'Logs', 'Log search default period (hours)', 'logs_request_messages_time_diff', 'number', 'Default age of log messages searched when no period is specified.', '1', array('min' => 1, 'max' => 8760));
    $add('General Settings', 'Logs', 'Log messages per request', 'logs_request_messages_limit', 'number', 'Maximum number of log records returned by one request.', '10', array('min' => 0, 'max' => 65536));
    $add('General Settings', 'Logs', 'First year in log archive', 'logs_first_year', 'year', 'Earliest year offered in the log archive selector.', '2007', array('min' => 2000, 'max' => (int) date('Y')));
    $add('General Settings', 'Logs', 'Log retention (days)', 'logs_purge_time_diff', 'number', 'Messages older than this number of days are removed when log purging is enabled. Zero means no retention delay.', '365', array('min' => 0, 'max' => 1460));

    $add('General Settings', 'Cookies', 'Session preference cookie lifetime (days)', 'session_cookie_lifetime', 'number', 'Lifetime of the language, style and preference cookies.', '365', array('min' => 0, 'max' => 1460));

    $add('General Settings', 'Open/close', 'Opening hour', 'opening_hour', 'hour', 'Hour of day when chat availability starts (0–23).', '0', array('min' => 0, 'max' => 23));
    $add('General Settings', 'Open/close', 'Closing hour', 'closing_hour', 'hour', 'Hour of day when chat availability ends (1–24). Must be later than the opening hour.', '24', array('min' => 1, 'max' => 24));
    $add('General Settings', 'Open/close', 'Open weekdays', 'opening_week_days', 'multi', 'Select the days when the chat is available. Stored as weekday numbers 0–6, where 0 is Sunday.', '0,1,2,3,4,5,6', array(
        'options' => array(
            array('value' => '0', 'label' => tra('Sunday')),
            array('value' => '1', 'label' => tra('Monday')),
            array('value' => '2', 'label' => tra('Tuesday')),
            array('value' => '3', 'label' => tra('Wednesday')),
            array('value' => '4', 'label' => tra('Thursday')),
            array('value' => '5', 'label' => tra('Friday')),
            array('value' => '6', 'label' => tra('Saturday')),
        ),
    ));

    $add('General Settings', 'Others', 'Timezone offset in seconds', 'time_zone_offset', 'number', 'Offset from UTC in seconds. Leave blank to use the server timezone.', '', array('min' => -43200, 'max' => 43200, 'allow_empty' => true));
    $add('General Settings', 'Others', 'Message history window (hours)', 'request_messages_time_diff', 'number', 'Maximum age of messages loaded for the chat history. The engine supports up to 8760 hours.', '24', array('min' => 1, 'max' => 8760));
    $add('General Settings', 'Others', 'Messages per history request', 'request_messages_limit', 'number', 'Maximum number of messages returned for one history request.', '10', array('min' => 1, 'max' => 500));

    $add('Users/Group Settings', 'Users/Group Settings', 'Maximum concurrent users', 'max_users_logged_in', 'number', 'Maximum number of ordinary users online at once. Administrators and moderators are not counted toward this limit.', '100', array('min' => 1, 'max' => 10000));
    $add('Users/Group Settings', 'Users/Group Settings', 'Default ban duration (minutes)', 'default_ban_time', 'number', 'Default ban duration when a moderator does not specify a duration.', '5', array('min' => 1, 'max' => 525600));
    $add('Users/Group Settings', 'Users/Group Settings', 'Inactive user timeout (minutes)', 'inactive_timeout', 'number', 'Minimum is 2 minutes; users without a status update for this period are considered inactive.', '2', array('min' => 2, 'max' => 1440));
    $add('Users/Group Settings', 'Users/Group Settings', 'Inactive user check interval (minutes)', 'inactive_check_interval', 'number', 'How often the chat checks for inactive users.', '5', array('min' => 1, 'max' => 1440));

    $add('Users/Group Settings', 'User privileges', 'Allow nickname changes', 'allow_nick_change', 'checkbox', 'Allow users to change their display name in the chat.', 'n');
    $add('Users/Group Settings', 'User privileges', 'Maximum nickname length', 'user_name_max_length', 'number', 'Maximum number of characters in a chat nickname.', '48', array('min' => 1, 'max' => 64));
    $add('Users/Group Settings', 'User privileges', 'Allow users to delete own messages', 'allow_user_message_delete', 'checkbox', 'Allow registered users to delete their own chat messages.', 'n');

    $groupOptions = array(array('value' => '', 'label' => tra('No moderator group')));
    foreach ($groups as $groupName) {
        $groupOptions[] = array('value' => $groupName, 'label' => $groupName);
    }
    $add('Users/Group Settings', 'Administrator/Moderator', 'Hide administrators in private rooms', 'admin_stealth_in_private_rooms', 'checkbox', 'Hide administrators from the member list of private rooms when they are not members or owners.', 'n');
    $add('Users/Group Settings', 'Administrator/Moderator', 'Ajax Chat moderator groups', 'moderator_groups', 'select', 'Members of the selected Tiki group receive the chat moderator role. Tiki administrators always remain administrators.', '', array('options' => $groupOptions));
    $add('Users/Group Settings', 'Administrator/Moderator', 'Close chat to non-administrators', 'chat_closed', 'checkbox', 'When enabled, only Tiki administrators can use the chat.', 'n');
    $add('Users/Group Settings', 'Administrator/Moderator', 'Check user IP address', 'ip_check', 'checkbox', 'End a chat session if the user IP address changes during that session.', 'y');

    $add('Chat Settings', 'Chat Settings', 'Maximum message length', 'message_text_max_length', 'number', 'Maximum number of characters in one chat message.', '1040', array('min' => 1, 'max' => 10000));
    $add('Chat Settings', 'Chat Settings', 'Maximum messages per minute', 'max_message_rate', 'number', 'Maximum number of messages a user may send per minute.', '20', array('min' => 1, 'max' => 1000));
    $add('Chat Settings', 'Chat Settings', 'Allow private rooms', 'allow_private_rooms', 'checkbox', 'Allow users to create persistent private rooms.', 'y');
    $add('Chat Settings', 'Chat Settings', 'Allow private channels', 'allow_private_channels', 'checkbox', 'Allow the original AJAX Chat private-channel feature.', 'y');
    $add('Chat Settings', 'Chat Settings', 'Allow private messages', 'allow_private_messages', 'checkbox', 'Allow users to send private messages.', 'y');
    $add('Chat Settings', 'Chat Settings', 'Show channel activity messages', 'show_channel_messages', 'checkbox', 'Show login, logout and channel enter/leave messages.', 'y');
    $add('Chat Settings', 'Chat Settings', 'Show recent messages on channel entry', 'request_messages_prior_channel_enter', 'checkbox', 'Load messages sent before the user entered the channel.', 'y');
    $add('Chat Settings', 'Chat Settings', 'Default public room name', 'default_channel_name', 'text', 'Name shown for the default public channel.', 'Public', array('maxlength' => 40));
    $add('Chat Settings', 'Chat Settings', 'Default public room ID', 'default_channel_id', 'number', 'ID of the default public channel. Keep 0 unless another channel ID is deliberately configured.', '0', array('min' => 0, 'max' => 100000000));
    $add('Chat Settings', 'Chat Settings', 'Maximum private rooms per user', 'max_private_rooms_per_user', 'number', 'Maximum number of persistent private rooms one user may own.', '10', array('min' => 0, 'max' => 100));
    $add('Chat Settings', 'Chat Settings', 'Maximum private room name length', 'private_room_max_name_length', 'number', 'Maximum number of characters in a private room name.', '40', array('min' => 1, 'max' => 40));
    $add('Chat Settings', 'Chat Settings', 'Private room name prefix', 'private_room_prefix', 'text', 'Prefix used to distinguish persistent private rooms. Maximum 16 characters.', '~', array('maxlength' => 16));
    $add('Chat Settings', 'Chat Settings', 'Private room name suffix', 'private_room_suffix', 'text', 'Suffix used to distinguish persistent private rooms. Maximum 16 characters.', '~', array('maxlength' => 16));
    $add('Chat Settings', 'Chat Settings', 'Private channel prefix', 'private_channel_prefix', 'text', 'Prefix used by the original private-channel feature. Maximum 16 characters.', '[', array('maxlength' => 16));
    $add('Chat Settings', 'Chat Settings', 'Private channel suffix', 'private_channel_suffix', 'text', 'Suffix used by the original private-channel feature. Maximum 16 characters.', ']', array('maxlength' => 16));
    $add('Chat Settings', 'Chat Settings', 'Changed nickname prefix', 'changed_nick_prefix', 'text', 'Prefix added to a changed nickname. Maximum 16 characters.', '(', array('maxlength' => 16));
    $add('Chat Settings', 'Chat Settings', 'Changed nickname suffix', 'changed_nick_suffix', 'text', 'Suffix added to a changed nickname. Maximum 16 characters.', ')', array('maxlength' => 16));
    $add('Chat Settings', 'Chat Settings', 'ChatBot display name', 'chatbot_name', 'text', 'Display name used for system messages. Maximum 255 characters.', 'ChatBot', array('maxlength' => 255));
    $add('Chat Settings', 'Chat Settings', 'Visible public channel IDs', 'limit_channel_list', 'text', 'Comma-separated public channel IDs. Leave blank to show all configured public channels.', '');
    $add('Chat Settings', 'Chat Settings', 'Always load history for channel IDs', 'request_messages_prior_channel_enter_list', 'text', 'Comma-separated channel IDs for which previous messages are always loaded. Leave blank to disable this override.', '');
    $add('Chat Settings', 'Chat Settings', 'Log access channel IDs', 'logs_user_access_channel_list', 'text', 'Comma-separated channel IDs included in user log access. Leave blank to allow all accessible channels.', '');

    return $definitions;
}
