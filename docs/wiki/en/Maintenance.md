# Maintenance and troubleshooting

## Installation status
The Maintenance tab reports the state of the six chat tables:
- **complete** – all six tables exist;
- **partial** – one or more tables are missing;
- **missing** – chat tables have not been created.

A complete schema alone does not guarantee the chat is reachable. The generated configuration, /chat entry points, and asset-routing rules are also checked.

## Install / repair
Use **Install / reinstall / repair AJAX Chat** if tables or package-managed files are missing. The installer checks the schema and restores missing package components. It does not overwrite unrelated files.

## Uninstall
Uninstalling deletes messages, rooms, memberships, bans, invitations, and online-state data.
1. Disable **Enable Ajax Chat** in General Preferences.
2. On Maintenance, enter the exact confirmation phrase: DELETE AJAX CHAT DATA.
3. Confirm **Uninstall and delete chat data**.

The operation removes the six runtime tables, the settings table, and files identified as package-created. Back up the database and files first.

## Common issues
### Chat reports that it is not configured
- Check TIKI_ROOT/temp/ajax-chat/Chat-config.php.
- Verify that PHP can write to TIKI_ROOT/temp/.
- Run Install / repair.
- Ensure temp/ajax-chat/ is protected from direct web access.

### Installer cannot create /chat
- Check PHP write permissions in the Tiki root.
- Check whether another application already uses /chat/.
- Do not overwrite unrelated index.php, tiki-auth.php, or .htaccess; resolve the URL conflict first.

### Chat loads without CSS or JavaScript
- Check TIKI_ROOT/chat/.htaccess.
- Verify Apache mod_rewrite and permitted .htaccess rules, or configure equivalent server rules.
- Check the /chat/css/, /chat/js/, and /chat/img/ URLs.

### User cannot sign in to chat
- The user must be signed in to TikiWiki.
- Check account status and Enable Ajax Chat.
- For role issues, verify the exact moderator Tiki group name.

### Administration entry is missing
- Verify that the Extension Package is enabled.
- Open tiki-admin.php?page=tp_edizontn_ajaxchat as a Tiki administrator.
- Check write permissions for _custom/shared/.

## Backups
Before upgrades or uninstall, back up:
- database tables tiki_ajaxchat_*;
- vendor_custom/tiki-pkg-ajax-chat/;
- temp/ajax-chat/Chat-config.php;
- package-managed files in /chat/;
- package-owned changes in _custom/shared/custom.php.

The configuration contains an HMAC secret. Keep it outside the public web space and never publish it in issues or logs.

## Tests and CI
The .github/workflows/chat-checks.yml workflow runs PHP syntax checks, PHP tests against MariaDB, and JavaScript checks. CI does not replace integration testing on the target TikiWiki version.