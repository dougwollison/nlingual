<?php
/**
 * nLingual Localization API
 *
 * @package nLingual
 * @subpackage Tools
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * The Localizer System
 *
 * An API for registering strings that required
 * localization, adding LocalizeThis tools to their
 * respective fields in the admin and filtering the
 * output on the frontend.
 *
 * @package nLingual
 * @subpackage Tools
 *
 * @api
 *
 * @since 2.0.0
 *
 * @method array get_strings_by_*() return the matching $strings_by_* property.
 * @method void register_*_meta( string $meta_key, array $args )
 *              localize a metadata field for an object type.
 */

class Localizer extends Handler {
	// =========================
	// ! Properties
	// =========================

	/**
	 * The name of the class.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	protected static $name;

	/**
	 * A list of all fields registered for localizing.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected static $registered_strings = array();

	/**
	 * An index of strings by key.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected static $strings_by_key = array();

	/**
	 * An index of strings by screen they are to appear on based on property.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected static $strings_by_screen = array();

	/**
	 * Storage for the strings found for the current screen.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected static $current_strings = array();

	/**
	 * Storage for the current object ID if applicable.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var int
	 */
	protected static $current_object_id = 0;

	/**
	 * Reference of which post fields are permitted for localization.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var int
	 */
	protected static $localizable_post_fields = array( 'post_content', 'post_title', 'post_excerpt' );

	// =========================
	// ! Property Access
	// =========================

	/**
	 * Get a string by ID.
	 *
	 * @since 2.0.0
	 *
	 * @param string $id The ID of the string to retrieve.
	 *
	 * @return Localizer_String|bool The retreived string, FALSE on failure.
	 */
	public static function get_string( $id ) {
		if ( isset( static::$registered_strings[ $id ] ) ) {
			return static::$registered_strings[ $id ];
		}
		return false;
	}

	/**
	 * Get a list of strings from an index.
	 *
	 * @since 2.0.0
	 *
	 * @param string $index The index to search in ("type" or "screen")
	 * @param string $value The index value to get entries for.
	 *
	 * @return array A list of strings for the specified index value.
	 */
	public static function get_strings_by( $index, $value ) {
		$property = "strings_by_{$index}";
		$list = static::$$property;

		if ( isset( $list[ $value ] ) ) {
			return $list[ $value ];
		} else {
			return array();
		}
	}

	/**
	 * Find strings for the current screen.
	 *
	 * @since 2.0.0
	 *
	 * @param WP_Screen $screen Optional. The screen to get strings for.
	 *
	 * @return array The strings found for the screen provided.
	 */
	public static function get_strings_for_screen( $screen ) {
		$list = static::$strings_by_screen;

		$strings = array();

		// Loop through each property and check if a list exists for it
		foreach ( $screen as $property => $value ) {
			if ( isset( $list[ $property ] ) ) {
				// Now check if there's a match for the exact value
				if ( isset( $list[ $property ][ $value ] ) ) {
					$strings = array_merge( $strings, $list[ $property ][ $value ] );
				}
			}
		}

		return $strings;
	}

	// =========================
	// ! Hook Registration
	// =========================

	/**
	 * Register backend hooks.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 */
	public static function register_hooks() {
		// Saving localized strings
		static::add_action( 'admin_init', 'save_localized_strings' );

		// Setup the strings for the screen and add the Help tab
		static::add_action( 'admin_head', 'setup_localized_strings' );

		// Do the call to the nlingualLocalizeFields utility
		static::add_action( 'admin_footer', 'do_localized_strings' );
	}

	// =========================
	// ! Registration Tools
	// =========================

