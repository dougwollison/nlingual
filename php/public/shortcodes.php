<?php
// ===================== //
//	Language Shortcodes  //
// ===================== //

/**
 * the_content filter.
 *
 * Unwrap the language shortcodes (fixes extra empty p tags).
 *
 * @since 1.0.0
 *
 * @uses nL_langs_by_slug()
 *
 * @param string $content The post content.
 *
 * @return string The modified post content.
 */
function nLingual_shortcodes($content){
	global $languages;
	//Strip closing p tags and opening p tags from beginning/end of string
	$content = preg_replace('#^\s*(?:</p>)\s*([\s\S]+)\s*(?:<p.*?>)\s*$#', '$1', $content);
	//Unwrap tags
	$content = preg_replace('#(?:<p.*?>)?(\[/?(?:'.implode('|', array_keys(nL_langs_by_slug())).').*\])(?:</p>)?#', '$1', $content);

	return trim($content);
}
add_filter('the_content', 'nLingual_shortcodes');

/**
 * [lang] shortcode.
 *
 * Return the containing text ONLY if it's for the current language.
 *
 * @since 1.0.0
 *
 * @uses nL_current_lang()
 *
 * @param array  $atts    The tag attributes (ignored; there shouldn't be any).
 * @param string $content The tag content.
 * @param string $lang    The name of the tag (the language slug).
 *
 * @return null|string NULL if not the current language, $content if so.
 */
function nLingual_show_language($atts, $content, $lang){
	if($lang == nL_current_lang()) return trim(do_shortcode($content));
	return null;
}

/**
 * Add a shortcode for each language slug.
 *
 * @since 1.0.0
 *
 * @uses nL_languages()
 */
foreach(nL_languages() as $lang){
	add_shortcode($lang->slug, 'nLingual_show_language');
}