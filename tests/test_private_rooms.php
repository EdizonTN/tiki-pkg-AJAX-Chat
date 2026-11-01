<?php
define('AJAX_CHAT_USER', 1);
define('AJAX_CHAT_ADMIN', 3);
define('AJAX_CHAT_PATH', dirname(__DIR__).'/chat/');

class TestResult {
	private $rows;
	function __construct($rows) { $this->rows = $rows; }
	function error() { return false; }
	function getError() { return ''; }
	function fetch() { return count($this->rows) ? array_shift($this->rows) : false; }
	function free() {}
}

class TestRoomDatabase {
	public $userID;
	private $members;
	function __construct($userID, $members) { $this->userID = $userID; $this->members = $members; }
	function makeSafe($value) { return "'".addslashes((string)$value)."'"; }
	function sqlQuery($sql) {
		if(strpos($sql, 'SELECT id FROM mz_chat_rooms') === 0) {
			return new TestResult(array(array('id'=>1)));
		}
		if(strpos($sql, 'SELECT r.id') !== false) {
			return new TestResult(($this->userID === 42 || in_array($this->userID, $this->members, true)) ? array(array('id'=>1)) : array());
		}
		if(strpos($sql, 'SELECT id, ownerID, name') !== false) {
			return new TestResult(array(array('id'=>1, 'ownerID'=>42, 'name'=>'Workshop')));
		}
		if(strpos($sql, 'SELECT id') !== false && strpos($sql, "WHERE name = 'Workshop'") !== false) {
			return new TestResult(array(array('id'=>1)));
		}
		if(strpos($sql, 'SELECT userID') !== false && strpos($sql, "roomID = '1'") !== false || strpos($sql, 'roomID = 1') !== false) {
			$allowed = in_array($this->userID, $this->members, true);
			return new TestResult($allowed ? array(array('userID'=>$this->userID)) : array());
		}
		return new TestResult(array());
	}
}

class AJAXChat {
	protected $_channels = null;
	protected $_allChannels = null;
	protected $_invitations = null;
	protected $db;
	protected $config;
	protected $userID;
	protected $userRole = AJAX_CHAT_USER;
	protected $userName = 'test-user';
	function __construct($userID, $members, $userRole = AJAX_CHAT_USER) {
		$this->userID = $userID;
		$this->userRole = $userRole;
		$this->db = new TestRoomDatabase($userID, $members);
		$this->config = array(
			'dbTableNames'=>array('rooms'=>'mz_chat_rooms','roomMembers'=>'mz_chat_room_members'),
			'roomChannelDiff'=>1500000000,
			'privateChannelDiff'=>500000000,
			'privateMessageDiff'=>1000000000,
			'privateRoomPrefix'=>'~',
			'privateRoomSuffix'=>'~',
			'privateChannelPrefix'=>'[',
			'privateChannelSuffix'=>']',
			'defaultChannelName'=>'Public',
			'defaultChannelID'=>0,
			'limitChannelList'=>null
		);
	}
	function getConfig($key) { return isset($this->config[$key]) ? $this->config[$key] : null; }
	function getDataBaseTable($key) { return $this->config['dbTableNames'][$key]; }
	function getUserID() { return $this->userID; }
	function getUserRole() { return $this->userRole; }
	function getUserName() { return $this->userName; }
	function getPrivateChannelID($userID = null) { return 500000000 + (int)($userID === null ? $this->userID : $userID); }
	function getPrivateChannelName($userName = null) { return '['.($userName === null ? $this->userName : $userName).']'; }
	function getNameFromID($id) { return null; }
	function getIDFromName($name) { return null; }
	function trimChannelName($name) { return trim($name); }
	function trimUserName($name) { return trim($name); }
	function stringLength($value) { return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value); }
	function subString($value, $start, $length = null) { if(function_exists('mb_substr')) return $length === null ? mb_substr($value, $start, null, 'UTF-8') : mb_substr($value, $start, $length, 'UTF-8'); return $length === null ? substr($value, $start) : substr($value, $start, $length); }
	function getCustomChannels() { return array('Public'=>1); }
	function getInvitations() { $this->_invitations = array(); return $this->_invitations; }
	function parseCustomCommands($text, $parts) { return false; }
	function insertParsedMessageInvite($parts) {}
	function insertParsedMessageUninvite($parts) {}
}