	/**
	 * Register a string for localization.
	 *
	 * @since 2.0.0
	 *
	 * @see Localizer_String::$properties for a list of valid values for $options.
	 *
	 * @uses Localizer::$registered_strings to store the new string by ID.
	 * @uses Localizer::$strings_by_key to store the new string by key.
	 * @uses Localizer::$strings_by_screen to store the new string by screen.
	 *
	 * @param string $id      A unique ID for the strings.
	 * @param array  $options The options for the string.
	 *
	 * @return Localizer_String the registered string object.
	 */
	public static function register_string( $id, array $options ) {
		// Abort if the screen isn't set
		if ( ! isset( $options['screen'] ) ) {
			return;
		}

		// Create a new string object from the arguments
		$string = new Localizer_String( $id, $options );

		// Add to the registry
		static::$registered_strings[ $id ] = $string;

		// Add to the key index
		if ( ! isset( static::$strings_by_key[ $string->key ] ) ) {
			static::$strings_by_key[ $string->key ] = array();
		}
		static::$strings_by_key[ $string->key ][] = $string;

		// Add to the screen index
		list( $property, $match ) = $string->screen;
		if ( ! isset( static::$strings_by_screen[ $property ] ) ) {
			static::$strings_by_screen[ $property ] = array();
		}
		if ( ! isset( static::$strings_by_screen[ $property ][ $match ] ) ) {
			static::$strings_by_screen[ $property ][ $match ] = array();
		}
		static::$strings_by_screen[ $property ][ $match ][] = $string;

		return $string;
	}

	/**
	 * Localize a standard option field.
	 *
	 * @since 2.0.0
	 *
	 * @param string $option The name of the option (as identified by get_option()).
	 * @param string $page   The id or base of the screen the field should be found on.
	 * @param array  $args   The custom arguments for the string.
	 *		@option string|array "screen"      A screen ID or property/value pair to match.
	 * 		@option string       "field"       The name of the input that handles this string.
	 * 		@option string       "field_id"    The id of the HTML input to target. (Defaults to input name)
	 */
	public static function register_option( $option, $page, $args = array() ) {
		// Build the args for the string and register it
		$args = wp_parse_args( $args, array(
			'type'   => 'option',
			'screen' => array( 'id', $page ),
			'field'  => $option,
		) );
		static::register_string( "option:{$option}", $args );

		// Add the filter to handle retrieval (frontend only)
		if ( ! is_backend() ) {
			static::add_filter( "pre_option_{$option}", 'handle_localized_option', 10, 1 );
		}

		// Add action to handle updating
		static::add_action( "update_option_{$option}", 'update_unlocalized_option', 10, 2 );
	}

	/**
	 * Localize term names/descriptions for a taxonomy.
	 *
	 * This adds localizer controls to name and description fields for terms,
	 * for when you don't want to localize terms as separate objects per language.
	 *
	 * @since 2.0.0
	 *
	 * @param string $taxonomy The taxonomy to localize term names for.
	 */
	public static function register_taxonomy( $taxonomy ) {
		// Register the name and description fields as normal
		$page = "edit-{$taxonomy}";
		$type = "term_{$taxonomy}";

		static::register_string( "term.{$taxonomy}:term_name", array(
			'key'      => "term_name",
			'type'     => 'term_field',
			'screen'   => array( 'id', $page ),
			'field'    => 'name',
		) );
		static::register_string( "term.{$taxonomy}:term_description", array(
			'key'      => "term_description",
			'type'     => 'term_field',
			'screen'   => array( 'id', $page ),
			'field'    => 'description',
		) );

		// Add the filters to handle it (frontend only)
		if ( ! is_backend() ) {
			static::add_filter( "get_{$taxonomy}", 'handle_localized_term', 10, 2 );
			static::add_filter( 'get_terms', 'handle_localized_terms', 10, 2 );
		}

		// Add action to handle updating
		static::add_action( "edited_term", 'update_unlocalized_term', 10, 3 );
	}

