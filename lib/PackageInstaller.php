<?php
/**
 * Tiki-managed lifecycle operations for the AJAX Chat package.
 */
class AjaxChatPackageInstaller
{
    private $root;
    private $package;
    private $config;
    private $tables = array(
        'tiki_ajaxchat_online', 'tiki_ajaxchat_messages',
        'tiki_ajaxchat_bans', 'tiki_ajaxchat_invitations',
        'tiki_ajaxchat_rooms', 'tiki_ajaxchat_room_members'
    );

    public function __construct($package)
    {
        $this->package = realpath($package);
        $this->root = realpath(dirname(__DIR__, 3));
        if (!$this->package || !$this->root || !is_file($this->root . '/tiki-setup.php')) {
            throw new RuntimeException('Tiki root or package directory could not be located.');
        }
        $this->config = $this->root . '/temp/ajax-chat/Chat-config.php';
    }

    private function getStoredPreferencesWithPrefix($prefix)
    {
        $db = TikiDb::get();
        $result = $db->query(
            'SELECT name, value FROM tiki_preferences WHERE LEFT(name, ?) = ?',
            array(strlen($prefix), $prefix),
            -1,
            -1,
            TikiDb::ERR_EXCEPTION
        );
        $preferences = array();
        while ($row = $result->fetchRow()) {
            if (isset($row['name'])) {
                $preferences[$row['name']] = $row['value'];
            }
        }
        return $preferences;
    }

    public function migrateLegacyPreferences()
    {
        $tikilib = TikiLib::lib('tiki');
        $legacyPrefix = 'tp_edizontn__ajax_chat__';
        $currentPrefix = 'tp_edizontn__ajaxchat__';
        $legacy = $this->getStoredPreferencesWithPrefix($legacyPrefix);
        if (!$legacy) {
            return 0;
        }
        $current = $this->getStoredPreferencesWithPrefix($currentPrefix);
        $migrated = 0;
        foreach ($legacy as $oldName => $value) {
            $newName = $currentPrefix . substr($oldName, strlen($legacyPrefix));
            if (!array_key_exists($newName, $current)) {
                if (!$tikilib->set_preference($newName, $value)) {
                    throw new RuntimeException('Could not migrate legacy AJAX Chat preference ' . $oldName . '.');
                }
                $current[$newName] = $value;
                $migrated++;
            }
            $tikilib->delete_preference($oldName);
        }
        return $migrated;
    }

    public function status()
    {
        $db = TikiDb::get();
        $result = $db->query('SHOW TABLES', array(), -1, -1, TikiDb::ERR_EXCEPTION);
        $actual = array();
        if ($result) {
            while ($row = $result->fetchRow()) {
                if (is_array($row)) {
                    $values = array_values($row);
                    if (isset($values[0])) {
                        $actual[] = (string) $values[0];
                    }
                }
            }
        }
        $count = 0;
        foreach ($this->tables as $table) {
            if (in_array($table, $actual, true)) {
                $count++;
            }
        }
        return $count === count($this->tables) ? 'complete' : ($count ? 'partial' : 'missing');
    }

    /**
     * True only when the generated config, schema and owned /chat entry point
     * are all present. Used to avoid offering a broken chat link in admin UI.
     */
    public function isReady()
    {
        $entry = $this->root . '/chat/index.php';
        $routing = $this->root . '/chat/.htaccess';
        if ($this->status() !== 'complete' || !is_file($this->config) || !is_readable($this->config)
            || !is_file($entry) || !is_readable($entry) || !is_file($routing) || !is_readable($routing)) {
            return false;
        }
        $entryContents = (string) file_get_contents($entry);
        $routingContents = (string) file_get_contents($routing);
        $configContents = (string) file_get_contents($this->config);
        return strpos($entryContents, 'EdizonTN AJAX Chat managed entry point') !== false
            && strpos($routingContents, 'EdizonTN AJAX Chat managed asset routing') !== false
            && strpos($configContents, 'Generated and managed by the EdizonTN AJAX Chat Tiki package.') !== false;
    }

