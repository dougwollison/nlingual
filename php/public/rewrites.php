<?php
// ========================= //
//	General Rewrite Filters  //
// ========================= //

/**
 * Intercept and replace the .mo filename to look for
 *
 * @since 1.0.0
 */
add_filter('locale', 'nLingual_intercept_local_name');
function nLingual_intercept_local_name($locale){
	if(!is_admin() && $mo = nL_get_lang('mo')){
		return $mo;
	}
	return $locale;
}

/**
 * Replace the page_on_front and page_for_posts values with the translated version
 *
 * @since 1.0.0
 */
add_filter('option_page_on_front', 'nLingual_get_curlang_version');
add_filter('option_page_for_posts', 'nLingual_get_curlang_version');
function nLingual_get_curlang_version($value){
	if(!is_admin()){
		$value = nL_get_translation($value);
	}
	return $value;
}

/**
 * Add fitler for running split_langs on the blogname
 *
 * @since 1.0.0
 */
add_filter('option_blogname', 'nL_split_langs');
add_filter('option_blogdescription', 'nL_split_langs');
add_filter('the_title', 'nL_split_langs');

/**
 * Link filters
 * Applies nL_localize_url() to most links
 *
 * @since 1.0.0
 */
add_filter('author_feed_link',				'nL_localize_url');
add_filter('author_feed_link',				'nL_localize_url');
add_filter('author_link',					'nL_localize_url');
add_filter('category_feed_link',			'nL_localize_url');
add_filter('category_link',					'nL_localize_url');
add_filter('day_link',						'nL_localize_url');
add_filter('feed_link',						'nL_localize_url');
add_filter('get_comment_author_url_link',	'nL_localize_url');
add_filter('get_pagenum_link',				'nL_localize_url');
add_filter('home_url',						'nL_localize_url');
add_filter('month_link',					'nL_localize_url');
add_filter('post_comments_feed_link',		'nL_localize_url');
add_filter('tag_feed_link',					'nL_localize_url');
add_filter('tag_link',						'nL_localize_url');
add_filter('term_link',						'nL_localize_url');
add_filter('the_permalink',					'nL_localize_url');
add_filter('year_link',						'nL_localize_url');

/**
 * Page link filter
 * Localizes the URL for the page in it's language,
 * but first checks if it's the Home page for that langauge;
 * returns the appropriate home url
 *
 * @since 1.0.0
 */
function nLingual_localize_page_permalink($link, $post_id){
	$lang = nL_get_post_lang($post_id);
	if($post_id == nL_get_translation(get_option('page_on_front'), $lang)){
		$link = nL_localize_url(home_url(), $lang, true);
	}else{
		$link = nL_localize_url($link, $lang, true);
	}

	return $link;
}
add_filter('page_link', 'nLingual_localize_page_permalink', 10, 2);

/**
 * Post link filter
 * Localizes the post link in the posts language
 *
 * @since 1.0.0
 */
function nLingual_localize_post_permalink($link, $post){
	$link = nL_localize_url($link, nL_get_post_lang($post->ID), true);

	return $link;
}
add_filter('post_link', 'nLingual_localize_post_permalink', 10, 2);

/**
 * Redirect canonical filter
 * Handles redirecting to the proper localized version of the URL
 *
 * @since 1.0.0
 */
function nLingual_localize_redirect($redirect_url, $requested_url){
	if(nL_localize_url($redirect_url) == nL_localize_url($requested_url))
		return false;

	return $redirect_url;
}
add_filter('redirect_canonical', 'nLingual_localize_redirect', 10, 2);

/**
 * Date format option filter
 * If l10n_dateformat option is true, localize the date_format value
 *
 * @since 1.0.0
 */
if(nL_get_option('l10n_dateformat')){
	function nLingual_l10n_date_format($format){
		if(!is_admin()){
			$format = __($format, wp_get_theme()->get('TextDomain'));
		}

		return $format;
	}
	add_filter('option_date_format', 'nLingual_l10n_date_format');
}

/**
 * Body class filter
 * Fix class names that contain %'s (because their encoded non-ascii names, and add the lang-[lang] class
 *
 * @since 1.0.0
 */
function nLingual_add_language_body_class($classes){
	global $wpdb;
	$object = get_queried_object();
	foreach($classes as &$class){
		$class = str_replace('%', '-', $class);
	}

	$classes[] = "lang-".nL_current_lang();

	return $classes;
}
add_filter('body_class', 'nLingual_add_language_body_class');

/**
 * Langauge attributes filter
 * Update lang attribute to use the current language's ISO name
 *
 * @since 1.0.0
 */
function nLingual_html_language_attributes($atts){
	$atts = preg_replace('/lang=".+?"/', 'lang="'.nL_get_lang('slug').'"', $atts);
	return $atts;
}
add_filter('language_attributes', 'nLingual_html_language_attributes');