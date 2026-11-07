<?php
/**
 * AJAX Chat database administration for TikiWiki.
 *
 * This endpoint is deliberately package-local. It does not register or alter
 * Tiki core administration menus and only operates on the six fixed chat tables.
 */
ini_set('display_errors', '0');
error_reporting(E_ALL);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

$configFile = __DIR__ . '/lib/Chat-config.php';
if (!is_file($configFile) || !is_readable($configFile)) {
    http_response_code(500);
    exit('Ajax Chat is not configured. Run the package installer first.');
}
$config = require($configFile);
if (!is_array($config) || empty($config['paths']['tikiRootPath']) || empty($config['paths']['tikiUrlPath'])) {
    http_response_code(500);
    exit('Invalid Tiki paths in Chat-config.php.');
}

require_once(__DIR__ . '/lib/tiki-bootstrap.php');
try {
    AjaxChatBootstrapTiki($config);
} catch (Throwable $e) {
    error_log('Ajax Chat admin bootstrap: ' . $e->getMessage());
    http_response_code(500);
    exit('TikiWiki could not be initialized.');
}

global $user, $tiki_p_admin;
if (empty($user) || $user === 'Anonymous') {
    http_response_code(401);
    exit('Please log in to TikiWiki first.');
}
$tikiLib = TikiLib::lib('tiki');
$userGroups = $tikiLib->get_user_groups($user);
$isTikiAdmin = (isset($tiki_p_admin) && $tiki_p_admin === 'y') || in_array('Admins', $userGroups, true);
if (!$isTikiAdmin) {
    http_response_code(403);
    exit('This page is available to Tiki administrators only.');
}
if (session_status() !== PHP_SESSION_ACTIVE) {
    http_response_code(500);
    exit('Tiki session is not active; database administration is unavailable.');
}

$tables = array(
    'tiki_ajaxchat_online',
    'tiki_ajaxchat_messages',
    'tiki_ajaxchat_bans',
    'tiki_ajaxchat_invitations',
    'tiki_ajaxchat_rooms',
    'tiki_ajaxchat_room_members'
);

function ajaxChatAdminStatus(array $tables) {
    $db = TikiDb::get();
    $found = array();
    $result = $db->query('SHOW TABLES', array(), -1, -1, TikiDb::ERR_EXCEPTION);
    $actualTables = array();
    if ($result) {
        while ($row = $result->fetchRow()) {
            if (is_array($row)) {
                $values = array_values($row);
                if (isset($values[0])) {
                    $actualTables[] = (string) $values[0];
                }
            }
        }
    }
    foreach ($tables as $table) {
        if (in_array($table, $actualTables, true)) {
            $found[] = $table;
        }
    }
    if (count($found) === count($tables)) {
        return array('complete', $found);
    }
    return array(count($found) ? 'partial' : 'missing', $found);
}

if (empty($_SESSION['ajax_chat_admin_token']) || !is_string($_SESSION['ajax_chat_admin_token'])) {
    $_SESSION['ajax_chat_admin_token'] = bin2hex(random_bytes(32));
}
$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    $expectedToken = $_SESSION['ajax_chat_admin_token'];
    unset($_SESSION['ajax_chat_admin_token']);
    if ($submittedToken === '' || !hash_equals($expectedToken, $submittedToken)) {
        $message = 'Security token expired or invalid. No database operation was performed.';
        $messageType = 'error';
    } else {
        $action = isset($_POST['action']) ? (string) $_POST['action'] : '';
        try {
            if ($action === 'install') {
                $sqlFile = __DIR__ . '/chat.sql';
                if (!is_file($sqlFile) || !is_readable($sqlFile)) {
                    throw new RuntimeException('chat.sql is missing or unreadable.');
                }
                $sql = file_get_contents($sqlFile);
                if ($sql === false || preg_match('/\\bDROP\\s+TABLE\\b/i', $sql)) {
                    throw new RuntimeException('The schema file is invalid or contains a forbidden DROP TABLE statement.');
                }
                $statements = preg_split('/;\\s*(?:\\r?\\n|$)/', trim($sql));
                $queries = array();
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if ($statement === '') {
                        continue;
                    }
                    if (!preg_match('/^CREATE\\s+TABLE\\s+IF\\s+NOT\\s+EXISTS\\s+([a-zA-Z0-9_]+)/i', $statement, $match)) {
                        throw new RuntimeException('Unexpected SQL statement in chat.sql.');
                    }
                    if (!in_array($match[1], $tables, true)) {
                        throw new RuntimeException('Schema contains an unexpected table name: ' . $match[1]);
                    }
                    $queries[] = $statement;
                }
                if (count($queries) !== count($tables)) {
                    throw new RuntimeException('Expected exactly six chat table definitions.');
                }
                $db = TikiDb::get();
                foreach ($queries as $query) {
                    $db->query($query, array(), -1, -1, TikiDb::ERR_EXCEPTION);
                }
                list($status) = ajaxChatAdminStatus($tables);
                if ($status !== 'complete') {
                    throw new RuntimeException('The schema is still incomplete after installation.');
                }
                $message = 'All six AJAX Chat tables are installed. Existing tables and their data were preserved.';
                $messageType = 'success';
            } elseif ($action === 'uninstall') {
                $confirmation = isset($_POST['confirmation']) ? trim((string) $_POST['confirmation']) : '';
                if ($confirmation !== 'DELETE AJAX CHAT DATA') {
                    throw new RuntimeException('Uninstall confirmation text did not match. No tables were removed.');
                }
                $db = TikiDb::get();
                foreach ($tables as $table) {
                    $db->query('DROP TABLE IF EXISTS `' . $table . '`', array(), -1, -1, TikiDb::ERR_EXCEPTION);
                }
                list($status) = ajaxChatAdminStatus($tables);
                if ($status !== 'missing') {
                    throw new RuntimeException('One or more AJAX Chat tables still exist after uninstall.');
                }
                $message = 'The six AJAX Chat tables were removed. All chat messages, rooms, bans and invitations stored in them have been permanently deleted.';
                $messageType = 'success';
            } else {
                throw new RuntimeException('Unknown database action.');
            }
        } catch (Throwable $e) {
            error_log('Ajax Chat database administration: ' . $e->getMessage());
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
    $_SESSION['ajax_chat_admin_token'] = bin2hex(random_bytes(32));
}

