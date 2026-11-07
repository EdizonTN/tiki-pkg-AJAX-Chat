<?php
/**
 * Embed the AJAX Chat from this Tiki package's own src directory.
 *
 * Usage: {package package="edizontn/ajax_chat" plugin="ajaxchat"}
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

    // The clean public URL is a Tiki Custom Route pointing to a wiki page
    // containing this plugin. Keep the iframe pointed at the package engine
    // to avoid recursively embedding the routed wiki page.
    $chatPagePath = isset($prefs['tp_edizontn__ajax_chat__chat_url'])
        ? trim((string) $prefs['tp_edizontn__ajax_chat__chat_url'])
        : '/chat';
    $invalidPath = (
        $chatPagePath === ''
        || $chatPagePath[0] !== '/'
        || strpos($chatPagePath, '//') === 0
        || strpos($chatPagePath, '?') !== false
        || strpos($chatPagePath, '#') !== false
        || strpos($chatPagePath, chr(92)) !== false
        || preg_match('~[^A-Za-z0-9/_-]|(?:^|/)\\.\\.(?:/|$)~', $chatPagePath)
    );
    if ($invalidPath) {
        $chatPagePath = '/chat';
    }
    $chatPageUrl = TikiLib::tikiUrl(ltrim($chatPagePath, '/'));
    $escapedChatPageUrl = htmlspecialchars($chatPageUrl, ENT_QUOTES, 'UTF-8');

    return '<iframe src="' . $escapedUrl . '" title="' . $title . '"'
        . ' loading="lazy" referrerpolicy="same-origin"'
        . ' style="display:block;width:100%;height:' . $height . 'px;border:1px solid #ccc;background:#fff"'
        . ' allow="clipboard-read; clipboard-write"></iframe>'
        . '<p class="ajaxchat-fullpage-link"><a href="' . $escapedChatPageUrl . '" target="_blank" rel="noopener noreferrer">'
        . htmlspecialchars(tra('Open Ajax Chat in a separate page'), ENT_QUOTES, 'UTF-8')
        . '</a></p>';
}
