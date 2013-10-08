<?php
// ========================= //
//	Language Dections Hooks  //
// ========================= //

/**
 * plugins_loaded action.
 *
 * Detect what the requested language is (based on method option)
 * overriding if the $_GET or $_POST variables are set, and then
 * applies the language via nL_set_lang().
 *
 * @since 1.2.0 Simplified URL processing; won't rewrite HTTP_HOST or REQUEST_URI.
 * @since 1.0.0
 *
 * @uses nL_lang_exists()
 * @uses nL_process_url()
 * @uses nL_get_option()
 * @uses nL_set_lang()
 */
function nLingual_detect_requested_language(){
	// Get the accepted language
	$alang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

	$lang = null;

	// First, use the HTTP_ACCEPT_LANGUAGE method if valid
	if(nL_lang_exists($alang)){
		$lang = $alang;
	}

	// Process the $here url (within nLingual) and get the language
	if($processed = nL_process_url()){
		$lang = $processed['lang'];
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
add_action('plugins_loaded', 'nLingual_detect_requested_language', 0);

/**
 * wp action.
 *
 * Detect the language of the requested post and apply it
 * Also replace the $wp_locale with the custom one.
 *
 * @since 1.0.0
 *
 * @global WP_Query $wp_query The main WP_Query instance.
 *
 * @uses nL_get_post_lang()
 * @uses nL_set_lang()
 * @uses nLingual_WP_Locale
 *
 * @param WP $wp The WordPress environment instance (by reference).
 */
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
add_action('wp', 'nLingual_detect_requested_post_language');

/**
 * wp action.
 *
 * Run nL_maybe_redirect() to check if any redirection is needed.
 *
 * @since 1.0.0
 *
 * @see nLingual::maybe_redirect()
 */
add_action('wp', 'nL_maybe_redirect');