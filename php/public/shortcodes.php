<?php
// ===================== //
//	Language Shortcodes  //
// ===================== //

/*
 * Add filter for unwrapping language shortcodes
 */
add_filter('the_content', 'nLingual_shortcodes');
function nLingual_shortcodes($content){
	global $languages;
	//Strip closing p tags and opening p tags from beginning/end of string
	$content = preg_replace('#^\s*(?:</p>)\s*([\s\S]+)\s*(?:<p.*?>)\s*$#', '$1', $content);
	//Unwrap tags
	$content = preg_replace('#(?:<p.*?>)?(\[/?(?:'.implode('|', array_keys(nL_langs_by_slug())).').*\])(?:</p>)?#', '$1', $content);

	return trim($content);
}

/*
 * Add a shortcode for each language slug
 */
foreach(nL_languages() as $lang){
	add_shortcode($lang->slug, 'nLingual_show_language');
}
/*
 * Language shortcode; return the containing text ONLY if it's for the current language
 */
function nLingual_show_language($atts, $content, $lang){
	if($lang == nL_current_lang()) return trim(do_shortcode($content));
	return null;
}