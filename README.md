# Mikrozone AJAX Chat – TikiWiki Extension Package

This repository contains the Tiki extension package and the AJAX-Chat-MZ engine.
The package root is `tiki-pkg-ajax-chat/`; the engine runs directly from its
`src/` subdirectory.

## Layout

```text
tiki-pkg-ajax-chat/
├── composer.json
├── tiki-package.json
├── README.md
├── lib/wiki-plugins/mzchat.php
├── prefs/tp_mikrozone__ajax_chat.php
└── src/                 # contents of the previous chat/ directory
    ├── index.php
    ├── install.php
    ├── lib/
    ├── js/
    ├── css/
    └── ...
```

## Deployment in TikiWiki

Place the complete `tiki-pkg-ajax-chat/` directory at:

```text
TIKI_ROOT/vendor_custom/tiki-pkg-ajax-chat/
```

**Do not extract `src/` into the Tiki document root and do not deploy it to
`_CHAT/`.** The application runs directly from:

```text
TIKI_ROOT/vendor_custom/tiki-pkg-ajax-chat/src/
```

The wiki plugin embeds
`vendor_custom/tiki-pkg-ajax-chat/src/index.php`. The chat engine's stored
`chatRootPath` and `chatUrlPath` must point to this package-local `src/`
directory. The Tiki root path and URL remain separate settings for the Tiki
authentication bridge.

## Tiki package metadata

- Composer package: `mikrozone/ajax_chat`
- Package type: `tiki-package`
- Minimum PHP version: 8.1
- Target platform: TikiWiki 30.1

Add the chat to a wiki page:

```text
{package package="mikrozone/ajax_chat" plugin="mzchat"}
```

Optional iframe height:

```text
{package package="mikrozone/ajax_chat" plugin="mzchat" height="520"}
```

The package provides preferences for enabling chat, moderator group mapping,
and optional administrator stealth in private rooms. Tiki administrators retain
the highest chat role; moderator status does not grant membership in private rooms.

## Installation and status

Keep both `composer.json` and `tiki-package.json` at the package root so Tiki
can identify it as an Extension Package. Use the package discovery and activation
mechanism supported by the installed Tiki version. The known Tiki 30.1 Custom
Packages listing issue is a Tiki core issue; do not work around it by moving the
chat engine into the Tiki document root.

The web bootstrap/upgrade workflow and full runtime validation are still in
progress. Back up Tiki files and the database before testing.

## Data and configuration

The integrated chat is designed to reuse the active Tiki database connection.
Its extension tables use the `tikiwiki_ext_ajax_chat_` prefix. Keep generated
configuration, secrets, and chat data package-local and do not overwrite them
during code updates.

See the repository-level `../README.md`, `../README-TIKI-BRIDGE.md`, and
`../PROJECT-STATUS.md` for development notes.