	/**
	 * Localize a post data field.
	 *
	 * This will assume the field name/ID is the same as the key.
	 *
	 * The post type value if provided will be used as the screen post_type value.
	 *
	 * @since 2.0.0
	 *
	 * @uses Localizer::$localizable_post_fields to check if the field name is allowed.
	 *
	 * @param string $post_type  The post type this applies to.
	 * @param string $field_name The post_field name (and the field name/ID).
	 * @param array  $args       The custom arguments for the string.
	 * 		@option string "field"       The name of the input that handles this string.
	 * 		@option string "field_id"    The id of the HTML input to target (Defaults to input name).
	 */
	public static function register_post_field( $post_type, $field_name, $args = array() ) {
		// Abort if the field name isn't allowed
		if ( ! in_array( $field_name, static::$localizable_post_fields ) ) {
			return;
		}

		// Build the args for the string and register it
		$args = wp_parse_args( $args, array(
			'key'    => "post_field:{$field_name}",
			'type'   => 'post_field',
			'screen' => array( 'post_type', $post_type ),
			'field'  => $meta_key,
		) );

		// Register the field as normal
		static::register_string( "post_field.{$post_type}:{$field_name}", $args );

		if ( ! is_backend() ) {
			// Setup filtering if needed (frontend only)
			static::maybe_add_action( 'the_post', 'handle_localized_post_fields', 10, 1 );
		}

		// Add action to handle updating
		static::maybe_add_action( 'post_updated', 'update_unlocalized_post_fields', 10, 2 );
	}

	/**
	 * Localize a meta data field.
	 *
	 * This will assume the field name/ID is the same as the key.
	 *
	 * This will guess the screen base based on $meta_type
	 * if not provided.
	 *
	 * @since 2.0.0
	 *
	 * @param string $meta_type The type of object the meta data is for.
	 * @param string $meta_key  The metadata key (and the field name/ID).
	 * @param array  $args      The custom arguments for the string.
	 *		@option string|array "screen"      A screen ID or property/value pair to match.
	 * 		@option string       "field"       The name of the input that handles this string.
	 * 		@option string       "field_id"    The id of the HTML input to target (Defaults to input name).
	 */
	public static function register_metadata( $meta_type, $meta_key, $args = array() ) {
		// Guess the screen based on available information
		if ( ! isset( $args['screen'] ) ) {
			// For posts, check for post_type
			if ( $meta_type == 'post' ) {
				if ( isset( $args['post_type'] ) ) {
					$args['screen'] = array( 'post_type', $args['post_type'] );
				} else {
					$args['screen'] = array( 'base', 'post' );
				}
			}
			// For terms, check for taxonomy
			elseif ( $meta_type == 'post' ) {
				if ( isset( $args['taxonomy'] ) ) {
					$args['screen'] = array( 'taxonomy', $args['taxonomy'] );
				} else {
					$args['screen'] = array( 'base', 'edit-tags' );
				}
			}
		}

		// Build the args for the string and register it
		$args = wp_parse_args( $args, array(
			'type'   => "{$meta_type}meta",
			'screen' => array( 'base', "edit-{$meta_type}" ),
			'field'  => $meta_key,
		) );

		// Register the field as normal
		static::register_string( "meta.{$meta_type}:{$meta_key}", $args );

		if ( ! is_backend() ) {
			// Setup filtering (if not already)
			static::maybe_add_filter( "get_{$meta_type}_metadata", 'handle_localized_metadata', 10, 4 );
		}

		// Add action to handle updating
		static::maybe_add_action( "update_{$meta_type}_metadata", 'update_unlocalized_metadata', 10, 4 );
	}

	// =========================
	// ! Get/Set Tools
	// =========================

