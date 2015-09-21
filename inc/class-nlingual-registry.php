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
	 * The default language id.
	 *
	 * @since 2.0.0
	 *
	 * @access protected (static)
	 *
	 * @var string
	 */
	protected static $default_lang;

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
	 * The synchronization rules.
	 *
	 * @since 2.0.0
	 *
	 * @access protected (static)
	 *
	 * @var array
	 */
	protected static $sync_rules = array();

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

	// =========================
	// ! Propert Access Methods
	// =========================

	/**
	 * Retrieve a property value.
	 *
	 * @since 2.0.0
	 *
	 * @param string $property The property name.
	 *
	 * @return mixed The property value.
	 */
	public static function get( $property ) {
		if ( property_exists( get_called_class(), $property ) ) {
			return static::$$property;
		}
		return null;
	}

	/**
	 * Get the languages collection.
	 *
	 * @since 2.0.0
	 *
	 * @param string $filter Optional A filter property to pass to Languages->filter().
	 * @param string $value  Optional A filter value to pass to Languages->filter().
	 *
	 * @return array An array of nLingual\Language objects.
	 */
	public static function languages( $filter = null, $value = null ) {
		return static::$languages->filter( $filter, $value );
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
	public static function is_localizable_supported( $item, $list ) {
		// Check if this feature is enabled
		$localizables = static::get( 'localizables' );
		if ( ! in_array( $item, $localizables ) ) {
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
	 * Test if the provided post type(s) are registered for translation.
	 *
	 * Will return true if at least 1 is supported.
	 *
	 * @since 2.0.0
	 *
	 * @param string|array $post_type The post type(s) to check.
	 */
	public static function is_post_type_supported() {

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
		static::$default_lang = get_option( 'nlingual_default_language', 0 );

		// Load languages
		static::$languages = get_option( 'nlingual_languages', new Languages );

		// Load sync rules
		static::$sync_rules = get_option( 'nlingual_sync_rules', array() );

		// Localizables list
		static::$localizables = get_option( 'nlingual_localizables', array( 'nav_menus', 'sidebars' ) );

		// Flag that we've loaded everything
		$loaded = true;
	}
}