# Installation and upgrades

## Requirements
- TikiWiki 30.1 (integration target).
- PHP 8.1 or newer.
- Tiki database with InnoDB and utf8mb4.
- The PHP process needs read access to package files and write access to relevant Tiki directories, including `temp/` and the Tiki root.
- Apache requires `mod_rewrite` and permitted `.htaccess` rules, or equivalent web-server routing rules.

The integration has not yet been confirmed by a clean live TikiWiki integration test. Deploy to staging first and create backups.

## 1. Deploy the package
Copy the entire repository to:

    TIKI_ROOT/vendor_custom/tiki-pkg-ajax-chat/

Keep `composer.json` and `tiki-package.json` at the package root. Do not copy `src/` into the Tiki root or move it to `_CHAT/`. Do not modify Tiki core or create a custom route.

## 2. Enable the package
1. Sign in as a Tiki administrator.
2. Open **Admin → Advanced → Packages**.
3. Find and enable `edizontn/ajaxchat`.
4. Open **AJAX Chat settings** in **Admin → Advanced**.

Enabling the package and adding a public chat link to the site's navigation are separate steps. Enabling the package does not automatically add an AJAX Chat item to the main site menu. To make the chat accessible from navigation, add a Tiki menu link pointing to `/chat/`. If **AJAX Chat settings** is also missing from the Advanced administration area, confirm that the package is enabled and that you are signed in with administrator permissions.

## 3. Install database tables and endpoints
On **AJAX Chat settings → Maintenance**, click **Install / reinstall / repair AJAX Chat**. The installer validates the Tiki and package roots, creates protected `TIKI_ROOT/temp/ajax-chat/`, generates `Chat-config.php` and a random HMAC secret, configures TikiDb, creates or checks the six chat tables, creates the public `/chat/` entry points and `.htaccess`, and checks readiness.

The generated configuration is outside the package so repository updates do not overwrite it. If an unrelated file already exists in `/chat`, or an unmanaged configuration is detected, the installer stops rather than overwriting it.

## 4. Verify
- The schema status on Maintenance should be **complete**.
- **Open Chat** should be available.
- Open `https://your-site/chat/` and verify login.
- Test the wiki plugin and regular-user/moderator permissions.

## 5. Embed in a wiki page
    {package package="edizontn/ajaxchat" plugin="ajaxchat"}

Optional iframe height:
    {package package="edizontn/ajaxchat" plugin="ajaxchat" height="520"}

The height is in pixels and is limited to 200–1200. The visitor must be logged in.

## Upgrades
1. Back up the database and package directory.
2. Update `vendor_custom/tiki-pkg-ajax-chat/`.
3. Preserve local changes and generated configuration in `temp/ajax-chat/`.
4. Run **Install / reinstall / repair AJAX Chat** if required.
5. Test both `/chat/` and the wiki plugin.

Database schema versioning and automatic migrations are not yet fully developed. Validate upgrades on staging before production.
