<?php
/**
 * nLingual API
 *
 * @package nLingual
 *
 * @since 1.0.0
 */

/**
 * Flag for path redirection method
 *
 * @since 1.0.0
 */
define('NL_REDIRECT_USING_PATH', 'NL_REDIRECT_USING_PATH');

/**
 * Flag for domain redirection method
 *
 * @since 1.0.0
 */
define('NL_REDIRECT_USING_DOMAIN', 'NL_REDIRECT_USING_DOMAIN');

/**
 * Flag for GET/POST redirection method
 *
 * @since 1.0.0
 */
define('NL_REDIRECT_USING_GET', 'NL_REDIRECT_USING_GET');

/**
 * nLingual API class
 *
 * API and utility collection for language, translation, and url processing/handling.
 * This class is intended to be used in a static fashion; global function-style
 * aliases can be found in nLingual.aliases.php.
 *
 * @package nLingual
 *
 * @since 1.0.0
 */
class nLingual{
	// ============ //
	//  Properties  //
	// ============ //

	/**
	 * The options storage array.
	 *
	 * @since 1.0.0
	 *
	 * @access protected (static)
	 *
	 * @var array
	 */
	protected static $options = array();

	/**
	 * The synchronization rules storage array.
	 *
	 * @since 1.0.0
	 *
	 * @access protected (static)
	 *
	 * @var array
	 */
	protected static $sync_rules = array();

	/**
	 * The list of languages.
	 *
	 * @since 1.0.0
	 *
	 * @access protected (static)
	 *
	 * @var array
	 */
	protected static $languages = array();

	/**
	 * The list of languages, sorted by ID.
	 *
	 * @since 1.0.0
	 *
	 * @access protected (static)
	 *
	 * @var array
	 */
	protected static $languages_by_id = array();

	/**
	 * The list of languages, sorted by slug.
	 *
	 * @since 1.0.0
	 *
	 * @access protected (static)
	 *
	 * @var array
	 */
	protected static $languages_by_slug = array();

	/**
	 * The list of supported post types.
	 *
	 * @since 1.0.0
	 *
	 * @access protected (static)
	 *
	 * @var array
	 */
	protected static $post_types = array();

	/**
	 * The separate for split_langs().
	 *
	 * @since 1.0.0
	 *
	 * @access protected (static)
	 *
	 * @var string
	 */
	protected static $separator;

	/**
	 * The slug of the default language.
	 *
	 * @since 1.0.0
	 *
	 * @access protected (static)
	 *
	 * @var string
	 */
	protected static $default;

	/**
	 * The slug of the current language.
	 *
	 * @since 1.0.0
	 *
	 * @access protected (static)
	 *
	 * @var string
	 */
	protected static $current;

	/**
	 * The slug of the other language (first language in the
	 * list that's not current).
	 *
	 * Usefully only for bilingual setups.
	 *
	 * @since 1.1.0
	 *
	 * @access protected (static)
	 *
	 * @var string
	 */
	protected static $other;

	/**
	 * A copy of $current for use in switch_lang().
	 *
	 * @since 1.0.0
	 *
	 * @access protected (static)
	 *
	 * @var string
	 */
	protected static $current_cache;

	/**
	 * An internal cache array for post languages and urls.
	 *
	 * @since 1.0.0
	 *
	 * @access protected (static)
	 *
	 * @var array
	 */
	protected static $cache = array();

	/**
	 * The list of loaded text domains for reload_textdomains().
	 *
	 * @since 1.0.0
	 *
	 * @access protected (static)
	 *
	 * @var array
	 */
	protected static $loaded_textdomains = array();

	/**
	 * The blog home url (so we don't have to call home_url or get_option every time).
	 *
	 * @since 1.0.0
	 *
	 * @access protected (static)
	 *
	 * @var string
	 */
	protected static $home_url;

	/**
	 * The parse_url array of $home_url.
	 *
	 * @since 1.0.0
	 *
	 * @access protected (static)
	 *
	 * @var array
	 */
	protected static $home;

	/**
	 * The current URL (https?://$_SERVER['HOST_NAME']$_SERVER['REQUEST_URI']).
	 *
	 * Used when we need to know what the original URL was before it was modified by nLingual.
	 *
	 * @since 1.0.0
	 *
	 * @access protected (static)
	 *
	 * @var string
	 */
	protected static $here_url;

	/**
	 * The parse_url array of $here_url.
	 *
	 * @since 1.0.0
	 *
	 * @access protected (static)
	 *
	 * @var array
	 */
	protected static $here;

	// ================= //
	//  Utility Methods  //
	// ================= //

	/**
	 * Utility function, make $lang the default if === true, the current if === null.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed &$lang The lang variable to process.
	 */
	public static function _lang(&$lang){
		if($lang === null)
			$lang = self::$current;
		elseif($lang === true)
			$lang = self::$default;
	}

	/**
	 * Utility function, return the by_id or by_slug array based on the provided $lang.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $lang The language id or slug to use and alter.
	 *
	 * @return array The proper languages array based on $lang.
	 */
	public static function _languages(&$lang){
		$array = self::$languages_by_slug;
		if(is_numeric($lang)){
			$lang = intval($lang);
			$array = self::$languages_by_id;
		}

		return $array;
	}

	/**
	 * Utility function, get the translation_id to use for insert/replace/update queries.
	 *
	 * Leave blank to use 0, which will yeild a new group id.
	 *
	 * @since 1.2.0 Added default arg of 0 to get a new group id.
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param int $id The post ID to find the existing translation_id for (default: 0).
	 *
	 * @return int The id of the translation to use.
	 */
	public static function _translation_group_id($id = 0){
		global $wpdb;

		if(!($group_id = $wpdb->get_var($wpdb->prepare("SELECT group_id FROM $wpdb->nL_translations WHERE post_id = %d", $id)))){
			// This will be new, so we have to get a new translation_id
			$group_id = $wpdb->get_var("SELECT MAX(group_id) + 1 FROM $wpdb->nL_translations");
		}

		return $group_id;
	}

