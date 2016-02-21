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
	 * Language switching log.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected static $previous_languages = array();

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

		// - The default language id.
		'default_language' => 0,
		// - The localize date format string option.
		'localize_date' => false,
		// - The patch WP_Locale option.
		'patch_wp_locale' => true,
		// - The backwards compatibility option.
		'backwards_compatible' => false,

		/* Content Management Options */

		// - The show all languages for objects option.
		'show_all_languages' => true,
		// - The DELETE sister posts option.
		'delete_sister_posts' => false,

		/* Request/Redirection Options */

		// - The language query var.
		'query_var' => 'nl_language',
		// - The URL redirection method.
		'url_rewrite_method' => 'get',
		// - The skip default language localizing option.
		'skip_default_l10n' => false,
		// - The post language override option.
		'post_language_override' => true,
		// - The permanent redirection option.
		'redirection_permanent' => false,

		/* Localizable Stuff */

		// - The supported post types.
		'post_types' => array(),
		// - The supported taxonomies.
		'taxonomies' => array(),
		// - The list of localizable features.
		'localizables' => array(),

		/* Synchronization Stuff */

		// - The synchronization rules.
		'sync_rules' => array(),
		// - The cloning rules.
		'clone_rules' => array(),
	);

	// =========================
	// ! Property Accessing
	// =========================

	/**
	 * Retrieve a option value.
	 *
	 * Will redirect to Registry::languages() if applicable.
	 *
	 * @since 2.0.0
	 *
	 * @param string $option  The option name.
	 * @param mixed  $default Optional. The default value to return.
	 *
	 * @return mixed The property value.
	 */
	public static function get( $option, $default = null ) {
		// Throw "unsupported" error if trying to set an unsupported option
		if ( ! in_array( $option, static::$options_whitelist ) ) {
			throw new Exception( "The option '{$option}' is not supported", NL_ERR_UNSUPPORTED );
		}

		// Check if it's set, return it's value.
		if ( isset( static::$options[ $option ] ) ) {
			return static::$options[ $option ];
		}

		return $default;
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
		// Throw "unsupported" error if trying to set an unsupported option
		if ( ! in_array( $option, static::$options_whitelist ) ) {
			throw new Exception( "The option '{$option}' is not supported", NL_ERR_UNSUPPORTED );
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
	 * Set the current language.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $language The desired language.
	 * @param bool  $lock     Wether or not to lock the selection.
	 */
	public static function set_language( $language, $lock = false ) {
		// Ensure $language is a Language
		if ( ! validate_language( $language ) ) {
			// Throw exception if not found
			throw new Exception( 'The language specified does not exist: ' . maybe_serialize( $language ), NL_ERR_NOTFOUND );
		}

		// If locked, fail
		if ( defined( 'NL_LANGUAGE_LOCKED' ) ) {
			return false;
		}

		Registry::set( 'current_language', $language->id );

		if ( $lock ) {
			// Lock the language from being changed again
			define( 'NL_LANGUAGE_LOCKED', true );
		}

		return true;
	}

	/**
	 * Switch to a different language.
	 *
	 * @since 2.0.0
	 *
	 * @uses validate_language() to ensure $language is a Language object.
	 * @uses Registry::$current_language to get/update the current language.
	 * @uses Registry::$previous_languages to log the current language.
	 *
	 * @param mixed $language The language object, slug or id.
	 */
	public static function switch_language( $language ) {
		// Ensure $language is a Language
		if ( ! validate_language( $language ) ) {
			return false; // Does not exist
		}

		// Log the current language
		static::$previous_languages[] = static::$current_language;

		// Replace $current_language with desired language
		static::$current_language = $language->id;
	}

	/**
	 * Switch back to the previous language.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::$previous_languages to get the previous language.
	 * @uses Registry::get() to get the default language id.
	 * @uses Registry::$current_language to update the current language.
	 */
	public static function restore_language() {
		$last_language = array_pop( static::$previous_languages );
		if ( ! $last_language ) {
			// No previous language, go with default
			$last_language = static::get( 'default_language' );
		}

		// Replace $current_language with last language
		static::$current_language = $last_language;
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
		return static::languages()->get( $language_id, $field );
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
		$language_id = static::$current_language ?: static::get( 'default_language' );
		return static::languages()->get( $language_id, $field );
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
	// ! Language Testing Tools
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
	 * @uses Registry::get() to get the localizables settings.
	 * @uses Registry::languages() to get the registered languages.
	 *
	 * @param string $item The name of the localizable to check support for.
	 * @param array  $list The list of registered objects.
	 */
	public static function is_feature_localizable( $item, $list ) {
		// Check if this feature is enabled
		$localizables = static::get( 'localizables' );
		if ( ! isset( $localizables[ $item ] ) || ! $localizables[ $item ] ) {
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
	 * @uses Registry::get() to get the localizables settings.
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
		$localizables = static::get( 'localizables' );
		if ( ! isset( $localizables[ $type ] ) ) {
			return false;
		}

		// Check if any under $type should be localizable
		if ( $localizables[ $type ] === true ) {
			return true;
		}

		// Check if specified location is localizable
		if ( in_array( $location, $localizables[ $type ] ) ) {
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
		$post_types = (array) $post_types; // Covnert to array

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
		$taxonomies = (array) $taxonomies; // Covnert to array

		// Get the supported post types list
		$supported = static::get( 'taxonomies' );

		return (bool) array_intersect( $supported, $taxonomies );
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
}
