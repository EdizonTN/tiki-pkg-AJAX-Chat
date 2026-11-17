<?php
/**
 * Package-specific preferences for AJAX Chat.
 */
function prefs_tp_edizontn__ajaxchat_list()
{
    return array(
        'tp_edizontn__ajaxchat__admin_menu_enabled' => array(
            'name' => tra('Show AJAX Chat in the administration menu'),
            'description' => tra('Controls visibility of the AJAX Chat administration menu entry.'),
            'type' => 'flag',
            'default' => 'y',
        ),
    );
}
