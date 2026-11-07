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
- Tiki package namespace: `EdizonTN\\AjaxChat`
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

The package provides preferences for enabling chat, moderator group mapping, and optional administrator stealth in private rooms. Tiki administrators retain the highest chat role; moderator status does not grant membership in private rooms.

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

The web bootstrap/upgrade workflow and full runtime validation are still in progress. Back up Tiki files and the database before testing.

See `README-TIKI-BRIDGE.md` and `PROJECT-STATUS.md` for development notes.
