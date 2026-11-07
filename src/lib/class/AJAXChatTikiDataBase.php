<?php
/**
 * AJAX Chat database adapter backed by TikiWiki's existing database layer.
 *
 * This adapter deliberately does not open a second connection. Tiki must have
 * been bootstrapped before AJAX Chat is initialized.
 */
class AJAXChatDataBaseTiki
{
    protected $_db;
    protected $_error = '';

    public function __construct(array &$dbConnectionConfig)
    {
        if (!class_exists('TikiDb') || !TikiDb::isAvailable()) {
            throw new RuntimeException('TikiWiki database is not initialized.');
        }

        $this->_db = TikiDb::get();
        $dbConnectionConfig['link'] = $this->_db;
        $dbConnectionConfig['name'] = '';
    }

    public function connect(&$dbConnectionConfig)
    {
        return TikiDb::isAvailable();
    }

    public function select($dbName)
    {
        // The database is selected by TikiWiki. Switching it here is forbidden.
        if ($dbName !== '' && $dbName !== null) {
            $this->_error = 'AJAX Chat must use the active TikiWiki database.';
            return false;
        }
        return true;
    }

    public function error()
    {
        return $this->_error !== '';
    }

    public function getError()
    {
        return $this->error() ? $this->_error : "No errors.\n";
    }

    public function &getConnectionID()
    {
        return $this->_db;
    }

    public function makeSafe($value)
    {
        return $this->_db->qstr((string) $value);
    }

    public function sqlQuery($sql)
    {
        return new AJAXChatTikiQuery($sql, $this->_db);
    }

    public function getName()
    {
        // Empty means table names are relative to Tiki's already selected DB.
        return '';
    }

    public function getLastInsertedID()
    {
        return $this->_db->lastInsertId();
    }
}
