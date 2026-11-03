<?php
/**
 * Compatibility wrapper around TikiDb query results for the AJAX Chat API.
 */
class AJAXChatTikiQuery
{
    protected $_result = null;
    protected $_error = '';

    public function __construct($sql, $db)
    {
        try {
            $this->_result = $db->query($sql, [], -1, -1, TikiDb::ERR_EXCEPTION);
        } catch (Throwable $e) {
            $this->_error = $e->getMessage();
        }
    }

    public function error()
    {
        return $this->_error !== '' || $this->_result === false || $this->_result === null;
    }

    public function getError()
    {
        return $this->error() ? $this->_error : "No errors.\n";
    }

    public function fetch()
    {
        if ($this->error() || !method_exists($this->_result, 'fetchRow')) {
            return null;
        }
        return $this->_result->fetchRow();
    }

    public function numRows()
    {
        if ($this->error() || !method_exists($this->_result, 'numRows')) {
            return 0;
        }
        return $this->_result->numRows();
    }

    public function affectedRows()
    {
        // Tiki's result wrapper exposes the affected-row count through numRows().
        return $this->numRows();
    }

    public function free()
    {
        // Tiki's PdoResult does not expose a free() method; results are released
        // when the wrapper is destroyed.
    }
}