require_once(AJAX_CHAT_PATH.'lib/class/CustomAJAXChat.php');

class TestableCustomAJAXChat extends CustomAJAXChat {
	function setAdminStealth($enabled) {
		$this->_mzBridgeConfig['adminStealthInPrivateRooms'] = (bool)$enabled;
	}
}



function check_room($condition, $message) {
	if(!$condition) {
		fwrite(STDERR, "FAIL: ".$message."\n");
		exit(1);
	}
	echo "PASS: ".$message."\n";
}

$roomChannelID = 1500000001;
$owner = new CustomAJAXChat(42, array(77));
check_room($owner->getChannelIDFromChannelName('~Workshop~') === $roomChannelID, 'owner can resolve the room channel');
check_room($owner->getChannelNameFromChannelID($roomChannelID) === '~Workshop~', 'owner can read the room name');
check_room(in_array($roomChannelID, $owner->getInvitations(), true), 'owner sees the room in the accessible channel list');

$member = new CustomAJAXChat(77, array(77));
check_room($member->getChannelIDFromChannelName('~Workshop~') === $roomChannelID, 'invited member can resolve the room channel');
check_room($member->getChannelNameFromChannelID($roomChannelID) === '~Workshop~', 'invited member can read the room name');
check_room(in_array($roomChannelID, $member->getInvitations(), true), 'invited member sees the room in the accessible channel list');
$memberChannels = $member->getChannels();
check_room(isset($memberChannels['~Workshop~']) && $memberChannels['~Workshop~'] === $roomChannelID, 'invited member sees the room in the channel selector');

$outsider = new CustomAJAXChat(99, array(77));
check_room($outsider->getChannelIDFromChannelName('~Workshop~') === null, 'non-member cannot resolve the private room');
check_room($outsider->getChannelNameFromChannelID($roomChannelID) === null, 'non-member cannot read the private room name');
check_room(!in_array($roomChannelID, $outsider->getInvitations(), true), 'non-member does not see the private room in their channel list');

$admin = new CustomAJAXChat(100, array(77), AJAX_CHAT_ADMIN);
check_room($admin->getChannelIDFromChannelName('~Workshop~') === $roomChannelID, 'administrator can resolve a private room without an invitation');
check_room($admin->getChannelNameFromChannelID($roomChannelID) === '~Workshop~', 'administrator can read a private room name without membership');
check_room(in_array($roomChannelID, $admin->getInvitations(), true), 'administrator sees all private rooms in the channel list');

$stealthAdmin = new TestableCustomAJAXChat(100, array(77), AJAX_CHAT_ADMIN);
$stealthAdmin->setAdminStealth(true);
check_room($stealthAdmin->isStealthAdminInPrivateRoom($roomChannelID), 'enabled stealth mode hides a non-member administrator in a private room');

$visibleOwner = new TestableCustomAJAXChat(42, array(77), AJAX_CHAT_ADMIN);
$visibleOwner->setAdminStealth(true);
check_room(!$visibleOwner->isStealthAdminInPrivateRoom($roomChannelID), 'a room-owning administrator is not hidden');

$visibleMember = new TestableCustomAJAXChat(77, array(77), AJAX_CHAT_ADMIN);
$visibleMember->setAdminStealth(true);
check_room(!$visibleMember->isStealthAdminInPrivateRoom($roomChannelID), 'an administrator who is a room member is not hidden');

$disabledStealth = new TestableCustomAJAXChat(100, array(77), AJAX_CHAT_ADMIN);
check_room(!$disabledStealth->isStealthAdminInPrivateRoom($roomChannelID), 'administrator stealth mode is disabled by default');

echo "Private-room access checks passed.\n";
