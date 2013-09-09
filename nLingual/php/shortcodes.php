<?php
add_filter('the_content', function($content){
	global $languages;
	//Strip closing p tags and opening p tags from beginning/end of string
	$content = preg_replace('#^\s*(?:</p>)\s*([\s\S]+)\s*(?:<p.*?>)\s*$#', '$1', $content);
	//Unwrap tags
	$content = preg_replace('#(?:<p.*?>)?(\[/?(?:'.implode('|', array_keys($languages)).').*\])(?:</p>)?#', '$1', $content);

	return trim($content);
});

foreach($languages as $lang => $data){
	add_shortcode($lang, 'show_language');
}
function show_language($atts, $content, $lang){
	if($lang == get_curlang()) return trim(do_shortcode($content));
}