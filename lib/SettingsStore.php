<?php
/**
 * Persistent settings store for the AJAX Chat package.
 *
 * Settings deliberately live outside tiki_preferences: Tiki unloads package
 * preference definitions when an extension is disabled, leaving orphaned
 * preference rows behind. This table remains owned by the package instead.
 */
class AjaxChatSettingsStore
{
    const TABLE = 'tiki_ajaxchat_settings';
    const PREFIX = 'tp_edizontn__ajaxchat__';
    const LEGACY_PREFIX = 'tp_edizontn__ajax_chat__';

    private $definitions;

    public function __construct()
    {
        require_once __DIR__ . '/SettingsDefinitions.php';
        $this->definitions = ajax_chat_setting_definitions();
    }

    public function ensureTable()
    {
        $db = TikiDb::get();
        $db->query(
            'CREATE TABLE IF NOT EXISTS `' . self::TABLE . '` (
                `name` varchar(100) NOT NULL,
                `value` text NOT NULL,
                PRIMARY KEY (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            array(),
            -1,
            -1,
            TikiDb::ERR_EXCEPTION
        );
    }

    private function suffix($fullName)
    {
        return substr($fullName, strlen(self::PREFIX));
    }

    public function getDefinitions()
    {
        return $this->definitions;
    }

    public function getAll()
    {
        $values = array();
        foreach ($this->definitions as $fullName => $definition) {
            $values[$this->suffix($fullName)] = (string) $definition['default'];
        }
        $result = TikiDb::get()->query(
            'SELECT name, value FROM `' . self::TABLE . '`',
            array(),
            -1,
            -1,
            TikiDb::ERR_EXCEPTION
        );
        while ($row = $result->fetchRow()) {
            if (isset($row['name']) && array_key_exists($row['name'], $values)) {
                $values[$row['name']] = (string) $row['value'];
            }
        }
        return $values;
    }

    /**
     * One-time, lossless migration from both previous Tiki preference
     * namespaces. Existing database-backed values always take precedence.
     */
    public function migratePreferences()
    {
        $db = TikiDb::get();
        $result = $db->query(
            'SELECT name, value FROM tiki_preferences WHERE LEFT(name, ?) = ? OR LEFT(name, ?) = ?',
            array(strlen(self::PREFIX), self::PREFIX, strlen(self::LEGACY_PREFIX), self::LEGACY_PREFIX),
            -1,
            -1,
            TikiDb::ERR_EXCEPTION
        );
        $rows = array();
        while ($row = $result->fetchRow()) {
            if (!isset($row['name'])) {
                continue;
            }
            $name = (string) $row['name'];
            $prefix = strpos($name, self::LEGACY_PREFIX) === 0 ? self::LEGACY_PREFIX : self::PREFIX;
            $suffix = substr($name, strlen($prefix));
            $priority = ($prefix === self::PREFIX) ? 2 : 1;
            if (!isset($rows[$suffix]) || $priority > $rows[$suffix]['priority']) {
                $rows[$suffix] = array('value' => (string) $row['value'], 'priority' => $priority);
            }
        }

        $existingResult = $db->query(
            'SELECT name FROM \`' . self::TABLE . '\`',
            array(),
            -1,
            -1,
            TikiDb::ERR_EXCEPTION
        );
        $existing = array();
        while ($row = $existingResult->fetchRow()) {
            if (isset($row['name'])) {
                $existing[(string) $row['name']] = true;
            }
        }

        foreach ($rows as $suffix => $row) {
            if (!isset($this->definitions[self::PREFIX . $suffix]) || isset($existing[$suffix])) {
                continue;
            }
            $this->saveOne($suffix, $row['value']);
        }

        // Remove both obsolete namespaces, including keys no longer exposed
        // by the current package, so disabling the package cannot leave
        // undefined preference warnings behind.
        $tikilib = TikiLib::lib('tiki');
        $allNames = $db->query(
            'SELECT name FROM tiki_preferences WHERE LEFT(name, ?) = ? OR LEFT(name, ?) = ?',
            array(strlen(self::PREFIX), self::PREFIX, strlen(self::LEGACY_PREFIX), self::LEGACY_PREFIX),
            -1,
            -1,
            TikiDb::ERR_EXCEPTION
        );
        while ($row = $allNames->fetchRow()) {
            if (isset($row['name'])) {
                $tikilib->delete_preference((string) $row['name']);
            }
        }
        return count($rows);
    }

    private function saveOne($suffix, $value)
    {
        TikiDb::get()->query(
            'INSERT INTO `' . self::TABLE . '` (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)',
            array($suffix, (string) $value),
            -1,
            -1,
            TikiDb::ERR_EXCEPTION
        );
    }

    public function save(array $submitted)
    {
        $allowed = array();
        foreach ($this->definitions as $fullName => $definition) {
            $allowed[$this->suffix($fullName)] = $definition;
        }
        foreach ($allowed as $suffix => $definition) {
            if (!array_key_exists($suffix, $submitted)) {
                continue;
            }
            if (!is_scalar($submitted[$suffix])) {
                throw new RuntimeException('Invalid value for setting: ' . $suffix);
            }
            $value = trim((string) $submitted[$suffix]);
            if (strlen($value) > 1000) {
                throw new RuntimeException('Setting is too long: ' . $suffix);
            }
            if ($definition['type'] === 'flag' && !in_array($value, array('y', 'n'), true)) {
                throw new RuntimeException('Invalid flag value for setting: ' . $suffix);
            }
            if ($definition['type'] === 'text' && preg_match('/(?:^|_)(?:hour|id|limit|length|timeout|interval|diff|users|rate|time|year|days|port|chat_id)$/', $suffix)
                && $value !== '' && !preg_match('/^-?[0-9]+$/', $value)) {
                throw new RuntimeException('This setting requires an integer: ' . $suffix);
            }
            $this->saveOne($suffix, $value);
        }
    }

    /**
     * Populate the compatibility preference array consumed by the existing
     * chat bridge. No values are written to Tiki's preference table.
     */
    public function loadIntoPreferences()
    {
        global $prefs;
        $values = $this->getAll();
        if (!is_array($prefs)) {
            $prefs = array();
        }
        foreach ($values as $suffix => $value) {
            $prefs[self::PREFIX . $suffix] = $value;
        }
    }

    public function remove()
    {
        TikiDb::get()->query('DROP TABLE IF EXISTS `' . self::TABLE . '`', array(), -1, -1, TikiDb::ERR_EXCEPTION);
    }
}
