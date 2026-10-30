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
   (64 hexadecimal characters recommended). Use the same value only in this chat config;
   do not publish it or place it in JavaScript.
5. Check `tikiRootPath`. By default it resolves two directory levels above
   `chat/lib/config.php`, which is correct when `chat/` is directly inside the Tiki root.
6. Add the exact Tiki group names that should be chat moderators to
   `tikiChatModeratorGroups`. Tiki administrators are chat administrators automatically.

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
directory as `_custom/shared/wiki-plugins`. Clear the Tiki cache after copying the file.

Then create a custom module in **Admin → Modules → Custom Modules**:

- Name: `mzchat`
- Title: `Mikrozone chat`
- Content: `{MZCHAT() /}`
- Enable wiki parsing for the module content, if that option is shown.

Assign the module in **Admin → Modules → Assigned Modules**:

- Zone: `Bottom` (site footer) or `Page Bottom` (bottom of the main content column).
- Parameters: `nobox=y`
- Cache time: `0`
- Leave the page filter empty to show it throughout the site.
- Restrict visibility to the `Registered` group if desired.

The plugin embeds the chat and provides a link to open it in a separate browser window.

## Current limitations to resolve before production

- The upstream private-channel implementation provides one personal private channel per user.
  It supports invitations/group conversation in that channel, but not multiple independently
  named private rooms created by a user. This needs a separate room model and is not claimed
  as complete.
- File upload is not implemented.
- Syntax highlighting for code messages is not implemented yet.
- The Tiki moderator group mapping must be set to the site's actual group name.
- The bridge and chat must be tested together on the real Tiki 30.1 + PHP-FPM + MariaDB stack
  before deployment. No runtime test against the user's hosting environment has been performed.
