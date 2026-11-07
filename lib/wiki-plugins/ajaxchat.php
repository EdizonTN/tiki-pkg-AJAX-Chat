<?php
/**
 * Embed the AJAX Chat from this Tiki package's own src directory.
 *
 * Usage: {package package="edizontn/ajaxchat" plugin="ajaxchat"}
 */
function ajaxchat_info()
{
    return [
        'name' => tra('Ajax Chat'),
        'description' => tra('Embeds the authenticated AJAX chat.'),
        'params' => [
            'height' => [
                'required' => false,
                'name' => tra('Height'),
                'description' => tra('Chat frame height in pixels (200–1200).'),
                'filter' => 'int',
                'default' => 420,
            ],
        ],
    ];
}

function ajaxchat($content, $params)
{
    global $user, $prefs;

    if (empty($user) || $user === 'Anonymous') {
        return '<div class="alert alert-info">' . tra('Please log in to use the Ajax Chat.') . '</div>';
    }

    if (
        isset($prefs['tp_edizontn__ajax_chat__chat_enabled'])
        && $prefs['tp_edizontn__ajax_chat__chat_enabled'] !== 'y'
    ) {
        return '';
    }

    $height = isset($params['height']) ? (int) $params['height'] : 420;
    $height = max(200, min(1200, $height));

    // The engine remains inside vendor_custom; it is not copied to Tiki's root.
    $url = TikiLib::tikiUrl('vendor_custom/tiki-pkg-ajax-chat/src/index.php?embed=1');
    $escapedUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    $title = htmlspecialchars(tra('Ajax Chat'), ENT_QUOTES, 'UTF-8');

    return '<iframe src="' . $escapedUrl . '" title="' . $title . '"'
        . ' loading="lazy" referrerpolicy="same-origin"'
        . ' style="display:block;width:100%;height:' . $height . 'px;border:1px solid #ccc;background:#fff"'
        . ' allow="clipboard-read; clipboard-write"></iframe>';
}
