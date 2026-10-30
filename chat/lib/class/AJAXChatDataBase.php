<?php
/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @copyright (c) Sebastian Tschan
 * @license Modified MIT License
 * @link https://blueimp.net/ajax/
 */

// Mikrozone uses MySQLi only. The legacy ext/mysql API was removed in PHP 7.
class AJAXChatDataBase {

	protected
		$_db;

	function __construct(&$dbConnectionConfig) {
		if (!extension_loaded('mysqli') || !class_exists('mysqli')) {
			throw new RuntimeException('The PHP mysqli extension is required by AJAX Chat.');
		}

		$type = isset($dbConnectionConfig['type']) ? strtolower($dbConnectionConfig['type']) : 'mysqli';
		if ($type !== 'mysqli') {
			throw new InvalidArgumentException('Unsupported database driver: '.$type.'. Configure mysqli.');
		}

		$this->_db = new AJAXChatDataBaseMySQLi($dbConnectionConfig);
	}
	
	function connect(&$dbConnectionConfig) {
		return $this->_db->connect($dbConnectionConfig);
	}
	
	function select($dbName) {
		return $this->_db->select($dbName);
	}
	
	function error() {
		return $this->_db->error();
	}
	
	function getError() {
		return $this->_db->getError();
	}
	
	function &getConnectionID() {
		return $this->_db->getConnectionID();
	}
	
	function makeSafe($value) {
		return $this->_db->makeSafe($value);
	}

	function sqlQuery($sql) {
		return $this->_db->sqlQuery($sql);
	}
	
	function getName() {
		return $this->_db->getName();
	}

	function getLastInsertedID() {
		return $this->_db->getLastInsertedID();
	}

}
