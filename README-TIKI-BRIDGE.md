# Mikrozone AJAX Chat – TikiWiki 30 bridge

This branch adds a Tiki-backed authentication bridge. TikiWiki remains the authority for
user identities and roles; the chat has no independent passwords or demo accounts.

## Authentication design

- The chat iframe calls `chat/tiki-auth.php` using the browser's existing Tiki session.
- The endpoint loads Tiki's `tiki-setup.php`, reads the authenticated Tiki user and groups,
  and issues a short-lived HMAC-SHA256 token.
- The token contains the Tiki user ID, unique Tiki login, chat role, issue/expiry times and nonce.
- The chat verifies the signature locally. It does not load Tiki on every 2-second chat poll.
- The browser refreshes the Tiki token every 45 seconds. Tokens expire after 120 seconds.
  A Tiki logout or account switch therefore invalidates the chat session within the refresh window.
- Tiki user ID is used as the chat user ID. The Tiki `Admins` group (or global
  `tiki_p_admin` permission) maps to chat administrator.
- Moderator groups are configurable through `tikiChatModeratorGroups`. The default is empty
  until the site's exact moderator group name is chosen.

## Deployment and configuration

1. Copy the repository's `chat/` directory into the Tiki root so the chat is available at `/chat/`.
2. Copy `chat/lib/config.php.example` to `chat/lib/config.php`.
3. Configure the MySQLi connection and database name.
4. Set `mzChatSharedSecret` to a random secret of at least 32 characters
   (64 hexadecimal characters recommended). Generate one with `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`.
   Use the same value only in this chat config; do not publish it or place it in JavaScript.
5. Check `tikiRootPath`. By default it resolves two directory levels above
   `chat/lib/config.php`, which is correct when `chat/` is directly inside the Tiki root.
6. Add the exact Tiki group names that should be chat moderators to
   `tikiChatModeratorGroups`. Tiki administrators are chat administrators automatically.

When upgrading an existing installation/configuration from an earlier branch, merge these
new settings from `config.php.example` into the existing `config.php` rather than replacing
the whole file:

- `dbTableNames['rooms'] = 'mz_chat_rooms'`
- `dbTableNames['roomMembers'] = 'mz_chat_room_members'`
- `roomChannelDiff = 1500000000`
- `allowPrivateRooms = true`
- `maxPrivateRoomsPerUser = 10`
- `privateRoomMaxNameLength = 40`
- `privateRoomPrefix = '~'`, `privateRoomSuffix = '~'`

Keep `roomChannelDiff` above the private-message ID range; the default value is intentional.

## Database installation

The schema uses the isolated `mz_chat_*` prefix and contains no destructive DROP statements.

Run from the chat directory:

```sh
php install.php
```

The installer is CLI-only. If command-line PHP is unavailable, import `chat/chat.sql`
through the database administration tool. Remove `chat/install.php` after installation.

## Install the Tiki wiki plugin

Copy:

```
_custom/shared/wiki-plugins/wikiplugin_mzchat.php
```

to the same path in the Tiki installation. Tiki 30 defines this shared custom wiki-plugin
directory as `_custom/shared/wiki-plugins`. Clear the Tiki cache after copying the file and enable `MZCHAT` in the plugin manager if Tiki reports it as disabled.

Then create a custom module in **Admin → Modules → Custom Modules**:

- Name: `mzchat`
- Title: `Mikrozone chat`
- Content: `{MZCHAT() /}`
- Enable **Must be wiki parsed** for the module content; this is required for `{MZCHAT() /}` to execute.

Assign the module in **Admin → Modules → Assigned Modules**:

- Zone: `Bottom` (site footer) or `Page Bottom` (bottom of the main content column). Enable the chosen horizontal zone in **Look & Feel → Layout Options** if it is not available.
- Parameters: `nobox=y`
- Cache time: `0`
- Leave the page filter empty to show it throughout the site.
- Restrict visibility to the `Registered` group if desired.

The plugin embeds the chat and provides a link to open it in a separate browser window.

## Current limitations to resolve before production

- Multiple named private rooms are now supported. The schema installer now creates two additional tables (`mz_chat_rooms` and `mz_chat_room_members`) without dropping existing data. Create a room with `/room create nazov`,
  list accessible rooms with `/room list`, leave a member room with `/room leave`, and
  delete an owned room with `/room delete nazov`. The owner invites online users with
  `/invite login` and removes them with `/uninvite login`. Membership is persistent.
  Room names appear as `~nazov~`; each user may own up to 10 rooms by default.
- Invitations currently target users who are online in the chat; offline Tiki-user lookup
  is not implemented yet.
- File upload is not implemented.
- Multiline code messages support `[code=php]...[/code]` and language selection from the Code toolbar button; `auto` enables automatic detection. The code is escaped before rendering. highlight.js 11.12.0 is bundled locally under BSD-3-Clause; see `THIRD-PARTY-NOTICES.md`.
- The Tiki moderator group mapping must be set to the site's actual group name.
- The bridge and chat must be tested together on the real Tiki 30.1 + PHP-FPM + MariaDB stack
  before deployment. No runtime test against the user's hosting environment has been performed.

## Local validation before deployment

Use the same PHP major/minor version as the web PHP-FPM runtime:

```sh
find chat _custom tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l
php tests/test_mz_chat_auth.php
php tests/test_chat_schema.php
node --check chat/js/chat.js
node tests/test_code_blocks.js
php -r 'echo extension_loaded("mysqli") ? "mysqli OK\\n" : "mysqli MISSING\\n";'
```

The token unit tests run without Tiki or a database. The bridge still needs an integration
test with the actual Tiki 30.1 installation and its PHP-FPM session configuration.
