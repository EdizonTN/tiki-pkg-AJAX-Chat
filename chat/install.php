<?php
/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @copyright (c) Sebastian Tschan
 * @license Modified MIT License
 * @link https://blueimp.net/ajax/
 *
 * Mikrozone: Tiki-admin-only, CSRF-protected web schema installer.
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

define('AJAX_CHAT_PATH', __DIR__.'/');

function mzInstallerStop($status, $message) {
	http_response_code($status);
	echo '<!doctype html><html lang="sk"><meta charset="utf-8"><title>Mikrozone chat installer</title>';
	echo '<body><main><h1>Mikrozone chat – inštalátor</h1><p>'.htmlspecialchars($message, ENT_QUOTES, 'UTF-8').'</p></main></body></html>';
	exit;
}

$configFile = AJAX_CHAT_PATH.'lib/config.php';
if (!is_file($configFile) || !is_readable($configFile)) {
	mzInstallerStop(500, 'Chýba alebo nie je čitateľný súbor _CHAT/lib/config.php.');
}
require($configFile);

$tikiRoot = isset($config['tikiRootPath']) ? $config['tikiRootPath'] : dirname(__DIR__).DIRECTORY_SEPARATOR;
$tikiSetup = rtrim($tikiRoot, '/\\').DIRECTORY_SEPARATOR.'tiki-setup.php';
if (!is_file($tikiSetup)) {
	mzInstallerStop(500, 'TikiWiki nebolo nájdené. Skontrolujte tikiRootPath v konfigurácii chatu.');
}

$tikiRootReal = realpath($tikiRoot);
if ($tikiRootReal === false || !is_dir($tikiRootReal) || !chdir($tikiRootReal)) {
	mzInstallerStop(500, 'Koreňový adresár TikiWiki nie je dostupný.');
}

// Bootstrap Tiki as if the request originated from its front controller.
// This preserves the normal Tiki session and permission context.
$scriptName = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '/_CHAT/install.php';
$tikiWebPath = rtrim(dirname(dirname($scriptName)), '/');
if ($tikiWebPath === '.') {
	$tikiWebPath = '';
}
$tikiIndexPath = $tikiWebPath.'/index.php';
$_SERVER['SCRIPT_NAME'] = $tikiIndexPath;
$_SERVER['PHP_SELF'] = $tikiIndexPath;
$_SERVER['REQUEST_URI'] = $tikiIndexPath;
$_SERVER['SCRIPT_FILENAME'] = $tikiRootReal.DIRECTORY_SEPARATOR.'index.php';

require_once($tikiSetup);

global $user, $tiki_p_admin;
if (empty($user) || $user === 'Anonymous') {
	mzInstallerStop(401, 'Najprv sa prihláste do TikiWiki.');
}

$tikiLib = TikiLib::lib('tiki');
$groups = $tikiLib->get_user_groups($user);
$isTikiAdmin = (isset($tiki_p_admin) && $tiki_p_admin === 'y') || in_array('Admins', $groups, true);
if (!$isTikiAdmin) {
	mzInstallerStop(403, 'Inštalátor je dostupný iba administrátorom TikiWiki.');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
	mzInstallerStop(500, 'Tiki session nie je aktívna; inštaláciu nemožno bezpečne spustiť.');
}

if (empty($_SESSION['mz_chat_installer_token']) || !is_string($_SESSION['mz_chat_installer_token'])) {
	try {
		$_SESSION['mz_chat_installer_token'] = bin2hex(random_bytes(32));
	} catch (Throwable $e) {
		mzInstallerStop(500, 'Nepodarilo sa vytvoriť bezpečnostný token.');
	}
}

