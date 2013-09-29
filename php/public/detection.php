<?php
// ========================= //
//	Language Dections Hooks  //
// ========================= //

/*
 * Detect and set the requested language
 */
add_action('plugins_loaded', 'nLingual_detect_requested_language', 0);
function nLingual_detect_requested_language(){
	// Get the accepted language, host name and requested uri
	$alang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
	$host = $_SERVER['HTTP_HOST'];
	$uri = $_SERVER['REQUEST_URI'];

	$lang = null;

	// First, use the HTTP_ACCEPT_LANGUAGE method if valid
	if(nL_lang_exists($alang)){
		$lang = $alang;
	}

	// Process the host & uri and get the language
	if($result = nL_process_url($host, $uri)){
		$lang = $result['lang'];
		// Update host & uri with processed versions
		$_SERVER['HTTP_HOST'] = $result['host'];
		$_SERVER['REQUEST_URI'] = $result['path'];
	}

	// Override with get_var method if present and valid
	$get_var = nL_get_option('get_var');
	if($get_var && isset($_GET[$get_var]) && nL_lang_exists($_GET[$get_var])){
		$lang = $_GET[$get_var];
	}

	// Override with post_var method if present and valid
	$post_var = nL_get_option('post_var');
	if($post_var && isset($_POST[$post_var]) && nL_lang_exists($_POST[$post_var])){
		$lang = $_POST[$post_var];
	}

	// Set the language if determined, but don't lock it
	if($lang) nL_set_lang($lang, false);
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
			$lang = nL_get_post_lang($id);
			$orig = nL_get_translation($id, true);

			if($orig == get_option('page_on_front')){
				$wp->query_vars = array();
				$wp->request = null;
				$wp->matched_rule = null;
				$wp->matched_query = null;
			}

			// Primarily if it's the Front or Posts page,
			// we need to set the language here and now
			nL_set_lang($lang);
		}
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

			// Set the language
			nL_set_lang($lang);
		}

		// Now that the language is definitely set,
		// override the $wp_locale
		global $wp_locale;
		// Load the nLingual local class
		require(dirname(NL_SELF).'/php/nLingual_WP_Locale.php');
		$wp_locale = new nLingual_WP_Locale();
	}
}