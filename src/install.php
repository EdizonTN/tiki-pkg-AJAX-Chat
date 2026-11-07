<?php
/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @copyright (c) Sebastian Tschan
 * @license Modified MIT License
 * @link https://blueimp.net/ajax/
 *
 * Tiki-admin-only, CSRF-protected web schema installer.
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

define('AJAX_CHAT_PATH', __DIR__.'/');

function ajaxChatInstallerStop($status, $message) {
	http_response_code($status);
	echo '<!doctype html><html lang="sk"><meta charset="utf-8"><title>Ajax Chat installer</title>';
	echo '<body><main><h1>Ajax Chat – inštalátor</h1><p>'.htmlspecialchars($message, ENT_QUOTES, 'UTF-8').'</p></main></body></html>';
	exit;
}

function ajaxChatFindTikiRoot($startDirectory) {
	$current = realpath($startDirectory);
	if ($current === false) {
		return false;
	}
	while (true) {
		if (is_file($current.DIRECTORY_SEPARATOR.'tiki-setup.php')) {
			return rtrim($current, '/\\').DIRECTORY_SEPARATOR;
		}
		$parent = dirname($current);
		if ($parent === $current) {
			return false;
		}
		$current = $parent;
	}
}

/**
 * Register the package-owned standalone route in Tiki's supported custom
 * bootstrap file. Existing administrator customizations are preserved.
 */
function ajaxChatRegisterStandaloneRouter($tikiRoot, $chatRoot) {
	$customDirectory = rtrim($tikiRoot, '/\\').DIRECTORY_SEPARATOR.'_custom'.DIRECTORY_SEPARATOR.'shared';
	$customFile = $customDirectory.DIRECTORY_SEPARATOR.'custom.php';
	$routerFile = rtrim($chatRoot, '/\\').DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'standalone-router.php';
	$marker = 'EdizonTN AJAX Chat standalone route';

	if (!is_file($routerFile) || !is_readable($routerFile)) {
		throw new RuntimeException('Súbor standalone-router.php chýba alebo nie je čitateľný.');
	}
	if (!is_dir($customDirectory) && !@mkdir($customDirectory, 0755, true) && !is_dir($customDirectory)) {
		throw new RuntimeException('Nepodarilo sa vytvoriť adresár _custom/shared.');
	}

	$include = "\n// BEGIN ".$marker."\nrequire_once ".var_export($routerFile, true).";\n// END ".$marker."\n";
	if (!is_file($customFile)) {
		$contents = "<?php\n".$include;
		if (@file_put_contents($customFile, $contents, LOCK_EX) === false) {
			throw new RuntimeException('Nepodarilo sa vytvoriť _custom/shared/custom.php.');
		}
		return true;
	}
	if (!is_readable($customFile) || !is_writable($customFile)) {
		throw new RuntimeException('_custom/shared/custom.php už existuje, ale nie je zapisovateľný. Router nebol zaregistrovaný.');
	}
	$contents = file_get_contents($customFile);
	if ($contents === false) {
		throw new RuntimeException('_custom/shared/custom.php sa nepodarilo prečítať.');
	}
	if (strpos($contents, $marker) !== false) {
		return true;
	}
	if (!preg_match('/\\A(\\s*<\\?php\\s*(?:declare\\s*\\(\\s*strict_types\\s*=\\s*1\\s*\\)\\s*;\\s*)?)/', $contents, $match)) {
		throw new RuntimeException('_custom/shared/custom.php nemá očakávaný PHP začiatok; súbor nebol zmenený.');
	}
	$offset = strlen($match[1]);
	$newContents = substr($contents, 0, $offset).$include.substr($contents, $offset);
	$handle = @fopen($customFile, 'c+');
	if ($handle === false || !flock($handle, LOCK_EX)) {
		if (is_resource($handle)) {
			fclose($handle);
		}
		throw new RuntimeException('_custom/shared/custom.php sa nepodarilo bezpečne otvoriť na zápis.');
	}
	$success = ftruncate($handle, 0) && rewind($handle);
	$written = 0;
	while ($success && $written < strlen($newContents)) {
		$count = fwrite($handle, substr($newContents, $written));
		if ($count === false || $count === 0) {
			$success = false;
			break;
		}
		$written += $count;
	}
	if ($success) {
		$success = fflush($handle);
	}
	flock($handle, LOCK_UN);
	fclose($handle);
	if (!$success) {
		throw new RuntimeException('Nepodarilo sa bezpečne zapísať _custom/shared/custom.php.');
	}
	return true;
}

