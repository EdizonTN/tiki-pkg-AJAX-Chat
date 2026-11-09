<h1>{tr}Ajax Chat settings{/tr}</h1>

<p>{tr}Configure AJAX Chat. Each option includes a short explanation next to its name. Settings are stored in the package-owned database table.{/tr}</p>

{if !$ajax_chat_chat_ready}
    <div class="alert alert-info" role="status">
        <p><strong>{tr}The AJAX Chat package is enabled, but chat initialization is not complete yet.{/tr}</strong></p>
        <p>{tr}Initialize the database tables, protected configuration and /chat entry point to finish setting up AJAX Chat. This does not modify Tiki core files.{/tr}</p>
        <form method="post" action="{$ajax_chat_admin_url|escape}">
            {ticket}
            <input type="hidden" name="redirect" value="0">
            <input type="hidden" name="ajax_chat_action" value="install">
            <button type="submit" class="btn btn-primary">{tr}Initialize AJAX Chat now{/tr}</button>
        </form>
    </div>
{/if}

{tabset name="ajaxchat-settings"}
    {foreach from=$ajax_chat_tabs key=tab item=sections}
        {tab name=$tab}
            <form method="post" action="{$ajax_chat_admin_url|escape}">
                {ticket}
                <input type="hidden" name="redirect" value="0">
                <input type="hidden" name="ajax_chat_action" value="save_settings">
                {foreach from=$sections key=section item=settings}
                    <h2>{$section|escape}</h2>
                    {foreach from=$settings item=setting}
                        <div class="mb-3 row">
                            <label class="col-sm-3 col-form-label" for="ajax-chat-setting-{$setting.key|escape}" title="{$setting.description|escape}">
                                {$setting.name|escape}
                                <a href="#" class="tikihelp text-info" title="{$setting.name|escape}: {$setting.description|escape}" onclick="return false;">{icon name="help"}</a>
                            </label>
                            <div class="col-sm-7 offset-sm-1">
                                {if $setting.type eq 'checkbox'}
                                    <input type="hidden" name="ajax_chat_settings[{$setting.key|escape}]" value="n">
                                    <input type="checkbox" id="ajax-chat-setting-{$setting.key|escape}" name="ajax_chat_settings[{$setting.key|escape}]" value="y" {if $setting.value eq 'y'}checked="checked"{/if}>
                                {elseif $setting.type eq 'select'}
                                    <select class="form-select" id="ajax-chat-setting-{$setting.key|escape}" name="ajax_chat_settings[{$setting.key|escape}]">
                                        {foreach from=$setting.options item=option}
                                            <option value="{$option.value|escape}" {if $option.selected}selected="selected"{/if}>{$option.label|escape}</option>
                                        {/foreach}
                                    </select>
                                {elseif $setting.type eq 'multi'}
                                    <input type="hidden" name="ajax_chat_settings[{$setting.key|escape}][]" value="">
                                    <select class="form-select" multiple="multiple" size="7" id="ajax-chat-setting-{$setting.key|escape}" name="ajax_chat_settings[{$setting.key|escape}][]">
                                        {foreach from=$setting.options item=option}
                                            <option value="{$option.value|escape}" {if $option.selected}selected="selected"{/if}>{$option.label|escape}</option>
                                        {/foreach}
                                    </select>
                                {elseif $setting.type eq 'number' || $setting.type eq 'hour' || $setting.type eq 'year'}
                                    <input class="form-control" type="number" step="1" id="ajax-chat-setting-{$setting.key|escape}" name="ajax_chat_settings[{$setting.key|escape}]" value="{$setting.value|escape}" min="{$setting.min}" max="{$setting.max}">
                                {elseif $setting.type eq 'url'}
                                    <input class="form-control" type="url" id="ajax-chat-setting-{$setting.key|escape}" name="ajax_chat_settings[{$setting.key|escape}]" value="{$setting.value|escape}" placeholder="https://chat.example.com">
                                {elseif $setting.type eq 'ip'}
                                    <input class="form-control" type="text" inputmode="url" pattern="[0-9a-fA-F:.]+" id="ajax-chat-setting-{$setting.key|escape}" name="ajax_chat_settings[{$setting.key|escape}]" value="{$setting.value|escape}">
                                {else}
                                    <input class="form-control" type="text" id="ajax-chat-setting-{$setting.key|escape}" name="ajax_chat_settings[{$setting.key|escape}]" value="{$setting.value|escape}" maxlength="{$setting.maxlength|default:1000}">
                                {/if}
                            </div>
                        </div>
                    {/foreach}
                {/foreach}
                <div class="mb-3 row">
                    <div class="col-sm-7 offset-sm-4">
                        <button type="submit" class="btn btn-primary">{tr}Save AJAX Chat settings{/tr}</button>
                    </div>
                </div>
            </form>
        {/tab}
    {/foreach}

    {tab name="Maintenance"}
        <h2>{tr}Installation and maintenance{/tr}</h2>
        <p>{tr}Manage the AJAX Chat database, generated configuration and standalone entry point from Tiki. No manual file creation is required.{/tr}</p>

        <h3>{tr}Installation status{/tr}</h3>
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

        <p>
            {if $ajax_chat_chat_ready}
                <a class="btn btn-success" href="{$ajax_chat_chat_url|escape}" target="_blank" rel="noopener">
                    {icon name="comments"} {tr}Open Chat{/tr}
                </a>
            {else}
                <button type="button" class="btn btn-success" disabled="disabled">
                    {icon name="comments"} {tr}Open Chat{/tr}
                </button>
                <span class="text-muted">{tr}Install or repair AJAX Chat first to enable this button.{/tr}</span>
            {/if}
        </p>

        <form method="post" action="{$ajax_chat_admin_url|escape}">
            {ticket}
            <input type="hidden" name="redirect" value="0">
            <input type="hidden" name="ajax_chat_action" value="install">
            <button type="submit" class="btn btn-primary">{tr}Install / reinstall / repair AJAX Chat{/tr}</button>
        </form>

        <h2>{tr}Uninstall{/tr}</h2>
        <p><strong>{tr}Warning:{/tr}</strong> {tr}This permanently deletes all AJAX Chat messages, rooms, memberships, bans, invitations and online status. Disable AJAX Chat first in General Preferences. Only the six AJAX Chat tables and files created by this package are removed. Unrelated files and Tiki data are left untouched.{/tr}</p>

        <form method="post" action="{$ajax_chat_admin_url|escape}" onsubmit="return confirm('{tr}This permanently deletes all AJAX Chat data. Continue?{/tr}');">
            {ticket}
            <input type="hidden" name="redirect" value="0">
            <input type="hidden" name="ajax_chat_action" value="uninstall">
            <div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="ajax-chat-confirmation">{tr}Confirmation phrase{/tr}</label>
                <div class="col-sm-7 offset-sm-1">
                    <p>{tr}Type the following phrase to confirm:{/tr} <code>{$ajax_chat_uninstall_confirmation|escape}</code></p>
                    <input id="ajax-chat-confirmation" type="text" name="confirmation" required autocomplete="off" class="form-control">
                </div>
            </div>
            <button type="submit" class="btn btn-danger">{tr}Uninstall and delete chat data{/tr}</button>
        </form>
    {/tab}
{/tabset}
