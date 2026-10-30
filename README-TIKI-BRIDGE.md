# Mikrozone AJAX Chat – TikiWiki 30 bridge

This branch adds a Tiki-backed authentication bridge. TikiWiki remains the authority for
user identities and roles; the chat has no independent passwords or demo accounts.

## Authentication design

- The chat iframe calls `/_CHAT/tiki-auth.php` using the browser's existing Tiki session.
- The endpoint loads Tiki's `tiki-setup.php`, reads the authenticated Tiki user and groups,
  and issues a short-lived HMAC-SHA256 token.
- The token contains the Tiki user ID, unique Tiki login, chat role, issue/expiry times and nonce.
- The chat verifies the signature locally. It does not load Tiki on every 2-second chat poll.
- The browser refreshes the Tiki token every 45 seconds. Tokens expire after 120 seconds.
  A Tiki logout or account switch therefore invalidates the chat session within the refresh window.
- Tiki user ID is used as the chat user ID. The Tiki `Admins` group (or global
  `tiki_p_admin` permission) maps to chat administrator.
- Moderator groups are read from the Tiki-managed `_custom/shared/Bridge-config.php` file
  (`moderatorGroups`). The installer creates it with an empty default list.

## Deployment and configuration

1. Copy the repository's `chat/` directory to any web-accessible directory **inside the TikiWiki filesystem root**. The directory does not need to be named `_CHAT`; its URL and filesystem paths are detected by the installer.
2. Copy `lib/Chat-config.php.example` to `lib/Chat-config.php`.
3. Configure the MySQLi connection and the dedicated chat database. Do not use the Tiki database or its database user.
4. Set `mzChatSharedSecret` to a random secret of at least 32 characters
   (64 hexadecimal characters recommended). Generate one with `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`.
   Keep it private; it is never sent to JavaScript.
5. Sign in to Tiki as an administrator and open the chat's `install.php` URL. The installer walks up the filesystem hierarchy **once during installation** to locate `tiki-setup.php`, derives the corresponding URL paths, creates the bridge configuration if needed, and saves all detected paths into `Chat-config.php`.
6. Edit `_custom/shared/Bridge-config.php` to set the exact Tiki group names that should be chat moderators. Tiki administrators are chat administrators automatically.

The saved path configuration contains:

- `chatRootPath` and `chatUrlPath`
- `tikiRootPath` and `tikiUrlPath`
- `bridgeConfigPath`

Normal chat requests and the authentication endpoint read these stored values. They do not search parent directories or re-detect installation paths. If the chat is moved, run the installer again to update the saved paths; it preserves the database settings and HMAC secret.

The bridge settings file is separate from the chat's private configuration. Tiki-side integration owns `Bridge-config.php`; database credentials and the HMAC secret remain only in `Chat-config.php`.

## Database installation

The schema uses the isolated `mz_chat_*` prefix and contains no destructive DROP statements.
Create the separate `mz_chat` database first. Configure a dedicated database user with
`SELECT, INSERT, UPDATE, DELETE, CREATE` privileges only on `mz_chat.*`; do not use the
Tiki database or its database user.

After copying the chat and creating `lib/Chat-config.php`, sign in to Tiki as an administrator
and open the chat's `install.php` URL. The web installer:
- requires an authenticated Tiki administrator;
- uses a session-bound CSRF token and accepts POST only for installation;
- creates/verifies the six expected tables in the configured chat database;
- refuses to run again when all six tables already exist.

It does not create the database or database account. After successful installation, delete
`install.php` and revoke the database user's `CREATE` privilege. Runtime access needs
only `SELECT, INSERT, UPDATE, DELETE`. The direct web access to `chat.sql` is denied
by `.htaccess`.

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
  list accessible rooms with `/room list` (admins can use `/room list all`), leave a member room with `/room leave`, and
  delete an owned room with `/room delete nazov`. The owner invites online users with
  `/invite login` and removes them with `/uninvite login`. Membership is persistent.
  Room names appear as `~nazov~`; each user may own up to 10 rooms by default.
- Invitations currently target users who are online in the chat; offline Tiki-user lookup
  is not implemented yet.
- File upload is not implemented.
- The optional Ruby socket server is not used by this deployment. It is AGPL-licensed;
  keep `socketServerEnabled = false` and omit `socket/` from the deployed copy.
  The main chat core and other bundled assets have separate license notices in
  `THIRD-PARTY-NOTICES.md`.
- Multiline code messages support `[code=php]...[/code]` and language selection from the Code toolbar button; `auto` enables automatic detection. The code is escaped before rendering. highlight.js 11.12.0 is bundled locally under BSD-3-Clause; see `THIRD-PARTY-NOTICES.md`.
- The Tiki moderator group mapping must be set to the site's actual group name.
- The bridge and chat must be tested together on the Tiki 30.1 + PHP + MariaDB stack
  before deployment. No runtime test against the user's hosting environment has been performed.

## Local validation before deployment

Use the same PHP major/minor version as the web PHP-FPM runtime:

```sh
find chat _custom tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l
php tests/test_mz_chat_auth.php
php tests/test_chat_schema.php
php tests/test_private_rooms.php
# Requires a local MariaDB 11.4 test database (see CI workflow)
php tests/test_chat_database.php
node --check chat/js/chat.js
node tests/test_code_blocks.js
php -r 'echo extension_loaded("mysqli") ? "mysqli OK" : "mysqli MISSING";'
```

The token unit tests run without Tiki or a database. The bridge still needs an integration
test with the actual Tiki 30.1 installation and its PHP-FPM session configuration.
