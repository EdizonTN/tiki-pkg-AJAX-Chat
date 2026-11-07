<?php
/**
 * Bootstrap TikiWiki for AJAX Chat requests.
 *
 * TikiDb is the database authority for the integrated chat. The installer
 * stores the filesystem path and URL; runtime code never performs path discovery.
 */
function AjaxChatBootstrapTiki(array $config)
{
    // Tiki's setup script is included below. PHP executes an included file
    // in the scope of its caller, so these Tiki runtime variables must be
    // explicitly bound to the global scope before loading tiki-setup.php.
    global $user, $prefs, $tiki_p_admin;

    $paths = isset($config['paths']) && is_array($config['paths']) ? $config['paths'] : array();
    $tikiRoot = isset($paths['tikiRootPath']) ? $paths['tikiRootPath'] : '';
    $tikiUrlPath = isset($paths['tikiUrlPath']) ? $paths['tikiUrlPath'] : '';

    if (!is_string($tikiRoot) || $tikiRoot === '' || !is_string($tikiUrlPath) || $tikiUrlPath === '') {
        throw new RuntimeException('Tiki paths are not configured.');
    }

    $tikiRoot = rtrim($tikiRoot, '/\\').DIRECTORY_SEPARATOR;
    $tikiSetup = $tikiRoot.'tiki-setup.php';
    if (!is_file($tikiSetup)) {
        throw new RuntimeException('Tiki setup file was not found at the configured path.');
    }

    if (!chdir($tikiRoot)) {
        throw new RuntimeException('Tiki root directory is not accessible.');
    }

    $tikiIndexPath = rtrim($tikiUrlPath, '/').'/index.php';
    if ($tikiIndexPath === 'index.php') {
        $tikiIndexPath = '/index.php';
    }

    // Tiki setup expects the request script to be rooted at the Tiki front controller.
    $_SERVER['SCRIPT_NAME'] = $tikiIndexPath;
    $_SERVER['PHP_SELF'] = $tikiIndexPath;
    $_SERVER['REQUEST_URI'] = $tikiIndexPath;
    $_SERVER['SCRIPT_FILENAME'] = $tikiRoot.'index.php';

    require_once($tikiSetup);

    if (!class_exists('TikiDb') || !TikiDb::isAvailable()) {
        throw new RuntimeException('Tiki database connection is not available.');
    }

    $packageRoot = dirname(rtrim($paths['chatRootPath'], '/\\\\'));
    $settingsStoreFile = $packageRoot . '/lib/SettingsStore.php';
    if (!is_file($settingsStoreFile)) {
        throw new RuntimeException('AJAX Chat settings store is missing.');
    }
    require_once $settingsStoreFile;
    $settingsStore = new AjaxChatSettingsStore();
    $settingsStore->loadIntoPreferences();

    return $tikiRoot;
}

/**
 * Overlay the safe, administrator-editable Tiki preferences onto the
 * upstream AJAX Chat configuration. Structural/security values remain in
 * Chat-config.php and cannot be changed through the preferences screen.
 */
