# Architecture and repository structure

## Overview
The repository contains two main layers: the Tiki Extension Package (metadata, administration, settings, wiki plugin, and installer) and the AJAX Chat engine in src/, extended with a Tiki authentication and database bridge.

Tiki provides user identity, sessions, groups, and database connectivity. The chat does not require a separate database.

## Main components
| Path | Purpose |
|---|---|
| composer.json | Package identity edizontn/ajaxchat, Tiki package type, PHP requirement, PSR-4 autoload |
| tiki-package.json | Tiki package namespace |
| admin/ and templates/admin/ | Administration logic and Smarty UI template |
| lib/PackageInstaller.php | Install, inspect, repair, and uninstall |
| lib/SettingsDefinitions.php | Setting definitions, defaults, and validation limits |
| lib/SettingsStore.php | Persistent settings and legacy preference migration |
| lib/wiki-plugins/ajaxchat.php | Wiki page embedding plugin |
| src/index.php | AJAX Chat engine entry point |
| src/tiki-auth.php | Tiki session verification and short-lived signed token |
| src/lib/tiki-bootstrap.php | Tiki runtime bootstrap and TikiDb adapter |
| tests/ | PHP and JavaScript tests |

## Runtime paths
- Package root: TIKI_ROOT/vendor_custom/tiki-pkg-ajax-chat/
- Chat engine: vendor_custom/tiki-pkg-ajax-chat/src/
- Protected generated config: TIKI_ROOT/temp/ajax-chat/Chat-config.php
- Public endpoint: TIKI_ROOT/chat/index.php
- Authentication endpoint: TIKI_ROOT/chat/tiki-auth.php
- Asset routing: TIKI_ROOT/chat/.htaccess

The installer creates small entry-point files in /chat that delegate to the package. CSS, JavaScript, image, sound, and Flash assets are mapped to src/. Existing unrelated files are not overwritten.

## Authentication flow
1. The visitor signs in to TikiWiki.
2. The chat endpoint loads generated configuration and bootstraps Tiki.
3. src/tiki-auth.php verifies the Tiki session and account status.
4. The role is derived from Tiki administrator privileges and configured moderator groups.
5. A signed token valid for 120 seconds is issued.

Anonymous access is disabled.

## Database
The chat uses the existing Tiki database through TikiDb. Its six runtime tables are tiki_ajaxchat_online, tiki_ajaxchat_messages, tiki_ajaxchat_bans, tiki_ajaxchat_invitations, tiki_ajaxchat_rooms, and tiki_ajaxchat_room_members. Package settings are stored in tiki_ajaxchat_settings.

## Security
The generated configuration and HMAC secret live outside the package in protected temp/ajax-chat/. Administrative POST actions require Tiki administrator permissions and a CSRF ticket. The installer refuses to overwrite unrelated files. Do not modify Tiki core.