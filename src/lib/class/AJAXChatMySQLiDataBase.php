<?php
/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @copyright (c) Sebastian Tschan
 * @license Modified MIT License
 * @link https://blueimp.net/ajax/
 */

// MySQLi adapter. Keep legacy error handling predictable on PHP 8.1+.
class AJAXChatDataBaseMySQLi {

    protected
		$_connectionID,
		$_errno = 0,
		$_error = '',
		$_dbName;

	function __construct(&$dbConnectionConfig) {
		$this->_connectionID = $dbConnectionConfig['link'];
		$this->_dbName = $dbConnectionConfig['name'];
	}
	
	// Method to connect to the DataBase server:
	function connect(&$dbConnectionConfig) {
		// PHP 8.1 changed the default MySQLi reporting mode to strict exceptions.
		// This adapter uses the original return-value/error-object API.
		mysqli_report(MYSQLI_REPORT_OFF);

		$this->_connectionID = @new mysqli(
			$dbConnectionConfig['host'],
			$dbConnectionConfig['user'],
			$dbConnectionConfig['pass']
		);
		if(!$this->_connectionID || $this->_connectionID->connect_errno) {
			$this->_errno = $this->_connectionID ? $this->_connectionID->connect_errno : mysqli_connect_errno();
			$this->_error = $this->_connectionID ? $this->_connectionID->connect_error : mysqli_connect_error();
			return false;
		}

		// Match the utf8mb4 table schema and make escaping charset-aware.
		if(!$this->_connectionID->set_charset('utf8mb4')) {
			$this->_errno = $this->_connectionID->errno;
			$this->_error = $this->_connectionID->error;
			return false;
		}
		return true;
	}
	
	// Method to select the DataBase:
	function select($dbName) {
		if(!$this->_connectionID->select_db($dbName)) {
			$this->_errno = $this->_connectionID->errno;
			$this->_error = $this->_connectionID->error;
			return false;
		}
		$this->_dbName = $dbName;
		return true;	
	}
	
	// Method to determine if an error has occured:
	function error() {
		return (bool)$this->_error;
	}
	
	// Method to return the error report:
	function getError() {
		if($this->error()) {
			$str = 'Error-Report: '	.$this->_error."\n";
			$str .= 'Error-Code: '.$this->_errno."\n";
		} else {
			$str = 'No errors.'."\n";
		}
		return $str;		
	}
	
	// Method to return the connection identifier:
	function &getConnectionID() {
		return $this->_connectionID;
	}
	
	// Method to prevent SQL injections:
	function makeSafe($value) {
		return "'".$this->_connectionID->escape_string($value)."'";
	}

	// Method to perform SQL queries:
	function sqlQuery($sql) {
		return new AJAXChatMySQLiQuery($sql, $this->_connectionID);
	}

	// Method to retrieve the current DataBase name:
	function getName() {
		return $this->_dbName;
	}

	// Method to retrieve the last inserted ID:
	function getLastInsertedID() {
		return $this->_connectionID->insert_id;
	}

}