function AjaxChatApplyTikiPreferences(array $config)
{
    global $prefs;
    if (!is_array($prefs)) {
        return $config;
    }

    $prefix = 'tp_edizontn__ajaxchat__';
    $flagMap = array(
        'allow_private_rooms' => 'allowPrivateRooms',
        'allow_private_channels' => 'allowPrivateChannels',
        'allow_private_messages' => 'allowPrivateMessages',
        'show_channel_messages' => 'showChannelMessages',
        'chat_closed' => 'chatClosed',
        'allow_nick_change' => 'allowNickChange',
        'allow_user_message_delete' => 'allowUserMessageDelete',
        'request_messages_prior_channel_enter' => 'requestMessagesPriorChannelEnter',
        'ip_check' => 'ipCheck',
        'logs_purge_logs' => 'logsPurgeLogs',
        'logs_user_access' => 'logsUserAccess',
        'socket_server_enabled' => 'socketServerEnabled',
        'session_cookie_secure' => 'sessionCookieSecure',
    );
    foreach ($flagMap as $pref => $key) {
        $name = $prefix . $pref;
        if (isset($prefs[$name])) {
            $config[$key] = ($prefs[$name] === 'y');
        }
    }

    $textMap = array(
        'lang_default' => 'langDefault',
        'style_default' => 'styleDefault',
        'default_channel_name' => 'defaultChannelName',
        'private_room_prefix' => 'privateRoomPrefix',
        'private_room_suffix' => 'privateRoomSuffix',
        'private_channel_prefix' => 'privateChannelPrefix',
        'private_channel_suffix' => 'privateChannelSuffix',
        'changed_nick_prefix' => 'changedNickPrefix',
        'changed_nick_suffix' => 'changedNickSuffix',
        'chatbot_name' => 'chatBotName',
        'socket_server_host' => 'socketServerHost',
        'socket_server_ip' => 'socketServerIP',
    );
    foreach ($textMap as $pref => $key) {
        $name = $prefix . $pref;
        if (array_key_exists($name, $prefs)) {
            $value = trim((string) $prefs[$name]);
            if ($key === 'langDefault') {
                if (in_array($value, isset($config['langAvailable']) ? $config['langAvailable'] : array(), true)) {
                    $config[$key] = $value;
                }
            } elseif ($key === 'styleDefault') {
                if (in_array($value, isset($config['styleAvailable']) ? $config['styleAvailable'] : array(), true)) {
                    $config[$key] = $value;
                }
            } elseif ($key === 'socketServerHost' && $value === '') {
                $config[$key] = null;
            } elseif (in_array($key, array('privateRoomPrefix', 'privateRoomSuffix', 'privateChannelPrefix', 'privateChannelSuffix', 'guestUserPrefix', 'guestUserSuffix', 'changedNickPrefix', 'changedNickSuffix'), true) && strlen($value) <= 16) {
                $config[$key] = $value;
            } elseif ($value !== '' && strlen($value) <= 255) {
                $config[$key] = $value;
            }
        }
    }

    $numberMap = array(
        'default_channel_id' => array('defaultChannelID', 0, 100000000),
        'max_private_rooms_per_user' => array('maxPrivateRoomsPerUser', 0, 100),
        'private_room_max_name_length' => array('privateRoomMaxNameLength', 1, 48),
        'opening_hour' => array('openingHour', 0, 23),
        'closing_hour' => array('closingHour', 1, 24),
        'inactive_timeout' => array('inactiveTimeout', 2, 1440),
        'inactive_check_interval' => array('inactiveCheckInterval', 1, 1440),
        'request_messages_time_diff' => array('requestMessagesTimeDiff', 1, 8760),
        'request_messages_limit' => array('requestMessagesLimit', 1, 500),
        'max_users_logged_in' => array('maxUsersLoggedIn', 1, 10000),
        'user_name_max_length' => array('userNameMaxLength', 1, 64),
        'message_text_max_length' => array('messageTextMaxLength', 1, 10000),
        'max_message_rate' => array('maxMessageRate', 1, 1000),
        'default_ban_time' => array('defaultBanTime', 1, 525600),
        'logs_request_messages_time_diff' => array('logsRequestMessagesTimeDiff', 1, 8760),
        'logs_request_messages_limit' => array('logsRequestMessagesLimit', 1, 500),
        'logs_first_year' => array('logsFirstYear', 2000, (int) date('Y')),
        'logs_purge_time_diff' => array('logsPurgeTimeDiff', 1, 36500),
        'session_cookie_lifetime' => array('sessionCookieLifeTime', 0, 3650),
        'socket_server_port' => array('socketServerPort', 1, 65535),
        'socket_server_chat_id' => array('socketServerChatID', 0, 2147483647),
    );
    foreach ($numberMap as $pref => $rule) {
        $name = $prefix . $pref;
        if (isset($prefs[$name]) && $prefs[$name] !== '' && is_numeric($prefs[$name])) {
            $config[$rule[0]] = max($rule[1], min($rule[2], (int) $prefs[$name]));
        }
    }

    $channelListMap = array(
        'limit_channel_list' => 'limitChannelList',
        'request_messages_prior_channel_enter_list' => 'requestMessagesPriorChannelEnterList',
        'logs_user_access_channel_list' => 'logsUserAccessChannelList',
    );
    foreach ($channelListMap as $pref => $key) {
        $name = $prefix . $pref;
        if (isset($prefs[$name])) {
            $ids = array();
            foreach (explode(',', (string) $prefs[$name]) as $id) {
                $id = trim($id);
                if ($id !== '' && ctype_digit($id)) {
                    $id = (int) $id;
                    if ($id < (int) $config['privateChannelDiff'] && !in_array($id, $ids, true)) {
                        $ids[] = $id;
                    }
                }
            }
            $config[$key] = $ids ? $ids : null;
        }
    }

    $offsetName = $prefix . 'time_zone_offset';
    if (isset($prefs[$offsetName]) && $prefs[$offsetName] !== '' && is_numeric($prefs[$offsetName])) {
        $config['timeZoneOffset'] = max(-43200, min(43200, (int) $prefs[$offsetName]));
    } elseif (isset($prefs[$offsetName]) && $prefs[$offsetName] === '') {
        $config['timeZoneOffset'] = null;
    }

    if ((int) $config['openingHour'] >= (int) $config['closingHour']) {
        // Avoid an invalid or ambiguous opening-hours interval.
        $config['openingHour'] = 0;
        $config['closingHour'] = 24;
    }

    $daysName = $prefix . 'opening_week_days';
    if (isset($prefs[$daysName])) {
        $days = array();
        foreach (explode(',', (string) $prefs[$daysName]) as $day) {
            if ($day !== '' && ctype_digit(trim($day))) {
                $day = (int) trim($day);
                if ($day >= 0 && $day <= 6 && !in_array($day, $days, true)) {
                    $days[] = $day;
                }
            }
        }
        if ($days) {
            $config['openingWeekDays'] = $days;
        }
    }

    return $config;
}
