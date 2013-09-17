<?php
/*
 * The nLingual class
 * Static use class for utilizing configuration options
 */

define('NL_REDIRECT_USING_PATH', 'NL_REDIRECT_USING_PATH');
define('NL_REDIRECT_USING_DOMAIN', 'NL_REDIRECT_USING_DOMAIN');
define('NL_REDIRECT_USING_ACCEPT', 'NL_REDIRECT_USING_ACCEPT');

class nLingual{
	protected static $options;
	protected static $languages = array();
	protected static $languages_by_iso = array();
	protected static $post_types;
	protected static $separator;
	protected static $default;
	protected static $cache = array();
	protected static $current;
	protected static $current_cache;
	protected static $domains = array(
		'theme' => 'default',
		'plugin' => 'nLingual'
	);

	/*
	 * Initialization method
	 * Loads options into local properties
	 */
	public static function init(){
		// Load options
		self::$options = wp_parse_args((array) get_option('nLingual-options'), array(
			// Default language
			'default_lang' => 'en',

			// Redirection settings
			'method' => NL_REDIRECT_USING_ACCEPT,
			'get_var' => 'lang',
			'post_var' => 'lang',

			// Supported post types
			'post_types' => array('page', 'post'),

			// Split settings
			'split_separator' => '//',

			// Auto localize...
			'l10n_dateformat' => true,

			// Syncronizing options
			'sync_post_fields' => array(),
			'sync_meta_fields' => array(),
			'sync_taxonomies' => array()
		));

		// Load languages
		$languages = get_option('nLingual-languages');
		// Default to english if no langauges are set
		if(!$languages) $languages = array(
			array(
				'iso'		=> 'en',
				'mo'		=> 'english',
				'tag'		=> 'En',
				'name'		=> 'English',
				'native'	=> 'English'
			)
		);
		self::$languages = $languages;

		// Loop through the languages and create a by_iso indexed version
		foreach($languages as $lang){
			self::$languages_by_iso[$lang['iso']] = $lang;
		}

		// Load  post types, defualt language, and set current langauge
		self::$post_types = self::get_option('post_types');
		self::$default = self::get_option('default_lang');
		self::$current = self::$default;

		// Create and register the translations table
		global $wpdb, $table_prefix;
		$wpdb->nL_translations = $table_prefix.'nL_translations';
		$wpdb->query("
		CREATE TABLE IF NOT EXISTS `$wpdb->nL_translations` (
			`translation_id` bigint(20) NOT NULL,
			`language` char(2) NOT NULL,
			`post_id` bigint(20) NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;
		");

		// Register the language taxonomy and terms
		add_action('init', array('nLingual', 'register_taxonomy'));

		// When the theme is loaded, set the theme domain
		add_action('after_theme_setup', array('nLingual', 'get_theme_domain'));
	}

	/*
	 * Return the plugin or theme's text domain
	 *
	 * @param bool $theme Wether to return the theme's domain or the plugin's
	 */
	public static function domain($theme = false){
		return self::$domains[$theme ? 'theme' : 'plugin'];
	}

	/*
	 * Return the value of a particular option
	 *
	 * @param string $name The name of the option to retrieve
	 */
	public static function get_option($name){
		if(isset(self::$options[$name])){
			return self::$options[$name];
		}

		return null;
	}

	/*
	 * Return the languages array
	 */
	public static function languages(){
		return self::$languages_by_iso;
	}

	/*
	 * Return the post_types array
	 */
	public static function post_types(){
		return self::$post_types;
	}

	/*
	 * Return the default language
	 */
	public static function default_lang(){
		return self::$default;
	}

	/*
	 * Hook for registering the Language taxonomy and terms
	 */
	public static function register_taxonomy(){
		// Register the Language taxonomy
		register_taxonomy(
			'language',
			nLingual::$post_types,
			array(
				'hierarchical'			=> false,
			    'show_ui'				=> false,
			    'show_admin_column'		=> true,
			    'update_count_callback'	=> '_update_post_term_count',
				'labels' => array(
					'name'							=> _x('Languages', 'taxonomy general name'),
					'singular_name'					=> _x('Language', 'taxonomy singular name'),
					'search_items'					=> __('Search Languages'),
					'popular_items'					=> __('Popular Languages'),
					'all_items'						=> __('All Languages'),
					'parent_item'					=> null,
					'parent_item_colon'				=> null,
					'edit_item'						=> __('Edit Language'),
					'update_item'					=> __('Update Language'),
					'add_new_item'					=> __('Add New Language'),
					'new_item_name'					=> __('New Language Name'),
					'separate_items_with_commas'	=> __('Separate languages with commas'),
					'add_or_remove_items'			=> __('Add or remove languages'),
					'choose_from_most_used'			=> __('Choose from the most used languages'),
					'not_found'						=> __('No languages found.'),
					'menu_name'						=> __('Languages'),
				)
			)
		);

		// Insert any terms needed
		foreach(self::$languages as $lang){
			if(!term_exists($lang['iso'], 'language')){
				wp_insert_term(
					$lang['name'],
					'language',
					array(
						'slug' => $lang['iso'],
					)
				);
			}
		}
	}

	/*
	 * Sanitize callback for processing the language data
	 */
	public static function process_languages(){
		print_r($_POST);exit;
	}

	/*
	 * Hook for caching the theme domain
	 */
	public static function get_theme_domain(){
		$domain = wp_get_theme()->get('TextDomain');
		if(self::$domains['theme'] == 'default' && $domain){
			self::$domains['theme'] = $domain;
		}
	}

	/*
	 * Get the cached language of the specified post id
	 *
	 * @param int $id The ID of the post in question
	 */
	public static function cacheGet($id){
		return self::$cache[$id];
	}

	/*
	 * Set the cached language of the specified post id
	 *
	 * @param int $id The ID of the post in question
	 * @param string $lang The language to cache fo the ID
	 */
	public static function cacheSet($id, $lang){
		self::$cache[$id] = $lang;
	}

	/*
	 * Test if a language is registered
	 *
	 * @param string $lang The slug of the language
	 */
	public static function lang_exists($lang){
		return isset(self::$languages_by_iso[$lang]);
	}

	/*
	 * Get the langauge property (or the full array) of a specified langauge (current language by default)
	 *
	 * @param string $field Optional The field to retrieve
	 * @param string $lang Optional The language to retrieve from
	 */
	public static function get_lang($field = null, $lang = null){
		if(is_null($lang))
			$lang = self::$current;
		elseif(!self::lang_exists($lang))
			return false;

		return is_null($field) ? $lang : self::$languages_by_iso[$lang][$field];
	}

	/*
	 * Set the current langauge
	 *
	 * @param string $lang The language to set/switchto
	 * @param bool $lock Wether or not to lock the change
	 */
	public static function set_lang($lang, $lock = true){
		if(defined('NLINGUAL_LANG_SET')) return;
		if($lock) define('NLINGUAL_LANG_SET', true);

		if(self::lang_exists($lang))
			self::$current = self::$current_cache = $lang;

		if(!$temp){
			return load_theme_textdomain(wp_get_theme()->get('TextDomain'), get_template_directory().'/lang');
		}

		return true;
	}

	/*
	 * Switch to the specified language (does not affect loaded text domain)
	 */
	public static function switch_lang($lang){
		self::$current = $lang;
	}

	/*
	 * Restore the current language to what it was before
	 */
	public static function restore_lang(){
		self::$current = self::$current_cache;
	}

	/*
	 * Get the language of the post in question, according to the nL_translations table
	 *
	 * @param mixed $id The ID or object of the post in question (defaults to current $post)
	 * @param string $default The default language to return should none be found (defaults to default language)
	 */
	public static function get_post_lang($id = null, $default = null){
		global $wpdb;

		if(is_null($default)){
			$default = self::$default;
		}

		if(is_null($id)){
			global $post;
			$id = $post->ID;
		}if(is_object($id)){
			$id = $id->ID;
		}

		// Check if it's cached, return if so
		if($lang = self::cacheGet($id)) return $lang;

		// Query the nL_translations table for the langauge of the post in question
		$lang = $wpdb->get_var($wpdb->prepare("SELECT language FROM $wpdb->nL_translations WHERE post_id = %d", $id));

		// If no language is found, make it the $default one
		if(!$lang) $lang = $default;

		// Add it to the cache
		self::cacheSet($id, $lang);

		return $lang;
	}

	/*
	 * Set the language of the post in question for the nL_translations table
	 *
	 * @param mixed $id The ID or object of the post in question (defaults to current $post)
	 * @param string $lang The language to set the post to (defaults to default language)
	 */
	public static function set_post_lang($id = null, $lang = null){
		global $wpdb;

		if(is_null($default)){
			$default = self::$default;
		}

		if(is_null($id)){
			global $post;
			$id = $post->ID;
		}if(is_object($id)){
			$id = $id->ID;
		}

		// Get the translation_id to use for this query
		if(!($translation_id = $wpdb->get_var($wpdb->prepare("SELECT translation_id FROM $wpdb->nL_translations WHERE post_id = %d", $id)))){
			$translation_id = $wpdb->get_var("SELECT MAX(translation_id) + 1 FROM $wpdb->nL_translations");
		}

		// Run the REPLACE query
		$wpdb->replace(
			$wpdb->nL_translations,
			array(
				'translation_id' => $translation_id,
				'language' => $lang,
				'post_id' => $id
			),
			array('%d', '%s', '%d')
		);

		// Add/Update the cache of it, just in case
		self::cacheSet($id, $lang);
	}

	/*
	 * Test if a post is in the specified language
	 *
	 * @param mixed $id The ID or object of the post in question (defaults to current $post)
	 */
	public static function in_this_lang($id, $lang){
		return self::get_post_lang($id, null) == $lang;
	}

	/*
	 * Test if a post is in the default language
	 *
	 * @param mixed $id The ID or object of the post in question (defaults to current $post)
	 */
	public static function in_default_lang($id = null){
		return self::get_post_lang($id, null) == self::$default;
	}

	/*
	 * Test if a post is in the current language
	 *
	 * @param mixed $id The ID or object of the post in question (defaults to current $post)
	 */
	public static function in_current_lang($id){
		return sefl::get_post_lang($id, null) == self::$current;
	}

	/*
	 * Get the translation of the post in the provided language, via the nL_translations table
	 *
	 * @param mixed $id The ID or object of the post in question (defaults to current $post)
	 * @param string $lang The slug of the language requested (defaults to current language)
	 * @param bool $return_self Wether or not to return the provided $id or just false should no original be found
	 */
	public static function get_translation($id, $lang = null, $return_self = true){
		global $wpdb;

		if(is_null($id)){
			global $post;
			$id = $post->ID;
		}if(is_object($id)){
			$id = $id->ID;
		}

		if(is_null($lang))
			$lang = self::$current;

		// Get the language according to the translations table
		$translation = $wpdb->get_var($wpdb->prepare("
			SELECT
				t1.post_id
			FROM
				$wpdb->nL_translations AS t1
				LEFT JOIN
					$wpdb->nL_translations AS t2
					ON (t1.translation_id = t2.translation_id)
			WHERE
				t2.post_id = %d
				AND t1.language = %s
		", $id, $lang));

		// Return the translation's id if found
		if($translation) return $translation;

		// Otherwise, return the original id or false, depending on $return_self
		return $return_self ? $id : false;
	}

	/*
	 * Return the IDs of all posts associated with this one, according to the nL_translations table
	 *
	 * @param id $post_id The id of the post
	 * @param bool $include_self Wether or not to include itself in the returned list
	 */
	public static function associated_posts($post_id, $include_self = true){
		global $wpdb;

		$query = "
			SELECT
				t1.post_id
			FROM
				$wpdb->nL_translations AS t1
				LEFT JOIN
					$wpdb->nL_translations AS t2
					ON (t1.translation_id = t2.translation_id)
			WHERE
				t2.post_id = %$1d
		";

		if(!$include_self){
			$query .= "AND t1.post_id != %$1d";
		}

		$posts = $wpdb->get_col($wpdb->prepare($query, $post_id));

		return $posts;
	}

	/*
	 * Get the permalink of the specified post in the specified language
	 *
	 * @param mixed $id The ID or object of the post in question (defaults to current $post)
	 * @param string $lang The slug of the language requested (defaults to current language)
	 * @param bool $echo Wether or not to echo the resulting $link
	 */
	public static function get_permalink($id = null, $lang = null, $echo = true){
		global $wpdb;

		if(is_null($lang))
			$lang = self::$current;

		$link = get_permalink(self::get_translated_post($id, $lang));

		$method = self::get_option('method');

		switch($method){
			case NL_REDIRECT_USING_DOMAIN:
				// Prepend the domain of the URL with the language slug
				$link = preg_replace('#(https?)://([\w\.-]+)#', '$1://'.$lang.'.$2', $link);
				break;
			case NL_REDIRECT_USING_PATH:
				// Prefix the path (after the base path of the site) with the language slug
				$home = stripslashget_option('home');
				$link = str_replace($home, "$home/$lang", $link);
				break;
			default:
				$link .= '?'.self::get_option('get_var').'='.$lang;
				break;
		}

		if($echo) echo $link;
		return $link;
	}

	/*
	 * Return or print a list of links to the current page in all available languages
	 *
	 * @param bool $echo Wether or not to echo the imploded list of links
	 * @param string $prefix The text to preceded the link list with
	 * @param string $sep The text to separate each link with
	 */
	public static function lang_links($echo = false, $prefix = '', $sep = ' '){
		echo $prefix;
		$links = array();
		foreach(self::$languages as $lang){
			$links[] = sprintf('<a href="%s">%s</a>', self::get_permalink(get_queried_object()->ID, $lang['iso'], false), $lang['native']);
		}

		if($echo) echo $prefix.implode($sep, $links);
		return $links;
	}

	/*
	 * Split a string at the separator and return the part corresponding to the specified language
	 *
	 * @param string $text The text to split
	 * @param string $lang The slug of the language requested (defaults to current language)
	 * @param string $sep The separator to use when splitting the string ($defaults to global separator)
	 * @param bool $force Wether or not to force the split when it normally would be skipped
	 */
	public static function split_langs($text, $lang = null, $sep = null, $force = false){
		if(is_null($lang))
			$lang = self::$current;
		if(is_null($sep))
			$sep = self::get_option('separator');

		if(!$sep) return $text;

		if(is_admin() && !$force && did_action('admin_notices')) return $text;

		$langs = array_map(function($lang){
			return $lang['iso'];
		}, self::$languages);

		$langn = array_search($lang, $langs);

		$sep = preg_quote($sep, '/');
		$text = preg_split("/\s*$sep\s*/", $text);

		if(isset($text[$langn])){
			$text = $text[$langn];
		}else{
			$text = $text[0];
		}

		return $text;
	}
}