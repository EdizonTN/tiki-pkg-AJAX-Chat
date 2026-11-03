# TikiWiki 30 Extension Package – Mikrozone AJAX Chat

This directory contains the source package in `package/`. It is designed to be deployed to `vendor_custom/mikrozone/ajax_chat/`.

- `package/composer.json`: Composer package metadata (Composer is not required on the production server).
- `package/tiki-package.json`: Tiki Extension Package manifest.
- `package/prefs/tp_mikrozone__ajax_chat.php`: native Tiki extension preferences.
- `package/lib/wiki-plugins/mzchat.php`: package wiki plugin, invoked with `{package package="mikrozone/ajax_chat" plugin="mzchat"}`.

The old standalone admin PHP page, manually copied Smarty template, and `_custom/shared/Bridge-config.php` settings file have been removed from this integration path. Settings are now defined as Tiki preferences.

The chat engine itself is deployed separately to `_CHAT/` and reuses the active TikiDb connection. Its SQL tables use the `tikiwiki_ext_ajax_chat_` prefix.

**Status:** initial package scaffolding only. The web bootstrap installer, package deployment/activation, and full TikiWiki 30.1 runtime validation remain to be implemented.