	// ================================= //
	//  Initialization and Hook Methods  //
	// ================================= //

	/**
	 * Loads options into local properties.
	 *
	 * @since 1.2.0 Admin_only option, active languages only one front end, Plugin::table() usage.
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses PluginToolkit::makeTable()
	 * @uses nLingual::$home_url
	 * @uses nLingual::$home
	 * @uses nLingual::$here_url
	 * @uses nLingual::$here
	 * @uses nLingual::$languages
	 * @uses nLingual::$options
	 * @uses nLingual::$sync_rules
	 * @uses nLingual::$post_types
	 * @uses nLingual::$current
	 * @uses nLingual::$default
	 * @uses nLingual::get_option()
	 * @uses nLingual::lang_slug()
	 */
	public static function init(){
		global $wpdb, $table_prefix;

		// Get the parsed Home URL
		self::$home_url = get_option('home');
		// Parse it
		self::$home = parse_url(self::$home_url);

		// Get the current URL
		self::$here_url = (is_ssl() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		// Parse it
		self::$here = parse_url(self::$here_url);

		// Add custom tables to the $wpdb instance.
		$wpdb->nL_languages = $table_prefix.'nL_languages';
		$wpdb->nL_translations = $table_prefix.'nL_translations';

		if(is_admin()){
			// Create the languages table
			PluginToolkit::makeTable(
				$wpdb->nL_languages,
				array(
					'columns' => array(
						'lang_id'     => 'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT',
						'active'      => 'BOOLEAN DEFAULT TRUE NOT NULL',
						'system_name' => 'varchar(255) CHARACTER SET utf8 NOT NULL',
						'native_name' => 'varchar(255) CHARACTER SET utf8 NOT NULL',
						'short_name'  => 'varchar(255) CHARACTER SET utf8 NOT NULL',
						'slug'        => 'char(2) NOT NULL',
						'iso'         => 'char(2) NOT NULL',
						'mo'          => 'varchar(100) NOT NULL',
						'list_order'  => 'int(11) UNSIGNED NOT NULL'
					),
					'primary_key' => 'lang_id',
					'unique_keys' => array(
						'slug' => array('slug')
					)
				)
			);

			// Create the translations table
			PluginToolkit::makeTable(
				$wpdb->nL_translations,
				array(
					'columns' => array(
						'group_id' => 'bigint(20) UNSIGNED NOT NULL',
						'lang_id'  => 'bigint(20) UNSIGNED NOT NULL',
						'post_id'  => 'bigint(20) UNSIGNED NOT NULL'
					),
					'unique_keys' => array(
						'post'        => array('post_id'),
						'translation' => array('group_id', 'lang_id')
					)
				)
			);
		}

		// Load languages (active only if is_admin is true)
		$where = is_admin() ? '' : 'WHERE active = 1';
		self::$languages = $wpdb->get_results("SELECT * FROM $wpdb->nL_languages $where ORDER BY list_order ASC", OBJECT);

		// Loop through the languages and create a lang_id and slug indexed version
		foreach(self::$languages as $lang){
			self::$languages_by_id[$lang->lang_id] = $lang;
			self::$languages_by_slug[$lang->slug] = $lang;
		}

		// Load options
		self::$options = wp_parse_args((array) get_option('nLingual-options'), array(
			// Default language
			'default_lang'      => 0,

			// Redirection settings
			'admin_only'        => false,
			'method'            => NL_REDIRECT_USING_GET,
			'get_var'           => 'lang',
			'post_var'          => 'lang',
			'skip_default_l10n' => false,

			// Supported post types
			'post_types'        => array('page', 'post'),

			// Split settings
			'split_separator'   => '//',

			// Auto localize...
			'l10n_dateformat'   => true,

			// Delete sisters...
			'delete_sisters'    => false
		));

		// Load sync rules
		self::$sync_rules = (array) get_option('nLingual-sync_rules', array());

		// Load  post types, defualt language, and set current language
		self::$post_types = self::get_option('post_types');
		self::$default = self::lang_slug(self::get_option('default_lang'));
		self::$current = self::$default;

		// Setup text domain related hooks
		add_action('plugins_loaded', array('nLingual', 'onloaded'));
		add_action('load_textdomain', array('nLingual', 'log_textdomain'), 10, 2);
	}

	/**
	 * Hook to run when the plugin is loaded.
	 *
	 * Loads text domain for this plugin.
	 *
	 * @since 1.0.0
	 */
	public static function onloaded(){
		load_plugin_textdomain('nLingual', false, dirname(plugin_basename(NL_SELF)).'/lang');
	}

	/**
	 * Hook to run when a textdomain is loaded.
	 *
	 * Logs the text domain for later use in reloading.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::$loaded_textdomains
	 */
	public static function log_textdomain($domain, $mofile, $force = false){
		if(!isset(self::$loaded_textdomains[$domain]) && !$force)
			self::$loaded_textdomains[$domain] = $mofile;
	}

	// ========================= //
	//  Property Access Methods  //
	// ========================= //

	/**
	 * Return the value of a particular option.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::$options
	 *
	 * @param string $name The name of the option to retrieve.
	 *
	 * @return mixed The option value.
	 */
	public static function get_option($name){
		if(isset(self::$options[$name])){
			return self::$options[$name];
		}

		return null;
	}

	/**
	 * Return the rule(s) for a specific post type (and maybe type).
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::$sync_rules
	 *
	 * @param string $post_type The slug of the post type to retrieve rules for.
	 * @param string $rule_type The specific rule type to retrieve.
	 *
	 * @return mixed The request rule(s) (empty array if nothing found).
	 */
	public static function sync_rules($post_type, $rule_type = null){
		if(isset(self::$sync_rules[$post_type])){
			if(isset(self::$sync_rules[$post_type][$rule_type])){
				return self::$sync_rules[$post_type][$rule_type];
			}else{
				return array();
			}
			return self::$sync_rules[$post_type];
		}else{
			return array();
		}
	}

	/**
	 * Return the languages array.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::$languages
	 *
	 * @return array The languages array.
	 */
	public static function languages(){
		return self::$languages;
	}

	/**
	 * Return the languages by id array.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::$languages_by_id
	 *
	 * @return array The languages array by id.
	 */
	public static function langs_by_id(){
		return self::$languages_by_id;
	}

	/**
	 * Return the languages by slug array.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::$languages_by_slug
	 *
	 * @return array The languages array by slug.
	 */
	public static function langs_by_slug(){
		return self::$languages_by_slug;
	}

	/**
	 * Return the post_types array.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::$post_types
	 *
	 * @return array The post_types array.
	 */
	public static function post_types(){
		return self::$post_types;
	}

	/**
	 * Return the default language.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::$default
	 *
	 * @return string The default language.
	 */
	public static function default_lang(){
		return self::$default;
	}

	/**
	 * Return the current language.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::$current
	 *
	 * @return string The current language.
	 */
	public static function current_lang(){
		return self::$current;
	}

	/**
	 * Return the other language.
	 *
	 * Will use get_other_lang() if not set yet.
	 *
	 * @since 1.1.0
	 *
	 * @uses nLingual::$other
	 * @uses nLingual::get_other_lang()
	 *
	 * @return string The other language.
	 */
	public static function other_lang(){
		return self::$other ? self::$other : self::get_other_lang();
	}

	// =============== //
	//  Cache Methods  //
	// =============== //

	/**
	 * Get the cached data for an object.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::$cache
	 *
	 * @param mixed  $id      The ID of cached object.
	 * @param string $section The name of the section to cache under.
	 *
	 * @return mixed The cached data.
	 */
	public static function cache_get($id, $section){
		if(isset(self::$cache[$section][$id]))
			return self::$cache[$section][$id];
		else
			return null;
	}

	/**
	 * Cache some data for an object.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::$cache
	 *
	 * @param mixed  $id      The ID of cached object.
	 * @param mixed  $data    The data to cache for the object.
	 * @param string $section The name of the section to cache under.
	 */
	public static function cache_set($id, $data, $section){
		self::$cache[$section][$id] = $data;
	}

	// ============================= //
	//  Basic Value Testing Methods  //
	// ============================= //

	/**
	 * Test if the current language is the specified language.
	 *
	 * @since 1.0.0
	 *
	 * @uses $current
	 *
	 * @return bool Wether or not the language is the current one.
	 */
	public static function is_lang($lang){
		return self::$current == $lang;
	}

	/**
	 * Test if the current language is the default language.
	 *
	 * @since 1.0.0
	 *
	 * @uses $default
	 * @uses nLingual::is_lang()
	 *
	 * @return bool The result of is_lang.
	 */
	public static function is_default(){
		return self::is_lang(self::$default);
	}

	/**
	 * Test if a language is registered.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::_languages()
	 *
	 * @param string $lang The slug of the language.
	 *
	 * @return bool Wether or not the language is set.
	 */
	public static function lang_exists($lang){
		$array = self::_languages($lang);
		return isset($array[$lang]);
	}

	/**
	 * Test if a post type is registered to use nLingual.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::$post_types
	 *
	 * @param mixed $type The slug of the post_type (null/false/"" = post).
	 * @param bool  $all  Wether to match all or at least one (if $type is array).
	 *
	 * @return bool Wether or not the post type is present.
	 */
	public static function post_type_supported($type = 'post', $all = true){
		if(!$type) $type = 'post';

		if($type == 'any') return true;

		if(is_array($type)){
			$match = array_intersect($type, self::$post_types);
			return $all ? count($match) == count($type) : $match;
		}

		return in_array($type, self::$post_types);
	}

	/**
	 * Check if admin_only option is enabled.
	 *
	 * @since 1.2.0
	 *
	 * @uses nLingual::get_option()
	 *
	 * @return bool The value of the admin_only option.
	 */
	public static function admin_only(){
		return self::get_option('admin_only');
	}

	// ======================= //
	//  Language Data Methods  //
	// ======================= //

	/**
	 * Get the language property (or the full object) of a specified language.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::_lang()
	 * @uses nLingual::lang_exists()
	 *
	 * @param string $field Optional The field to retrieve.
	 * @param mixed  $lang  Optional The slug/id of the language to retrieve from.
	 *
	 * @return mixed False if the language isn't found, The language object, or the requested language field.
	 */
	public static function get_lang($field = null, $lang = null){
		self::_lang($lang);

		$array = self::_languages($lang);

		// Handle shorthand names for fields
		switch($field){
			case 'id': $field = 'lang_id'; break;
			case 'name': $field = 'system_name'; break;
			case 'native': $field = 'native_name'; break;
			case 'order': $field = 'list_order'; break;
		}

		if(!isset($array[$lang])) return false;
		if($field === true) return $array[$lang];
		return $array[$lang]->$field;
	}

	/**
	 * Determine the other language and update the property.
	 *
	 * This will be the first language in the list that is NOT
	 * the current language; It will also return the value.
	 *
	 * @since 1.1.0
	 *
	 * @uses nLingual::$current
	 * @uses nLingual::$other
	 *
	 * @return string the other language.
	 */
	public static function get_other_lang(){
		foreach(self::$languages as $lang){
			if($lang->slug == self::$current) continue;
			else self::$other = $lang->slug;
			break;
		}

		return self::$other;
	}

	/**
	 * Get the lang_id based on the slug provided.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::get_lang()
	 *
	 * @param string $slug The slug of the language to fetch.
	 *
	 * @return int The language id.
	 */
	public static function lang_id($slug){
		return intval(self::get_lang('lang_id', $slug));
	}

	/**
	 * Get the slug based on the lang_id provided.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::get_lang()
	 *
	 * @param int $lang_id The id of the language to fetch.
	 *
	 * @return string The language slug.
	 */
	public static function lang_slug($lang_id){
		return self::get_lang('slug', $lang_id);
	}

	/**
	 * Set the current language.
	 *
	 * @since 1.1.0 Added self::get_other_lang() usage.
	 * @since 1.0.0
	 *
	 * @uses nLingual::$current
	 * @uses nLingual::$current_cache
	 * @uses nLingual::lang_exists()
	 * @uses nLingual::get_other_lang()
	 * @uses nLingual::reload_textdomains()
	 *
	 * @param string $lang The language to set/switchto.
	 * @param bool   $lock Wether or not to lock the change.
	 */
	public static function set_lang($lang, $lock = true){
		if(defined('NLINGUAL_LANG_SET')) return;
		if($lock) define('NLINGUAL_LANG_SET', true);

		$old_locale = get_locale();

		if(self::lang_exists($lang)){
			// Set the current language (and the current cache language
			self::$current = self::$current_cache = $lang;

			// Update the other language
			self::get_other_lang();

			$new_locale = get_locale();

			// Reload the theme's text domain if the locale has changed
			if($old_locale != $new_locale){
				self::reload_textdomains($old_locale, $new_locale);
			}
		}
	}

	/**
	 * Switch to the specified language (does not affect loaded text domain).
	 *
	 * @since 1.1.0 Added self::get_other_lang() usage.
	 * @since 1.0.0
	 *
	 * @uses nLingual::$current
	 * @uses nLingual::_lang()
	 * @uses nLingual::get_other_lang()
	 *
	 * @param string $lang The slug of language to switch to.
	 */
	public static function switch_lang($lang){
		self::_lang($lang);

		self::$current = $lang;

		// Update the other language
		self::get_other_lang();
	}

	/**
	 * Restore the current language to what it was before.
	 *
	 * @since 1.1.0 Added self::get_other_lang() usage.
	 * @since 1.0.0
	 *
	 * @uses nLingual::$current
	 * @uses nLingual::$current_cache
	 * @uses nLingual::get_other_lang()
	 */
	public static function restore_lang(){
		self::$current = self::$current_cache;

		// Update the other language
		self::get_other_lang();
	}

	/**
	 * Get the query var to use for language filtering;
	 * - general "language" normally, or...
	 * - noconflict "nl_language" if in admin_only mode.
	 *
	 * @since 1.2.0
	 *
	 * @uses nLingual::admin_only
	 *
	 * @return string The query var to use.
	 */
	public static function query_var(){
		return self::admin_only() ? 'nl_language' : 'language';
	}

	// ======================= //
	//  Post Language Methods  //
	// ======================= //

	/**
	 * Get the language of the post in question, according to the nL_translations table.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 * @global WP_Post $post The current post in the loop.
	 *
	 * @uses nLingual::lang_slug()
	 * @uses nLingual::cache_get()
	 * @uses nLingual::cache_set()
	 *
	 * @param mixed $id The ID or object of the post in question (defaults to current $post).
	 *
	 * @return string The post language (slug).
	 */
	public static function get_post_lang($id = null){
		global $wpdb;

		if(is_null($id)){
			// Get the current post ID
			global $post;
			$id = $post->ID;
		}if(is_object($id)){
			// Get the ID from the post object
			$id = $id->ID;
		}

		// Check if it's cached, return if so
		if($lang = self::cache_get($id, 'lang')) return $lang;

		// Query the nL_translations table for the language of the post in question
		$lang_id = $wpdb->get_var($wpdb->prepare("SELECT lang_id FROM $wpdb->nL_translations WHERE post_id = %d", $id));
		$lang = $lang_id ? self::lang_slug($lang_id) : null;

		// Add it to the cache
		self::cache_set($id, $lang, 'lang');

		return $lang;
	}

	/**
	 * Set the language of the post in question for the nL_translations table.
	 *
	 * @since 1.2.1 Delete's the original post language before inserting it.
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @users self::_lang()
	 * @users self::_translation_group_id()
	 * @users self::lang_id()
	 * @users self::cache_set()
	 *
	 * @param mixed  $id   The ID or object of the post in question (defaults to current $post).
	 * @param string $lang The language to set the post to (defaults to default language).
	 */
	public static function set_post_lang($id = null, $lang = null){
		global $wpdb;

		if(is_null($id)){
			global $post;
			$id = $post->ID;
		}if(is_object($id)){
			$id = $id->ID;
		}

		self::_lang($lang);

		// Abort if lang doesn't exist
		if(!self::lang_exists($lang)) return;

		// Delete the original post language first
		self::delete_post_lang($id);

		// Run the REPLACE query
		$wpdb->replace(
			$wpdb->nL_translations,
			array(
				'group_id' => self::_translation_group_id(), // Just fetch a new group_id
				'lang_id' => self::lang_id($lang),
				'post_id' => $id
			),
			array('%d', '%d', '%d')
		);

		// Add/Update the cache of it, just in case
		self::cache_set($id, $lang, 'lang');
	}

	/**
	 * Delete the language link for the post in question.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param mixed $id The ID or object of the post in question.
	 *
	 * @return mixed The result of $wpdb->delete().
	 */
	public static function delete_post_lang($id){
		global $wpdb;

		return $wpdb->delete(
			$wpdb->nL_translations,
			array('post_id' => $id),
			array('%d')
		);
	}

	// =============================== //
	//  Post Language Testing Methods  //
	// =============================== //

	/**
	 * Test if a post is in the specified language.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::get_post_lang()
	 *
	 * @param mixed  $id   The ID or object of the post in question (defaults to current $post).
	 * @param string $lang The slug of the language to compare with.
	 *
	 * @return bool Wether or not the post language matches the provided one.
	 */
	public static function in_this_lang($id, $lang){
		return self::get_post_lang($id) == $lang;
	}

	/**
	 * Test if a post is in the default language.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::$default
	 * @uses nLingual::get_post_lang()
	 *
	 * @param mixed $id The ID or object of the post in question (defaults to current $post).
	 *
	 * @return bool The result of in_this_lang().
	 */
	public static function in_default_lang($id = null){
		return self::in_this_lang($id, self::$default);
	}

	/**
	 * Test if a post is in the current language.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::$current
	 * @uses nLingual::get_post_lang()
	 *
	 * @param mixed $id The ID or object of the post in question (defaults to current $post).
	 *
	 * @return bool The result of in_this_lang().
	 */
	public static function in_current_lang($id){
		return self::in_this_lang($id, self::$current);
	}

	/**
	 * Test if a post is in the other language.
	 *
	 * @since 1.1.0
	 *
	 * @uses nLingual::$other
	 * @uses nLingual::get_post_lang()
	 *
	 * @param mixed $id The ID or object of the post in question (defaults to current $post).
	 *
	 * @return bool The result of in_this_lang().
	 */
	public static function in_other_lang($id){
		return self::in_this_lang($id, self::$other);
	}

	// ==================== //
	//  Transation Methods  //
	// ==================== //

	/**
	 * Get the translation of the post in the provided language, via the nL_translations table.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses nLingual::_lang()
	 * @uses nLingual::lang_id()
	 *
	 * @param mixed  $id          The ID or object of the post in question (defaults to current $post).
	 * @param string $lang        The slug of the language requested (defaults to current language).
	 * @param bool   $return_self Wether or not to return the provided $id or just false should no original be found.
	 *
	 * @return int The id of the translation (or the original $id if $return_self is true).
	 */
	public static function get_translation($id, $lang = null, $return_self = true){
		global $wpdb;

		if(is_null($id)){
			global $post;
			$id = $post->ID;
		}if(is_object($id)){
			$id = $id->ID;
		}

		self::_lang($lang);

		// Get the language according to the translations table
		$translation = $wpdb->get_var($wpdb->prepare("
			SELECT
				t1.post_id
			FROM
				$wpdb->nL_translations AS t1
				LEFT JOIN
					$wpdb->nL_translations AS t2
					ON (t1.group_id = t2.group_id)
			WHERE
				t2.post_id = %d
				AND t1.lang_id = %s
		", $id, self::lang_id($lang)));

		// Return the translation's id if found
		if($translation) return $translation;

		// Otherwise, return the original id or false, depending on $return_self
		return $return_self ? $id : false;
	}

	/**
	 * Delete a translation link for the provided post in the provided language.
	 *
	 * In actuality, this simply changes the group ID for the post in that language,
	 * otherwise it would delete the langauge for that post, which we don't want.
	 *
	 * @since 1.2.0 Fixed how translation link is deleted; no reassigns group ID.
	 * @since 1.0.1
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses nLingual::_translation_group_id()
	 *
	 * @param int        $post_id The id of the post to use as an achor.
	 * @param int|string $lang    The slug or id of the language language to unlink.
	 */
	public static function unlink_translation($post_id, $lang){
		global $wpdb;

		// Get the group ID for this post
		$group_id = self::_translation_group_id($post_id);

		// Get a group ID for the sister post
		$new_group_id = self::_translation_group_id();

		// Update the group ID for the translation
		$wpdb->update(
			$wpdb->nL_translations,
			array(
				'group_id' => $new_group_id,
			),
			array(
				'group_id' => $group_id,
				'lang_id' => self::lang_id($lang)
			),
			array('%d'),
			array('%d', '%d')
		);
	}

	/**
	 * Associate translations together in the nL_translations table.
	 *
	 * @since 1.0.1 Added self::unlink_translation() usage.
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance
	 *
	 * @uses nLingual::_translation_group_id()
	 * @uses nLingual::unlink_translation()
	 *
	 * @param int   $post_id The id of the post to use as an achor.
	 * @param array $posts   A list of ids of the other posts to link together (in lang => post_id format).
	 */
	public static function associate_posts($post_id, $posts){
		global $wpdb;

		$group_id = self::_translation_group_id($post_id);

		$query = "
			REPLACE INTO
				$wpdb->nL_translations
				(group_id, lang_id, post_id)
			VALUES
		";

		$values = array();
		foreach($posts as $lang => $id){
			if($id <= 0){
				// Not a post, unlink the translation
				self::unlink_translation($post_id, $lang);
			}else{
				$lang_id = self::lang_id($lang);
				$values[] = $wpdb->prepare("(%d,%s,%d)", $group_id, $lang_id, $id);
			}
		}

		if(!$values) return;

		$query .=  implode(',', $values);

		$wpdb->query($query);
	}

	/**
	 * Return the IDs of all posts associated with this one, according to the nL_translations table.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param int  $post_id      The id of the post.
	 * @param bool $include_self Wether or not to include itself in the returned list.
	 *
	 * @return array A lang=>id array of associated posts.
	 */
	public static function associated_posts($post_id, $include_self = false){
		global $wpdb;

		$query = "
			SELECT
				l.slug as language,
				t1.post_id
			FROM
				$wpdb->nL_translations AS t1
			LEFT JOIN
				$wpdb->nL_translations AS t2
				ON (t1.group_id = t2.group_id)
			LEFT JOIN
				$wpdb->nL_languages AS l
				ON (t1.lang_id = l.lang_id)
			WHERE
				t2.post_id = %1\$d
		";

		if(!$include_self){
			$query .= "AND t1.post_id != %1\$d";
		}

		$results = $wpdb->get_results($wpdb->prepare($query, $post_id));

		$posts = array();
		foreach($results as $row){
			$posts[$row->language] = $row->post_id;
		}

		return $posts;
	}

	// ======================== //
	//  URL Processing Methods  //
	// ======================== //

	/**
	 * Utility for building URLs.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data The parse_url data to build with.
	 *
	 * @return string The assembled URL.
	 */
	public static function build_url($data){
		$url = '';

		$data = array_merge(array(
			'scheme'=>'',
			'user'=>'',
			'pass'=>'',
			'host'=>'',
			'port'=>'',
			'path'=>'',
			'query'=>'',
			'fragment'=>''
		), $data);

		if(isset($data['args']) && is_array($data['args'])){
			$data['query'] = http_build_query($data['args']);
		}

		if($data['scheme'])
			$url .= $data['scheme'].'://';

		if($data['user'])
			$url .= $data['user'];

		if($data['pass'])
			$url .= ':'.$data['pass'];

		if($data['user'])
			$url .= '@';

		if($data['host'])
			$url .= $data['host'];

		if($data['port'])
			$url .= ':'.$data['port'];

		if($data['path'])
			$url .= $data['path'];

		if($data['query'])
			$url .= '?'.$data['query'];

		if($data['fragment'])
			$url .= '#'.$data['fragment'];

		return $url;
	}

	/**
	 * Process just the hostname portion of a URL and get the language.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::lang_exists()
	 *
	 * @param string $host  The hostname to process.
	 * @param string &$lang Optional the variable to store the language data in.
	 *
	 * @return string The processed hostname with the language removed.
	 */
	public static function process_domain($host, &$lang = null){
		// Check if a language slug is present and is an existing language
		if(preg_match('#^([a-z]{2})\.#i', $host, $match) && self::lang_exists($match[1])){
			$lang = $match[1];
			$host = substr($host, 3); // Recreate the hostname sans the language slug at the beginning
		}

		return $host;
	}

	/**
	 * Process just the path portion of a URL and get the language.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::lang_exists()
	 *
	 * @param string $path  The path to process.
	 * @param string &$lang Optional the variable to store the language data in.
	 *
	 * @return string The processed path with the language removed.
	 */
	public static function process_path($path, &$lang = null){
		// Get the path of the home URL, with trailing slash
		$home = trailingslashit(parse_url(get_option('home'), PHP_URL_PATH));

		// Strip the home path from the beginning of the path
		$path = substr($path, strlen($home)); // Now /en/... or /mysite/en/... will become en/...

		// If substr didn't work (e.g. $path == $home), return $home
		if(!$path) return $home;

		// Check if a language slug is present and is an existing language
		if(preg_match('#^([a-z]{2})(/.*|$)$#i', $path, $match) && self::lang_exists($match[1])){
			$lang = $match[1];
			$path = substr($path, 3); // Recreate the url sans the language slug and slash after it
		}

		return $home.$path;
	}

	/**
	 * Process a full URL (the host and uri portions) and get the language.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::$here
	 * @uses nLingual::get_option()
	 * @uses nLingual::process_domain()
	 * @uses nLingual::process_path()
	 *
	 * @param mixed $url_data The URL string or parsed array to proces.
	 *
	 * @return array An array of the resulting language, host name and requested uri.
	 */
	public static function process_url($url_data){
		$lang = null;

		// Copy to $old_url_data
		$old_url_data = $url_data;

		// If no URL, use $here_parsed
		if(is_null($url_data)) $url_data = self::$here;

		// If not already an array, parse it
		if(!is_array($url_data)){
			$url_data = parse_url($url_data);
		}

		// Default the host/path/query keys
		$url_data = array_merge(array('host'=>'', 'path'=>'/', 'query' => ''), $url_data);

		// Parse the query string into new args entry
		parse_str($url_data['query'], $url_data['args']);

		if(isset($url_data['args']['lang'])){
			$url_data['lang'] = $url_data['args']['lang'];
			unset($url_data['args']['lang']);
		}

		// Proceed based on method
		switch(self::get_option('method')){
			case NL_REDIRECT_USING_DOMAIN:
				$url_data['host'] = self::process_domain($url_data['host'], $url_data['lang']);
				break;
			case NL_REDIRECT_USING_PATH:
				$url_data['path'] = self::process_path($url_data['path'], $url_data['lang']);
				break;
		}

		/**
		 * Filter the $url_data array.
		 *
		 * @since 1.1.3
		 *
		 * @param array $url_data     The updated URL data.
		 * @param array $old_url_data The original URL data.
		 */
		$url_data = apply_filters('nLingual_process_url', $url_data, $old_url_data);

		// Ensure $url_data['lang'] is always set
		if(!isset($url_data['lang'])) $url_data['lang'] = null;

		return $url_data;
	}

	// ======================== //
	//  URL Conversion Methods  //
	// ======================== //

	/**
	 * Localize the URL with the supplied language.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::$home
	 * @uses nLingual::cache_get()
	 * @uses nLingual::cache_set()
	 * @uses nLingual::current_lang()
	 * @uses nLingual::get_option()
	 * @uses nLingual::process_url()
	 *
	 * @param string $old_url    The URL to localize.
	 * @param string $lang       The language to localize with (default's to current language).
	 * @param bool   $relocalize Wether or not to relocalize the url if it already is.
	 *
	 * @return string The localized url.
	 */
	public static function localize_url($old_url, $lang = null, $relocalize = false){
		// Check if it's a URI (path only, no hostname),
		// prefix with the domain of the home url if so
		if(!parse_url($old_url, PHP_URL_HOST)){
			// Make a copy of the $home property
			$home = self::$home;
			// Change the path to the URI
			$home['path'] = $old_url;
			// Rebuild it as a full URL
			$old_url = self::build_url($home);
		}

		// Copy to new_url
		$new_url = $old_url;

		// Get the home url
		$home = get_option('home');

		// Don't mess with the url when in the admin
		if(defined('WP_ADMIN'))
			return $old_url;

		if(!$lang) $lang = self::$current;

		// Create an identifier for the url for caching
		$id = "[$lang]$old_url";

		// Check if this URL has been taken care of before,
		// return cached result
		if($cached = self::cache_get($id, 'url')){
			return $cached;
		}

		// Only proceed if it's a local url (and not simply the unslashed $home url)
		if(strpos($new_url, $home) !== false){
			// If $relocalized is true, delocalize the URL first
			if($relocalize){
				$new_url = self::delocalize_url($new_url);
			}

			// Process the url
			$url_data = self::process_url($new_url);

			// If processing failed (i.e. not yet localized),
			// and if the URL is not a wp-admin/[anything].php one,
			// and if we're not in the default language (or if skip_default_l10n is disabled)
			// Go ahead and localize the URL
			if(
				(!$url_data['lang'])
				&& !preg_match('#^/wp-([\w-]+.php|(admin|content|includes)/)#', $url_data['path'])
				&& ($lang != self::$default || !self::get_option('skip_default_l10n'))
			){
				switch(self::get_option('method')){
					case NL_REDIRECT_USING_DOMAIN:
						$url_data['host'] = "$lang.{$url_data['host']}";
						break;
					case NL_REDIRECT_USING_PATH:
						if(isset(self::$home['path']) && self::$home['path']){ // $home has a base path, need to insert $lang AFTER it
							$home = preg_quote(self::$home['path'],'#');
							$url_data['path'] = preg_replace("#^($home)(/.*|$)#", "$1/$lang$2", $url_data['path']);
						}else{
							// $home is the domain root, just need to prefix the whole path
							$url_data['path'] = "/$lang{$url_data['path']}";
						}
						break;
					default:
						parse_str($url_data['query'], $url_data['args']);
						$url_data['args'][self::get_option('get_var')] = $lang;
						break;
				}

				$new_url = self::build_url($url_data);
			}
		}

		/**
		 * Filter the new localized URL.
		 *
		 * @since 1.1.3
		 *
		 * @param string $new_url    The new localized URL.
		 * @param string $old_url    The original URL passed to this function.
		 * @param string $lang       The slug of the language requested.
		 * @param bool   $relocalise Whether or not to forcibly relocalize the URL.
		 */
		$new_url = apply_filters('nLingual_localize_url', $new_url, $old_url, $lang, $relocalize);

		// Store the URL in the cache
		self::cache_set($id, $new_url, 'url');

		return $new_url;
	}

	/**
	 * Delocalize a URL; remove language information.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::process_url()
	 * @uses nLingual::build_url()
	 *
	 * @param string $url The URL to delocalize.
	 *
	 * @return string The delocalized url.
	 */
	public static function delocalize_url($url){
		// Parse and process the url
		$url_data = self::process_url($url);

		// If a language was extracted, rebuild the $url
		if($url_data['lang']){
			$url = self::build_url($url_data);
		}

		return $url;
	}

	// ========================= //
	//  Translation URL Methods  //
	// ========================= //

	/**
	 * Get the permalink of the specified post in the specified language.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::_lang()
	 * @uses nLingual::get_translation()
	 * @uses nLingual::localize_url()
	 *
	 * @param mixed  $id   The ID or object of the post in question (defaults to current $post).
	 * @param string $lang The slug of the language requested (defaults to current language).
	 *
	 * @return string The permalink to the translated post.
	 */
	public static function get_permalink($id = null, $lang = null){
		self::_lang($lang);

		$link = get_permalink(self::get_translation($id, $lang));

		$link = self::localize_url($link, $lang, true);

		return $link;
	}

	/**
	 * Echo's the results of self::get_permalink().
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::get_permalink()
	 *
	 * @param mixed  $id   The ID or object of the post in question (defaults to current $post).
	 * @param string $lang The slug of the language requested (defaults to current language).
	 */
	public static function the_permalink($id = null, $lang = null){
		echo self::get_permalink($id, $lang);
	}

	/**
	 * Find the post with the provided path, and return the one for the provided language version.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::_lang()
	 * @uses nLingual::get_permalink()
	 *
	 * @param string $path      The path (in /parent/child/ or /page/ form) of the page to find.
	 * @param string $post_type The slug of the post type it should be looking for (defaults to page).
	 * @param string $lang      The slug of the language requested (defaults to current language).
	 * @param bool   $echo      Wether or not to echo the resulting $link.
	 *
	 * @return string The translated permalink.
	 */
	public static function translate_link($path, $post_type = null, $lang = null, $echo = true){
		if(!$post_type) $post_type = 'page';

		self::_lang($lang);

		$post = get_page_by_path(trim($path, '/'), OBJECT, $post_type);

		if($echo) self::the_permalink($post->ID, $lang);

		return self::get_permalink($post->ID, $lang);
	}

	// ========================= //
	//  URL Redirection Methods  //
	// ========================= //

	/**
	 * Return the current URL, translated for the provided language.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::_lang()
	 * @uses nLingual::build_url()
	 * @uses nLingual::get_permalink()
	 * @uses nLingual::localize_url()
	 *
	 * @param string $lang The language to translate the url to.
	 *
	 * @return string The localized url.
	 */
	public static function localize_here($lang = null){
		self::_lang($lang);

		$url = false;

		// Get the current URI
		$uri = $_SERVER['REQUEST_URI'];

		// Get where we are now and what the new URL should be
		switch(true){
			case is_front_page():
				$here = home_url('/');
				break;
			case is_home():
				$page = get_option('page_for_posts');
				$url = self::get_permalink($page, $lang);
				break;
			case is_singular():
				$post = get_queried_object()->ID;
				$url = self::get_permalink($post, $lang);
				break;
			case is_tax() || is_tag() || is_category():
				$here = get_term_link(get_queried_object());
				break;
			case is_day():
				$here = get_day_link(get_query_var('year'), get_query_var('monthnum'), get_query_var('day'));
				break;
			case is_month():
				$here = get_day_link(get_query_var('year'), get_query_var('monthnum'));
				break;
			case is_year():
				$here = get_year_link(get_query_var('year'));
				break;
			case is_search():
				$here = home_url('/?s='.get_query_var('s'));
				break;
			default: // Just localize the URI
				$url = self::localize_url($uri, $lang, true);
				$url = apply_filters('nLingual_localize_here', $url);
				return $url;
		}

		// If $url hasn't been set, localize $here to create it
		if($url === false){
			$url = self::localize_url($here, $lang, true);
		}

		// Parse the $url
		$url_data = parse_url($url);

		// If paged, add page/n to the $url_data path
		if(is_paged()){
			$url_data['path'] .= sprintf('page/%d/', get_query_var('paged'));
			$url = self::build_url($url_data);
		}

		// Apply any filters
		$url_data = apply_filters('nLingual_localize_here_array', $url_data);

		// Build the URL
		$url = self::build_url($url_data);

		return $url;
	}

	/**
	 * Checks if redirection is needed such as forced language specification or trainling slashes.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::$here_url
	 * @uses nLingual::delocalize_url()
	 * @uses nLingual::localize_here()
	 */
	public static function maybe_redirect(){
		// Don't bother unless WP is done/in progress
		if(!did_action('wp')) return;

		// Get the current URL
		$requested = self::$here_url;

		// Check in case it's just the home page
		if(rtrim($requested, '/') == self::delocalize_url(get_option('home'))) return;

		// Get where we should be in the current language
		$redirect = self::localize_here();

		if($requested != $redirect){
			wp_redirect($redirect, 301);
			exit;
		}
	}

	// ============================== //
	//  General Use & Filter Methods  //
	// ============================== //

	/**
	 * Get an array of URLs for each language.
	 *
	 * Returned in slug => url format.
	 *
	 * @since 1.2.0
	 *
	 * @uses nLingual::$languages
	 * @uses nLingual::$current
	 * @uses nLingual::localize_here()
	 *
	 * @param bool $skip_current Wether or not to include the current language url (Default: true).
	 *
	 * @return array The array of language links.
	 */
	public static function get_lang_links($skip_current = false){
		$links = array();
		foreach(self::$languages as $lang){
			// If skip_current is true and this is the current language, skip
			if($skip_current && $lang->slug == self::$current) continue;

			// Get the localized version of the current URL
			$url = self::localize_here($lang->slug);

			// Create and append the HTML link to the list
			$links[$lang->slug] = $url;
		}
	}

	/**
	 * Print out the results from get_lang_links().
	 *
	 * @since 1.2.0
	 *
	 * @uses nLingual::get_lang_links()
	 * @uses nLingual::get_lang()
	 *
	 * @param string $prefix       The text to preceded the link list with (Default: "").
	 * @param string $sep          The text to separate each link with (Default: " ").
	 * @param bool   $skip_current Wether or not to include the current language link (Default: true).
	 * @param bool   $echo         Wether or not to echo the html (Default: true).
	 *
	 * @return array The array of HTML links
	 */
	public static function print_lang_links($prefix = '', $sep = ' ', $skip_current = true, $echo = true){
		$links = self::get_lang_links($skip_current);
		foreach($links as $lang => &$link){
			$link = sprintf('<a href="%s">%s</a>', $link, self::get_lang('native', $lang));
		}

		$html = $prefix.implode($sep, $links);
		if($echo) echo $html;
		return $links;
	}

	/**
	 * Split a string at the separator and return the part corresponding to the specified language.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::$languages
	 * @uses nLingual::_lang()
	 * @uses nLingual::get_option()
	 *
	 * @param string $text  The text to split.
	 * @param string $lang  The slug of the language requested (defaults to current language).
	 * @param string $sep   The separator to use when splitting the string ($defaults to global separator).
	 * @param bool   $force Wether or not to force the split when it normally would be skipped.
	 *
	 * @return string The proper text to return
	 */
	public static function split_langs($text, $lang = null, $sep = null, $force = false){
		self::_lang($lang);

		if(is_null($sep))
			$sep = self::get_option('separator');

		if(!$sep) return $text;

		if(is_admin() && !$force && did_action('admin_notices')) return $text;

		$langs = array_map(function($lang){
			return $lang->slug;
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

	// ================================== //
	//  Text Domain Manipulation Methods  //
	// ================================== //

	/**
	 * Reload all current text domains with those of the new current language.
	 *
	 * @since 1.0.0
	 *
	 * @uses nLingual::$loaded_textdomains
	 *
	 * @param string $old_locale The previous locale.
	 * @param string $new_locale The new locale to change to.
	 */
	public static function reload_textdomains($old_locale, $new_locale){
		if(self::$loaded_textdomains){
			foreach(self::$loaded_textdomains as $domain => $mofile){
				unload_textdomain($domain);

				extract(pathinfo($mofile));

				// Replace the locale in the basename with the new locale
				$basename = str_replace($old_locale, $new_locale, $basename);

				// Rebuild the mofile path
				$mofile = "$dirname/$basename";

				// Now reload the textdomain
				load_textdomain($domain, $mofile);
			}
		}
	}

	// ===================== //
	//  Deprecated Methods  //
	// ===================== //

	/**
	 * Return or print a list of links to the current page in all available languages.
	 *
	 * @deprecated Expect to be gone by version 1.5.0; use get_lang_links() or print_lang_links() instead.
	 *
	 * @since 1.2.0 Move part of functionality to self::get_lang_links().
	 * @since 1.1.1 Added $skip_current argument.
	 * @since 1.0.0
	 *
	 * @uses nLingual::get_lang_links()
	 * @uses nLingual::get_lang()
	 *
	 * @param bool   $echo         Wether or not to echo the imploded list of links  (Default: false).
	 * @param string $prefix       The text to preceded the link list with (Default: "").
	 * @param string $sep          The text to separate each link with (Default: " ").
	 * @param bool   $skip_current Wether or not to include the current language link (Default: false).
	 *
	 * @return array The array of HTML links
	 */
	public static function lang_links($echo = false, $prefix = '', $sep = ' ', $skip_current = false){
		$links = self::get_lang_links($skip_current);
		foreach($links as $lang => &$link){
			$link = sprintf('<a href="%s">%s</a>', $link, self::get_lang('native', $lang));
		}

		if($echo) echo $prefix.implode($sep, $links);
		return $links;
	}
}