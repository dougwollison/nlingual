<?php
namespace nLingual;

/**
 * nLingual Options Registry
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

class Registry {
	// =========================
	// ! Properties
	// =========================

	/**
	 * Internal cache array.
	 *
	 * @since 2.0.0
	 *
	 * @access protected (static)
	 * (static)
	 * @var array
	 */
	protected static $cache = array();

	/**
	 * The language query var.
	 *
	 * @since 2.0.0
	 *
	 * @access protected (static)
	 *
	 * @var string
	 */
	protected static $query_var;

	/**
	 * The URL redirection method.
	 *
	 * @since 2.0.0
	 *
	 * @access protected (static)
	 *
	 * @var string
	 */
	protected static $redirection_method;

	/**
	 * The post language override option.
	 *
	 * @since 2.0.0
	 *
	 * @access protected (static)
	 *
	 * @var bool
	 */
	protected static $postlang_override;

	/**
	 * The skip default language localizing option.
	 *
	 * @since 2.0.0
	 *
	 * @access protected (static)
	 *
	 * @var bool
	 */
	protected static $skip_default_l10n;

	/**
	 * The default language id.
	 *
	 * @since 2.0.0
	 *
	 * @access protected (static)
	 *
	 * @var int
	 */
	protected static $default_lang;

	/**
	 * The current language id.
	 *
	 * @since 2.0.0
	 *
	 * @access protected (static)
	 *
	 * @var int
	 */
	protected static $current_lang;

	/**
	 * The language directory.
	 *
	 * @since 2.0.0
	 *
	 * @access protected (static)
	 *
	 * @var nLingualLanguages
	 */
	protected static $languages;

	/**
	 * The supported post types.
	 *
	 * @since 2.0.0
	 *
	 * @access protected (static)
	 *
	 * @var array
	 */
	protected static $post_types = array();

	/**
	 * The list of localizable features.
	 *
	 * @since 2.0.0
	 *
	 * @access protected (static)
	 *
	 * @var array
	 */
	protected static $localizables = array();

	/**
	 * The synchronization rules.
	 *
	 * @since 2.0.0
	 *
	 * @access protected (static)
	 *
	 * @var array
	 */
	protected static $sync_rules = array();

	// =========================
	// ! Propert Access Methods
	// =========================

	/**
	 * Retrieve a property value.
	 *
	 * @since 2.0.0
	 *
	 * @param string $property The property name.
	 * @param mixed  $default  Optional The default value to return.
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
	 * @param string $filter Optional A filter property to pass to Languages->filter().
	 * @param string $value  Optional A filter value to pass to Languages->filter().
	 *
	 * @return Language The langauges collection (optionally filtered).
	 */
	public static function languages( $filter = null, $value = null ) {
		return static::$languages->filter( $filter, $value );
	}

	/**
	 * Get the info for a language.
	 *
	 * @since 2.0.0
	 *
	 * @param int    $lang_id The ID of the language to get info for.
	 * @param string $field   Optional The field to get from language.
	 *
	 * @return mixed The langauge or the value of the language's field.
	 */
	public static function get_lang( $lang_id, $field = null ) {
		$language = static::$languages->get( $lang_id );
		if ( is_null( $field ) ) {
			return $language;
		} else {
			return $language->$field;
		}
	}

	/**
	 * Shortcut; get the default language or a field for it.
	 *
	 * @since 2.0.0
	 *
	 * @see Registry::get_lang()
	 *
	 * @param string $field Optional The field to get from the langauge.
	 */
	public static function default_lang( $field = null ) {
		$lang_id = static::get( 'default_lang' );
		return static::get_lang( $lang_id, $field );
	}

	/**
	 * Shortcut; get the current language or a field for it.
	 *
	 * @since 2.0.0
	 *
	 * @see Registry::get_lang()
	 *
	 * @param string $field Optional The field to get from the langauge.
	 */
	public static function current_lang( $field = null ) {
		$lang_id = static::get( 'current_lang' ) ?: static::get( 'default_lang' );
		return static::get_lang( $lang_id, $field );
	}

	// =========================
	// ! Cache Methods
	// =========================

	/**
	 * Get the cached data for an object.
	 *
	 * @since 2.0.0
	 *
	 * @param string     $section The section of the cache to look in.
	 * @param int|string $id      The id of the cached object.
	 * @param mixed      $default The default value to return if needed.
	 *
	 * @return mixed The cached data.
	 */
	public static function cache_get( $section, $id, $default = null ) {
		// Check if section doesn't exist
		if ( ! isset( static::$cache[ $section ] ) ) {
			return $default;
		}

		// Check if object doesn't exist
		if ( ! isset( static::$cache[ $section ][ $id ] ) ) {
			return $default;
		}

		return static::$cache[ $section ][ $id ];
	}

	/**
	 * Store the cached data for an object.
	 *
	 * @since 2.0.0
	 *
	 * @param string     $section The section of the cache to look in.
	 * @param int|string $id      The id of the cached object.
	 * @param mixed      $value   The data to store.
	 *
	 * @return mixed The cached data.
	 */
	public static function cache_set( $section, $id, $value = null ) {
		// Ensure the section exists
		if ( ! isset( static::$cache[ $section ] ) ) {
			static::$cache[ $section ] = array();
		}

		static::$cache[ $section ][ $id ] = $value;
	}

	/**
	 * Delete the cached data for an object.
	 *
	 * @since 2.0.0
	 *
	 * @param string     $section The section of the cache to look in.
	 * @param int|string $id      The id of the cached object.
	 */
	public static function cache_delete( $section, $id, $value = null ) {
		// Skip if the section doesn't even exist
		if ( ! isset( static::$cache[ $section ] ) ) {
			return;
		}

		unset( static::$cache[ $section ][ $id ] );
	}

	// =========================
	// ! Option Testing Methods
	// =========================

	/**
	 * Check if localizable feature is supported.
	 *
	 * @since 2.0.0
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
	 * @since 2.0.0
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
	 * @since 2.0.0
	 *
	 * @param string|array $post_type The post type(s) to check.
	 *
	 * @return bool Wether or not the post type(s) are supported.
	 */
	public static function is_post_type_supported( $post_types ) {
		$post_types = (array) $post_types; // Covnert to array

		return (bool) array_intersect( static::$post_types, $post_types );
	}

	// =========================
	// ! Setup Method
	// =========================

	/**
	 * Load the relevant options.
	 *
	 * @since 2.0.0
	 *
	 * @param bool $reload Should we reload the options?
	 */
	public static function load( $reload = false ) {
		static $loaded = false;
		if ( $loaded && ! $reload ) {
			// Already did this
			return;
		}

		// Load simple options
		static::$query_var = get_option( 'nlingual_query_var', '' );
		static::$redirection_method = get_option( 'nlingual_redirection_method', '' );
		static::$default_lang = get_option( 'nlingual_default_language', 0 );
		static::$postlang_override = get_option( 'nlingual_postlang_override', 0 );
		static::$skip_default_l10n = get_option( 'nlingual_skip_default_l10n', 0 );

		// Load languages
		static::$languages = get_option( 'nlingual_languages', new Languages );

		// Load supported post types list
		static::$post_types = get_option( 'nlingual_post_types', array() );

		// Load localizables list
		static::$localizables = get_option( 'nlingual_localizables', array() );

		// Load sync rules
		static::$sync_rules = get_option( 'nlingual_sync_rules', array() );

		// Flag that we've loaded everything
		$loaded = true;
	}
}