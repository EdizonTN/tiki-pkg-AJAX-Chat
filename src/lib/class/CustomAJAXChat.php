<?php
/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @copyright (c) Sebastian Tschan
 * @license Modified MIT License
 * @link https://blueimp.net/ajax/
 *
 * Tikiwiki adapter:
 * - TikiWiki is the only identity and role authority.
 * - Multiple named private rooms are stored separately from public channels.
 */

class CustomAJAXChat extends AJAXChat {

	protected $_ajaxChatHiddenAdminPresence = null;
	protected $_ajaxChatBridgeConfig = array(
		'moderatorGroups' => array(),
		'adminStealthInPrivateRooms' => false
	);

	function initCustomConfig() {
        // Tiki preferences are conveyed in the short-lived signed auth token.
        // No separate Bridge-config.php file is used.
        $this->_mzBridgeConfig = array(
            'moderatorGroups' => array(),
            'adminStealthInPrivateRooms' => false
        );
    }

	function databaseError($context, $result) {
		$details = is_object($result) && method_exists($result, 'getError') ? $result->getError() : 'Unknown database error';
		error_log('Ajax Chat database error in '.$context.': '.$details);
		http_response_code(500);
		exit('Chat database error.');
	}

	function getOnlineUsersData($channelIDs = null, $key = null, $value = null) {
		$onlineUsersData = parent::getOnlineUsersData(null, null, null);
		if (!empty($this->_mzBridgeConfig['adminStealthInPrivateRooms']) && $onlineUsersData) {
			if ($this->_mzHiddenAdminPresence === null) {
				$roomDiff = (int)$this->getConfig('roomChannelDiff');
			$sql = 'SELECT o.userID, o.channel
				FROM '.$this->getDataBaseTable('online').' o
				INNER JOIN '.$this->getDataBaseTable('rooms').' r
					ON o.channel = '.$roomDiff.' + r.id
				LEFT JOIN '.$this->getDataBaseTable('roomMembers').' m
					ON m.roomID = r.id AND m.userID = o.userID
				WHERE o.userRole = '.$this->db->makeSafe(AJAX_CHAT_ADMIN).'
					AND r.ownerID <> o.userID
					AND m.userID IS NULL';
			$result = $this->db->sqlQuery($sql);
			if ($result->error()) {
				$this->databaseError(__METHOD__, $result);
			}
			$hidden = array();
			while ($row = $result->fetch()) {
				$hidden[(int)$row['channel'].':'.(int)$row['userID']] = true;
			}
				$result->free();
				$this->_mzHiddenAdminPresence = $hidden;
			}
			$hidden = $this->_mzHiddenAdminPresence;
			$onlineUsersData = array_values(array_filter($onlineUsersData, function($userData) use ($hidden) {
				$key = (int)$userData['channel'].':'.(int)$userData['userID'];
				return !isset($hidden[$key]);
			}));
		}

		if ($channelIDs || $key) {
			$filtered = array();
			foreach ($onlineUsersData as $userData) {
				if ($channelIDs && !in_array($userData['channel'], $channelIDs)) {
					continue;
				}
				if ($key) {
					if (!isset($userData[$key])) {
						return $filtered;
					}
					if ($value !== null) {
						if ($userData[$key] == $value) {
							$filtered[] = $userData;
						}
					} else {
						$filtered[] = $userData[$key];
					}
				} else {
					$filtered[] = $userData;
				}
			}
			return $filtered;
		}
		return $onlineUsersData;
	}

	function isStealthAdminInPrivateRoom($channelID) {
		if (empty($this->_mzBridgeConfig['adminStealthInPrivateRooms'])
			|| $this->getUserRole() != AJAX_CHAT_ADMIN) {
			return false;
		}
		$room = $this->getPrivateRoomByChannelID($channelID, false);
		if (!$room || (int)$room['ownerID'] === (int)$this->getUserID()) {
			return false;
		}
		return !$this->isRoomMember($room['id'], $this->getUserID());
	}