function ajaxChatInstallerSchemaStatus() {
	$tables = array(
		'tiki_ajaxchat_online',
		'tiki_ajaxchat_messages',
		'tiki_ajaxchat_bans',
		'tiki_ajaxchat_invitations',
		'tiki_ajaxchat_rooms',
		'tiki_ajaxchat_room_members'
	);
	try {
		if (!class_exists('TikiDb') || !TikiDb::isAvailable()) {
			return 'unknown';
		}
		$db = TikiDb::get();
		$found = 0;
		foreach ($tables as $table) {
			$result = $db->query("SHOW TABLES LIKE ".$db->qstr($table), array(), -1, -1, TikiDb::ERR_EXCEPTION);
			if ($result && $result->fetchRow()) {
				$found++;
			}
		}
		if ($found === count($tables)) {
			return 'complete';
		}
		return $found > 0 ? 'partial' : 'missing';
	} catch (Throwable $e) {
		error_log('Ajax Chat schema check: '.$e->getMessage());
		return 'unknown';
	}
}

function ajaxChatWritePhpConfig($file, array $values) {
	// The installer is allowed to update the existing config file, but must not
	// require write access to lib/ itself. Keep its root:www-data ownership and
	// permissions by writing to the existing inode instead of temp+rename.
	if (!is_file($file) || !is_writable($file)) {
		return false;
	}
	$contents = "<?php\n// Generated by the Ajax Chat installer.\n".
		"define('AJAX_CHAT_BANNED', 6);\n".
		"define('AJAX_CHAT_CUSTOM', 5);\n".
		"define('AJAX_CHAT_CHATBOT', 4);\n".
		"define('AJAX_CHAT_ADMIN', 3);\n".
		"define('AJAX_CHAT_MODERATOR', 2);\n".
		"define('AJAX_CHAT_USER', 1);\n".
		"define('AJAX_CHAT_GUEST', 0);\n".
		"return ".var_export($values, true).";\n";
	$handle = @fopen($file, 'c+');
	if ($handle === false) {
		return false;
	}
	if (!flock($handle, LOCK_EX)) {
		fclose($handle);
		return false;
	}
	$success = ftruncate($handle, 0) && rewind($handle);
	$offset = 0;
	$length = strlen($contents);
	while ($success && $offset < $length) {
		$written = fwrite($handle, substr($contents, $offset));
		if ($written === false || $written === 0) {
			$success = false;
			break;
		}
		$offset += $written;
	}
	if ($success) {
		$success = fflush($handle);
	}
	flock($handle, LOCK_UN);
	fclose($handle);
	return $success;
}

$configFile = AJAX_CHAT_PATH.'lib/Chat-config.php';
if (!is_file($configFile) || !is_readable($configFile)) {
	ajaxChatInstallerStop(500, 'Chýba alebo nie je čitateľný súbor lib/Chat-config.php. Najprv skopírujte Chat-config.php.example na Chat-config.php a nastavte jedinečný HMAC secret. Databázové údaje sa preberajú z TikiWiki.');
}
$ajaxChatInstallerConfig = require($configFile);
if (!is_array($ajaxChatInstallerConfig)) {
	ajaxChatInstallerStop(500, 'Chat-config.php musí vracať konfiguračné pole.');
}

$chatDbConfig = isset($ajaxChatInstallerConfig['dbConnection']) && is_array($ajaxChatInstallerConfig['dbConnection'])
	? $ajaxChatInstallerConfig['dbConnection'] : array();
if (!isset($chatDbConfig['type']) || $chatDbConfig['type'] !== 'tikidb') {
	ajaxChatInstallerStop(500, 'Chat musí používať databázové pripojenie TikiWiki (type=tikidb).');
}
$secret = isset($ajaxChatInstallerConfig['ajaxChatSharedSecret']) ? $ajaxChatInstallerConfig['ajaxChatSharedSecret'] : '';
if (!is_string($secret) || strlen($secret) < 32 || strpos($secret, 'REPLACE_WITH_') === 0) {
	ajaxChatInstallerStop(500, 'HMAC secret musí byť jedinečný náhodný reťazec s dĺžkou aspoň 32 znakov.');
}

