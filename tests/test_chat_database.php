<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = (int)(getenv('DB_PORT') ?: 3306);
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'root';
$database = getenv('DB_NAME') ?: 'mz_chat_ci';

$db = new mysqli($host, $user, $password, $database, $port);
$db->set_charset('utf8mb4');

$sql = file_get_contents(__DIR__.'/../chat/chat.sql');
if($sql === false) {
	throw new RuntimeException('Cannot read chat/chat.sql');
}
$statements = preg_split('/;\s*(?:\r?\n|$)/', trim($sql));
foreach($statements as $statement) {
	$statement = trim($statement);
	if($statement !== '') {
		$db->query($statement);
	}
}

$db->query("INSERT INTO mz_chat_rooms (ownerID, name, dateTime) VALUES (42, 'Workshop', NOW())");
$roomID = (int)$db->insert_id;
if($roomID < 1) {
	throw new RuntimeException('Room insert did not return an ID');
}

$db->query("INSERT INTO mz_chat_room_members (roomID, userID, userName, dateTime)
	VALUES ($roomID, 77, 'member77', NOW())
	ON DUPLICATE KEY UPDATE userName = VALUES(userName), dateTime = NOW()");

$owner = $db->query("SELECT id FROM mz_chat_rooms WHERE ownerID = 42 AND name = 'Workshop'")->fetch_assoc();
$member = $db->query("SELECT r.id
	FROM mz_chat_rooms r
	LEFT JOIN mz_chat_room_members m ON m.roomID = r.id AND m.userID = 77
	WHERE r.ownerID = 77 OR m.userID IS NOT NULL")->fetch_assoc();
$outsider = $db->query("SELECT r.id
	FROM mz_chat_rooms r
	LEFT JOIN mz_chat_room_members m ON m.roomID = r.id AND m.userID = 99
	WHERE r.ownerID = 99 OR m.userID IS NOT NULL")->fetch_assoc();
$offlineLookup = $db->query("SELECT userID FROM mz_chat_room_members
	WHERE roomID = $roomID AND userName = 'member77'")->fetch_assoc();

if(!$owner || (int)$owner['id'] !== $roomID) {
	throw new RuntimeException('Owner room lookup failed');
}
if(!$member || (int)$member['id'] !== $roomID) {
	throw new RuntimeException('Member room lookup failed');
}
if($outsider) {
	throw new RuntimeException('A non-member was returned by the membership query');
}
if(!$offlineLookup || (int)$offlineLookup['userID'] !== 77) {
	throw new RuntimeException('Offline member lookup by stored login failed');
}

$db->query("DELETE FROM mz_chat_room_members WHERE roomID = $roomID AND userID = 77");
$revoked = $db->query("SELECT userID FROM mz_chat_room_members WHERE roomID = $roomID AND userID = 77")->fetch_assoc();
if($revoked) {
	throw new RuntimeException('Membership removal failed');
}
$db->query("DELETE FROM mz_chat_rooms WHERE id = $roomID");

echo "MariaDB private-room schema and query checks passed.\n";
