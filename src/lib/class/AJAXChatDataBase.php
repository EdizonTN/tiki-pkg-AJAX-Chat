<?php
/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @copyright (c) Sebastian Tschan
 * @license Modified MIT License
 * @link https://blueimp.net/ajax/
 */

// Supports TikiDb for integrated installs and MySQLi for standalone development.
class AJAXChatDataBase {

    protected $_db;

    function __construct(&$dbConnectionConfig) {
        $type = isset($dbConnectionConfig['type']) ? strtolower($dbConnectionConfig['type']) : 'mysqli';

        if ($type === 'tikidb') {
            require_once(AJAX_CHAT_PATH.'lib/class/AJAXChatTikiQuery.php');
            require_once(AJAX_CHAT_PATH.'lib/class/AJAXChatTikiDataBase.php');
            $this->_db = new AJAXChatDataBaseTiki($dbConnectionConfig);
            return;
        }

        if (!extension_loaded('mysqli') || !class_exists('mysqli')) {
            throw new RuntimeException('The PHP mysqli extension is required by standalone AJAX Chat.');
        }
        if ($type !== 'mysqli') {
            throw new InvalidArgumentException('Unsupported database driver: '.$type.'.');
        }
        $this->_db = new AJAXChatDataBaseMySQLi($dbConnectionConfig);
    }

    function connect(&$dbConnectionConfig) { return $this->_db->connect($dbConnectionConfig); }
    function select($dbName) { return $this->_db->select($dbName); }
    function error() { return $this->_db->error(); }
    function getError() { return $this->_db->getError(); }
    function &getConnectionID() { return $this->_db->getConnectionID(); }
    function makeSafe($value) { return $this->_db->makeSafe($value); }
    function sqlQuery($sql) { return $this->_db->sqlQuery($sql); }
    function getName() { return $this->_db->getName(); }
    function getLastInsertedID() { return $this->_db->getLastInsertedID(); }
}
