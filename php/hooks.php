<?php
/*
 * Detect and set the requested language
 */
add_action('init', 'nLingual_detect_requested_language');
function nLingual_detect_requested_language(){
	$post_var = nL_get_option('post_var');
	$get_var = nL_get_option('get_var');
	$method = nL_get_option('method');

	$alang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

	$host = &$_SERVER['HTTP_HOST'];
	$uri = &$_SERVER['REQUEST_URI'];

	$lang = null;
	$lock = false;

	// First, use the HTTP_ACCEPT_LANGUAGE method if valid
	if(nL_lang_exists($alang)){
		$lang = $alang;
		$lock = false;
	}

	// Overried with domain method if we're using it, and if valid
	if($method == NL_REDIRECT_USING_DOMAIN && preg_match('#^([a-z]{2})\.#i', $host, $match) && nL_lang_exists($match[1])){
		$lang = $match[1];
		$host = substr($host, 3);
	}else // Or with path method if we're using that, and if valid
	if($method == NL_REDIRECT_USING_PATH && preg_match('#^/([a-z]{2})(/.*)?$#i', $uri, $match) && nL_lang_exists($match[1])){
		$lang = $match[1];
		$host = substr($uri, 3);
	}

	// Override with get_var method if present and valid
	if($get_var && isset($_GET[$get_var]) && nL_lang_exists($_GET[$get_var])){
		$lang = $_GET[$get_var];
	}

	// Override with post_var method if present and valid
	if($post_var && isset($_POST[$post_var]) && nL_lang_exists($_POST[$post_var])){
		$lang = $_POST[$post_var];
	}

	if($lang) nL_set_lang($lang, $lock);
}

/*
 * Check if a translated version of the front page is being requested,
 * adjust query to treat it as the front page
 */
add_action('parse_request', 'nLingual_check_alternate_frontpage');
function nLingual_check_alternate_frontpage(&$wp){
	global $wpdb;
	if(!is_admin() && isset($wp->query_vars['pagename'])){
		$name = basename($wp->query_vars['pagename']);
		$id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type != 'revision'", $name));

		if(!nL_in_default_lang($id)){
			$lang = nL_get_lang($id);
			$orig = nL_get_original_post($id);

			if($orig == get_option('page_on_front')){
				$wp->query_vars = array();
				$wp->request = null;
				$wp->matched_rule = null;
				$wp->matched_query = null;
			}

			nL_set_lang($lang);
		}
	}
}

/*
 * Set the language query_var if on the front end and requesting a language supporting post type
 */
add_action('parse_query', 'nLingual_set_language_query_var');
function nLingual_set_language_query_var(&$wp_query){
	if(!is_admin() && in_array($wp_query->query_vars['post_type'], nL_post_types()) && !isset($wp_query->query_vars['language'])){
		$wp_query->query_vars['language'] = nL_get_lang();
	}
}

/*
 * Detect the language of the requested post and apply
 */
add_action('wp', 'nLingual_detect_requested_post_language');
function nLingual_detect_requested_post_language(&$wp){
	global $wp_query;
	if(!is_admin()){
		if(isset($wp_query->post)){
			$lang = nL_get_post_lang($wp_query->post->ID);
			nL_set_lang($lang);
		}

		// Now that the language is definitely set,
		// override the $wp_locale
		global $wp_locale;
		// Load the nLingual local class
		require(__DIR__.'/nLingual_WP_Locale.php');
		$wp_locale = new nLingual_WP_Locale();
	}
}

/*
 * Intercept and replace the .mo filename to look for
 */
add_filter('locale', 'nLingual_intercept_local_name');
function nLingual_intercept_local_name($locale){
	if(!is_admin()){
		return nL_get_lang('mo');
	}
	return $locale;
}

/*
 * Replace the page_on_front and page_for_posts values with the translated version
 */
add_filter('option_page_on_front', 'nLingual_get_curlang_version');
add_filter('option_page_for_posts', 'nLingual_get_curlang_version');
function nLingual_get_curlang_version($value){
	if(!is_admin()){
		$value = nL_get_translated_post($value);
	}
	return $value;
}


/*
 * Add fitler for running split_langs on the blogname and the_title
 */
add_filter('option_blogname', 'nL_split_langs');
add_filter('the_title', 'nL_split_langs');

/*
 * If l10n_dateformat option is true, add fitler for localizing the date_format vlaue
 */
if(nL_get_option('l10n_dateformat')){
	add_filter('option_date_format', 'nLingual_l10n_date_format');
	function nLingual_l10n_date_format($format){
		if(!is_admin()){
			$format = __($format, nL_domain(true));
		}

		return $format;
	}
}

/*
 * Fix class names that contain %'s (because their encoded non-ascii names, and add the lang-[lang] class
 */
add_filter('body_class', 'nLingual_add_language_body_class');
function nLingual_add_language_body_class($classes){
	global $wpdb;
	$object = get_queried_object();
	foreach($classes as &$class){
		if(strpos($class, '%') !== false){
			$lang = nL_get_post_lang($object->ID);
			$orig = nL_get_original_post($object->ID);
			$class = $wpdb->get_var($wpdb->prepare("SELECT post_name FROM $wpdb->posts WHERE ID = %d", $orig));
			$class .= " $class--$lang";
		}
	}

	$classes[] = "lang-".nL_get_lang();

	return $classes;
}

/*
 * Update lang attribute to use the current languages ISO name
 */
add_filter('language_attributes', 'nLingual_html_language_attributes');
function nLingual_html_language_attributes($atts){
	$atts = preg_replace('/lang=".+?"/', 'lang="'.nL_get_lang('iso').'"', $atts);
	return $atts;
}