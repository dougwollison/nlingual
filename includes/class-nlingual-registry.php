<?php
/**
 * nLingual Registry API
 *
 * @package nLingual
 * @subpackage Tools
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * The Registry
 *
 * Stores all the configuration options for the system.
 *
 * @api
 *
 * @since 2.0.0
 *
 * @method array get_sync_rules()       Get the sync rules.
 * @method array get_clone_rules()      Get the clone rules.
 * @method array get_post_sync_rules()  Get the sync rules for a post type.
 * @method array get_post_clone_rules() Get the clone rules for a post type.
 * @method array get_term_sync_rules()  Get the sync rules for a taxonomy.
 * @method array get_term_clone_rules() Get the clone rules for a taxonomy.
 */

class Registry {
	// =========================
	// ! Properties
	// =========================

	// ! - Internal

	/**
	 * The loaded status flag.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	protected static $__loaded = false;

	/**
	 * The current language id.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var int
	 */
	protected static $current_language;

	/**
	 * The language directory.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var Languages
	 */
	protected static $languages;

	/**
	 * The options storage array
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected static $options = array();

	/**
	 * The options whitelist/defaults.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected static $options_whitelist = array(
		/* General Options */

		// - The default language id
		'default_language' => 0,
		// - The localize date format string option
		'localize_date' => false,
		// - The patch WP_Locale option
		'patch_wp_locale' => true,
		// - The backwards compatibility option
		'backwards_compatible' => false,

		/* Content Management Options */

		// - The show all languages for objects option
		'show_all_languages' => true,
		// - The TRASH sister posts option
		'trash_sister_posts' => false,
		// - The DELETE sister posts option
		'delete_sister_posts' => false,
		// - The patch font stack option
		'patch_font_stack' => false,

		/* Request/Redirection Options */

		// - The language query var
		'query_var' => 'nl_language',
		// - The URL redirection method
		'url_rewrite_method' => 'get',
		// - The skip default language localizing option
		'skip_default_l10n' => false,
		// - The post language override option
		'post_language_override' => true,
		// - The must_have_language option
		'language_is_required' => false,
		// - The permanent redirection option
		'redirection_permanent' => false,

		/* Localizable Things */

		// - The supported post types
		'post_types' => array(),
		// - The supported taxonomies
		'taxonomies' => array(),
		// - The supported nav menus
		'nav_menu_locations' => array(),
		// - The supported sidebars
		'sidebar_locations' => array(),

		/* Synchronization Rules */

		// - The synchronization rules
		'sync_rules' => array(),
		// - The cloning rules
		'clone_rules' => array(),

		/* Hidden Options */

