# AJAX Chat – TikiWiki Extension Package

This repository is the complete TikiWiki extension package published by **EdizonTN**. The package metadata and Tiki integration live at the repository root; the AJAX Chat engine runs from the package-local `src/` directory.

## Layout

```text
tiki-AJAX-Chat/
├── composer.json
├── tiki-package.json
├── lib/
│   └── wiki-plugins/
│       └── ajaxchat.php
├── prefs/
│   └── tp_edizontn__ajax_chat.php
└── src/                 # AJAX Chat engine and Tiki bridge
    ├── index.php
    ├── install.php
    ├── admin.php
    ├── tiki-auth.php
    ├── chat.sql
    ├── lib/
    ├── js/
    ├── css/
    └── ...
```

## Deployment in TikiWiki

Deploy or clone the complete repository at:

```text
TIKI_ROOT/vendor_custom/tiki-pkg-ajax-chat/
```

**Do not copy or extract `src/` into the Tiki document root and do not deploy it to `_CHAT/`.** The chat engine runs directly from:

```text
TIKI_ROOT/vendor_custom/tiki-pkg-ajax-chat/src/
```

The wiki plugin embeds `vendor_custom/tiki-pkg-ajax-chat/src/index.php`. The installer stores the chat filesystem path and URL, as well as the Tiki root path and URL, in the protected `src/lib/Chat-config.php` configuration file.

## Tiki package metadata

- Composer package: `edizontn/ajax_chat`
- Tiki package namespace: `EdizonTN\AjaxChat`
- Package type: `tiki-package`
- Minimum PHP version: 8.1
- Target platform: TikiWiki 30.1

Add the chat to a wiki page:

```text
{package package="edizontn/ajax_chat" plugin="ajaxchat"}
```

Optional iframe height:

```text
{package package="edizontn/ajax_chat" plugin="ajaxchat" height="520"}
```

The package exposes the configurable, runtime-safe AJAX Chat options through **Settings → Packages → Extension Preferences**. This includes language and style, public/private rooms and private messages, nickname policies, opening hours, message/history limits, moderation defaults, log retention/access, IP checks and optional socket-server settings. Moderator group mapping and administrator stealth are Tiki bridge settings. Guest logins remain disabled by design because Tiki authentication is mandatory. Tiki administrators retain the highest chat role; moderator status does not grant membership in private rooms.\n\nDatabase management is provided by the package-local `src/admin.php` page. Open it at `vendor_custom/tiki-pkg-ajax-chat/src/admin.php` while logged in as a Tiki administrator. It is restricted to Tiki administrators and supports schema installation/repair and explicit, confirmed removal of the six chat tables. The uninstall operation deletes chat data but does not remove package files or modify Tiki tables.

## Database

The integrated chat reuses TikiWiki's active database connection through `TikiDb`; it does not create a separate database connection. Its six extension tables use the `tiki_ajaxchat_` prefix:

- `tiki_ajaxchat_online`
- `tiki_ajaxchat_messages`
- `tiki_ajaxchat_bans`
- `tiki_ajaxchat_invitations`
- `tiki_ajaxchat_rooms`
- `tiki_ajaxchat_room_members`

Generated configuration, secrets, and chat data must remain package-local and must not be overwritten during code updates.

## Installation and status

Keep `composer.json` and `tiki-package.json` at the package root so Tiki can identify it as an Extension Package. Use the package discovery and activation mechanism supported by the installed Tiki version.

The Tiki preference overlay and database administration page are implemented on the `feature/tiki-admin-settings` development branch. They still require validation against the target Tiki 30.1 installation. Back up Tiki files and the database before testing.

See `README-TIKI-BRIDGE.md` and `PROJECT-STATUS.md` for development notes.
