<?php
class nLingual{
	protected static $languages;
	protected static $default;
	protected static $current;
	protected static $current_cache;
	protected static $post_types;
	protected static $separator;
	protected static $cache = array();

	/*
	 * Initialization method
	 * Loads options into local properties
	 */
	public static function init(){
		self::$languages = get_option('nLingual-languages', array(
			'en' => array(
				'iso' => 'en',
				'mo' => 'english',
				'tag' => 'En',
				'name' => 'English',
				'native' => 'English'
			)
		));
		self::$post_types = get_option('nLingual-post_types', array('page', 'post'));
		self::$separator = get_option('nLingual-separator', '//');
		self::$default = get_option('nLingual-default_lang', 'en');
		self::$current = self::$default;
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
	public static function exists($lang){
		return isset(self::$languages[$lang]);
	}

	/*
	 * Get the langauge property (or the full array) of a specified langauge (current language by default)
	 *
	 * @param string $field Optional The field to retrieve
	 * @param string $lang Optional The language to retrieve from
	 */
	public static function get($field = null, $lang = null){
		if(is_null($lang))
			$lang = self::$current;
		elseif(!self::exists($lang))
			return false;

		return is_null($field) ? $lang : self::$languages[$lang][$field];
	}

	/*
	 * Set the current langauge
	 *
	 * @param string $lang The language to set/switchto
	 * @param bool $lock Wether or not to lock the change
	 */
	public static function set($lang, $lock = true){
		if(defined('NLINGUAL_LANG_SET')) return;
		if($lock) define('NLINGUAL_LANG_SET', true);

		if(self::exists($lang))
			self::$current = self::$current_cache = $lang;

		if(!$temp){
			return load_theme_textdomain(wp_get_theme()->get('TextDomain'), get_template_directory().'/lang');
		}

		return true;
	}

	/*
	 * Switch to the specified language (does not affect loaded text domain)
	 */
	public static function switchto($lang){
		self::$current = $lang;
	}

	/*
	 * Restore the current language to what it was before
	 */
	public static function restore(){
		self::$current = self::$current_cache;
	}

	/*
	 * Get the language of the post in question
	 *
	 * @param mixed $id The ID or object of the post in question (defaults to current $post)
	 * @param string $default The default language to return should none be found
	 */
	public static function post_lang($id = null, $default = null){
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

		if($lang = self::cacheGet($id)) return $lang;

		$lang = $default;

		if(($languages = wp_get_object_terms($id, 'language'))
		&& is_array($languages)){
			$lang = reset($languages)->slug;
		}

		self::cacheSet($id, $lang);

		return $lang;
	}

	/*
	 * Test if a post is in the default language
	 *
	 * @param mixed $id The ID or object of the post in question (defaults to current $post)
	 */
	public static function in_default($id = null){
		return post_lang($id, null) == self::$default;
	}

	/*
	 * Test if a post is in the current language
	 *
	 * @param mixed $id The ID or object of the post in question (defaults to current $post)
	 */
	public static function in_current($id){
		return post_lang($id, null) == self::$current;
	}

	/*
	 * Get the original post in the default language
	 *
	 * @param mixed $id The ID or object of the post in question (defaults to current $post)
	 * @param bool $return_self Wether or not to return the provided $id or just false should no original be found (or it is the original)
	 */
	public static function original_post($id = null, $return_self = true){
		global $wpdb;

		if(is_null($id)){
			global $post;
			$id = $post->ID;
		}if(is_object($id)){
			$id = $id->ID;
		}

		$lang = self::post_lang($id);
		$orig = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %d", "_translated_$lang", $id));

		if($orig) return $orig;
		return $return_self ? $id : false;
	}

	/*
	 * Get the version of the post in the provided language
	 *
	 * @param mixed $id The ID or object of the post in question (defaults to current $post)
	 * @param string $lang The slug of the language requested (defaults to current language)
	 * @param bool $return_self Wether or not to return the provided $id or just false should no original be found (or it is the original)
	 */
	public static function translated_post($id, $lang = null, $return_self = true){
		global $wpdb;
		if(is_null($lang))
			$lang = self::$current;

		$postlang = self::post_lang($id);

		if($postlang == $lang) return $id;

		if($postlang == self::$default){
			//Search this posts meta data for the alternate
			$alt = get_post_meta($id, "_translated_$lang", true);
		}elseif($orig = self::original_post($id, false)){
			//Search for the post this one is the translantion of, then get the alternate
			$alt = self::translated_post($orig, $lang, $return_self);
		}

		if($alt && $alt > 0) return $alt;
		return $return_self ? $id : false;
	}

	/*
	 * Get the permalink of the specified post in the specified language
	 *
	 * @param mixed $id The ID or object of the post in question (defaults to current $post)
	 * @param string $lang The slug of the language requested (defaults to current language)
	 * @param bool $echo Wether or not to echo the resulting $link
	 */
	public static function permalink($id = null, $lang = null, $echo = true){
		global $wpdb;

		$link = get_permalink(self::translated_post($id, $lang));

		if($echo) echo $link;
		return $link;
	}

	/*
	 * Print out a list of links to the current page in all available languages
	 *
	 * @param string $prefix The text to preceded the link list with
	 * @param string $sep The text to separate each link with
	 */
	public static function links($prefix = '', $sep = ' '){
		echo $prefix;
		$links = array();
		foreach(self::$languages as $lang => $data){
			$links[] = sprintf('<a href="%s">%s</a>', !is_front_page() ? self::permalink(get_queried_object()->ID, $lang, false) : "?lang=$lang", $data['native']);
		}
		echo implode($sep, $links);
	}

	/*
	 * Split a string at the separator and return the part corresponding to the specified language
	 *
	 * @param string $text The text to split
	 * @param string $lang The slug of the language requested (defaults to current language)
	 * @param string $sep The separator to use when splitting the string ($defaults to global separator)
	 * @param bool $force Wether or not to force the split when it normally would be skipped
	 */
	public static function split($text, $lang = null, $sep = null, $force = false){
		if(is_null($lang))
			$lang = self::$current;
		if(is_null($sep))
			$sep = self::$separator;

		if(is_admin() && !$force && did_action('admin_notices')) return $text;

		$langs = array_keys(self::$languages);
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