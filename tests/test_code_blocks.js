// Regression test for multiline code rendering. No browser or npm dependencies required.
var hljs = {
	getLanguage: function(name) {
		return name === 'php' || name === 'javascript' || name === 'plaintext';
	}
};

var ajaxChat = {
	settings: {bbCode: true, lineBreaks: true, maxWordLength: 80},
	bbCodeTags: ['code'],
	inArray: function(list, value) { return list.indexOf(value) !== -1; },
	containsUnclosedTags: function() { return false; },
	encodeSpecialChars: function(text) {
		return text.replace(/[&<>'"]/g, function(character) {
			return {'&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#39;', '"':'&quot;'}[character];
		});
	},
	replaceBBCodeCode: function(content, language) {
		var languageClass = '';
		language = (language || '').replace(/^\s+|\s+$/g, '').toLowerCase().replace(/[^a-z0-9+#.-]/g, '');
		if(language === 'auto') language = '';
		else if(language && !hljs.getLanguage(language)) language = 'plaintext';
		if(language) languageClass = ' class="language-' + language + '"';
		var code = this.encodeSpecialChars(content.replace(/\t/g, '    '));
		return '<pre class="ajaxchat-code" data-language="' + this.encodeSpecialChars(language || 'auto') + '"><code' + languageClass + '>' + code + '</code></pre>';
	},
	replaceBBCode: function(text) {
		return text.replace(/\[(\w+)(?:=([^<>]*?))?\]([\s\S]+?)\[\/\1\]/gm, this.replaceBBCodeCallback.bind(this));
	},
	replaceBBCodeCallback: function(match, tag, language, content) {
		if(!this.inArray(this.bbCodeTags, tag)) return match;
		if(tag === 'code') return this.replaceBBCodeCode(content, language);
		return match;
	},
	replaceLineBreaks: function(text) { return text.replace(/\n/g, '<br/>'); },
	replaceHyperLinks: function(text) {
		return text.replace(/(^|\s|>)((?:http|https):\/\/[^\s<>]+)/gm, '$1<a>$2</a>');
	},
	replaceEmoticons: function(text) { return text; },
	breakLongWords: function(text) { return text; },
	replaceCustomText: function(text) { return text; },
	replaceText: function(text) {
		var codeBlocks = [];
		text = this.replaceBBCode(text);
		text = text.replace(/<pre class="ajaxchat-code"[\s\S]*?<\/pre>/g, function(block) {
			var placeholder = '\uE000' + codeBlocks.length.toString(36) + '\uE001';
			codeBlocks.push({placeholder: placeholder, html: block});
			return placeholder;
		});
		text = this.replaceLineBreaks(text);
		text = this.replaceHyperLinks(text);
		text = this.replaceEmoticons(text);
		text = this.breakLongWords(text);
		text = this.replaceCustomText(text);
		for(var i = 0; i < codeBlocks.length; i++) {
			text = text.replace(codeBlocks[i].placeholder, codeBlocks[i].html);
		}
		return text;
	}
};

var source = 'before\n[code=php]\n<?php\necho "<tag>"; // https://example.com\n[/code]\nafter';
var rendered = ajaxChat.replaceText(source);
function assert(condition, message) {
	if(!condition) {
		console.error('FAIL: ' + message);
		process.exit(1);
	}
}
assert(rendered.indexOf('<pre class="ajaxchat-code" data-language="php">') !== -1, 'language class is rendered');
assert(rendered.indexOf('&lt;tag&gt;') !== -1, 'HTML-like code is escaped');
assert(rendered.indexOf('<a>https://example.com</a>') === -1, 'URLs inside code are not auto-linked');
assert(rendered.indexOf('echo &quot;&lt;tag&gt;&quot;; // https://example.com') !== -1, 'code text and line breaks are preserved');
assert(rendered.indexOf('before<br/>') !== -1 && rendered.indexOf('</pre><br/>after') !== -1, 'normal message line breaks are retained');
console.log('Multiline code rendering checks passed.');
