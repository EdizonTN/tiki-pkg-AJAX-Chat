<?php
/*
 * Mikrozone AJAX Chat plugin for TikiWiki 30+.
 * Install in: _custom/shared/wiki-plugins/wikiplugin_mzchat.php
 */

function wikiplugin_mzchat_info() {
	return array(
		'name' => tra('Mikrozone Chat'),
		'description' => tra('Embeds the authenticated Mikrozone AJAX chat.'),
		'documentation' => '',
		'tags' => array('basic'),
		'prefs' => array(),
		'introduced' => '30.0',
		'params' => array()
	);
}

function wikiplugin_mzchat($data, $params) {
	global $user;

	if (empty($user) || $user === 'Anonymous') {
		return '<div class="mzchat-login-required">'.tra('Prihláste sa do Mikrozone, aby ste mohli používať chat.').'</div>';
	}

	$chatUrl = TikiLib::tikiUrl('chat/index.php?embed=1');
	$popupUrl = TikiLib::tikiUrl('chat/index.php?popup=1');
	$chatUrl = htmlspecialchars($chatUrl, ENT_QUOTES, 'UTF-8');
	$popupUrl = htmlspecialchars($popupUrl, ENT_QUOTES, 'UTF-8');

	$html = '<section class="mzchat-embed" style="width:100%;margin:1rem 0;">';
	$html .= '<div class="mzchat-embed-header" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.5rem .75rem;background:#f2f2f2;">';
	$html .= '<strong>Mikrozone chat</strong>';
	$html .= '<a href="'.$popupUrl.'" target="mzchatPopup" rel="noopener" onclick="window.open(this.href,\'mzchatPopup\',\'width=1000,height=700,resizable=yes,scrollbars=yes\');return false;">'.tra('Otvoriť v samostatnom okne').'</a>';
	$html .= '</div>';
	$html .= '<iframe src="'.$chatUrl.'" title="Mikrozone chat" loading="lazy" style="display:block;width:100%;height:280px;border:1px solid #ccc;background:#fff;"></iframe>';
	$html .= '</section>';

	// Prevent the returned HTML from being parsed as wiki syntax a second time.
	return ' '.$html.' ';
}