		// - The old split-language separator
		'_old_separator' => '',
	);

	/**
	 * The deprecated options and their alternatives.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected static $options_deprecated = array(
		'default_lang'      => 'default_language',
		'delete_sisters'    => 'delete_sister_posts',
		'post_var'          => 'query_var',
		'get_var'           => 'query_var',
		'l10n_dateformat'   => 'localize_date',
		'separator'         => '_old_separator',
	);

	// =========================
	// ! Property Accessing
	// =========================

	/**
	 * Retrieve the whitelist.
	 *
	 * @internal Used by the Installer.
	 *
	 * @since 2.0.0
	 *
	 * @return array The options whitelist.
	 */
	public static function get_defaults() {
		return static::$options_whitelist;
	}

	/**
	 * Check if an option is supported.
	 *
	 * Will also udpate the option value if it was deprecated
	 * but has a sufficient alternative.
	 *
	 * @since 2.0.0
	 *
	 * @param string &$option The option name.
	 *
	 * @return bool Wether or not the option is supported.
	 */
	public static function has( &$option ) {
		if ( isset( static::$options_deprecated[ $option ] ) ) {
			$option = $deprecated_options[ $option ];
		}

		return in_array( $option, static::$options_whitelist );
	}

	/**
	 * Retrieve a option value.
	 *
	 * @since 2.0.0
	 *
	 * @param string $option  The option name.
	 * @param mixed  $default Optional. The default value to return.
	 *
	 * @return mixed The property value.
	 */
	public static function get( $option, $default = null ) {
		// Trigger notice error if trying to set an unsupported option
		if ( ! static::has( $option ) ) {
			trigger_error( "[nLingual] The option '{$option}' is not supported", E_NOTICE );
		}

		// Check if it's set, return it's value.
		if ( isset( static::$options[ $option ] ) ) {
			$value = static::$options[ $option ];
		} else {
			$value = $default;
		}

		// Handle internal filtering of certain options
		switch ( $option ) {
			case 'url_rewrite_method':
				// If rewrites can be used, must be path or domain
				if ( static::can_use_rewrites() ) {
					if ( $value != 'domain' ) {
						$value = 'path';
					}
				}
				// Otherwise, must be get
				else {
					$value = 'get';
				}
				break;
		}

		return $value;
	}

	/**
	 * Override a option value.
	 *
	 * Will not work for $languages, that has it's own method.
	 *
	 * @since 2.0.0
	 *
	 * @param string $option The option name.
	 * @param mixed  $value  The value to assign.
	 */
	public static function set( $option, $value = null ) {
		// Trigger notice error if trying to set an unsupported option
		if ( ! static::has( $option ) ) {
			trigger_error( "[nLingual] The option '{$option}' is not supported", E_NOTICE );
		}

		static::$options[ $option ] = $value;
	}

	/**
	 * Get the languages collection.
	 *
	 * @since 2.0.0
	 *
	 * @uses Languages::filter() to filter the languages before returning it.
	 *
	 * @param string $filter Optional. A filter property to pass to Languages->filter().
	 * @param string $value  Optional. A filter value to pass to Languages->filter().
	 *
	 * @return Language The languages collection (optionally filtered).
	 */
	public static function languages( $filter = null, $value = null ) {
		return static::$languages->filter( $filter, $value );
	}

	/**
	 * Get the sync or cloning rules for a specific object.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::get() to retrive the appropriate rules array.
	 *
	 * @param string $rule_type   The type of rules to retrieve ('sync' or 'clone').
	 * @param string $sections... Optional A list of indexes drilling down into the array.
	 *
	 * @return array The array of rules, empty if not found.
	 */
	public static function get_rules( $rule_type ) {
		// Get the rules
		$rules = Registry::get( $rule_type . '_rules' );

		// Fail if no rules found
		if ( ! $rules ) {
			return array();
		}

		// Get the args as the sections map
		$sections = func_get_args();
		array_shift( $sections ); // Skip the first argment (list type)

		// If no section list is present, return the rules
		if ( ! $sections ) {
			return $rules;
		}

		// Loop through the sections list
		foreach ( $sections as $section ) {
			// Drill down if an array is found
			if ( isset( $rules[ $section ] ) && is_array( $rules[ $section ] ) ) {
				$rules = $rules[ $section ];
			} else {
				// Abort and return empty array
				return array();
			}
		}

		return $rules;
	}

	// =========================
	// ! Language Accessing
	// =========================

	/**
	 * Get the info for a language.
	 *
	 * @since 2.0.0
	 *
	 * @uses Languages::get() to validate/retrieve the language ID.
	 *
	 * @param mixed  $id_or_slug The ID or slug of the language to get info for.
	 * @param string $field      Optional. The field to get from the language.
	 *
	 * @return mixed The language or the value of the language's field.
	 */
	public static function get_language( $id_or_slug, $field = null ) {
		// Check if id/slug is a value, and that it matches a language
		if ( $id_or_slug && $language = static::$languages->get( $id_or_slug ) ) {
			if ( is_null( $field ) ) {
				return $language;
			}

			return $language->$field;
		}

		return false;
	}

	/**
	 * Set the current language.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $language The desired language.
	 * @param bool  $lock     Wether or not to lock the selection.
	 * @param bool  $override Wether or not to override the lock.
	 */
	public static function set_language( $language, $lock = false, $override = false ) {
		// Ensure $language is a Language
		if ( ! validate_language( $language ) ) {
			// Throw exception if not found
			throw new Exception( 'The language specified does not exist: ' . maybe_serialize( $language ), NL_ERR_NOTFOUND );
		}

		// If locked, fail
		if ( defined( 'NL_LANGUAGE_LOCKED' ) && ! $override ) {
			return false;
		}

		static::$current_language = $language->id;

		if ( $lock ) {
			// Lock the language from being changed again
			define( 'NL_LANGUAGE_LOCKED', true );
		}

		return true;
	}

	/**
	 * Shortcut; get the default language or a field for it.
	 *
	 * @since 2.0.0
	 *
	 * @see Registry::get_language() for details.
	 *
	 * @uses Registry::get() to get the default language id.
	 *
	 * @param string $field Optional. The field to get from the language.
	 */
	public static function default_language( $field = null ) {
		$language_id = static::get( 'default_language' );

		// If default_language is not set/valid, default to first language
		if ( ! $language_id || ! static::$languages->get( $language_id ) ) {
			$language_id = static::$languages->sort()->nth( 0 )->id;
			// Also update it for future calls
			static::set( 'default_language', $language_id );
		}

		return static::get_language( $language_id, $field );
	}

	/**
	 * Shortcut; get the current language or a field for it.
	 *
	 * @since 2.0.0
	 *
	 * @see Registry::get_language() for details.
	 *
	 * @uses Registry::$current_language
	 * @uses Registry::get() to get the default language id.
	 *
	 * @param string $field Optional. The field to get from the language.
	 */
	public static function current_language( $field = null ) {
		$language_id = static::$current_language ?: static::default_language( 'id' );
		return static::get_language( $language_id, $field );
	}

	// =========================
	// ! Language Testing
	// =========================

	/**
	 * Compare two languages.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $language1 The language to compare with.
	 * @param mixed $language2 The language to compare against.
	 *
	 * @return bool The result of the comparision.
	 */
	public static function compare_languages( $language1, $language2 ) {
		// Ensure $language1 is a Language
		if ( ! validate_language( $language1 ) ) {
			return false; // Does not exist
		}
		// Ensure $language2 is a Language
		if ( ! validate_language( $language2 ) ) {
			return false; // Does not exist
		}

		// Test if the IDs match
		return $language1->id == $language2->id;
	}

	/**
	 * Alias of compare_languages(), comparing against the default language.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $language The language to compare.
	 *
	 * @return bool The result of compare_languages().
	 */
	public static function is_language_default( $language ) {
		return static::compare_languages( $language, static::default_language() );
	}

	/**
	 * Alias of compare_languages(), comparing against the current language.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $language The language to compare.
	 *
	 * @return bool The result of compare_languages().
	 */
	public static function is_language_current( $language ) {
		return static::compare_languages( $language, static::current_language() );
	}

	/**
	 * Alias of compare_languages(), comparing the current and default languages
	 *
	 * @since 2.0.0
	 *
	 * @return bool The result of compare_languages().
	 */
	public static function in_default_language() {
		return static::compare_languages( static::current_language(), static::default_language() );
	}

	// =========================
	// ! Other Testing Tools
	// =========================

	/**
	 * Check if localizable feature is supported.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::get() to get the matching localizable list.
	 * @uses Registry::languages() to get the registered languages.
	 *
	 * @param string $item The name of the localizable to check support for.
	 * @param array  $list The list of registered objects.
	 */
	public static function is_feature_localizable( $item, $list ) {
		// Check if this feature is enabled
		if ( ! static::get( $item ) ) {
			return false;
		}

		// Check if there are items registered and languages are present
		$languages = static::languages();
		if ( ! $list || ! $languages->count() ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if a specific location is localizable.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::get() to get the matching localizable list.
	 *
	 * @param string $type     The type of location to check for.
	 * @param string $location The ID of the location to check.
	 *
	 * @return bool Wether or not the location is localizable.
	 */
	public static function is_location_localizable( $type, $location ) {
		// Turn $type into proper key name
		$type .= '_locations';

		// Check if type is present in localizables list
		$localizables = static::get( $type );
		if ( ! $localizables ) {
			return false;
		}

		// Check if any under $type should be localizable
		if ( $localizables === true ) {
			return true;
		}

		// Check if specified location is localizable
		if ( in_array( $location, $localizables ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Test if the provided post type(s) are registered for translation.
	 *
	 * Will return true if at least 1 is supported.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::get() to get the post_types list.
	 *
	 * @param string|array $post_types The post type(s) to check.
	 *
	 * @return bool Wether or not the post type(s) are supported.
	 */
	public static function is_post_type_supported( $post_types ) {
		$post_types = (array) $post_types; // Convert to array

		// Get the supported post types list
		$supported = static::get( 'post_types' );

		return (bool) array_intersect( $supported, $post_types );
	}

	/**
	 * Test if the provided taxonomy(ies) are registered for translation.
	 *
	 * Will return true if at least 1 is supported.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::get() to get the taxonomies list.
	 *
	 * @param string|array $taxonomies The taxonomy(ies) to check.
	 *
	 * @return bool Wether or not the taxonomy(ies) are supported.
	 */
	public static function is_taxonomy_supported( $taxonomies ) {
		$taxonomies = (array) $taxonomies; // Convert to array

		// Get the supported post types list
		$supported = static::get( 'taxonomies' );

		return (bool) array_intersect( $supported, $taxonomies );
	}

	/**
	 * Test if rewriting can be used.
	 *
	 * Basically just checks if there's a permalink structure set.
	 *
	 * @internal Used by the Registry and Manager.
	 *
	 * @global \WP_Rewrite $wp_rewrite The WordPress rewrite API.
	 *
	 * @return bool Wether or not rewriting can be used.
	 */
	public static function can_use_rewrites() {
		global $wp_rewrite;

		if ( $wp_rewrite ) {
			// Use WP_Rewrite's check
			return ! empty( $wp_rewrite->using_permalinks() );
		} else {
			// Does not exist yet, check permalink_structure option
			return ! empty( get_option( 'permalink_structure' ) );
		}
	}

	// =========================
	// ! Setup Method
	// =========================

	/**
	 * Load the relevant options.
	 *
	 * @since 2.0.0
	 *
	 * @see Registry::$__loaded to prevent unnecessary reloading.
	 * @see Registry::$options_whitelist to filter the found options.
	 * @see Registry::set() to actually set the value.
	 *
	 * @param bool $reload Should we reload the options?
	 */
	public static function load( $reload = false ) {
		if ( static::$__loaded && ! $reload ) {
			// Already did this
			return;
		}

		// Load the options
		$options = get_option( 'nlingual_options' );
		foreach ( static::$options_whitelist as $option => $default ) {
			$value = $default;
			if ( isset( $options[ $option ] ) ) {
				$value = $options[ $option ];

				// Ensure the value is the same type as the default
				settype( $value, gettype( $default ) );
			}

			static::set( $option, $value );
		}

		// Load the languages
		$data = get_option( 'nlingual_languages', array(
			'entries' => array(),
			'auto_increment' => 1,
		) );
		static::$languages = new Languages( $data['entries'], $data['auto_increment'] );

		// Flag that we've loaded everything
		static::$__loaded = true;
	}

	/**
	 * Save the options and languages to the database.
	 *
	 * @since 2.0.0
	 *
	 * @param string $what Optional. Save just options/languages or both (true)?
	 */
	public static function save( $what = true ) {
		if ( $what == 'options' ) {
			// Save the options
			update_option( 'nlingual_options', static::$options );
		}

		if ( $what == 'languages' ) {
			// Save the languages
			update_option( 'nlingual_languages', static::$languages->export() );
		}
	}

	// =========================
	// ! Overloading
	// =========================

	/**
	 * Handle aliases of existing methods, namely get_rules().
	 *
	 * @since 2.0.0
	 *
	 * @param string $name The name of the method being called.
	 * @param array  $args The list of arguments for the method.
	 *
	 * @return mixed The result of the target method.
	 */
	public static function __callStatic( $name, $args ) {
		// Check if $name could be a kind of get_rules() alias
		if ( preg_match( '/^get_(\w+?)_rules$/', $name, $matches ) ) {
			// This should attempt to handle methods like "get_post_sync_rules"

			// Split at the underscore into sections
			$sections = explode( '_', $matches[1] ); // should be rule

			// Flip and append the arguments
			$sections = array_merge( array_reverse( $sections ), $args );

			// Handle renaming of level 2
			if ( isset( $sections[1] ) ) {
				// If level 2 is "post", change to "post_type"
				if ( $sections[1] == 'post' ) {
					$sections[1] = 'post_type';
				}
				// If it's "term", change to "taxonomy"
				elseif ( $sections[1] == 'term' ) {
					$sections[1] = 'taxonomy';
				}
			}

			// Pass to get_rules and return output
			return call_user_func_array( array( __CLASS__, 'get_rules' ), $sections );
		}

		// No match, throw exception
		throw new Exception( _f( 'Call to unrecognized method alias %1$s::%2$s()', 'nlingual', __CLASS__, $name ), NL_ERR_UNSUPPORTED );
	}
}
