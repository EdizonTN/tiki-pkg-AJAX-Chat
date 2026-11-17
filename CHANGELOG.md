# Changelog

## [0.1.15] - 2026-11-17

- Register the AJAX Chat administration page in both the UAB backend child registry (when available) and the top-level Control Panels registry as a fallback, so it has more than one supported navigation registration path.
- TikiWiki 30.2 runtime visibility and exact menu placement remain unverified.


## [0.1.14] - 2026-11-17

- Document and make explicit the customization-safety contract: preserve all unrelated content in an existing `_custom/shared/custom.php`; append/remove only the package-marked navigation block.
- Refuse to overwrite an existing unowned `ajaxchat-admin-navigation.php` helper; Tiki `.tpl` templates and theme files are not modified.


All notable changes to the TikiWiki AJAX Chat package are documented here.

## [0.1.13] - 2026-11-17

- Investigation correction: `$admin_icons` is the Control Panels registry and does not register entries in the UAB **Manage → Website Functions** sidebar. The current navigation helper therefore does not yet satisfy that requested placement.
- The `_custom/shared/custom.php` bootstrap path is supported by Tiki, but that fact alone does not establish a supported extension point for adding a UAB sidebar item. Keep the PR marked WIP until the actual sidebar registration mechanism is verified on TikiWiki 30.2.

- Restore automatic admin-navigation bootstrap generation removed during the 0.1.12 investigation.
- Create/update only the package-marked `_custom/shared/custom.php` include block and `ajaxchat-admin-navigation.php` helper; preserve unrelated custom.php content.
- Register the page in Tiki's native `$admin_icons` registry (UAB Configure → Advanced; classic admin entry) on first settings-page visit or Install/repair.
- Remove only package-owned navigation artifacts during uninstall; remove the obsolete Application-menu entry.
- UAB Manage → Website Functions placement and live Tiki 30.2 runtime behavior remain unverified.

## [0.1.12] - 2026-11-17

- Bump the Composer package version from 0.1.11 to 0.1.12.
- Continue the TikiWiki 30.2 Unified Admin Backend navigation investigation.
- Keep the direct administration URL documented: `tiki-admin.php?page=tp_edizontn_ajaxchat`.
- Clarify that automatic registration under **Manage → Website Functions** is not implemented yet.
- Do not write to `_custom` or create an item in the regular Application menu as a workaround.
- No live TikiWiki 30.2 runtime verification was performed for this update.

## [0.1.11]

Previous package release. See the repository history for its changes.