try {
    list($schemaStatus, $existingTables) = ajaxChatAdminStatus($tables);
} catch (Throwable $e) {
    error_log('Ajax Chat schema status: ' . $e->getMessage());
    $schemaStatus = 'unknown';
    $existingTables = array();
}
$token = htmlspecialchars($_SESSION['ajax_chat_admin_token'], ENT_QUOTES, 'UTF-8');
$statusLabels = array(
    'complete' => 'Complete – all six tables exist',
    'partial' => 'Incomplete – only some tables exist',
    'missing' => 'Not installed – no chat tables found',
    'unknown' => 'Unknown – database status could not be checked'
);
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
echo '<title>AJAX Chat database administration</title></head>';
echo '<body><main style="max-width:52rem;margin:3rem auto;padding:1.5rem;font:16px/1.5 sans-serif">';
echo '<h1>AJAX Chat – database administration</h1>';
echo '<p>Signed in as Tiki administrator: <strong>' . $escape($user) . '</strong></p>';
echo '<p><strong>Schema status:</strong> ' . $escape(isset($statusLabels[$schemaStatus]) ? $statusLabels[$schemaStatus] : $schemaStatus) . '</p>';
if ($message !== '') {
    echo '<p role="status" style="padding:1rem;border:1px solid ' . ($messageType === 'success' ? '#39834a' : '#a33') . ';">' . $escape($message) . '</p>';
}
echo '<h2>Tables</h2><ul>';
foreach ($tables as $table) {
    echo '<li><code>' . $escape($table) . '</code> – ' . (in_array($table, $existingTables, true) ? 'present' : 'missing') . '</li>';
}
echo '</ul>';
echo '<h2>Install or repair</h2><p>Creates missing AJAX Chat tables using CREATE TABLE IF NOT EXISTS. Existing chat data is not deleted.</p>';
echo '<form method="post"><input type="hidden" name="csrf_token" value="' . $token . '"><input type="hidden" name="action" value="install"><button type="submit">Install / verify tables</button></form>';
echo '<h2>Uninstall</h2><p><strong>Irreversible:</strong> this permanently deletes all chat messages, rooms, room memberships, bans, invitations and online status. It does not modify TikiWiki tables or package files.</p>';
echo '<form method="post" onsubmit="return confirm(\'This permanently deletes all AJAX Chat data. Continue?\');">';
echo '<input type="hidden" name="csrf_token" value="' . $token . '"><input type="hidden" name="action" value="uninstall">';
echo '<label>Type <code>DELETE AJAX CHAT DATA</code> to confirm: <input type="text" name="confirmation" required autocomplete="off"></label> ';
echo '<button type="submit">Uninstall and delete chat data</button></form>';
echo '<p><a href="' . $escape(rtrim($config['paths']['tikiUrlPath'], '/') . '/tiki-admin.php?page=packages') . '">Return to Tiki package settings</a></p>';
echo '</main></body></html>';
