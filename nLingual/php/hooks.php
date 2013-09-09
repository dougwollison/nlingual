<?php
add_action('init', function(){
	if(isset($_GET['lang'])){
		if(!$_GET['lang']) $_GET['lang'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		set_curlang($_GET['lang']);
		unset($_GET['lang']);
	}elseif(isset($_POST['lang'])){
		if(!$_POST['lang']) $_POST['lang'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		set_curlang($_POST['lang']);
		unset($_POST['lang']);
	}else{
		set_curlang($_SERVER['HTTP_ACCEPT_LANGUAGE'], false);
		unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
	}
});

add_action('parse_request', function(&$wp){
	global $wpdb;
	if(!is_admin() && isset($wp->query_vars['pagename'])){
		$name = basename($wp->query_vars['pagename']);
		$id = get_post_id_by_name($name);

		if(!in_default_language($id)){
			$lang = get_language($id);
			$orig = get_original_post($id);

			if($orig == get_option('page_on_front')){
				$wp->query_vars = array();
				$wp->request = null;
				$wp->matched_rule = null;
				$wp->matched_query = null;
			}

			set_curlang($lang);
		}
	}
});

add_action('parse_query', function(&$wp_query){
	if(!is_admin() && in_array($wp_query->query_vars['post_type'], theme_post_types()) && !isset($wp_query->query_vars['language'])){
		$wp_query->query_vars['language'] = get_curlang();
	}
});

add_action('wp', function(&$wp){
	global $wp_query;
	if(!is_admin()){
		if(isset($wp_query->post)){
			$lang = get_language($wp_query->post->ID);
		}

		set_curlang($lang);

		unset($GLOBALS['wp_locale']);
		global $wp_locale;
		$wp_locale = new theme_WP_Locale();
	}
});

add_filter('locale', function($locale){
	if(!is_admin()){
		return get_curlang('mo');
	}
	return $locale;
});

add_filter('option_page_on_front', 'get_curlang_version');
add_filter('option_page_for_posts', 'get_curlang_version');
function get_curlang_version($value){
	if(!is_admin()){
		$value = get_translated_post($value);
	}
	return $value;
}

add_filter('the_title', 'add_original_title', 100);
function add_original_title($title){
	global $post, $wpdb;

	$obj = $post;
	if(is_object($title)){
		$obj = $title;
		$title = $title->post_title;
	}

	if(is_admin() && is_object($obj)){
		if(!in_default_language($obj->ID)){
			$lang = get_language($obj->ID);
			$origID = get_original_post($obj->ID, false);
			$orig = $wpdb->get_var($wpdb->prepare("SELECT post_title FROM $wpdb->posts WHERE ID = %d", $origID));
			if($orig){
				$title .= " [$lang; $orig]";
			}
		}
	}
	return $title;
}

add_filter('option_blogname', 'split_langs');
add_filter('the_title', 'split_langs');

add_filter('option_date_format', function($format){
	if(!is_admin())
		$format = __($format, 'foresite');

	return $format;
});

add_filter('body_class', function($classes){
	global $wp_query, $wpdb;
	foreach($classes as &$class){
		if(strpos($class, '%') !== false){
			$lang = get_language($wp_query->queried_object->ID);
			$orig = get_original_post($wp_query->queried_object->ID);
			$class = $wpdb->get_var($wpdb->prepare("SELECT post_name FROM $wpdb->posts WHERE ID = %d", $orig));
			$class .= " $class--$lang";
		}
	}

	$classes[] = "lang-".get_curlang();

	return $classes;
});

add_filter('language_attributes', function($atts){
	$atts = preg_replace('/lang=".+?"/', 'lang="'.get_curlang('iso').'"', $atts);
	return $atts;
});