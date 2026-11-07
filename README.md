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
│   └── tp_edizontn__ajaxchat.php
└── src/                 # AJAX Chat engine and Tiki bridge
    ├── index.php
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

The wiki plugin embeds `vendor_custom/tiki-pkg-ajax-chat/src/index.php`. The Tiki administration page generates a protected configuration file and random HMAC secret in `TIKI_ROOT/temp/ajax-chat/`, creates the six database tables, and creates package-owned `TIKI_ROOT/chat/index.php`, `TIKI_ROOT/chat/tiki-auth.php` and `TIKI_ROOT/chat/.htaccess` files. The `.htaccess` maps the public `/chat/css`, `/chat/js`, `/chat/img`, `/chat/sounds` and `/chat/flash` asset URLs to the package's `src/` tree, so assets are served without copying them into the document root. The `/chat` URL is therefore served by a real directory and does not depend on intercepting Tiki's route dispatcher or editing `_custom/shared/custom.php`.

## Tiki package metadata

- Composer package: `edizontn/ajaxchat`
- Tiki package namespace: `EdizonTN\AjaxChat`
- Package type: `tiki-package`
- Minimum PHP version: 8.1
- Target platform: TikiWiki 30.1

Add the chat to a wiki page:

```text
{package package="edizontn/ajaxchat" plugin="ajaxchat"}
```

Optional iframe height:

```text
{package package="edizontn/ajaxchat" plugin="ajaxchat" height="520"}
```

The package exposes the configurable, runtime-safe AJAX Chat options through **Settings → Packages → Extension Preferences**. This includes language and style, public/private rooms and private messages, nickname policies, opening hours, message/history limits, moderation defaults, log retention/access, IP checks and optional socket-server settings. Moderator group mapping and administrator stealth are Tiki bridge settings. Guest logins remain disabled by design because Tiki authentication is mandatory. Tiki administrators retain the highest chat role; moderator status does not grant membership in private rooms.

Database and file lifecycle management is provided by the native Tiki extension administration page: `tiki-admin.php?page=tp_edizontn_ajaxchat`. It is available to Tiki administrators after enabling the package under Admin → Packages. Install / repair creates the configuration, secret, database tables and `/chat` entry point. Uninstall requires the chat to be disabled and an explicit confirmation phrase; it removes only the six package tables and files marked as package-owned.

## Database

The integrated chat reuses TikiWiki's active database connection through `TikiDb`; it does not create a separate database connection. Its six extension tables use the `tiki_ajaxchat_` prefix:

- `tiki_ajaxchat_online`
- `tiki_ajaxchat_messages`
- `tiki_ajaxchat_bans`
- `tiki_ajaxchat_invitations`
- `tiki_ajaxchat_rooms`
- `tiki_ajaxchat_room_members`

Generated configuration and secrets are stored outside the package in the protected Tiki `temp/ajax-chat/` directory so package updates cannot overwrite them.

## Installation and status

Keep `composer.json` and `tiki-package.json` at the package root so Tiki can identify it as an Extension Package. Use the package discovery and activation mechanism supported by the installed Tiki version.

The standalone chat URL is `/chat`. The Tiki admin page creates `TIKI_ROOT/chat/index.php` only if it does not already belong to another file. Apache serves this physical directory directly, so the chat does not require a custom Tiki route, SEF route registration, `_custom/shared/custom.php`, or Tiki core modifications.

See `README-TIKI-BRIDGE.md` and `PROJECT-STATUS.md` for development notes.
