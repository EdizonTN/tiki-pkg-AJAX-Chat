# Third-party notices and license boundaries

## AJAX Chat core

The main AJAX Chat code is distributed under the project's **Modified MIT License**.
See `chat/license.txt`. Preserve that notice in redistributed copies.

## Optional Ruby socket server

The files `chat/socket/server` and `chat/socket/server.rb` identify themselves as
**GNU Affero General Public License (AGPL)** components in their source headers.

The Mikrozone deployment uses ordinary HTTP/AJAX polling; the socket server is disabled
and is not required. It can be omitted from the deployed chat directory. If these files
are redistributed or modified, keep their AGPL notices and comply with the AGPL for
those components. The AGPL does not automatically relicense the separately licensed
AJAX Chat core.

## Icons and emoticons

The assets in `chat/img/` are identified in `chat/img/license.txt` as **Creative
Commons Attribution-ShareAlike 2.5**. The notice attributes the Tango Desktop Project
and the additional emoticons to `zerwas2ky`. Preserve the attribution and license text;
modified versions of those assets remain subject to the ShareAlike condition.

## Notification sounds

The audio files in `chat/sounds/` are identified in `chat/sounds/license.txt` as
being under the **Soundsnap Licence**. That notice permits use in websites, including
commercial websites, but prohibits commercial distribution of the sounds 'as they are'.
This is not an OSI open-source license. Keep the notice if the sounds are retained.
For a strictly open-source-only asset bundle, remove or replace these sounds with
CC0/BSD/MIT-compatible audio.

## highlight.js 11.12.0

- Project: https://github.com/highlightjs/highlight.js
- Files vendored in this repository:
  - `chat/js/vendor/highlight.min.js`
  - `chat/css/highlight-github.min.css`
- License: **BSD 3-Clause**.
- The complete license text is distributed as `chat/js/vendor/HIGHLIGHTJS-LICENSE.txt`.

The vendored build is local; the chat does not contact a third-party CDN to highlight code.
