<?php
/*
 * AJAX Chat <-> TikiWiki signed authentication tokens.
 * The token is short-lived and signed with a shared server-side secret.
 */

class AjaxChatAuth {

	public static function createToken(array $claims, $secret) {
		if (!is_string($secret) || strlen($secret) < 32) {
			throw new RuntimeException('MZ Chat shared secret must contain at least 32 characters.');
		}
		$json = json_encode($claims, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		if ($json === false) {
			throw new RuntimeException('Unable to encode MZ Chat authentication claims.');
		}
		$payload = self::base64UrlEncode($json);
		$signature = hash_hmac('sha256', $payload, $secret);
		return $payload.'.'.$signature;
	}

	public static function verifyToken($token, $secret) {
		if (!is_string($secret) || strlen($secret) < 32 || !is_string($token) || strlen($token) > 4096) {
			return false;
		}
		$parts = explode('.', $token);
		if (count($parts) !== 2 || !preg_match('/^[a-f0-9]{64}$/', $parts[1])) {
			return false;
		}
		$expected = hash_hmac('sha256', $parts[0], $secret);
		if (!hash_equals($expected, $parts[1])) {
			return false;
		}
		$json = self::base64UrlDecode($parts[0]);
		if ($json === false) {
			return false;
		}
		$claims = json_decode($json, true);
		if (!is_array($claims)) {
			return false;
		}

		$now = time();
		if (
			!isset($claims['uid'], $claims['login'], $claims['name'], $claims['role'], $claims['iat'], $claims['exp'], $claims['nonce']) ||
			!is_numeric($claims['uid']) || (int)$claims['uid'] < 1 ||
			!is_string($claims['login']) || $claims['login'] === '' ||
			!is_string($claims['name']) || trim($claims['name']) === '' ||
			!in_array((int)$claims['role'], array(1, 2, 3), true) ||
			!is_numeric($claims['iat']) || !is_numeric($claims['exp']) ||
			(int)$claims['iat'] > ($now + 30) ||
			(int)$claims['exp'] <= $now ||
			((int)$claims['exp'] - (int)$claims['iat']) > 180 ||
			!is_string($claims['nonce']) || strlen($claims['nonce']) < 16
		) {
			return false;
		}

		$claims['uid'] = (int)$claims['uid'];
		$claims['role'] = (int)$claims['role'];
		$claims['iat'] = (int)$claims['iat'];
		$claims['exp'] = (int)$claims['exp'];
		return $claims;
	}

	private static function base64UrlEncode($value) {
		return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
	}

	private static function base64UrlDecode($value) {
		if (!preg_match('/^[A-Za-z0-9_-]+$/', $value)) {
			return false;
		}
		$padding = strlen($value) % 4;
		if ($padding) {
			$value .= str_repeat('=', 4 - $padding);
		}
		return base64_decode(strtr($value, '-_', '+/'), true);
	}
}
