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
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected static $taxonomies = array();

	/**
	 * The list of localizable features.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected static $localizables = array();

	/**
	 * The synchronization rules.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected static $sync_rules = array();

	/**
	 * The cloning rules.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected static $clone_rules = array();

	/**
	 * The language directory.
	 *
	 * @since 2.0.0
	 *
	 * @var Languages
	 */
	protected static $languages;

	// =========================
	// ! Property Accessing
	// =========================

	/**
	 * Retrieve a property value.
	 *
	 * @since 2.0.0
	 *
	 * @param string $property The property name.
	 * @param mixed  $default  Optional. The default value to return.
	 *
	 * @return mixed The property value.
	 */
	public static function get( $property, $default = null ) {
		if ( property_exists( get_called_class(), $property ) ) {
			return static::$$property;
		}
		return $default;
	}

	/**
	 * Override a property value.
	 *
	 * @since 2.0.0
	 *
	 * @param string $property The property name.
	 * @param mixed  $value    The value to assign.
	 */
	public static function set( $property, $value = null ) {
		if ( property_exists( get_called_class(), $property ) ) {
			static::$$property = $value;
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
		} else {
			return $language->$field;
		}
	}

	/**
	 * Switch to a different language.
	 *
	 * @since 2.0.0
	 *
	 * @uses Utilities::_language() to ensure $language is a Language object.
	 * @uses Registry::$current_language to get/update the current language.
	 * @uses Registry::$previous_languages to log the current language.
	 *
	 * @param mixed $language The language object, slug or id.
	 */
	public static function switch_language( $language ) {
		// Ensure $language is a Language
		if ( ! is_language( $language ) ) {
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
	 * @uses Registry::$current_language to update the current language.
	 */
	public static function restore_language() {
		$last_language = array_pop( static::$previous_languages );
		if ( ! $last_language ) {
			// No previous language, go with default
			$last_language = static::$default_language;
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
	 * @uses Registry::$default_language
	 *
	 * @param string $field Optional. The field to get from the language.
	 */
	public static function default_language( $field = null ) {
		$language_id = static::$default_language;
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
	 *
	 * @param string $field Optional. The field to get from the language.
	 */
	public static function current_language( $field = null ) {
		$language_id = static::$current_language ?: static::$default_language;
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
			}
			// Abort and return empty array
			else {
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
		if ( ! is_language( $language ) ) {
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
	 * @uses Registry::$localizables to get the localizables settings.
	 * @uses Registry::languages() to get the registered languages.
	 *
	 * @param string $item The name of the localizable to check support for.
	 * @param array  $list The list of registered objects.
	 */
	public static function is_feature_localizable( $item, $list ) {
		// Check if this feature is enabled
		$localizables = static::$localizables;
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
	 * @uses Registry::$localizables to get the localizables settings.
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
		$localizables = static::$localizables;
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
	 * @uses Registry::$post_types to get the post_types list.
	 *
	 * @param string|array $post_types The post type(s) to check.
	 *
	 * @return bool Wether or not the post type(s) are supported.
	 */
	public static function is_post_type_supported( $post_types ) {
		$post_types = (array) $post_types; // Covnert to array

		// Get the supported post types list
		$supported = static::$post_types;

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
	 * @uses Registry::$taxonomies to get the taxonomies list.
	 *
	 * @param string|array $taxonomies The taxonomy(ies) to check.
	 *
	 * @return bool Wether or not the taxonomy(ies) are supported.
	 */
	public static function is_taxonomy_supported( $taxonomies ) {
		$taxonomies = (array) $taxonomies; // Covnert to array

		// Get the supported post types list
		$supported = static::$taxonomies;

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
	 * @see Registry::$default_language
	 * @see Registry::$show_all_languages
	 * @see Registry::$localize_date
	 * @see Registry::$skip_default_l10n
	 * @see Registry::$post_language_override
	 * @see Registry::$redirection_permanent
	 * @see Registry::$patch_wp_locale
	 * @see Registry::$backwards_compatible
	 * @see Registry::$query_var
	 * @see Registry::$url_rewrite_method
	 * @see Registry::$post_types
	 * @see Registry::$taxonomies
	 * @see Registry::$localizables
	 * @see Registry::$sync_rules
	 * @see Registry::$clone_rules
	 * @see Registry::$languages
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param bool $reload Should we reload the options?
	 */
	public static function load( $reload = false ) {
		global $wpdb;

		static $loaded = false;
		if ( $loaded && ! $reload ) {
			// Already did this
			return;
		}

		// Load simple options
		static::$default_language       = (bool) get_option( 'nlingual_default_language', 0 );
		static::$show_all_languages     = (bool) get_option( 'nlingual_show_all_languages', 1 );
		static::$localize_date          = (bool) get_option( 'nlingual_localize_date', 0 );
		static::$skip_default_l10n      = (bool) get_option( 'nlingual_skip_default_l10n', 0 );
		static::$post_language_override = (bool) get_option( 'nlingual_post_language_override', 0 );
		static::$redirection_permanent  = (bool) get_option( 'nlingual_redirection_permanent', 0 );
		static::$patch_wp_locale        = (bool) get_option( 'nlingual_patch_wp_locale', 0 );
		static::$backwards_compatible   = (bool) get_option( 'nlingual_backwards_compatible', 0 );
		static::$query_var              = get_option( 'nlingual_query_var', 'nl_language' );
		static::$url_rewrite_method     = get_option( 'nlingual_url_rewrite_method', 'get' );

		// Load complex options
		static::$post_types   = get_option( 'nlingual_post_types', array() );
		static::$taxonomies   = get_option( 'nlingual_taxonomies', array() );
		static::$localizables = get_option( 'nlingual_localizables', array() );
		static::$sync_rules   = get_option( 'nlingual_sync_rules', array() );
		static::$clone_rules  = get_option( 'nlingual_clone_rules', array() );

		// Load stuff from database
		$data = $wpdb->get_results( "SELECT * FROM $wpdb->nl_languages ORDER BY list_order ASC", ARRAY_A );
		static::$languages = new Languages( $data );

		// Flag that we've loaded everything
		$loaded = true;
	}
}