	function switchChannel($channelName) {
		$channelID = $this->getChannelIDFromChannelName($channelName);
		if ($channelID !== null && $this->getChannel() == $channelID) {
			return;
		}
		if (!$this->validateChannel($channelID)) {
			$this->insertChatBotMessage(
				$this->getPrivateMessageID(),
				'/error InvalidChannelName '.$channelName
			);
			return;
		}

		$oldChannel = $this->getChannel();
		$hideOldPresence = $this->isStealthAdminInPrivateRoom($oldChannel);
		$hideNewPresence = $this->isStealthAdminInPrivateRoom($channelID);
		$this->setChannel($channelID);
		$this->updateOnlineList();

		if (!$hideOldPresence) {
			$this->insertChatBotMessage($oldChannel, '/channelLeave '.$this->getUserName(), null, 1);
		}
		if (!$hideNewPresence) {
			$this->insertChatBotMessage($this->getChannel(), '/channelEnter '.$this->getUserName(), null, 1);
		}
		$this->addInfoMessage($channelName, 'channelSwitch');
		$this->addInfoMessage($channelID, 'channelID');
		$this->_requestVars['lastID'] = 0;
	}

	function chatViewLogout($type) {
		$hidePresence = $this->isStealthAdminInPrivateRoom($this->getChannel());
		$this->removeFromOnlineList();
		if (!$hidePresence) {
			if($type !== null) {
				$type = ' '.$type;
			}
			$this->insertChatBotMessage(
				$this->getChannel(),
				'/logout '.$this->getUserName().$type,
				null,
				1
			);
		}
	}

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
			if($oldName !== $newName) {
				$sql = 'UPDATE '.$this->getDataBaseTable('roomMembers').'
					SET userName = '.$this->db->makeSafe($newName).'
					WHERE userID = '.$this->db->makeSafe($this->getUserID());
				$result = $this->db->sqlQuery($sql);
				if($result->error()) {
					$this->databaseError(__METHOD__, $result);
				}
			}
			if($oldName !== $newName || $oldRole !== $claims['role']) {
				$this->updateOnlineList();
			}
			return true;
		}
		return ((int)$this->getSessionVar('TikiAuthExpires') > time());
	}

	function verifyTikiAuthToken($token) {
		$claims = AjaxChatAuth::verifyToken($token, $this->getConfig('ajaxChatSharedSecret'));
		// AJAX Chat reserves ID ranges above this value for private channels/messages.
		if($claims && $claims['uid'] >= (int)$this->getConfig('privateChannelDiff')) {
			return false;
		}
        if ($claims) {
            $this->_mzBridgeConfig['adminStealthInPrivateRooms'] =
                !empty($claims['adminStealth']) && (int)$claims['role'] === AJAX_CHAT_ADMIN;
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

	// All configured public channels are available to authenticated Tiki users.
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
			// Add private channels and persistent private rooms the current user may access.
			foreach($this->getInvitations() as $privateChannelID) {
				$privateChannelName = $this->getChannelNameFromChannelID($privateChannelID);
				if($privateChannelName !== null && !array_key_exists($privateChannelName, $this->_channels)) {
					$this->_channels[$privateChannelName] = $privateChannelID;
				}
			}
		}
		return $this->_channels;
	}

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

	function getCustomChannels() {
		$channels = null;
		require(AJAX_CHAT_PATH.'lib/data/channels.php');
		return array_flip($channels);
	}

	// Extend the upstream invitations with persistent private-room memberships.
	function getInvitations() {
		if($this->_invitations === null) {
			$this->_invitations = parent::getInvitations();
			if ($this->getUserRole() == AJAX_CHAT_ADMIN) {
				$sql = 'SELECT id FROM '.$this->getDataBaseTable('rooms');
			} else {
				$sql = 'SELECT r.id
					FROM '.$this->getDataBaseTable('rooms').' r
					LEFT JOIN '.$this->getDataBaseTable('roomMembers').' m
						ON m.roomID = r.id AND m.userID = '.$this->db->makeSafe($this->getUserID()).'
					WHERE r.ownerID = '.$this->db->makeSafe($this->getUserID()).'
						OR m.userID IS NOT NULL';
			}
			$result = $this->db->sqlQuery($sql);
			if($result->error()) {
				$this->databaseError(__METHOD__, $result);
			}
			while($row = $result->fetch()) {
				$channelID = (int)$this->getConfig('roomChannelDiff') + (int)$row['id'];
				if(!in_array($channelID, $this->_invitations)) {
					$this->_invitations[] = $channelID;
				}
			}
			$result->free();
		}
		return $this->_invitations;
	}

	function getPrivateRoomByID($roomID, $requireAccess = true) {
		$roomID = (int)$roomID;
		if($roomID < 1) {
			return null;
		}
		$sql = 'SELECT id, ownerID, name
			FROM '.$this->getDataBaseTable('rooms').'
			WHERE id = '.$this->db->makeSafe($roomID).'
			LIMIT 1';
		$result = $this->db->sqlQuery($sql);
		if($result->error()) {
			$this->databaseError(__METHOD__, $result);
		}
		$room = $result->fetch();
		$result->free();
		if(!$room) {
			return null;
		}
		if($requireAccess
			&& (int)$room['ownerID'] !== (int)$this->getUserID()
			&& $this->getUserRole() != AJAX_CHAT_ADMIN) {
			$sql = 'SELECT userID
				FROM '.$this->getDataBaseTable('roomMembers').'
				WHERE roomID = '.$this->db->makeSafe($roomID).'
					AND userID = '.$this->db->makeSafe($this->getUserID()).'
				LIMIT 1';
			$result = $this->db->sqlQuery($sql);
			if($result->error()) {
				$this->databaseError(__METHOD__, $result);
			}
			$member = $result->fetch();
			$result->free();
			if(!$member) {
				return null;
			}
		}
		return $room;
	}

	function getPrivateRoomByChannelID($channelID, $requireAccess = true) {
		$diff = (int)$this->getConfig('roomChannelDiff');
		$channelID = (int)$channelID;
		if($channelID <= $diff) {
			return null;
		}
		return $this->getPrivateRoomByID($channelID - $diff, $requireAccess);
	}

	function getPrivateRoomByName($name, $requireAccess = true) {
		$name = $this->trimChannelName($name);
		if($name === '') {
			return null;
		}
		$sql = 'SELECT id
			FROM '.$this->getDataBaseTable('rooms').'
			WHERE name = '.$this->db->makeSafe($name).'
			LIMIT 1';
		$result = $this->db->sqlQuery($sql);
		if($result->error()) {
			$this->databaseError(__METHOD__, $result);
		}
		$row = $result->fetch();
		$result->free();
		return $row ? $this->getPrivateRoomByID($row['id'], $requireAccess) : null;
	}

	function getPrivateRoomChannelName($name) {
		return $this->getConfig('privateRoomPrefix').$name.$this->getConfig('privateRoomSuffix');
	}

	function getChannelIDFromChannelName($channelName) {
		if(!$channelName) {
			return null;
		}
		$channels = $this->getAllChannels();
		if(array_key_exists($channelName, $channels)) {
			return $channels[$channelName];
		}
		if($channelName == $this->getPrivateChannelName()) {
			return $this->getPrivateChannelID();
		}

		$length = $this->stringLength($channelName);
		$prefix = $this->getConfig('privateRoomPrefix');
		$suffix = $this->getConfig('privateRoomSuffix');
		$prefixLength = $this->stringLength($prefix);
		$suffixLength = $this->stringLength($suffix);
		if($length > ($prefixLength + $suffixLength)
			&& $this->subString($channelName, 0, $prefixLength) == $prefix
			&& $this->subString($channelName, $length - $suffixLength) == $suffix) {
			$roomName = $this->subString($channelName, $prefixLength, $length - ($prefixLength + $suffixLength));
			$room = $this->getPrivateRoomByName($roomName, true);
			if($room) {
				return (int)$this->getConfig('roomChannelDiff') + (int)$room['id'];
			}
			return null;
		}

		// Retain the upstream personal private-channel syntax: [TikiLogin].
		$privatePrefix = $this->getConfig('privateChannelPrefix');
		$privateSuffix = $this->getConfig('privateChannelSuffix');
		$privatePrefixLength = $this->stringLength($privatePrefix);
		$privateSuffixLength = $this->stringLength($privateSuffix);
		if($length > ($privatePrefixLength + $privateSuffixLength)
			&& $this->subString($channelName, 0, $privatePrefixLength) == $privatePrefix
			&& $this->subString($channelName, $length - $privateSuffixLength) == $privateSuffix) {
			$userName = $this->subString($channelName, $privatePrefixLength, $length - ($privatePrefixLength + $privateSuffixLength));
			$userID = $this->getIDFromName($userName);
			if($userID !== null) {
				return $this->getPrivateChannelID($userID);
			}
		}
		return null;
	}

	function getChannelNameFromChannelID($channelID) {
		foreach($this->getAllChannels() as $name => $id) {
			if($id == $channelID) {
				return $name;
			}
		}
		if((int)$channelID == (int)$this->getPrivateChannelID()) {
			return $this->getPrivateChannelName();
		}
		$room = $this->getPrivateRoomByChannelID($channelID, $this->getUserRole() != AJAX_CHAT_ADMIN);
		if($room) {
			return $this->getPrivateRoomChannelName($room['name']);
		}
		$userName = $this->getNameFromID($channelID - $this->getConfig('privateChannelDiff'));
		if($userName === null) {
			return null;
		}
		return $this->getPrivateChannelName($userName);
	}

	function parseCustomCommands($text, $textParts) {
		if(isset($textParts[0]) && $textParts[0] === '/room') {
			$this->insertParsedMessageRoom($textParts);
			return true;
		}
		return parent::parseCustomCommands($text, $textParts);
	}

	function insertParsedMessageRoom($textParts) {
		$action = isset($textParts[1]) ? strtolower($textParts[1]) : 'help';
		switch($action) {
			case 'create':
				if(!$this->getConfig('allowPrivateRooms') || $this->getUserRole() < AJAX_CHAT_USER) {
					$this->insertChatBotMessage($this->getPrivateMessageID(), $this->getLang('privateRoomCreateDenied'));
					return;
				}
				$rawName = implode('_', array_slice($textParts, 2));
				$name = $this->trimChannelName($rawName);
				$maxLength = (int)$this->getConfig('privateRoomMaxNameLength');
				if($name === '' || $this->stringLength($name) > $maxLength) {
					$this->insertChatBotMessage($this->getPrivateMessageID(), sprintf($this->getLang('privateRoomNameInvalid'), $maxLength));
					return;
				}
				if($this->getPrivateRoomByName($name, false) || array_key_exists($name, $this->getAllChannels()) || array_key_exists($this->getPrivateRoomChannelName($name), $this->getAllChannels())) {
					$this->insertChatBotMessage($this->getPrivateMessageID(), $this->getLang('privateRoomNameExists'));
					return;
				}
				$sql = 'SELECT COUNT(*) AS roomCount
					FROM '.$this->getDataBaseTable('rooms').'
					WHERE ownerID = '.$this->db->makeSafe($this->getUserID());
				$result = $this->db->sqlQuery($sql);
				if($result->error()) {
					$this->databaseError(__METHOD__, $result);
				}
				$countRow = $result->fetch();
				$result->free();
				if((int)$countRow['roomCount'] >= (int)$this->getConfig('maxPrivateRoomsPerUser')) {
					$this->insertChatBotMessage($this->getPrivateMessageID(), $this->getLang('privateRoomLimitReached'));
					return;
				}
				$sql = 'INSERT INTO '.$this->getDataBaseTable('rooms').'
					(ownerID, name, dateTime)
					VALUES (
						'.$this->db->makeSafe($this->getUserID()).',
						'.$this->db->makeSafe($name).',
						NOW()
					)';
				$result = $this->db->sqlQuery($sql);
				if($result->error()) {
					$this->insertChatBotMessage($this->getPrivateMessageID(), $this->getLang('privateRoomCreateFailed'));
					return;
				}
				$roomID = (int)$this->db->getLastInsertedID();
				if($roomID < 1) {
					$this->insertChatBotMessage($this->getPrivateMessageID(), $this->getLang('privateRoomCreateFailed'));
					return;
				}
				$channelID = (int)$this->getConfig('roomChannelDiff') + $roomID;
				$this->addRoomToInvitationCache($channelID);
				$channelName = $this->getPrivateRoomChannelName($name);
				$this->insertChatBotMessage($this->getPrivateMessageID(), sprintf($this->getLang('privateRoomCreated'), $channelName));
				$this->switchChannel($channelName);
				break;

			case 'list':
				if(isset($textParts[2]) && strtolower($textParts[2]) === 'all' && $this->getUserRole() == AJAX_CHAT_ADMIN) {
					$sql = 'SELECT id, ownerID, name
						FROM '.$this->getDataBaseTable('rooms').'
						ORDER BY id DESC
						LIMIT 50';
					$result = $this->db->sqlQuery($sql);
					if($result->error()) {
						$this->databaseError(__METHOD__, $result);
					}
					$rooms = array();
					while($row = $result->fetch()) {
						$rooms[] = $row;
					}
					$result->free();
					if(!$rooms) {
						$this->insertChatBotMessage($this->getPrivateMessageID(), $this->getLang('privateRoomAdminListEmpty'));
					} else {
						$this->insertChatBotMessage($this->getPrivateMessageID(), $this->getLang('privateRoomAdminListHeader'));
						foreach($rooms as $roomRow) {
							$ownerName = $this->getNameFromID($roomRow['ownerID']);
							$ownerLabel = $ownerName !== null ? $ownerName : '#'.$roomRow['ownerID'];
							$this->insertChatBotMessage($this->getPrivateMessageID(), sprintf($this->getLang('privateRoomAdminListOwner'), $this->getPrivateRoomChannelName($roomRow['name']), $ownerLabel));
						}
					}
				} else {
					$this->insertParsedMessageList($textParts);
				}
				break;

			case 'leave':
				$this->leavePrivateRoom();
				break;

			case 'delete':
				$rawName = implode('_', array_slice($textParts, 2));
				if($rawName !== '') {
					$room = $this->getPrivateRoomByName($rawName, false);
				} else {
					$room = $this->getPrivateRoomByChannelID($this->getChannel(), false);
				}
				if(!$room) {
					$this->insertChatBotMessage($this->getPrivateMessageID(), $this->getLang('privateRoomNotFound'));
					return;
				}
				if((int)$room['ownerID'] !== (int)$this->getUserID() && $this->getUserRole() != AJAX_CHAT_ADMIN) {
					$this->insertChatBotMessage($this->getPrivateMessageID(), $this->getLang('privateRoomDeleteDenied'));
					return;
				}
				$channelID = (int)$this->getConfig('roomChannelDiff') + (int)$room['id'];
				$defaultChannelID = (int)$this->getConfig('defaultChannelID');
				if ((int)$this->getChannel() === $channelID) {
					$this->setChannel($defaultChannelID);
					$this->updateOnlineList();
					$this->addInfoMessage($this->getConfig('defaultChannelName'), 'channelSwitch');
					$this->addInfoMessage($defaultChannelID, 'channelID');
					$this->_requestVars['lastID'] = 0;
				}
				$connection = $this->db->getConnectionID();
				if (!$connection->begin_transaction()) {
					error_log('Ajax Chat room deletion: transaction start failed: '.$connection->error);
					http_response_code(500);
					exit('Chat database error.');
				}
				try {
					$queries = array(
						'UPDATE '.$this->getDataBaseTable('online').' SET channel = '.$this->db->makeSafe($defaultChannelID).' WHERE channel = '.$this->db->makeSafe($channelID),
						'DELETE FROM '.$this->getDataBaseTable('messages').' WHERE channel = '.$this->db->makeSafe($channelID),
						'DELETE FROM '.$this->getDataBaseTable('roomMembers').' WHERE roomID = '.$this->db->makeSafe($room['id']),
						'DELETE FROM '.$this->getDataBaseTable('rooms').' WHERE id = '.$this->db->makeSafe($room['id'])
					);
					foreach($queries as $query) {
						$result = $this->db->sqlQuery($query);
						if($result->error()) {
							throw new RuntimeException($result->getError());
						}
					}
					if (!$connection->commit()) {
						throw new RuntimeException($connection->error);
					}
				} catch (Throwable $e) {
					$connection->rollback();
					error_log('Ajax Chat room deletion failed: '.$e->getMessage());
					$this->insertChatBotMessage($this->getPrivateMessageID(), 'Nepodarilo sa odstrániť miestnosť.');
					return;
				}
				$this->removeRoomFromInvitationCache($channelID);
				$this->insertChatBotMessage($this->getPrivateMessageID(), sprintf($this->getLang('privateRoomDeleted'), $this->getPrivateRoomChannelName($room['name'])));
				break;

			case 'help':
			default:
				$this->insertChatBotMessage($this->getPrivateMessageID(), $this->getLang('privateRoomHelp'));
				break;
		}
	}

	function leavePrivateRoom() {
		$room = $this->getPrivateRoomByChannelID($this->getChannel(), true);
		if(!$room) {
			$this->insertChatBotMessage($this->getPrivateMessageID(), $this->getLang('privateRoomNotInRoom'));
			return;
		}
		if((int)$room['ownerID'] === (int)$this->getUserID()) {
			$this->insertChatBotMessage($this->getPrivateMessageID(), $this->getLang('privateRoomOwnerCannotLeave'));
			return;
		}
		$this->removeRoomMember($room['id'], $this->getUserID());
		$this->removeRoomFromInvitationCache((int)$this->getConfig('roomChannelDiff') + (int)$room['id']);
		$this->switchChannel($this->getConfig('defaultChannelName'));
		$this->insertChatBotMessage($this->getPrivateMessageID(), sprintf($this->getLang('privateRoomLeft'), $this->getPrivateRoomChannelName($room['name'])));
	}

	function insertParsedMessageInvite($textParts) {
		$room = $this->getPrivateRoomByChannelID($this->getChannel(), true);
		if(!$room) {
			return parent::insertParsedMessageInvite($textParts);
		}
		if((int)$room['ownerID'] !== (int)$this->getUserID() && $this->getUserRole() != AJAX_CHAT_ADMIN) {
			$this->insertChatBotMessage($this->getPrivateMessageID(), '/error InviteNotAllowed');
			return;
		}
		if(!isset($textParts[1]) || trim($textParts[1]) === '') {
			$this->insertChatBotMessage($this->getPrivateMessageID(), '/error MissingUserName');
			return;
		}
		$targetName = $this->trimUserName($textParts[1]);
		$toUserID = $this->getIDFromName($targetName);
		if($toUserID === null) {
			$this->insertChatBotMessage($this->getPrivateMessageID(), '/error UserNameNotFound '.$targetName);
			return;
		}
		if((int)$toUserID === (int)$room['ownerID'] || $this->isRoomMember($room['id'], $toUserID)) {
			$this->insertChatBotMessage($this->getPrivateMessageID(), sprintf($this->getLang('privateRoomMemberAlready'), $targetName));
			return;
		}
		$this->addRoomMember($room['id'], $toUserID, $targetName);
		$roomName = $this->getPrivateRoomChannelName($room['name']);
		$this->insertChatBotMessage($this->getPrivateMessageID(), '/inviteto '.$targetName.' '.$roomName);
		$this->insertChatBotMessage($this->getPrivateMessageID($toUserID), '/invite '.$this->getUserName().' '.$roomName);
	}

	function insertParsedMessageUninvite($textParts) {
		$room = $this->getPrivateRoomByChannelID($this->getChannel(), true);
		if(!$room) {
			return parent::insertParsedMessageUninvite($textParts);
		}
		if((int)$room['ownerID'] !== (int)$this->getUserID() && $this->getUserRole() != AJAX_CHAT_ADMIN) {
			$this->insertChatBotMessage($this->getPrivateMessageID(), '/error InviteNotAllowed');
			return;
		}
		if(!isset($textParts[1]) || trim($textParts[1]) === '') {
			$this->insertChatBotMessage($this->getPrivateMessageID(), '/error MissingUserName');
			return;
		}
		$targetName = $this->trimUserName($textParts[1]);
		$toUserID = $this->getIDFromName($targetName);
		if($toUserID === null) {
			$sql = 'SELECT userID FROM '.$this->getDataBaseTable('roomMembers').'
				WHERE roomID = '.$this->db->makeSafe($room['id']).'
					AND userName = '.$this->db->makeSafe($targetName).'
				LIMIT 1';
			$result = $this->db->sqlQuery($sql);
			if($result->error()) {
				$this->databaseError(__METHOD__, $result);
			}
			$row = $result->fetch();
			$result->free();
			$toUserID = $row ? (int)$row['userID'] : null;
		}
		if($toUserID === null || (int)$toUserID === (int)$room['ownerID'] || !$this->isRoomMember($room['id'], $toUserID)) {
			$this->insertChatBotMessage($this->getPrivateMessageID(), $this->getLang('privateRoomMemberNotFound'));
			return;
		}
		$this->removeRoomMember($room['id'], $toUserID);
		$roomName = $this->getPrivateRoomChannelName($room['name']);
		$this->insertChatBotMessage($this->getPrivateMessageID(), '/uninviteto '.$targetName.' '.$roomName);
		$this->insertChatBotMessage($this->getPrivateMessageID($toUserID), '/uninvite '.$this->getUserName().' '.$roomName);
	}

	function isRoomMember($roomID, $userID) {
		$sql = 'SELECT userID FROM '.$this->getDataBaseTable('roomMembers').'
			WHERE roomID = '.$this->db->makeSafe($roomID).'
				AND userID = '.$this->db->makeSafe($userID).'
			LIMIT 1';
		$result = $this->db->sqlQuery($sql);
		if($result->error()) {
			$this->databaseError(__METHOD__, $result);
		}
		$member = $result->fetch();
		$result->free();
		return (bool)$member;
	}

	function addRoomMember($roomID, $userID, $userName) {
		$sql = 'INSERT INTO '.$this->getDataBaseTable('roomMembers').'
			(roomID, userID, userName, dateTime)
			VALUES (
				'.$this->db->makeSafe($roomID).',
				'.$this->db->makeSafe($userID).',
				'.$this->db->makeSafe($userName).',
				NOW()
			)
			ON DUPLICATE KEY UPDATE userName = VALUES(userName), dateTime = NOW()';
		$result = $this->db->sqlQuery($sql);
		if($result->error()) {
			$this->databaseError(__METHOD__, $result);
		}
	}

	function removeRoomMember($roomID, $userID) {
		$sql = 'DELETE FROM '.$this->getDataBaseTable('roomMembers').'
			WHERE roomID = '.$this->db->makeSafe($roomID).'
				AND userID = '.$this->db->makeSafe($userID);
		$result = $this->db->sqlQuery($sql);
		if($result->error()) {
			$this->databaseError(__METHOD__, $result);
		}
	}

	function addRoomToInvitationCache($channelID) {
		if($this->_invitations === null) {
			$this->getInvitations();
		}
		if(!in_array($channelID, $this->_invitations)) {
			$this->_invitations[] = $channelID;
		}
	}

	function removeRoomFromInvitationCache($channelID) {
		if($this->_invitations !== null) {
			$this->_invitations = array_values(array_diff($this->_invitations, array($channelID)));
		}
	}
}