// Path detection is deliberately performed only by this installer.
$chatRootReal = realpath(__DIR__);
$tikiRoot = ajaxChatFindTikiRoot(__DIR__);
if ($chatRootReal === false || $tikiRoot === false) {
	ajaxChatInstallerStop(500, 'TikiWiki nebolo nájdené v nadradených adresároch chatu. Chat musí byť umiestnený v adresárovej štruktúre TikiWiki.');
}
$tikiRootReal = rtrim($tikiRoot, '/\\');
if (strpos($chatRootReal, $tikiRootReal.DIRECTORY_SEPARATOR) !== 0) {
	ajaxChatInstallerStop(500, 'Adresár chatu sa nenachádza pod koreňovým adresárom TikiWiki.');
}

$scriptName = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '';
if ($scriptName === '' || substr($scriptName, -strlen('/install.php')) !== '/install.php') {
	ajaxChatInstallerStop(500, 'Web server neposkytol platnú URL cestu inštalátora.');
}
$installerScriptName = $scriptName;
$chatUrlPath = rtrim(dirname($scriptName), '/').'/';
$relativeChatPath = substr($chatRootReal, strlen($tikiRootReal));
$relativeParts = array_values(array_filter(explode(DIRECTORY_SEPARATOR, trim($relativeChatPath, DIRECTORY_SEPARATOR)), 'strlen'));
$tikiUrlPath = rtrim($chatUrlPath, '/');
for ($i = 0; $i < count($relativeParts); $i++) {
	$tikiUrlPath = dirname($tikiUrlPath);
}
if ($tikiUrlPath === '.' || $tikiUrlPath === '') {
	$tikiUrlPath = '/';
} else {
	$tikiUrlPath = rtrim($tikiUrlPath, '/').'/';
}

if (!chdir($tikiRootReal)) {
	ajaxChatInstallerStop(500, 'Koreňový adresár TikiWiki nie je dostupný.');
}
$tikiIndexPath = rtrim($tikiUrlPath, '/').'/index.php';
if ($tikiIndexPath === 'index.php') {
	$tikiIndexPath = '/index.php';
}
$_SERVER['SCRIPT_NAME'] = $tikiIndexPath;
$_SERVER['PHP_SELF'] = $tikiIndexPath;
$_SERVER['REQUEST_URI'] = $tikiIndexPath;
$_SERVER['SCRIPT_FILENAME'] = $tikiRootReal.DIRECTORY_SEPARATOR.'index.php';

require_once($tikiRootReal.DIRECTORY_SEPARATOR.'tiki-setup.php');

if (!class_exists('TikiDb') || !TikiDb::isAvailable()) {
	ajaxChatInstallerStop(500, 'Databázové pripojenie TikiWiki nie je dostupné.');
}

global $user, $tiki_p_admin;
if (empty($user) || $user === 'Anonymous') {
	ajaxChatInstallerStop(401, 'Najprv sa prihláste do TikiWiki.');
}

$tikiLib = TikiLib::lib('tiki');
$groups = $tikiLib->get_user_groups($user);
$isTikiAdmin = (isset($tiki_p_admin) && $tiki_p_admin === 'y') || in_array('Admins', $groups, true);
if (!$isTikiAdmin) {
	ajaxChatInstallerStop(403, 'Inštalátor je dostupný iba administrátorom TikiWiki.');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
	ajaxChatInstallerStop(500, 'Tiki session nie je aktívna; inštaláciu nemožno bezpečne spustiť.');
}

if (empty($_SESSION['ajax_chat_installer_token']) || !is_string($_SESSION['ajax_chat_installer_token'])) {
	try {
		$_SESSION['ajax_chat_installer_token'] = bin2hex(random_bytes(32));
	} catch (Throwable $e) {
		ajaxChatInstallerStop(500, 'Nepodarilo sa vytvoriť bezpečnostný token.');
	}
}

