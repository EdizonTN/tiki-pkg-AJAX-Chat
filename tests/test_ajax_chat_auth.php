<?php
require_once(__DIR__.'/../src/lib/class/AjaxChatAuth.php');

function assert_ajax_chat($condition, $message) {
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

$token = AjaxChatAuth::createToken($claims, $secret);
$decoded = AjaxChatAuth::verifyToken($token, $secret);
assert_ajax_chat($decoded && $decoded['uid'] === 42 && $decoded['role'] === 1, 'valid token is accepted');
assert_ajax_chat(AjaxChatAuth::verifyToken($token, str_repeat('b', 64)) === false, 'wrong secret is rejected');

$tampered = substr_replace($token, ($token[strlen($token) - 1] === '0' ? '1' : '0'), -1);
assert_ajax_chat(AjaxChatAuth::verifyToken($tampered, $secret) === false, 'tampered signature is rejected');

$expiredClaims = $claims;
$expiredClaims['iat'] = $now - 180;
$expiredClaims['exp'] = $now - 1;
$expired = AjaxChatAuth::createToken($expiredClaims, $secret);
assert_ajax_chat(AjaxChatAuth::verifyToken($expired, $secret) === false, 'expired token is rejected');

$invalidRoleClaims = $claims;
$invalidRoleClaims['role'] = 99;
$invalidRole = AjaxChatAuth::createToken($invalidRoleClaims, $secret);
assert_ajax_chat(AjaxChatAuth::verifyToken($invalidRole, $secret) === false, 'unknown role is rejected');

$longLivedClaims = $claims;
$longLivedClaims['exp'] = $now + 600;
$longLived = AjaxChatAuth::createToken($longLivedClaims, $secret);
assert_ajax_chat(AjaxChatAuth::verifyToken($longLived, $secret) === false, 'overlong token lifetime is rejected');

echo "All AjaxChatAuth tests passed.\n";
