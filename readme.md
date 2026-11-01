# AJAX-Chat-MZ

**AJAX-Chat-MZ** is the Mikrozone-maintained adaptation of [Blueimp's AJAX Chat](https://github.com/Frug/AJAX-Chat). It is being developed for the Mikrozone community and its planned integration with **TikiWiki 30**.

This repository contains the chat application, the Tiki authentication bridge, private-room extensions, installation tools, and automated checks. The default branch is `master`.

## Main features

- AJAX-based browser chat with no full-page reloads.
- TikiWiki authentication bridge: Tiki remains the authority for user identities and groups.
- Role mapping for Tiki administrators and configured moderator groups.
- Named private rooms with persistent membership and invitations.
- Administrator access to private rooms, with an optional setting to hide an administrator's presence in rooms they do not belong to.
- Message moderation, including deletion.
- BBCode support and formatted code messages with syntax highlighting.
- Slovak and English interface labels for Mikrozone additions.
- Relocatable installation paths detected by the web installer and stored in configuration.
- Automated PHP, JavaScript, schema, authentication, and private-room tests.

## Requirements

The current CI configuration validates the project with:

- PHP 8.2
- MariaDB 11.4
- PHP extensions `mysqli` and `mbstring`
- Node.js 20 for JavaScript checks

A web deployment also requires a web server capable of running the selected PHP version and a separate database for chat data. Do not use the TikiWiki database or its database account for the chat.

## TikiWiki integration

The bridge is designed for TikiWiki 30. It uses the existing Tiki session to authenticate users and maps Tiki identities and groups to chat roles. The chat verifies short-lived signed tokens locally, so it does not need to bootstrap Tiki on every chat update request.

**Start with the detailed deployment and configuration guide:**

- [TikiWiki bridge, installation, configuration, database setup, and limitations](README-TIKI-BRIDGE.md)

The Tiki wiki-page plugin is a separate integration step and is not yet the recommended way to test the current bridge. Follow the bridge guide and test the chat directly at its installed URL.

## Local validation

Run the checks from the repository root:

```sh
find chat _custom tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l

php tests/test_mz_chat_auth.php
php tests/test_chat_schema.php
php tests/test_private_rooms.php

# Requires a local MariaDB test database:
php tests/test_chat_database.php

node --check chat/js/chat.js
node --check chat/js/lang/en.js
node --check chat/js/lang/sk.js
node tests/test_code_blocks.js
```

The GitHub Actions workflow runs these checks with PHP 8.2, Node.js 20, and MariaDB 11.4.

## Project status

This is an actively developed Mikrozone adaptation, not a ready-made general-purpose TikiWiki package. Complete integration testing with the target TikiWiki installation and its actual PHP-FPM/session configuration is still required before production deployment.

Known limitations and security-sensitive deployment details are documented in [README-TIKI-BRIDGE.md](README-TIKI-BRIDGE.md).

## Credits and licensing

The project is based on Blueimp's AJAX Chat. The upstream chat code and bundled third-party components retain their respective licenses and copyright notices. See:

- [chat/license.txt](chat/license.txt)
- [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md)

Mikrozone-specific changes are maintained in this repository. Check the applicable notices before redistributing the complete application.
