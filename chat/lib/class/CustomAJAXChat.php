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
		$claims = MZChatAuth::verifyToken($token, $this->getConfig('mzChatSharedSecret'));
		// AJAX Chat reserves ID ranges above this value for private channels/messages.
		if($claims && $claims['uid'] >= (int)$this->getConfig('privateChannelDiff')) {
			return false;
		}
		return $claims;
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

	// Store all existing public channels, including the configured default channel.
	function &getAllChannels() {
		if($this->_allChannels === null) {
			$customChannels = $this->getCustomChannels();
			$defaultChannelFound = false;
			foreach($customChannels as $name => $id) {
				$this->_allChannels[$this->trimChannelName($name)] = $id;
				if($id == $this->getConfig('defaultChannelID')) {
					$defaultChannelFound = true;
				}
			}
			if(!$defaultChannelFound) {
				unset($this->_allChannels[$this->getConfig('defaultChannelName')]);
				$this->_allChannels = array_merge(
					array($this->trimChannelName($this->getConfig('defaultChannelName')) => $this->getConfig('defaultChannelID')),
					$this->_allChannels
				);
			}
		}
		return $this->_allChannels;
	}

	// Load the public channel list from the local channel definition file.
	function getCustomChannels() {
		$channels = null;
		require(AJAX_CHAT_PATH.'lib/data/channels.php');
		return array_flip($channels);
	}
}
