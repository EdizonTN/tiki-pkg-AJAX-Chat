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

    public function __construct(array $runtimeConfig = array(), array $groups = array())
    {
        require_once __DIR__ . '/SettingsDefinitions.php';
        $this->definitions = ajax_chat_setting_definitions($runtimeConfig, $groups);
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
            'SELECT name FROM `' . self::TABLE . '`',
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
        $current = $this->getAll();
        $normalized = array();

        foreach ($allowed as $suffix => $definition) {
            if (!array_key_exists($suffix, $submitted)) {
                continue;
            }
            $value = $submitted[$suffix];
            $type = $definition['type'];

            if ($type === 'multi') {
                if (!is_array($value)) {
                    throw new RuntimeException('Invalid selection for setting: ' . $suffix);
                }
                $validOptions = array();
                foreach ($definition['options'] as $option) {
                    $validOptions[] = (string) $option['value'];
                }
                $selected = array();
                foreach ($value as $item) {
                    $item = trim((string) $item);
                    if ($item === '') {
                        continue;
                    }
                    if (!in_array($item, $validOptions, true)) {
                        throw new RuntimeException('Invalid selection for setting: ' . $suffix);
                    }
                    if (!in_array($item, $selected, true)) {
                        $selected[] = $item;
                    }
                }
                sort($selected, SORT_NUMERIC);
                $normalized[$suffix] = implode(',', $selected);
                continue;
            }

            if (!is_scalar($value)) {
                throw new RuntimeException('Invalid value for setting: ' . $suffix);
            }
            $value = trim((string) $value);

            if ($type === 'checkbox') {
                if (!in_array($value, array('y', 'n'), true)) {
                    throw new RuntimeException('Invalid checkbox value for setting: ' . $suffix);
                }
            } elseif ($type === 'number' || $type === 'hour' || $type === 'year') {
                if ($value === '' && !empty($definition['allow_empty'])) {
                    $normalized[$suffix] = '';
                    continue;
                }
                if (!preg_match('/^-?[0-9]+$/', $value)) {
                    throw new RuntimeException('This setting requires an integer: ' . $suffix);
                }
                $number = (int) $value;
                if ($number < (int) $definition['min'] || $number > (int) $definition['max']) {
                    throw new RuntimeException('Value out of range for setting: ' . $suffix . ' (' . $definition['min'] . '–' . $definition['max'] . ')');
                }
                $value = (string) $number;
            } elseif ($type === 'select') {
                $validOptions = array();
                foreach ($definition['options'] as $option) {
                    $validOptions[] = (string) $option['value'];
                }
                if (!in_array($value, $validOptions, true)) {
                    throw new RuntimeException('Invalid selection for setting: ' . $suffix);
                }
            } elseif ($type === 'url') {
                if (strlen($value) > 255) {
                    throw new RuntimeException('Socket server URL must not exceed 255 characters.');
                }
                if ($value !== '') {
                    $url = filter_var($value, FILTER_VALIDATE_URL);
                    $parts = $url ? parse_url($url) : false;
                    if (!$parts || empty($parts['host']) || empty($parts['scheme'])
                        || !in_array(strtolower($parts['scheme']), array('http', 'https', 'ws', 'wss'), true)) {
                        throw new RuntimeException('Enter a valid socket server URL for setting: ' . $suffix);
                    }
                }
            } elseif ($type === 'ip') {
                if (!filter_var($value, FILTER_VALIDATE_IP)) {
                    throw new RuntimeException('Enter a valid IPv4 or IPv6 address for setting: ' . $suffix);
                }
            } elseif ($type === 'text') {
                $maxLength = isset($definition['maxlength']) ? (int) $definition['maxlength'] : 1000;
                $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
                if ($length > $maxLength) {
                    throw new RuntimeException('Setting is too long: ' . $suffix . ' (maximum ' . $maxLength . ' characters)');
                }
                if (in_array($suffix, array('limit_channel_list', 'request_messages_prior_channel_enter_list', 'logs_user_access_channel_list'), true)) {
                    if ($value !== '') {
                        $ids = array();
                        foreach (explode(',', $value) as $id) {
                            $id = trim($id);
                            if (!ctype_digit($id) || (int) $id >= 500000000) {
                                throw new RuntimeException('Enter comma-separated channel IDs below 500000000 for setting: ' . $suffix);
                            }
                            $id = (string) (int) $id;
                            if (!in_array($id, $ids, true)) {
                                $ids[] = $id;
                            }
                        }
                        $value = implode(',', $ids);
                    }
                }
            } else {
                throw new RuntimeException('Unsupported setting type: ' . $type);
            }
            $normalized[$suffix] = $value;
        }

        foreach ($normalized as $suffix => $value) {
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
