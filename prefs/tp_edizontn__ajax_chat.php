<?php
/**
 * Site-wide preferences for the AJAX Chat extension.
 */
function prefs_tp_edizontn__ajax_chat_list()
{
    return [
        'tp_edizontn__ajax_chat__chat_enabled' => [
            'name' => tra('Enable Ajax Chat'),
            'description' => tra('Allow authenticated Tiki users to access the chat.'),
            'type' => 'flag',
            'default' => 'y',
        ],
        'tp_edizontn__ajax_chat__moderator_groups' => [
            'name' => tra('Ajax Chat moderator groups'),
            'description' => tra('Comma-separated exact Tiki group names. Tiki administrators always retain the highest chat role.'),
            'type' => 'text',
            'default' => '',
        ],
        'tp_edizontn__ajax_chat__admin_stealth_in_private_rooms' => [
            'name' => tra('Hide administrators in private rooms'),
            'description' => tra('When enabled, an administrator who is not a member or owner of a private room is hidden from that room member list.'),
            'type' => 'flag',
            'default' => 'n',
        ],
    ];
}
