<?php
/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @copyright (c) Sebastian Tschan
 * @license Modified MIT License
 * @link https://blueimp.net/ajax/
 *
 * Mikrozone: non-destructive, CLI-only schema installer.
 */

// Do not expose a database installer over HTTP.
if (PHP_SAPI !== 'cli') {
	http_response_code(403);
	exit("Run this installer from the command line or import chat.sql using a database administration tool.\n");
}

error_reporting(E_ALL);
ini_set('display_errors', 0);

define('AJAX_CHAT_PATH', __DIR__.'/');

$configFile = AJAX_CHAT_PATH.'lib/config.php';
if (!is_file($configFile) || !is_readable($configFile)) {
	fwrite(STDERR, "Missing or unreadable lib/config.php. Copy config.php.example and configure the database first.\n");
	exit(1);
}

require(AJAX_CHAT_PATH.'lib/custom.php');
require(AJAX_CHAT_PATH.'lib/classes.php');

class CustomAJAXChatInstaller extends CustomAJAXChatInterface {

	function getDataBaseTableCreationQueries() {
		$sqlFile = AJAX_CHAT_PATH.'chat.sql';
		if (!is_file($sqlFile) || !is_readable($sqlFile)) {
			throw new RuntimeException('Failed to read chat.sql.');
		}

		$sql = file_get_contents($sqlFile);
		if ($sql === false) {
			throw new RuntimeException('Failed to read chat.sql.');
		}

		// The schema is deliberately restricted to CREATE TABLE IF NOT EXISTS.
		if (preg_match('/\\bDROP\\s+TABLE\\b/i', $sql)) {
			throw new RuntimeException('Unsafe DROP TABLE statement found in chat.sql; refusing to continue.');
		}
		if (preg_match('/\\bCREATE\\s+TABLE\\s+(?!IF\\s+NOT\\s+EXISTS)\\b/i', $sql)) {
			throw new RuntimeException('Every schema table must use CREATE TABLE IF NOT EXISTS.');
		}

		$statements = preg_split('/;\\s*(?:\\r?\\n|$)/', trim($sql));
		$queries = array();
		foreach ($statements as $statement) {
			$statement = trim($statement);
			if ($statement !== '') {
				$queries[] = $statement;
			}
		}
		if (count($queries) !== 4) {
			throw new RuntimeException('Expected exactly four CREATE TABLE statements in chat.sql.');
		}
		return $queries;
	}

	function createDataBaseTables() {
		$expectedTables = array(
			'online' => 'mz_chat_online',
			'messages' => 'mz_chat_messages',
			'bans' => 'mz_chat_bans',
			'invitations' => 'mz_chat_invitations'
		);
		foreach ($expectedTables as $key => $tableName) {
			if ($this->getConfig('dbTableNames', $key) !== $tableName) {
				throw new RuntimeException('Database table configuration mismatch for '.$key.'. Expected '.$tableName.'.');
			}
		}

		$queries = $this->getDataBaseTableCreationQueries();
		foreach ($queries as $sql) {
			$result = $this->db->sqlQuery($sql);
			if ($result->error()) {
				throw new RuntimeException($result->getError());
			}
		}
		echo "Mikrozone chat tables created/verified successfully. Existing tables and their data were not dropped.\n";
	}
}

try {
	$installer = new CustomAJAXChatInstaller();
	$installer->createDataBaseTables();
	echo "Security: remove chat/install.php after installation.\n";
} catch (Throwable $e) {
	fwrite(STDERR, "Installation failed: ".$e->getMessage()."\n");
	exit(1);
}
