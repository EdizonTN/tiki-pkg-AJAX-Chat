# Mikrozone AJAX Chat – TikiWiki extension package

This directory is the TikiWiki Extension Package source. The package name is `mikrozone/ajax_chat`.

## Package integration

- `composer.json` and `tiki-package.json` identify the package.
- `prefs/tp_mikrozone__ajax_chat.php` defines settings managed by Tiki under Admin → Packages → Extensions Preferences.
- `lib/wiki-plugins/mzchat.php` provides the package wiki plugin.
- Add the chat to a wiki page with:

```text
{package package="mikrozone/ajax_chat" plugin="mzchat"}
```

## Preferences

- Enable/disable chat.
- Moderator groups as a comma-separated list of exact Tiki group names.
- Optional hiding of administrators who are not members of a private room.

Tiki administrators always receive the highest chat role. A moderator role does not grant automatic membership in private rooms.

## Deployment

Composer metadata is included for development and package tooling. Composer is not intended to be a production-server prerequisite. The planned web installer/updater will place this package under `vendor_custom/mikrozone/ajax_chat` and the chat engine under `_CHAT/`, then make it available through Tiki's Packages administration.

Manual ZIP deployment and the web installer are not yet implemented or validated. Do not treat this source package as a production-ready installable release.
