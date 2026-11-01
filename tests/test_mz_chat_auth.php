<?php
require_once(__DIR__.'/../chat/lib/class/MZChatAuth.php');

function assert_mz_chat($condition, $message) {
	if(!$condition) {
		fwrite(STDERR, "FAIL: ".$message."\n");
		exit(1);
	}
	echo "PASS: ".$message."\n";
}

$secret = str_repeat('a', 64);
$now = time();
$claims = array(
	'uid' => 42,
	'login' => 'test-user',
	'name' => 'test-user',
	'role' => 1,
	'iat' => $now,
	'exp' => $now + 120,
	'nonce' => bin2hex(random_bytes(16))
);

$token = MZChatAuth::createToken($claims, $secret);
$decoded = MZChatAuth::verifyToken($token, $secret);
assert_mz_chat($decoded && $decoded['uid'] === 42 && $decoded['role'] === 1, 'valid token is accepted');
assert_mz_chat(MZChatAuth::verifyToken($token, str_repeat('b', 64)) === false, 'wrong secret is rejected');

$tampered = substr_replace($token, ($token[strlen($token) - 1] === '0' ? '1' : '0'), -1);
assert_mz_chat(MZChatAuth::verifyToken($tampered, $secret) === false, 'tampered signature is rejected');

$expiredClaims = $claims;
$expiredClaims['iat'] = $now - 180;
$expiredClaims['exp'] = $now - 1;
$expired = MZChatAuth::createToken($expiredClaims, $secret);
assert_mz_chat(MZChatAuth::verifyToken($expired, $secret) === false, 'expired token is rejected');

$invalidRoleClaims = $claims;
$invalidRoleClaims['role'] = 99;
$invalidRole = MZChatAuth::createToken($invalidRoleClaims, $secret);
assert_mz_chat(MZChatAuth::verifyToken($invalidRole, $secret) === false, 'unknown role is rejected');

$longLivedClaims = $claims;
$longLivedClaims['exp'] = $now + 600;
$longLived = MZChatAuth::createToken($longLivedClaims, $secret);
assert_mz_chat(MZChatAuth::verifyToken($longLived, $secret) === false, 'overlong token lifetime is rejected');

echo "All MZChatAuth tests passed.\n";
