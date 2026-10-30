<?php
/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @copyright (c) Sebastian Tschan
 * @license Modified MIT License
 * @link https://blueimp.net/ajax/
 *
 * Mikrozone authentication adapter:
 * TikiWiki is the only identity and role authority. No chat passwords are used.
 */

class CustomAJAXChat extends AJAXChat {

	// Authenticate only with a signed token issued by the local TikiWiki session.
	function getValidLoginUserData() {
		$claims = $this->verifyTikiAuthToken($this->getRequestVar('authToken'));
		if(!$claims) {
			return null;
		}

		$this->storeTikiAuthClaims($claims, $this->getRequestVar('authToken'));

		return array(
			'userID' => $claims['uid'],
			'userName' => $this->trimUserName($claims['name']),
			'userRole' => $claims['role']
		);
	}

	// Revalidate the chat session against a fresh Tiki token on every AJAX request.
	// Tokens are refreshed by the browser before their 120-second lifetime expires.
	function revalidateUserID() {
		$token = $this->getRequestVar('authToken');
		if(is_string($token) && $token !== '') {
			$claims = $this->verifyTikiAuthToken($token);
			if(!$claims || $claims['uid'] !== (int)$this->getUserID()) {
				return false;
			}

			$oldName = $this->getUserName();
			$oldRole = (int)$this->getUserRole();
			$newName = $this->trimUserName($claims['name']);
			$this->setUserName($newName);
			$this->setUserRole($claims['role']);
			$this->storeTikiAuthClaims($claims, $token);

			if($oldName !== $newName || $oldRole !== $claims['role']) {
				$this->updateOnlineList();
			}
			return true;
		}

		$expires = (int)$this->getSessionVar('TikiAuthExpires');
		return ($expires > time());
	}

	function verifyTikiAuthToken($token) {
		return MZChatAuth::verifyToken($token, $this->getConfig('mzChatSharedSecret'));
	}

	function storeTikiAuthClaims(array $claims, $token) {
		$this->setSessionVar('TikiAuthExpires', $claims['exp']);
		$this->setSessionVar('TikiAuthUserID', $claims['uid']);
		$this->setSessionVar('TikiAuthLogin', $claims['login']);
		$this->setSessionVar('TikiAuthName', $claims['name']);
		$this->setSessionVar('TikiAuthRole', $claims['role']);
		$this->setSessionVar('TikiAuthToken', $token);
	}

	// Every authenticated Tiki user can access the configured public channels.
	// Private channels remain protected by AJAX Chat's invitation checks.
	function &getChannels() {
		if($this->_channels === null) {
			$this->_channels = array();
			$allChannels = $this->getAllChannels();
			$limit = $this->getConfig('limitChannelList');

			foreach($allChannels as $channelName => $channelID) {
				if($limit && !in_array($channelID, $limit)) {
					continue;
				}
				$this->_channels[$channelName] = $channelID;
			}
		}
		return $this->_channels;
	}

	// Load the public channel list from the local channel definition file.
	function getCustomChannels() {
		$channels = null;
		require(AJAX_CHAT_PATH.'lib/data/channels.php');
		return array_flip($channels);
	}
}
