<?php
// ========================= //
//	Language Dections Hooks  //
// ========================= //

/*
 * Detect and set the requested language
 */
add_action('plugins_loaded', 'nLingual_detect_requested_language', 0);
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
		// Update host & uri with processed versions
		$_SERVER['HTTP_HOST'] = $processed['host'];
		$_SERVER['REQUEST_URI'] = $processed['path'].($processed['query'] ? '?'.$processed['query'] : '');
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

/*
 * Run nL_maybe_redirect to check if any redirection is needed
 */
add_action('wp', 'nL_maybe_redirect');