$resultMessage = '';
$resultClass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
	$expectedToken = $_SESSION['mz_chat_installer_token'];
	unset($_SESSION['mz_chat_installer_token']);

	if ($submittedToken === '' || !hash_equals($expectedToken, $submittedToken)) {
		mzInstallerStop(403, 'Neplatný alebo expirovaný bezpečnostný token. Obnovte stránku a skúste znova.');
	}

	require(AJAX_CHAT_PATH.'lib/custom.php');
	require(AJAX_CHAT_PATH.'lib/classes.php');

	class CustomAJAXChatInstaller extends CustomAJAXChatInterface {
		function getDataBaseTableCreationQueries() {
			$sqlFile = AJAX_CHAT_PATH.'chat.sql';
			if (!is_file($sqlFile) || !is_readable($sqlFile)) {
				throw new RuntimeException('Súbor chat.sql sa nepodarilo prečítať.');
			}

			$sql = file_get_contents($sqlFile);
			if ($sql === false) {
				throw new RuntimeException('Súbor chat.sql sa nepodarilo prečítať.');
			}

			if (preg_match('/\\bDROP\\s+TABLE\\b/i', $sql)) {
				throw new RuntimeException('Schéma obsahuje zakázaný príkaz DROP TABLE.');
			}
			if (preg_match('/\\bCREATE\\s+TABLE\\s+(?!IF\\s+NOT\\s+EXISTS)\\b/i', $sql)) {
				throw new RuntimeException('Každá tabuľka musí používať CREATE TABLE IF NOT EXISTS.');
			}

			$statements = preg_split('/;\\s*(?:\\r?\\n|$)/', trim($sql));
			$queries = array();
			foreach ($statements as $statement) {
				$statement = trim($statement);
				if ($statement !== '') {
					$queries[] = $statement;
				}
			}
			if (count($queries) !== 6) {
				throw new RuntimeException('Očakávalo sa presne šesť príkazov CREATE TABLE.');
			}
			return $queries;
		}

		function createDataBaseTables() {
			$expectedTables = array(
				'online' => 'mz_chat_online',
				'messages' => 'mz_chat_messages',
				'bans' => 'mz_chat_bans',
				'invitations' => 'mz_chat_invitations',
				'rooms' => 'mz_chat_rooms',
				'roomMembers' => 'mz_chat_room_members'
			);
			foreach ($expectedTables as $key => $tableName) {
				if ($this->getConfig('dbTableNames', $key) !== $tableName) {
					throw new RuntimeException('Nesúlad konfigurácie názvu tabuľky: '.$key.'.');
				}
			}

			foreach ($this->getDataBaseTableCreationQueries() as $sql) {
				$result = $this->db->sqlQuery($sql);
				if ($result->error()) {
					throw new RuntimeException($result->getError());
				}
			}
		}
	}

	try {
		$installer = new CustomAJAXChatInstaller();
		$installer->createDataBaseTables();
		$resultMessage = 'Hotovo. Všetkých šesť tabuliek chatu bolo vytvorených alebo už existovalo. Existujúce tabuľky ani dáta neboli odstránené.';
		$resultClass = 'success';
	} catch (Throwable $e) {
		error_log('Mikrozone chat installer: '.$e->getMessage());
		$resultMessage = 'Inštalácia zlyhala. Podrobnosti nájdete v error logu servera.';
		$resultClass = 'error';
	}

	// A fresh one-time token is generated for the next page load.
	try {
		$_SESSION['mz_chat_installer_token'] = bin2hex(random_bytes(32));
	} catch (Throwable $e) {
		mzInstallerStop(500, 'Nepodarilo sa obnoviť bezpečnostný token.');
	}
}

$token = htmlspecialchars($_SESSION['mz_chat_installer_token'], ENT_QUOTES, 'UTF-8');
echo '<!doctype html><html lang="sk"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
echo '<title>Mikrozone chat – inštalátor</title></head><body><main style="max-width:48rem;margin:3rem auto;padding:1.5rem;font:16px/1.5 sans-serif">';
echo '<h1>Mikrozone chat – inštalátor</h1>';
echo '<p>Prihlásený administrátor TikiWiki: <strong>'.htmlspecialchars((string)$user, ENT_QUOTES, 'UTF-8').'</strong></p>';
echo '<p>Inštalátor vytvorí alebo overí šesť tabuliek s prefixom <code>mz_chat_</code> v samostatnej databáze nastavenej v konfigurácii chatu. Nepoužíva príkazy DROP TABLE.</p>';
if ($resultMessage !== '') {
	echo '<p role="status" style="padding:1rem;border:1px solid '.($resultClass === 'success' ? '#39834a' : '#a33').';">'.htmlspecialchars($resultMessage, ENT_QUOTES, 'UTF-8').'</p>';
}
echo '<form method="post"><input type="hidden" name="csrf_token" value="'.$token.'">';
echo '<button type="submit">Vytvoriť / overiť databázové tabuľky</button></form>';
echo '<p><strong>Po úspešnej inštalácii:</strong> odstráňte súbor <code>_CHAT/install.php</code> a odoberte databázovému používateľovi oprávnenie <code>CREATE</code>. Chat na bežnú prevádzku potrebuje iba SELECT, INSERT, UPDATE a DELETE.</p>';
echo '</main></body></html>';