$resultMessage = '';
$resultDetail = '';
$resultClass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
	$expectedToken = $_SESSION['ajax_chat_installer_token'];
	unset($_SESSION['ajax_chat_installer_token']);

	if ($submittedToken === '' || !hash_equals($expectedToken, $submittedToken)) {
		$resultMessage = 'Formulár už bol odoslaný alebo bezpečnostný token expiroval. Stav inštalácie bol znovu načítaný.';
		$resultDetail = '';
		$resultClass = 'error';
	} else {
		require(AJAX_CHAT_PATH.'lib/custom.php');
	require(AJAX_CHAT_PATH.'lib/classes.php');

	class CustomAJAXChatInstaller extends CustomAJAXChatInterface {
		// The config was already loaded before Tiki bootstrap; avoid including it twice
		// (which would redefine the AJAX_CHAT_* constants).
		function initConfig() {
			global $ajaxChatInstallerConfig;
			$this->_config = $ajaxChatInstallerConfig;
			$this->initCustomConfig();
		}

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

		function validateDataBaseTables(array $expectedTables) {
			$expectedColumns = array(
				'online' => array('userID', 'userName', 'userRole', 'channel', 'dateTime', 'ip'),
				'messages' => array('id', 'userID', 'userName', 'userRole', 'channel', 'dateTime', 'ip', 'text'),
				'bans' => array('userID', 'userName', 'dateTime', 'ip'),
				'invitations' => array('userID', 'channel', 'dateTime'),
				'rooms' => array('id', 'ownerID', 'name', 'dateTime'),
				'roomMembers' => array('roomID', 'userID', 'userName', 'dateTime')
			);
			foreach ($expectedColumns as $key => $columns) {
				$tableName = $expectedTables[$key];
				$result = $this->db->sqlQuery('SHOW COLUMNS FROM `'.$tableName.'`');
				if ($result->error()) {
					throw new RuntimeException('Nepodarilo sa overiť stĺpce tabuľky '.$tableName.': '.$result->getError());
				}
				$actualColumns = array();
				while ($row = $result->fetch()) {
					$actualColumns[] = $row['Field'];
				}
				$result->free();
				foreach ($columns as $column) {
					if (!in_array($column, $actualColumns, true)) {
						throw new RuntimeException('Tabuľka '.$tableName.' nemá požadovaný stĺpec '.$column.'.');
					}
				}
				$status = $this->db->sqlQuery("SHOW TABLE STATUS WHERE Name = '".$tableName."'");
				if ($status->error()) {
					throw new RuntimeException('Nepodarilo sa overiť storage engine tabuľky '.$tableName.'.');
				}
				$tableStatus = $status->fetch();
				$status->free();
				if (!$tableStatus || !isset($tableStatus['Engine']) || strtolower($tableStatus['Engine']) !== 'innodb') {
					throw new RuntimeException('Tabuľka '.$tableName.' musí používať InnoDB kvôli transakčnému mazaniu miestností.');
				}
			}
		}

		function createDataBaseTables() {
			$expectedTables = array(
				'online' => 'tiki_ajaxchat_online',
				'messages' => 'tiki_ajaxchat_messages',
				'bans' => 'tiki_ajaxchat_bans',
				'invitations' => 'tiki_ajaxchat_invitations',
				'rooms' => 'tiki_ajaxchat_rooms',
				'roomMembers' => 'tiki_ajaxchat_room_members'
			);
			foreach ($expectedTables as $key => $tableName) {
				if ($this->getConfig('dbTableNames', $key) !== $tableName) {
					throw new RuntimeException('Nesúlad konfigurácie názvu tabuľky: '.$key.'.');
				}
			}

			// If the complete schema already exists, do not run the installer again.
			$existingTables = 0;
			foreach ($expectedTables as $tableName) {
				$result = $this->db->sqlQuery("SHOW TABLES LIKE '".$tableName."'");
				if ($result->error()) {
					throw new RuntimeException($result->getError());
				}
				if ($result->numRows() > 0) {
					$existingTables++;
				}
				$result->free();
			}
			if ($existingTables === count($expectedTables)) {
				$this->validateDataBaseTables($expectedTables);
				return false;
			}

			foreach ($this->getDataBaseTableCreationQueries() as $sql) {
				$result = $this->db->sqlQuery($sql);
				if ($result->error()) {
					throw new RuntimeException($result->getError());
				}
			}
			$this->validateDataBaseTables($expectedTables);
			return true;
		}
	}

	try {
        $ajaxChatInstallerConfig['paths'] = array(
            'chatRootPath' => rtrim($chatRootReal, '/\\').DIRECTORY_SEPARATOR,
            'chatUrlPath' => $chatUrlPath,
            'tikiRootPath' => $tikiRoot,
            'tikiUrlPath' => $tikiUrlPath
        );
		if (!ajaxChatWritePhpConfig($configFile, $ajaxChatInstallerConfig)) {
			throw new RuntimeException('Nepodarilo sa bezpečne uložiť Chat-config.php.');
		}
		$installer = new CustomAJAXChatInstaller();
		if ($installer->createDataBaseTables()) {
			$resultMessage = 'Chat je úspešne nakonfigurovaný a pripravený na testovanie. Chýbajúce databázové tabuľky boli vytvorené; existujúce tabuľky ani dáta neboli odstránené.';
		} else {
			$resultMessage = 'Chat je úspešne nakonfigurovaný a pripravený na testovanie. Databázová schéma už existovala; cesty a konfigurácia bridge boli aktualizované, tabuľky sa nemenili.';
		}
		ajaxChatRegisterStandaloneRouter($tikiRootReal, $chatRootReal);
		$resultMessage .= ' Integrácia čistej URL bola zaregistrovaná v Tiki custom bootstrap súbore.';
		$resultClass = 'success';
	} catch (Throwable $e) {
		error_log('Ajax Chat installer: '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
		$resultMessage = 'Inštalácia zlyhala.';
		$errorDetail = get_class($e).': '.$e->getMessage().' ('.basename($e->getFile()).':'.$e->getLine().')';
		foreach (array(
			isset($chatDbConfig['pass']) ? $chatDbConfig['pass'] : '',
			isset($ajaxChatInstallerConfig['ajaxChatSharedSecret']) ? $ajaxChatInstallerConfig['ajaxChatSharedSecret'] : ''
		) as $sensitiveValue) {
			if (is_string($sensitiveValue) && $sensitiveValue !== '') {
				$errorDetail = str_replace($sensitiveValue, '[REDACTED]', $errorDetail);
			}
		}
		$resultDetail = $errorDetail;
		$resultClass = 'error';
	}

	}

	// Post/Redirect/Get prevents browser refresh from replaying the form.
	try {
		$_SESSION['ajax_chat_installer_token'] = bin2hex(random_bytes(32));
	} catch (Throwable $e) {
		ajaxChatInstallerStop(500, 'Nepodarilo sa obnoviť bezpečnostný token.');
	}
	$_SESSION['ajax_chat_installer_flash'] = array(
		'message' => $resultMessage,
		'detail' => $resultDetail,
		'class' => $resultClass
	);
	header('Location: '.$installerScriptName, true, 303);
	exit;
}

