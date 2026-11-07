<h1>{tr}AJAX Chat administration{/tr}</h1>

<p>{tr}Manage the AJAX Chat database, generated configuration and standalone entry point from Tiki. No manual file creation is required.{/tr}</p>

<h2>{tr}Installation status{/tr}</h2>
{if $ajax_chat_schema_status eq 'complete'}
    <div class="alert alert-success" role="status">{tr}All six AJAX Chat database tables are present.{/tr}</div>
{elseif $ajax_chat_schema_status eq 'partial'}
    <div class="alert alert-warning" role="status">{tr}The database schema is incomplete. Use Install / repair to create missing tables.{/tr}</div>
{elseif $ajax_chat_schema_status eq 'missing'}
    <div class="alert alert-info" role="status">{tr}AJAX Chat is not installed yet.{/tr}</div>
{else}
    <div class="alert alert-warning" role="status">{tr}The database schema status could not be determined.{/tr}</div>
{/if}

<p>{tr}The installer creates the protected configuration and random HMAC secret in Tiki's temp directory, creates the six package-owned database tables, and generates{/tr} <code>/chat/index.php</code>. {tr}The generated /chat entry point does not overwrite an existing file.{/tr}</p>

<form method="post" action="{$ajax_chat_admin_url|escape}">
    {ticket}
    <input type="hidden" name="redirect" value="0">
    <input type="hidden" name="ajax_chat_action" value="install">
    <button type="submit" class="btn btn-primary">{tr}Install / repair AJAX Chat{/tr}</button>
</form>

<hr>

<h2>{tr}Uninstall{/tr}</h2>
<p><strong>{tr}Warning:{/tr}</strong> {tr}This permanently deletes all AJAX Chat messages, rooms, memberships, bans, invitations and online status. Disable AJAX Chat first in Extension Preferences. Only the six AJAX Chat tables and files created by this package are removed. Unrelated files and Tiki data are left untouched.{/tr}</p>

<form method="post" action="{$ajax_chat_admin_url|escape}" onsubmit="return confirm('{tr}This permanently deletes all AJAX Chat data. Continue?{/tr}');">
    {ticket}
    <input type="hidden" name="redirect" value="0">
    <input type="hidden" name="ajax_chat_action" value="uninstall">
    <label for="ajax-chat-confirmation">{tr}Type the following phrase to confirm:{/tr} <code>{$ajax_chat_uninstall_confirmation|escape}</code></label>
    <input id="ajax-chat-confirmation" type="text" name="confirmation" required autocomplete="off" class="form-control">
    <button type="submit" class="btn btn-danger">{tr}Uninstall and delete chat data{/tr}</button>
</form>
