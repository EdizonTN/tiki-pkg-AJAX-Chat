<?php
/**
 * Site-wide preferences for the AJAX Chat extension.
 *
 * Only runtime-safe options are exposed here. Database credentials, table
 * names, filesystem paths, authentication secrets and reserved ID ranges stay
 * installer-managed and are deliberately not editable in the Tiki UI.
 */
function prefs_tp_edizontn__ajax_chat_list()
{
    $prefs = [
        'tp_edizontn__ajax_chat__chat_enabled' => [
            'name' => tra('Enable Ajax Chat'),
            'description' => tra('Allow authenticated Tiki users to access the chat.'),
            'urladmin' => 'tiki-admin.php?page=tp_edizontn_ajax-chat',
            'tags' => ['basic'],
            'type' => 'flag',
            'default' => 'y',
        ],
        'tp_edizontn__ajax_chat__moderator_groups' => [
            'name' => tra('Ajax Chat moderator groups'),
            'description' => tra('Comma-separated exact Tiki group names. Tiki administrators always retain the highest chat role.'),
            'tags' => ['basic'],
            'type' => 'text',
            'default' => '',
        ],
        'tp_edizontn__ajax_chat__admin_stealth_in_private_rooms' => [
            'name' => tra('Hide administrators in private rooms'),
            'description' => tra('When enabled, an administrator who is not a member or owner of a private room is hidden from that room member list.'),
            'tags' => ['basic'],
            'type' => 'flag',
            'default' => 'n',
        ],
    ];

    $flags = [
        'allow_private_rooms' => ['Allow private rooms', 'Allow users to create persistent private rooms.', 'y'],
        'allow_private_channels' => ['Allow private channels', 'Allow the original AJAX Chat private-channel feature.', 'y'],
        'allow_private_messages' => ['Allow private messages', 'Allow users to send private messages.', 'y'],
        'show_channel_messages' => ['Show channel activity messages', 'Show login, logout and channel enter/leave messages.', 'y'],
        'chat_closed' => ['Close chat to non-administrators', 'When enabled, only Tiki administrators can use the chat.', 'n'],
        'allow_nick_change' => ['Allow nickname changes', 'Allow users to change their chat display name.', 'n'],
        'allow_user_message_delete' => ['Allow users to delete own messages', 'Allow registered users to delete their own chat messages.', 'n'],
        'request_messages_prior_channel_enter' => ['Show recent messages on channel entry', 'Load messages sent before the user entered the channel.', 'y'],
        'ip_check' => ['Check user IP address', 'Check whether the IP address remains the same during a chat session.', 'y'],
        'logs_purge_logs' => ['Purge old chat logs', 'Enable automatic deletion of messages older than the configured retention period.', 'n'],
        'logs_user_access' => ['Allow users to view chat logs', 'Grant registered users access to chat logs; administrators always have access.', 'n'],
        'socket_server_enabled' => ['Enable AJAX Chat socket server', 'Requires a separately running socket server; normally keep disabled on shared hosting.', 'n'],
        'session_cookie_secure' => ['Require HTTPS for chat cookies', 'Enable on HTTPS sites. Disable only for local HTTP development.', 'y'],
    ];
    foreach ($flags as $key => $definition) {
        $prefs['tp_edizontn__ajax_chat__' . $key] = [
            'name' => tra($definition[0]),
            'description' => tra($definition[1]),
            'type' => 'flag',
            'default' => $definition[2],
        ];
    }

    $texts = [
        'lang_default' => ['Default language', 'Language code from the bundled AJAX Chat language list.', 'sk'],
        'style_default' => ['Default chat style', 'Style name from the bundled AJAX Chat style list.', 'prosilver'],
        'default_channel_name' => ['Default public room name', 'Name shown for the default public channel.', 'Public'],
        'default_channel_id' => ['Default public room ID', 'Keep 0 unless you have deliberately configured another public channel ID.', '0'],
        'max_private_rooms_per_user' => ['Maximum private rooms per user', 'Maximum number of persistent private rooms one user may own.', '10'],
        'private_room_max_name_length' => ['Maximum private room name length', 'Maximum number of characters in a private room name.', '40'],
        'private_room_prefix' => ['Private room name prefix', 'Prefix used to distinguish persistent private rooms.', '~'],
        'private_room_suffix' => ['Private room name suffix', 'Suffix used to distinguish persistent private rooms.', '~'],
        'private_channel_prefix' => ['Private channel prefix', 'Prefix used by the original private-channel feature.', '['],
        'private_channel_suffix' => ['Private channel suffix', 'Suffix used by the original private-channel feature.', ']'],
        'changed_nick_prefix' => ['Changed nickname prefix', 'Prefix used to mark changed nicknames.', '('],
        'changed_nick_suffix' => ['Changed nickname suffix', 'Suffix used to mark changed nicknames.', ')'],
        'chatbot_name' => ['ChatBot display name', 'Display name used for system messages.', 'ChatBot'],
        'limit_channel_list' => ['Visible public channel IDs', 'Optional comma-separated list of public channel IDs. Leave blank to show all configured public channels.', ''],
        'request_messages_prior_channel_enter_list' => ['Always load history for channel IDs', 'Optional comma-separated list of channel IDs for which earlier messages are always loaded.', ''],
        'logs_user_access_channel_list' => ['Log access channel IDs', 'Optional comma-separated list of channels included in user log access. Leave blank to allow all accessible channels.', ''],
        'socket_server_host' => ['Socket server hostname', 'Client-side socket server hostname; blank uses the current host.', ''],
        'socket_server_ip' => ['Socket server IP address', 'Server-side address used to broadcast messages.', '127.0.0.1'],
    ];
    foreach ($texts as $key => $definition) {
        $prefs['tp_edizontn__ajax_chat__' . $key] = [
            'name' => tra($definition[0]),
            'description' => tra($definition[1]),
            'type' => 'text',
            'default' => $definition[2],
        ];
    }

    $numbers = [
        'opening_hour' => ['Opening hour', 'Hour of day when chat availability starts (0–23).', '0'],
        'closing_hour' => ['Closing hour', 'Hour of day when chat availability ends (1–24).', '24'],
        'time_zone_offset' => ['Timezone offset in seconds', 'Leave blank to use the server timezone; valid range is -43200 to 43200.', ''],
        'inactive_timeout' => ['Inactive user timeout (minutes)', 'Minimum 2 minutes.', '2'],
        'inactive_check_interval' => ['Inactive user check interval (minutes)', 'How often inactive users are checked.', '5'],
        'request_messages_time_diff' => ['Message history window (hours)', 'Maximum age of messages loaded on each chat request.', '24'],
        'request_messages_limit' => ['Messages per history request', 'Maximum number of messages returned on each request.', '10'],
        'max_users_logged_in' => ['Maximum concurrent users', 'Does not limit administrators or moderators.', '100'],
        'user_name_max_length' => ['Maximum nickname length', 'Maximum length of a chat display name.', '48'],
        'message_text_max_length' => ['Maximum message length', 'Maximum length of one chat message.', '1040'],
        'max_message_rate' => ['Maximum messages per minute', 'Maximum number of messages a user may send per minute.', '20'],
        'default_ban_time' => ['Default ban duration (minutes)', 'Default duration when a moderator kicks a user without specifying a duration.', '5'],
        'logs_request_messages_time_diff' => ['Log search default period (hours)', 'Default log period when no search period is supplied.', '1'],
        'logs_request_messages_limit' => ['Log messages per request', 'Maximum number of log messages returned per request.', '10'],
        'logs_first_year' => ['First year in log archive', 'Earliest year offered in the log archive selector.', '2007'],
        'logs_purge_time_diff' => ['Log retention (days)', 'Messages older than this are purged when log purging is enabled.', '365'],
        'session_cookie_lifetime' => ['Session preference cookie lifetime (days)', 'Lifetime of language, style and settings cookies.', '365'],
        'socket_server_port' => ['Socket server port', 'TCP port of the separately running socket server.', '1935'],
        'socket_server_chat_id' => ['Socket server chat ID', 'Distinguishes installations sharing one socket server.', '0'],
    ];
    foreach ($numbers as $key => $definition) {
        $prefs['tp_edizontn__ajax_chat__' . $key] = [
            'name' => tra($definition[0]),
            'description' => tra($definition[1]),
            'type' => 'text',
            'default' => $definition[2],
        ];
    }

    $prefs['tp_edizontn__ajax_chat__opening_week_days'] = [
        'name' => tra('Open weekdays'),
        'description' => tra('Comma-separated weekdays: 0=Sunday through 6=Saturday. Default: every day.'),
        'type' => 'text',
        'default' => '0,1,2,3,4,5,6',
    ];

    return $prefs;
}
