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

    public function status()
    {
        $db = TikiDb::get();
        $count = 0;
        foreach ($this->tables as $table) {
            $result = $db->query('SHOW TABLES LIKE ' . $db->qstr($table), array(), -1, -1, TikiDb::ERR_EXCEPTION);
            if ($result && $result->fetchRow()) {
                $count++;
            }
        }
        return $count === count($this->tables) ? 'complete' : ($count ? 'partial' : 'missing');
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
        if (is_file($this->config)) {
            return;
        }
        $template = $this->package . '/src/lib/Chat-config.php.example';
        if (!is_file($template)) {
            throw new RuntimeException('Chat-config.php.example is missing.');
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
        if (!is_file($dir . '/.htaccess')) {
            if (file_put_contents($dir . '/.htaccess', "# EdizonTN AJAX Chat protected config directory\nRequire all denied\n", LOCK_EX) === false) {
                throw new RuntimeException('Could not protect the generated configuration directory.');
            }
        }
        if (!is_file($dir . '/index.php')) {
            if (file_put_contents($dir . '/index.php', "<?php\n// EdizonTN AJAX Chat protected config directory\nhttp_response_code(404);\nexit;\n", LOCK_EX) === false) {
                throw new RuntimeException('Could not protect the generated configuration directory.');
            }
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

    public function install()
    {
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
        $this->writeCleanUrl();
    }

    public function uninstall()
    {
        global $prefs;
        if (($prefs['tp_edizontn__ajax_chat__chat_enabled'] ?? 'y') === 'y') {
            throw new RuntimeException('Disable AJAX Chat in Extension Preferences before uninstalling.');
        }
        $db = TikiDb::get();
        foreach ($this->tables as $table) {
            $db->query('DROP TABLE IF EXISTS ' . chr(96) . $table . chr(96), array(), -1, -1, TikiDb::ERR_EXCEPTION);
        }
        $entry = $this->root . '/chat/index.php';
        if (is_file($entry) && strpos((string) file_get_contents($entry), 'EdizonTN AJAX Chat managed entry point') !== false) {
            unlink($entry);
            if (count(scandir(dirname($entry))) === 2) {
                rmdir(dirname($entry));
            }
        }
        if (is_file($this->config) && strpos((string) file_get_contents($this->config), 'Generated and managed by the EdizonTN AJAX Chat Tiki package.') !== false) {
            unlink($this->config);
            $dir = dirname($this->config);
            foreach (array('.htaccess', 'index.php') as $name) {
                $path = $dir . '/' . $name;
                if (is_file($path)) {
                    $contents = (string) file_get_contents($path);
                    $owned = ($name === '.htaccess' && strpos($contents, 'EdizonTN AJAX Chat protected config directory') !== false)
                        || ($name === 'index.php' && strpos($contents, 'EdizonTN AJAX Chat protected config directory') !== false);
                    if ($owned) {
                        unlink($path);
                    }
                }
            }
            if (count(scandir($dir)) === 2) {
                rmdir($dir);
            }
        }
    }
}