    private function validateSchema()
    {
        $expected = array(
            'tiki_ajaxchat_online' => array('userID', 'userName', 'userRole', 'channel', 'dateTime', 'ip'),
            'tiki_ajaxchat_messages' => array('id', 'userID', 'userName', 'userRole', 'channel', 'dateTime', 'ip', 'text'),
            'tiki_ajaxchat_bans' => array('userID', 'userName', 'dateTime', 'ip'),
            'tiki_ajaxchat_invitations' => array('userID', 'channel', 'dateTime'),
            'tiki_ajaxchat_rooms' => array('id', 'ownerID', 'name', 'dateTime'),
            'tiki_ajaxchat_room_members' => array('roomID', 'userID', 'userName', 'dateTime')
        );
        $db = TikiDb::get();
        foreach ($expected as $table => $columns) {
            $result = $db->query('SHOW COLUMNS FROM ' . chr(96) . $table . chr(96), array(), -1, -1, TikiDb::ERR_EXCEPTION);
            $actual = array();
            while ($row = $result->fetchRow()) {
                if (isset($row['Field'])) {
                    $actual[] = $row['Field'];
                }
            }
            foreach ($columns as $column) {
                if (!in_array($column, $actual, true)) {
                    throw new RuntimeException('The AJAX Chat table ' . $table . ' is missing column ' . $column . '.');
                }
            }
            $status = $db->query('SHOW TABLE STATUS WHERE Name = ' . $db->qstr($table), array(), -1, -1, TikiDb::ERR_EXCEPTION);
            $tableStatus = $status ? $status->fetchRow() : false;
            if (!$tableStatus || !isset($tableStatus['Engine']) || strtolower($tableStatus['Engine']) !== 'innodb') {
                throw new RuntimeException('The AJAX Chat table ' . $table . ' must use InnoDB.');
            }
        }
    }

    private function writeConfig()
    {
        $dir = dirname($this->config);
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create Tiki temp/ajax-chat.');
        }
        if (!is_writable($dir)) {
            throw new RuntimeException('Tiki temp/ajax-chat is not writable by PHP.');
        }

        $htaccess = $dir . '/.htaccess';
        if (!is_file($htaccess)) {
            if (file_put_contents($htaccess, "# EdizonTN AJAX Chat protected config directory\nRequire all denied\n", LOCK_EX) === false) {
                throw new RuntimeException('Could not protect the generated configuration directory.');
            }
        } else {
            $rules = (string) file_get_contents($htaccess);
            if (stripos($rules, 'require all denied') === false && stripos($rules, 'deny from all') === false) {
                throw new RuntimeException('The existing temp/ajax-chat/.htaccess does not deny web access; the configuration was not exposed.');
            }
        }

