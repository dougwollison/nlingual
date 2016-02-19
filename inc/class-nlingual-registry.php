<?php
/**
 * nLingual Options Registry
 *
 * @package nLingual
 * @subpackage Helpers
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * The Options Registry
 *
 * Stores all the configuration options for the system.
 *
 * @package nLingual
 * @subpackage Helpers
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
	 * The whitelist of accessible options.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected static $options_whitelist = array(
		'show_all_languages'     => true,
		'localize_date'          => false,
		'skip_default_l10n'      => false,
		'post_language_override' => false,
		'redirection_permanent'  => false,
		'patch_wp_locale'        => false,
		'backwards_compatible'   => false,
		'default_language'       => 0,
		'query_var'              => 'nl_language',
		'url_rewrite_method'     => 'get',
		'post_types'             => array(),
		'taxonomies'             => array(),
		'localizables'           => array(),
		'sync_rules'             => array(),
		'clone_rules'            => array(),
	);

	// ! - System Options

	/**
	 * The default language id.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var int
	 */
	protected static $default_language;

	/**
	 * The show all languages for objects option.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var bool
	 */
	protected static $show_all_languages;

	/**
	 * The localize date format string option.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var bool
	 */
	protected static $localize_date;

	/**
	 * The skip default language localizing option.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var bool
	 */
	protected static $skip_default_l10n;

	/**
	 * The patch WP_Locale option.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var bool
	 */
	protected static $patch_wp_locale;

	/**
	 * The post language override option.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var bool
	 */
	protected static $post_language_override;

	/**
	 * The permanent redirection option.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var bool
	 */
	protected static $redirection_permanent;

	/**
	 * The backwards compatibility option.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var bool
	 */
	protected static $backwards_compatible;

	/**
	 * The language query var.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	protected static $query_var;

	/**
	 * The URL redirection method.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	protected static $url_rewrite_method;

	/**
	 * The supported post types.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected static $post_types = array();

	/**
	 * The supported taxonomies.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected static $taxonomies = array();

	/**
	 * The list of localizable features.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected static $localizables = array();

	/**
	 * The synchronization rules.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected static $sync_rules = array();

	/**
	 * The cloning rules.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected static $clone_rules = array();

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
	 * @param bool   $force   Optional. Re-fetch from the database.
	 *
	 * @return mixed The property value.
	 */
	public static function get( $option, $default = null, $force = true ) {
		// Throw "unsupported" error if trying to set an unsupported property
		if ( ! property_exists( get_called_class(), $option ) ) {
			throw new Exception( "Registry::$option is not supported", NL_ERR_UNSUPPORTED );
		}

		// Throw "forbidden" error if trying to replace one of the special properties
		if ( ! in_array( $option, static::$options_whitelist ) ) {
			throw new Exception( "You cannot overwrite Registry::$option", NL_ERR_FORBIDDEN );
		}

		// Fetch the new value if desired
		if ( $force ) {
			static::$$option = get_option( "nlingual_{$option}", $default );
		}

		if ( static::$$option === null ) {
			return $default;
		} else {
			return static::$$option;
		}
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
	 * @param bool   $save   Optional. Save the change to the database.
	 */
	public static function set( $option, $value = null, $save = false ) {
		// Throw "unsupported" error if trying to set an unsupported property
		if ( ! property_exists( get_called_class(), $option ) ) {
			throw new Exception( "Registry::$option is not supported", NL_ERR_UNSUPPORTED );
		}

		// Throw "forbidden" error if trying to replace one of the special properties
		if ( ! in_array( $option, static::$options_whitelist ) ) {
			throw new Exception( "You cannot overwrite Registry::$option", NL_ERR_FORBIDDEN );
		}

		static::$$option = $value;

		// Save the new value if desired
		if ( $save ) {
			update_option( "nlingual_{$option}", $value );
		}
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
	 * Get the info for a language.
	 *
	 * @since 2.0.0
	 *
	 * @uses Languages::get() to validate/retrieve the language ID.
	 *
	 * @param int    $language_id The ID of the language to get info for.
	 * @param string $field       Optional. The field to get from language.
	 *
	 * @return mixed The language or the value of the language's field.
	 */
	public static function get_language( $language_id, $field = null ) {
		$language = static::$languages->get( $language_id );
		if ( is_null( $field ) ) {
			return $language;
		}

		return $language->$field;
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
		$language_id = static::$current_language ?: static::get( 'default_language' );
		return static::get_language( $language_id, $field );
	}

	/**
	 * Get the sync or cloning rules for a specific object.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::get() to retrive the appropriate rules array.
	 *
	 * @param string $rule_type   The type of rules to retrieve ('sync' or 'clone').
	 * @param string $sections... Optional A list of indexes drilling down into the value.
	 *
	 * @return array The array of rules, empty if not found.
	 */
	public static function get_rules( $rule_type ) {
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
			// Drill down if found
			if ( isset( $rules[ $section ] ) ) {
				$rules = $rules[ $section ];
			} else {
				// Abort and return empty array
				return array();
			}
		}

		return $rules;
	}

	// =========================
	// ! Property Testinging
	// =========================

	/**
	 * Check if the current language is the specified one.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_language() to get the current language.
	 *
	 * @param mixed $language The language to test for (by id, slug or object).
	 */
	public static function is_language( $language ) {
		// Ensure $language is a Language
		if ( ! validate_language( $language ) ) {
			return false; // Does not exist
		}

		// Test if the IDs match
		$result = static::current_language()->id == $language->id;

		return $result;
	}

	/**
	 * Alias of is_language(), check if it's the default language.
	 *
	 * @since 2.0.0
	 *
	 * @see Registry::is_language() for details.
	 */
	public static function is_default_language() {
		return static::is_language( static::default_language() );
	}

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
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @see Registry::$__loaded
	 * @see Registry::$options_whitelist
	 * @see Registry::set() to actually set the value.
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param bool $reload Should we reload the options?
	 */
	public static function load( $reload = false ) {
		global $wpdb;

		if ( static::$__loaded && ! $reload ) {
			// Already did this
			return;
		}

		// Load the options
		foreach ( static::$options_whitelist as $option => $default ) {
			$value = get_option( "nlingual_{$option}", $default );

			// If the default was boolean, convert value to boolean
			if ( is_bool( $default ) ) {
				$value = (bool) $value;
			}

			static::set( $option, $value );
		}

		// Load the languages
		$data = $wpdb->get_results( "SELECT * FROM $wpdb->nl_languages ORDER BY list_order ASC", ARRAY_A );
		static::$languages = new Languages( $data );

		// Flag that we've loaded everything
		static::$__loaded = true;
	}
}