if (isset($_SESSION['ajax_chat_installer_flash']) && is_array($_SESSION['ajax_chat_installer_flash'])) {
	$flash = $_SESSION['ajax_chat_installer_flash'];
	unset($_SESSION['ajax_chat_installer_flash']);
	$resultMessage = isset($flash['message']) ? $flash['message'] : '';
	$resultDetail = isset($flash['detail']) ? $flash['detail'] : '';
	$resultClass = isset($flash['class']) ? $flash['class'] : '';
}

// Disable the public installer URL only on the post-redirect request that
// displays a successful installation result. A normal GET must not rename it.
$installerRenameAttempted = ($resultClass === 'success');
$installerRenamed = false;
if ($installerRenameAttempted) {
	$renamedInstallerPath = __FILE__.'_';
	if (is_file($renamedInstallerPath)) {
		$resultDetail = trim($resultDetail."\n").' Premenovanie sa nevykonalo: install.php_ už existuje.';
	} elseif (@rename(__FILE__, $renamedInstallerPath)) {
		$installerRenamed = true;
	} else {
		$resultDetail = trim($resultDetail."\n").' UPOZORNENIE: install.php sa nepodarilo automaticky premenovať na install.php_. Vykonajte to ručne.';
	}
}

$schemaStatus = ajaxChatInstallerSchemaStatus();
$token = htmlspecialchars($_SESSION['ajax_chat_installer_token'], ENT_QUOTES, 'UTF-8');
echo '<!doctype html><html lang="sk"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
echo '<title>Ajax Chat – inštalátor</title></head><body><main style="max-width:48rem;margin:3rem auto;padding:1.5rem;font:16px/1.5 sans-serif">';
echo '<h1>Ajax Chat – inštalátor</h1>';
echo '<p>Prihlásený administrátor TikiWiki: <strong>'.htmlspecialchars((string)$user, ENT_QUOTES, 'UTF-8').'</strong></p>';
if ($schemaStatus === 'complete') {
	echo '<p role="status" style="padding:1rem;border:1px solid #39834a;"><strong>Chat je nakonfigurovaný.</strong> Databázová schéma je kompletná: všetkých šesť chatových tabuliek existuje. Konfigurácia ciest a Tiki bridge je uložená.</p><p>Chat je pripravený na funkčné testovanie; prihlásenie používateľa a odosielanie správ ešte treba overiť.</p>';
} elseif ($schemaStatus === 'partial') {
	echo '<p role="status" style="padding:1rem;border:1px solid #a33;">Databázová schéma je neúplná. Niektoré chatové tabuľky chýbajú.</p>';
} elseif ($schemaStatus === 'unknown') {
	echo '<p role="status">Stav databázovej schémy sa nepodarilo automaticky overiť.</p>';
}
echo '<p>Inštalátor zistí a uloží cesty k TikiWiki a chatu. Použije už aktívne databázové pripojenie TikiWiki a vytvorí alebo overí šesť vlastných tabuliek s prefixom <code>tiki_ajaxchat_</code>. Detekcia ciest sa vykonáva iba pri spustení inštalátora.</p>';
echo '<dl><dt>Chat filesystem</dt><dd><code>'.htmlspecialchars(rtrim($chatRootReal, '/\\'), ENT_QUOTES, 'UTF-8').'</code></dd>';
echo '<dt>Chat URL</dt><dd><code>'.htmlspecialchars($chatUrlPath, ENT_QUOTES, 'UTF-8').'</code></dd>';
echo '<dt>Tiki filesystem</dt><dd><code>'.htmlspecialchars($tikiRoot, ENT_QUOTES, 'UTF-8').'</code></dd>';
echo '<dt>Tiki URL</dt><dd><code>'.htmlspecialchars($tikiUrlPath, ENT_QUOTES, 'UTF-8').'</code></dd></dl>';
if ($resultMessage !== '') {
	echo '<p role="status" style="padding:1rem;border:1px solid '.($resultClass === 'success' ? '#39834a' : '#a33').';">'.htmlspecialchars($resultMessage, ENT_QUOTES, 'UTF-8').'</p>';
	if ($resultDetail !== '') {
		echo '<pre style="padding:1rem;overflow-wrap:anywhere;white-space:pre-wrap;background:#f5f5f5;border:1px solid #ccc;">'.htmlspecialchars($resultDetail, ENT_QUOTES, 'UTF-8').'</pre>';
	}
}
echo '<form method="post"><input type="hidden" name="csrf_token" value="'.$token.'">';
echo '<button type="submit">'.($schemaStatus === 'complete' ? 'Overiť existujúcu inštaláciu' : 'Vytvoriť / overiť databázové tabuľky').'</button></form>';
if ($installerRenamed) {
	echo '<p><strong>Bezpečnostné opatrenie:</strong> po úspešnej konfigurácii bol inštalátor automaticky premenovaný na <code>install.php_</code>. Pôvodná URL <code>install.php</code> už nie je dostupná.</p>';
} elseif ($installerRenameAttempted) {
	echo '<p><strong>Bezpečnostné opatrenie:</strong> inštalátor sa nepodarilo automaticky premenovať. Ručne premenujte <code>install.php</code> na <code>install.php_</code> a overte, že pôvodná URL už nie je dostupná.</p>';
}

echo '</main></body></html>';