	/**
	 * Get the localized version of the string.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 *
	 * @param string $key         The string key to search for.
	 * @param int    $language_id The language ID to match.
	 * @param int    $object_id   The object ID if relevent (otherwise 0).
	 *
	 * @return string|bool The localized version, false if nothing found.
	 */
	public static function get_string_value( $key, $language_id, $object_id = 0, $check_reg = true ) {
		global $wpdb;

		// Abort if check isn't bypassed and fails
		if ( $check_reg && ! isset( static::$strings_by_key[ $key ] ) ) {
			return null;
		}

		$cache_id = "$key/$object_id/$language_id";

		// Check if it's cached, return if so
		if ( $cached = wp_cache_get( $cache_id, 'nlingual:localized' ) ) {
			return $cached;
		}

		$value = $wpdb->get_var( $wpdb->prepare( "
			SELECT localized_value
			FROM $wpdb->nl_localizerdata
			WHERE string_key = %s
			AND language_id = %d
			AND object_id = %d
		", $key, $language_id, $object_id ) );

		// Add it to the cache
		wp_cache_set( $cache_id, $value, 'nlingual:localized' );

		return $value;
	}

	/**
	 * Get all localized versions of a string.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Localizer::$strings_by_key to check if any strings are registered under this key.
	 * @uses Registry::languages() to fill out empty slots for each language as needed.
	 *
	 * @param string $key       The string key to search for.
	 * @param int    $object_id Optional. The object ID if relevent (otherwise 0).
	 * @param bool   $check_reg Optional. Wether or not to check if the string is regsitered before fetching (default TRUE).
	 *
	 * @return array The localized versions of the specified string.
	 */
	public static function get_string_values( $key, $object_id = 0, $check_reg = true ) {
		global $wpdb;

		// Abort if check isn't bypassed and fails
		if ( $check_reg && ! isset( static::$strings_by_key[ $key ] ) ) {
			return array();
		}

		$results = $wpdb->get_results( $wpdb->prepare( "
			SELECT language_id, localized_value
			FROM $wpdb->nl_localizerdata
			WHERE string_key = %s
			AND object_id = %d
		", $key, $object_id ) );

		$strings = array();
		foreach ( $results as $result ) {
			$strings[ $result->language_id ] = $result->localized_value;
		}

		// Fill with empty values for all languages
		foreach ( Registry::languages() as $language ) {
			if ( ! isset( $strings[ $language->id ] ) ) {
				$strings[ $language->id ] = '';
			}
		}

		return $strings;
	}

	/**
	 * Save the localized version of the string.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Localizer::$strings_by_key to check if any strings are registered under this key.
	 *
	 * @param string $key         The string key to search for.
	 * @param int    $language_id The language ID to save for.
	 * @param int    $object_id   The object ID if relevent (otherwise 0).
	 * @param string $value       The localized value of the string.
	 * @param bool   $check_reg   Optional. Wether or not to check if the string is regsitered before fetching (default TRUE).
	 */
	public static function save_string_value( $key, $language_id, $object_id, $value, $check_reg = true ) {
		global $wpdb;

		// Abort if check isn't bypassed and fails
		if ( $check_reg && ! isset( static::$strings_by_key[ $key ] ) ) {
			return;
		}

		return $wpdb->replace( $wpdb->nl_localizerdata, array(
			'language_id'     => $language_id,
			'object_id'       => $object_id,
			'string_key'      => $key,
			'localized_value' => $value,
		), array( '%d', '%d', '%s', '%s' ) );
	}

	// =========================
	// ! Localize Handling Filters
	// =========================

	/**
	 * Replace an option with it's localized version if found.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_language() to get the current language.
	 * @uses Localizer::get_string_value() to retrieve the localized value.
	 *
	 * @param mixed $pre_option Value to return instead of the option value.
	 *
	 * @return string The localized version of the option.
	 */
	public static function handle_localized_option( $pre_option ) {
		// Get the current filter and the option based on it
		$filter = current_filter();
		$option = preg_replace( '/^pre_option_/', '', $filter );

		// Get the current language
		$language = Registry::current_language();

		// Get the localized version of the string if it exists
		if ( $value = static::get_string_value( "option:{$option}", $language->id ) ) {
			return $value;
		}

		return $pre_option;
	}

	/**
	 * Stores the new option value as the localized version for the default language.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_language() to get the current language.
	 * @uses Localizer::save_string_value() to save the unlocalized value.
	 *
	 * @param mixed $old_value The old value of the option (unused).
	 * @param mixed $value     The new value of the option.
	 */
	public static function update_unlocalized_option( $old_value, $value ) {
		// Get the current filter and the option based on it
		$filter = current_filter();
		$option = preg_replace( '/^update_option_/', '', $filter );

		// Get the default language
		$language = Registry::default_language();

		// Store this value as the version for the default language
		static::save_string_value( "option:{$option}", $language->id, 0, $value );
	}

	/**
	 * Replace a term's name and description with the localized versions if found.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_language() to get the current language.
	 * @uses Localizer::get_string_value() to retrieve the localized value.
	 *
	 * @param object $term     The term to be localized.
	 *
	 * @return object The term with localized name and description.
	 */
	public static function handle_localized_term( $term ) {
		// Get the current language
		$language = Registry::current_language();

		// Get the localized version of the string if it exists
		if ( $name = static::get_string_value( "term_name", $language->id, $term->term_id ) ) {
			$term->name = $name;
		}
		if ( $description = static::get_string_value( "term_description", $language->id, $term->term_id ) ) {
			$term->description = $description;
		}

		return $term;
	}

	/**
	 * Alias of handle_lcoalize_term() for a collection of terms.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @uses Localizer::handle_localized_term() to handle each term.
	 *
	 * @param array $terms The list of terms to handle.
	 *
	 * @return array The modified list of terms.
	 */
	public static function handle_localized_terms( $terms ) {
		foreach ( $terms as &$term ) {
			$term = static::handle_localized_term( $term );
		}
		return $terms;
	}

	/**
	 * Stores the new name/description as the localized versions for the default language.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_language() to get the current language.
	 * @uses Localizer::save_string_value() to save the unlocalized values.
	 *
	 * @param int $term_id     The ID of the term being updated.
	 * @param int $tt_id       The term taxonomy ID of the term (unused).
	 * @param string $taxonomy The taxonomy of the term being updated.
	 */
	public static function update_unlocalized_term( $term_id, $tt_id, $taxonomy ) {
		// Get the udpated term, since we aren't provided with the updated values
		$term = get_term( $term_id, $taxonomy );

		// Abort if no term is found
		if ( ! $term ) {
			return;
		}

		// Get the default language
		$language = Registry::default_language();

		// Store this value as the version for the default language
		static::save_string_value( "term_name", $language->id, $term_id, $term->name );
		static::save_string_value( "term_description", $language->id, $term_id, $term->description );
	}

	/**
	 * Replace a post's fields with their localized versions if found.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_language() to get the current language.
	 * @uses Localizer::$localizable_post_fields for the whitelist of post fields.
	 * @uses Localizer::get_string_value() to retrieve the localized value.
	 *
	 * @param WP_Post $post The post object to filter.
	 */
	public static function handle_localized_post_fields( $post ) {
		// Abort if no post is specified
		if ( ! $post ) return;

		// Get the current language
		$language = Registry::current_language();

		// Loop through each localizable field and replace as needed
		foreach ( static::$localizable_post_fields as $field_name ) {
			// Get the localized version, replace it if found
			if ( $localized = static::get_string_value( "post_field:{$field_name}", $language->id, $post->ID ) ) {
				$post->$field_name = $localized;
			}
		}
	}

	/**
	 * Stores the updated values of a posts field as the localized version for the default language.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_language() to get the current language.
	 * @uses Localizer::$localizable_post_fields for the whitelist of post fields.
	 * @uses Localizer::save_string_value() to save the unlocalized value.
	 *
	 * @param int $post_id The ID of the post being updated.
	 * @param WP_Post $post The updated post object.
	 */
	public static function update_unlocalized_post_fields( $post_id, $post ) {
		// Abort if no post is specified
		if ( ! $post ) return;

		// Get the current language
		$language = Registry::current_language();

		// Loop through each localizable field and replace with localized values if found
		foreach ( static::$localizable_post_fields as $field_name ) {
			// Store this value as the version for the default language
			static::save_string_value( "post_field:{$field_name}", $language->id, $post_id, $post->$field_name );
		}
	}

	/**
	 * Replace a metadata value with it's localized version if found.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_language() to get the current language.
	 * @uses Localizer::get_string_value() to retrieve the localized value.
	 *
	 * @param null|array|string $pre_value The value that should be returned (single or array)
	 * @param int               $object_id The object's ID.
	 * @param string            $meta_key  The metadata key to retrieve data for.
	 * @param bool              $single    Return a single value or array of values?
	 *
	 * @return string The localized version of the value.
	 */
	public static function handle_localized_metadata( $pre_value, $object_id, $meta_key, $single ) {
		// Get the meta type based on the current filter name
		$filter = current_filter();
		$meta_type = preg_replace( '/^get_(.+?)_metadata$/', '$1', $filter );

		// Get the current language
		$language = Registry::current_language();

		// Get the localized version of the string if it exists
		if ( $value = static::get_string_value( "meta.{$meta_type}:{$meta_key}", $language->id, $object_id ) ) {
			return $value;
		}

		return $pre_value;
	}

	/**
	 * Stores the new meta value as the localized version for the default language.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_language() to get the current language.
	 * @uses Localizer::save_string_value() to save the unlocalized value.
	 *
	 * @param int    $meta_id The ID of updated metadata entry (ignored).
	 * @param int    $object_id The object's ID.
	 * @param string $meta_key  The metadata key to retrieve data for.
	 * @param string $meta_value The update meta value.
	 */
	public static function update_unlocalized_metadata( $meta_id, $object_id, $meta_key, $meta_value ) {
		// Get the meta type based on the current filter name
		$filter = current_filter();
		$meta_type = preg_replace( '/^get_(.+?)_metadata$/', '$1', $filter );

		// Get the default language
		$language = Registry::default_language();

		// Store this value as the version for the default language
		static::save_string_value( "meta.{$meta_type}:{$meta_key}", $language->id, $object_id, $meta_value );
	}

	// =========================
	// ! Callbacks
	// =========================

	/**
	 * Save handler for localized strings.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @global string $pagenow The current page slug.
	 *
	 * @uses Registry::languages() to get the available languages.
	 * @uses Localizer::$registered_strings to loop through all registered strings.
	 * @uses Localizer::save_string_value() to save the localized values.
	 */
	public static function save_localized_strings() {
		global $pagenow;

		// Check if the localize strings collection and nonces are set
		if ( ! isset( $_REQUEST['nlingual_localized'] ) || ! isset( $_REQUEST['_nl_l10n_nonce'] ) ) {
			return;
		}

		// Determin object ID to use
		$object_id_keys = array(
			'post.php'      => 'post_ID',
			'edit-tags.php' => 'tag_ID',
			'profile.php'   => 'user_id',
		);
		// If this screen is for an object, get the ID
		if ( isset( $object_id_keys[ $pagenow ] ) ) {
			$object_id_key = $object_id_keys[ $pagenow ];

			// Abort if no object ID is found.
			if ( ! isset( $_REQUEST[ $object_id_key ] ) ) {
				return;
			}

			$object_id = $_REQUEST[ $object_id_key ];
		} else {
			// Default to 0 since it's not for an object
			$object_id = 0;
		}

		// Get the strings and nonces
		$localized = $_REQUEST['nlingual_localized'];
		$nonces = $_REQUEST['_nl_l10n_nonce'];

		// Get the languages
		$languages = Registry::languages();

		// Storage of strings to save
		$to_save = array();

		// Loop through registered strings
		foreach ( static::$registered_strings as $string ) {
			// Check if set, skip otherwise
			if ( ! isset( $localized[ $string->field ] ) ) {
				continue;
			}

			// Fail if nonce does
			if ( ! isset( $nonces[ $string->field ] ) || ! wp_verify_nonce( $nonces[ $string->field ], "nlingual_localize_{$string->key}" ) ) {
				cheatin();
			}

			// Loop through each localized version
			foreach ( $localized[ $string->field ] as $language_id => $value ) {
				// Fail if the language is not found
				if ( ! $languages->get( $language_id ) ) {
					wp_die( __( 'That language does not exist.' ) );
				}

				// Unescape the value
				$value = stripslashes( $value );

				// Add the entry to save
				$to_save[] = array( $string->key, $language_id, $object_id, $value );
			}
		}

		// Perform the saves
		foreach ( $to_save as $save ) {
			call_user_func_array( array( static::$name, 'save_string_value' ), $save );
		}
	}

	/**
	 * Get the strings relevant to the current screen and add the help tab.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @uses Localizer::$current_strings to store the strings found.
	 * @uses Localizer::get_strings_for_screen() to get the strings for the screen.
	 * @uses Documenter::get_tab_content
	 */
	public static function setup_localized_strings() {
		// Get the current screen
		$screen = get_current_screen();

		// Determin object ID to use
		$object_id_keys = array(
			'post'      => 'post',
			'edit-tags' => 'tag_ID',
			'user-edit' => 'user_id',
		);
		// If this screen is for an object, get the ID
		if ( isset( $object_id_keys[ $screen->base ] ) ) {
			$object_id_key = $object_id_keys[ $screen->base ];

			// Abort if no object ID is found.
			if ( ! isset( $_REQUEST[ $object_id_key ] ) ) {
				return;
			}

			$object_id = $_REQUEST[ $object_id_key ];
		} else {
			// Default to 0 since it's not for an object
			$object_id = 0;
		}

		// Now get the strings registered to this screen (by id or base)
		$strings = static::get_strings_for_screen( $screen );

		// If no strings are found, abort
		if ( ! $strings ) {
			return;
		}

		// Store the strings and object id
		static::$current_strings = $strings;
		static::$current_object_id = $object_id;

		// Add the help tab to this screen
		Documenter::setup_help_tabs( 'localizer' );
	}

	/**
	 * Print the script for adding the localizer utility to fields.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @uses Localizer::$current_strings to get the stored strings.
	 * @uses Localizer::get_string_values() to get the localized values of each string.
	 */
	public static function do_localized_strings() {
		// Abort if no strings are found
		if ( ! $strings = static::$current_strings ) {
			return;
		}

		// Get the current object id
		$object_id = static::$current_object_id;

		// Create the entries for each strings
		$data = array();
		foreach ( $strings as $string ) {
			// Get the localized values for this string if available
			$values = static::get_string_values( $string->key, $object_id );

			// Create the nonce
			$nonce = wp_create_nonce( "nlingual_localize_{$string->key}" );

			// Create the row
			$data[] = array( $string->field, $string->field_id, $values, $nonce );
		}

		?>
		<script>
		nlingualLocalizeFields(<?php echo json_encode( $data ); ?>);
		</script>
		<?php
	}

	// =========================
	// ! Overloading
	// =========================

	/**
	 * Method overloader.
	 *
	 * @since 2.0.0
	 *
	 * @uses Localizer::get_strings_by() if the method name starts with "get_strings_by_".
	 * @uses Localizer::register_metadata() if method name is "register_[post_type]_meta".
	 *
	 * @param string $name The name of the method being called.
	 * @param array  $args The arguments for the method.
	 *
	 * @return mixed The result of the redirected method.
	 */
	public static function __callStatic( $name, $args ) {
		// Check if it's a get_strings_by alias
		if ( preg_match( '/^get_strings_by_(\w+)$/', $name, $matches ) ) {
			$index = $matches[1];
			array_unshift( $args, $index );
			return call_user_func_array( array( static::$name, 'get_strings_by' ), $args );
		}
		// Check if it's a register_metadata alias
		if ( preg_match( '/^register_(\w+)_meta$/', $name, $matches ) ) {
			$meta_type = $matches[1];
			array_unshift( $args, $meta_type );
			return call_user_func_array( array( static::$name, 'register_metadata' ), $args );
		}
		return false;
	}
}
