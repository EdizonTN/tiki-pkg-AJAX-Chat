# Configuration and permissions

Manage settings at tiki-admin.php?page=tp_edizontn_ajaxchat. Access requires a Tiki administrator. Values are stored in tiki_ajaxchat_settings, not in the global tiki_preferences table.

## General Preferences
- **Enable Ajax Chat** – enables or disables chat usage.
- **Default language** – default UI language, selected from languages available in the engine.
- **Default chat style** – default visual theme.
- **Socket Server** – enables and configures the optional socket server (hostname, IP, port, Chat ID). It requires a separately operated server; otherwise leave it disabled.

## General Settings
- **Logs** – automatic deletion of old logs, user log access, search period, result count, and retention period.
- **Cookies** – preference-cookie lifetime and HTTPS-cookie requirement.
- **Open/close** – opening/closing hours and days of availability.
- **Others** – timezone, history time window, and number of messages loaded per request.

Weekday values: 0 = Sunday, 1 = Monday, …, 6 = Saturday. Opening hour is 0–23; closing hour is 1–24.

## Users/Group Settings
- **Maximum concurrent users** – limit for regular concurrent users.
- **Default ban duration** – default ban length in minutes.
- **Inactive user timeout/check interval** – inactivity timeout and check interval.
- **User privileges** – nickname changes, maximum nickname length, and deletion of own messages.
- **Administrator/Moderator** – Tiki moderator group, hiding administrators in private rooms, restricting chat to administrators, and IP checks.

The moderator group is selected from existing Tiki groups. Tiki administrators always have the highest role. Moderator status does not automatically grant access to private rooms.

## Chat Settings
Configure message length and rate limits; private rooms, channels, and messages; system join/leave messages; history loading; default public room name and ID; private-room limits; room and nickname prefixes/suffixes; ChatBot display name; and channel ID lists for visibility, history, and log access.

Numeric and string limits are validated by the settings catalogue. Invalid values are rejected when saved.

## Suggested baseline
1. Enable Enable Ajax Chat.
2. Select a default language and style.
3. Set a moderator group if moderation is delegated.
4. Keep the socket server disabled unless you operate one separately.
5. Keep Require HTTPS for chat cookies enabled on HTTPS sites.
6. Configure history, message limits, and private-room policy.
7. Save and test as both a regular user and a moderator.

## Roles and authentication
- **Tiki Admin** – highest chat role.
- **Moderator** – member of the configured Tiki group.
- **User** – signed-in Tiki user without moderator privileges.

Anonymous login is disabled. Chat does not maintain separate passwords or user accounts.

## Data locations
- Settings: tiki_ajaxchat_settings.
- Generated runtime config and secret: temp/ajax-chat/Chat-config.php.
- Configuration template: src/lib/Chat-config.php.example.

Change normal settings through the administration UI; avoid editing generated configuration manually.