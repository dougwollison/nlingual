<?php
// ======================== //
//	Public Rewrite Filters  //
// ======================== //

/**
 * locale filter.
 *
 * Intercept and replace the .mo filename to look for.
 *
 * @since 1.0.0
 *
 * @uses nL_get_lang()
 *
 * @param string $locale The basename (sans extension) of the locale file to look for.
 *
 * @return string The modified locale (if one is found).
 */
add_filter('locale', 'nLingual_intercept_local_name');
function nLingual_intercept_local_name($locale){
	if(!is_admin() && $mo = nL_get_lang('mo')){
		return $mo;
	}
	return $locale;
}

/**
 * option_(page_on_front/page_for_posts) filter.
 *
 * Replaces the value of the page ID to the translation of it in the current language.
 *
 * Won't run on admin side.
 *
 * @since 1.0.0
 *
 * @uses nL_get_translation()
 *
 * @param int $id The id of the page.
 *
 * @return int The id of the translation.
 */
function nLingual_get_curlang_version($id){
	if(!is_admin()){
		$id = nL_get_translation($id);
	}
	return $id;
}
add_filter('option_page_on_front', 'nLingual_get_curlang_version');
add_filter('option_page_for_posts', 'nLingual_get_curlang_version');

/**
 * link/url filters.
 *
 * Applies nL_localize_url() to most links.
 *
 * @since 1.0.0
 *
 * @see nLingual::localize_url()
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
 * page_link filter.
 *
 * Localizes the URL for the page in it's language, but first checks if
 * it's the Home page for that language; returns the appropriate url.
 *
 * @since 1.0.0
 *
 * @uses nL_get_post_lang()
 * @uses nL_get_translation()
 * @uses nL_localize_url()
 *
 * @param string $link    The page link to be filtered.
 * @param int    $post_id The ID of the page it belongs to.
 *
 * @return string The localized link.
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
 * page_link filter.
 *
 * Localizes the URL for the post in it's language.
 *
 * @since 1.0.0
 *
 * @uses nL_get_post_lang()
 * @uses nL_localize_url()
 *
 * @param string  $link The page link to be filtered.
 * @param WP_Post $post The post it belongs to.
 *
 * @return string The localized link.
 */
function nLingual_localize_post_permalink($link, $post){
	$link = nL_localize_url($link, nL_get_post_lang($post->ID), true);

	return $link;
}
add_filter('post_link', 'nLingual_localize_post_permalink', 10, 2);

/**
 * redirect_canonical filter.
 *
 * Handles redirecting to the proper localized version of the URL.
 *
 * @since 1.0.0
 *
 * @uses nL_localize_url()
 *
 * @param string $redirect_url  The intended redirect URL.
 * @param string $requested_url The originally requested URL.
 *
 * @return bool|string False if localized versions of both URLs match,
 *  the passed redirect_url if not.
 */
function nLingual_localize_redirect($redirect_url, $requested_url){
	if(nL_localize_url($redirect_url) == nL_localize_url($requested_url))
		return false;

	return $redirect_url;
}
add_filter('redirect_canonical', 'nLingual_localize_redirect', 10, 2);

if(nL_get_option('l10n_dateformat')){
	/**
	 * option_date_format filter.
	 *
	 * Localize the date_format option.
	 *
	 * @since 1.0.0
	 *
	 * @param string $format The original date format string.
	 *
	 * @return string The new localized format string.
	 */
	function nLingual_l10n_date_format($format){
		if(!is_admin()){
			$format = __($format, wp_get_theme()->get('TextDomain'));
		}

		return $format;
	}
	add_filter('option_date_format', 'nLingual_l10n_date_format');
}

/**
 * body_class filter.
 *
 * Fix class names that contain %'s (because their encoded non-ascii names,
 * attempts to add the name of it's default language post, and add the lang-[lang] class.
 *
 * @since 1.0.0
 *
 * @global wpdb $wpdb The database abstraction class instance.
 *
 * @uses nL_current_lang()
 */
function nLingual_add_language_body_class($classes){
	global $wpdb;
	$object = get_queried_object();

	foreach($classes as &$class){
		// Fix class names that contain % signs.
		$class = str_replace('%', '-', $class);
	}

	// If the language is not in the default language, try to add
	// the post name of the it's default language sister.
	if(is_singular() && !nL_in_default_lang($object->ID) && $orig = nL_get_translation($object->ID, true, false)){
		$orig_name = $wpdb->get_var($wpdb->prepare("SELECT post_name FROM $wpdb->posts WHERE ID = %d", $orig));
		$classes[] = "$orig_name $orig_name--$lang";
	}

	$classes[] = "lang-".nL_current_lang();

	return $classes;
}
add_filter('body_class', 'nLingual_add_language_body_class');

/**
 * language_attributes filter.
 *
 * Replace with the current language's ISO name.
 *
 * @since 1.0.0
 *
 * @uses nL_get_lang()
 *
 * @param string $atts The attributes list for the HTML element.
 *
 * @return string The modified attributes list.
 */
function nLingual_html_language_attributes($atts){
	$atts = preg_replace('/lang=".+?"/', 'lang="'.nL_get_lang('iso').'"', $atts);
	return $atts;
}
add_filter('language_attributes', 'nLingual_html_language_attributes');