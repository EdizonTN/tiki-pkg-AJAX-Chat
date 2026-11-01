<?php
$sqlFile = __DIR__.'/../chat/chat.sql';
$sql = file_get_contents($sqlFile);
if($sql === false) {
	fwrite(STDERR, "Cannot read chat.sql\n");
	exit(1);
}
if(preg_match('/\\bDROP\\s+TABLE\\b/i', $sql)) {
	fwrite(STDERR, "Unsafe DROP TABLE statement found\n");
	exit(1);
}
$count = preg_match_all('/\\bCREATE\\s+TABLE\\s+IF\\s+NOT\\s+EXISTS\\b/i', $sql);
if($count !== 6) {
	fwrite(STDERR, "Expected 6 CREATE TABLE IF NOT EXISTS statements, found ".$count."\n");
	exit(1);
}
if(strpos($sql, 'CREATE TABLE IF NOT EXISTS mz_chat_room_members') === false ||
	strpos($sql, 'userName VARCHAR(48) NOT NULL') === false) {
	fwrite(STDERR, "Private-room membership schema is incomplete\n");
	exit(1);
}
echo "Chat schema checks passed.\n";