        if (is_file($this->config)) {
            $existingContents = (string) file_get_contents($this->config);
            if (strpos($existingContents, 'Generated and managed by the EdizonTN AJAX Chat Tiki package.') === false) {
                throw new RuntimeException('A configuration file already exists but is not managed by this package; it was not overwritten.');
            }
            $existingConfig = require $this->config;
            if (!is_array($existingConfig)
                || empty($existingConfig['ajaxChatSharedSecret'])
                || !is_string($existingConfig['ajaxChatSharedSecret'])
                || strlen($existingConfig['ajaxChatSharedSecret']) < 64
                || empty($existingConfig['paths']['chatRootPath'])
                || empty($existingConfig['paths']['chatUrlPath'])) {
                throw new RuntimeException('The existing package configuration is incomplete or invalid.');
            }
            return;
        }
        $template = $this->package . '/src/lib/Chat-config.php.example';
        if (!is_file($template) || !is_readable($template)) {
            throw new RuntimeException('Chat-config.php.example is missing or unreadable.');
        }
        $config = require $template;
        if (!is_array($config)) {
            throw new RuntimeException('Chat configuration template is invalid.');
        }
        $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($this->package, strlen($this->root) + 1));
        $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '/tiki-admin.php';
        $base = rtrim(dirname($script), '/');
        $base = ($base === '' || $base === '.') ? '/' : $base . '/';
        $config['dbConnection'] = array('type' => 'tikidb', 'link' => null, 'name' => '');
        $config['paths'] = array(
            'chatRootPath' => $this->package . '/src/',
            'chatUrlPath' => $base . $relative . '/src/',
            'tikiRootPath' => $this->root . '/',
            'tikiUrlPath' => $base
        );
        $config['ajaxChatSharedSecret'] = bin2hex(random_bytes(32));
        $php = "<?php\n// Generated and managed by the EdizonTN AJAX Chat Tiki package.\n";
        $php .= "define('AJAX_CHAT_BANNED', 6);\ndefine('AJAX_CHAT_CUSTOM', 5);\ndefine('AJAX_CHAT_CHATBOT', 4);\ndefine('AJAX_CHAT_ADMIN', 3);\ndefine('AJAX_CHAT_MODERATOR', 2);\ndefine('AJAX_CHAT_USER', 1);\ndefine('AJAX_CHAT_GUEST', 0);\n";
        $php .= 'return ' . var_export($config, true) . ";\n";
        if (file_put_contents($this->config, $php, LOCK_EX) === false) {
            throw new RuntimeException('Could not write generated chat configuration.');
        }
        chmod($this->config, 0640);

        if (!is_file($dir . '/index.php')) {
            if (file_put_contents($dir . '/index.php', "<?php\n// EdizonTN AJAX Chat protected config directory\nhttp_response_code(404);\nexit;\n", LOCK_EX) === false) {
                throw new RuntimeException('Could not protect the generated configuration directory.');
            }
        }
    }

    private function assertCleanUrlAvailable()
    {
        $entry = $this->root . '/chat/index.php';
        $routing = $this->root . '/chat/.htaccess';
        if (is_file($entry) && strpos((string) file_get_contents($entry), 'EdizonTN AJAX Chat managed entry point') === false) {
            throw new RuntimeException('/chat/index.php already exists and belongs to another application.');
        }
        if (file_exists($entry) && !is_file($entry)) {
            throw new RuntimeException('/chat/index.php exists but is not a regular file.');
        }
        if (is_file($routing) && strpos((string) file_get_contents($routing), 'EdizonTN AJAX Chat managed asset routing') === false) {
            throw new RuntimeException('/chat/.htaccess already exists and is not managed by this package; it was not overwritten.');
        }
        if (file_exists($routing) && !is_file($routing)) {
            throw new RuntimeException('/chat/.htaccess exists but is not a regular file.');
        }
    }

    private function writeCleanUrl()
    {
        $dir = $this->root . '/chat';
        if (!is_dir($dir) && !mkdir($dir, 0755)) {
            throw new RuntimeException('Cannot create /chat URL directory. Check Tiki root permissions.');
        }
        $file = $dir . '/index.php';
        $marker = 'EdizonTN AJAX Chat managed entry point';
        if (is_file($file) && strpos((string) file_get_contents($file), $marker) === false) {
            throw new RuntimeException('/chat/index.php already exists and was not overwritten.');
        }
        $source = "<?php\n// " . $marker . "\nrequire " . var_export($this->package . '/src/index.php', true) . ";\n";
        if (file_put_contents($file, $source, LOCK_EX) === false) {
            throw new RuntimeException('Could not create /chat/index.php.');
        }
    }

    /**
     * Route the chat's static asset directories to the package source tree.
     * The public /chat entry point is intentionally separate from the engine,
     * so relative URLs such as css/black.css need an explicit Apache mapping.
     */
    private function writeAssetRouting()
    {
        $dir = $this->root . '/chat';
        $file = $dir . '/.htaccess';
        $config = require $this->config;
        $assetUrl = isset($config['paths']['chatUrlPath']) ? $config['paths']['chatUrlPath'] : '';
        if (!is_string($assetUrl)
            || !preg_match('#^/[A-Za-z0-9_./-]+/$#', $assetUrl)
            || strpos($assetUrl, '/../') !== false) {
            throw new RuntimeException('The configured AJAX Chat asset URL is invalid.');
        }
        $assetUrl = rtrim($assetUrl, '/');
        if (is_file($file)
            && strpos((string) file_get_contents($file), 'EdizonTN AJAX Chat managed asset routing') === false) {
            throw new RuntimeException('/chat/.htaccess already exists and was not overwritten.');
        }
        $source = "# EdizonTN AJAX Chat managed asset routing\n";
        $source .= "<IfModule mod_rewrite.c>\n";
        $source .= "RewriteEngine On\n";
        $source .= "RewriteRule ^(css|js|img|sounds|flash)/(.*)$ " . $assetUrl . "/$1/$2 [L]\n";
        $source .= "</IfModule>\n";
        if (file_put_contents($file, $source, LOCK_EX) === false) {
            throw new RuntimeException('Could not create /chat/.htaccess for AJAX Chat assets.');
        }
    }

    public function install()
    {
        $this->assertCleanUrlAvailable();
        $this->writeConfig();
        $sql = file_get_contents($this->package . '/src/chat.sql');
        if (!is_string($sql) || preg_match('/\\bDROP\\s+TABLE\\b/i', $sql)) {
            throw new RuntimeException('AJAX Chat schema is missing or unsafe.');
        }
        $statements = preg_split('/;\\s*(?:\\r?\\n|$)/', trim($sql));
        $queries = array();
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '') {
                continue;
            }
            if (!preg_match('/^CREATE\\s+TABLE\\s+IF\\s+NOT\\s+EXISTS\\s+([a-zA-Z0-9_]+)/i', $statement, $match)
                || !in_array($match[1], $this->tables, true)) {
                throw new RuntimeException('Unexpected statement in AJAX Chat schema.');
            }
            $queries[] = $statement;
        }
        if (count($queries) !== 6) {
            throw new RuntimeException('Expected exactly six AJAX Chat table definitions.');
        }
        $db = TikiDb::get();
        foreach ($queries as $query) {
            $db->query($query, array(), -1, -1, TikiDb::ERR_EXCEPTION);
        }
        if ($this->status() !== 'complete') {
            throw new RuntimeException('AJAX Chat database schema is incomplete.');
        }
        $this->validateSchema();
        $this->writeCleanUrl();
        $this->writeAssetRouting();
    }

    public function uninstall()
    {
        global $prefs;
        if (($prefs['tp_edizontn__ajaxchat__chat_enabled'] ?? 'y') === 'y') {
            throw new RuntimeException('Disable AJAX Chat in Extension Preferences before uninstalling.');
        }
        $db = TikiDb::get();
        foreach ($this->tables as $table) {
            $db->query('DROP TABLE IF EXISTS ' . chr(96) . $table . chr(96), array(), -1, -1, TikiDb::ERR_EXCEPTION);
        }
        $entry = $this->root . '/chat/index.php';
        if (is_file($entry) && strpos((string) file_get_contents($entry), 'EdizonTN AJAX Chat managed entry point') !== false) {
            unlink($entry);
        }
        $routing = $this->root . '/chat/.htaccess';
        if (is_file($routing) && strpos((string) file_get_contents($routing), 'EdizonTN AJAX Chat managed asset routing') !== false) {
            unlink($routing);
        }
        if (is_dir(dirname($entry)) && count(scandir(dirname($entry))) === 2) {
            rmdir(dirname($entry));
        }
        $configExists = is_file($this->config);
        $configOwned = $configExists
            && strpos((string) file_get_contents($this->config), 'Generated and managed by the EdizonTN AJAX Chat Tiki package.') !== false;
        if (!$configExists || $configOwned) {
            if ($configOwned) {
                unlink($this->config);
            }
            $dir = dirname($this->config);
            foreach (array('.htaccess', 'index.php') as $name) {
                $path = $dir . '/' . $name;
                if (is_file($path)) {
                    $contents = (string) file_get_contents($path);
                    $owned = strpos($contents, 'EdizonTN AJAX Chat protected config directory') !== false;
                    if ($owned) {
                        unlink($path);
                    }
                }
            }
            if (is_dir($dir) && count(scandir($dir)) === 2) {
                rmdir($dir);
            }
        }

    }
}
