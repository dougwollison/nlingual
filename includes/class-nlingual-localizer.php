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
 * An API for registering fields whose values require
 * localization, adding LocalizeThis tools to them
 * in the admin and filtering their frontend output.
 *
 * @api
 *
 * @since 2.0.0
 *
 * @method array get_fields_by_*() return the matching $fields_by_* property.
 * @method void  register_*_meta_field( field $meta_key, array $args )
 *               localize a metadata field for an object type.
 */
final class Localizer extends Handler {
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
	private static $name;

	/**
	 * A list of all fields registered for localizing.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	private static $registered_fields = array();

	/**
	 * An index of fields by key.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	private static $fields_by_key = array();

	/**
	 * An index of fields by type.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	private static $fields_by_type = array();

	/**
	 * An index of fields by screen they are to appear on based on property.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	private static $fields_by_screen = array();

	/**
	 * Storage for the fields found for the current screen.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	private static $current_fields = array();

	/**
	 * Storage for the current object ID if applicable.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var int
	 */
	private static $current_object_id = 0;

	/**
	 * Registry of objects whos localized data has been preloaded.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	private static $preloaded = array();

	/**
	 * Reference of which post fields are permitted for localization.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var int
	 */
	private static $localizable_post_fields = array( 'post_content', 'post_title', 'post_excerpt' );

	// =========================
	// ! Property Access
	// =========================

	/**
	 * Get a field by ID.
	 *
	 * @since 2.0.0
	 *
	 * @param string $id The ID of the field to retrieve.
	 *
	 * @return Localizer_Field|bool The retreived field, FALSE on failure.
	 */
	public static function get_field( $id ) {
		if ( isset( self::$registered_fields[ $id ] ) ) {
			return self::$registered_fields[ $id ];
		}
		return false;
	}

	/**
	 * Get a list of fields from an index.
	 *
	 * @since 2.0.0
	 *
	 * @param string $index The index to search in ("key", "type" or "screen")
	 * @param string $value The index value to get entries for.
	 *
	 * @return array A list of fields for the specified index value.
	 */
	public static function get_fields_by( $index, $value ) {
		$property = "fields_by_{$index}";
		$list = self::$$property;

		if ( isset( $list[ $value ] ) ) {
			return $list[ $value ];
		}

		return array();
	}

	/**
	 * Find fields for the current screen.
	 *
	 * @since 2.0.0
	 *
	 * @param WP_Screen $screen Optional. The screen to get fields for.
	 *
	 * @return array The fields found for the screen provided.
	 */
	public static function get_fields_for_screen( $screen ) {
		$list = self::$fields_by_screen;

		$fields = array();

		// Loop through each property and check if a list exists for it
		foreach ( $screen as $property => $value ) {
			if ( isset( $list[ $property ] ) ) {
				// Now check if there's a match for the exact value
				if ( isset( $list[ $property ][ $value ] ) ) {
					$fields = array_merge( $fields, $list[ $property ][ $value ] );
				}
			}
		}

		return $fields;
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
		// Backend-only hooks
		if ( is_backend() ) {
			// Saving localized fields
			self::add_action( 'admin_init', 'save_localized_fields', 10, 0 );

			// Setup the fields for the screen and add the Help tab
			self::add_action( 'admin_head', 'setup_localized_fields', 10, 0 );

			// Do the call to the nLingual.localizeFields utility
			self::add_action( 'admin_footer', 'do_localized_fields', 10, 0 );
		}
		// Frontend-only hooks
		else {
			// Setup preloading of all localized options
			self::add_action( 'init', 'preload_localized_fields', 10, 0 );
		}
	}

	// =========================
	// ! Registration Tools
	// =========================

	/**
	 * Register a field for localization.
	 *
	 * @since 2.0.0
	 *
	 * @see Localizer_Field::$properties for a list of valid values for $options.
	 *
	 * @uses Localizer::$registered_fields to store the new field by ID.
	 * @uses Localizer::$fields_by_key to store the new field by key.
	 * @uses Localizer::$fields_by_screen to store the new field by screen.
	 *
	 * @param string $id      A unique ID for the fields.
	 * @param array  $options The options for the field.
	 *
	 * @return Localizer_Field the registered field object.
	 */
	public static function register_field( $id, array $options ) {
		// Abort if the screen isn't set
		if ( ! isset( $options['screen'] ) ) {
			return;
		}

		// Create a new field object from the arguments
		$field = new Localizer_Field( $id, $options );

		// Add to the registry
		self::$registered_fields[ $id ] = $field;

		// Add to the key index
		if ( ! isset( self::$fields_by_key[ $field->key ] ) ) {
			self::$fields_by_key[ $field->key ] = array();
		}
		self::$fields_by_key[ $field->key ][] = $field;

		// Add to the type index
		if ( ! isset( self::$fields_by_type[ $field->type ] ) ) {
			self::$fields_by_type[ $field->type ] = array();
		}
		self::$fields_by_type[ $field->type ][] = $field;

		// Add to the screen index
		list( $property, $match ) = array_pad( $field->screen, 2, null );
		if ( ! isset( self::$fields_by_screen[ $property ] ) ) {
			self::$fields_by_screen[ $property ] = array();
		}
		if ( ! isset( self::$fields_by_screen[ $property ][ $match ] ) ) {
			self::$fields_by_screen[ $property ][ $match ] = array();
		}
		self::$fields_by_screen[ $property ][ $match ][] = $field;

		return $field;
	}

	/**
	 * Localize a standard option string.
	 *
	 * @since 2.0.0
	 *
	 * @param string $option The name of the option (as identified by get_option()).
	 * @param string $page   The id or base of the screen the field should be found on.
	 * @param array  $args   The custom arguments for the field.
	 *		@option string|array "screen"      A screen ID or property/value pair to match.
	 * 		@option string       "field"       The name of the input that handles this field.
	 * 		@option string       "field_id"    The id of the HTML input to target. (Defaults to input name)
	 */
	public static function register_option_field( $option, $page, $args = array() ) {
		// Build the args for the field and register it
		$args = wp_parse_args( $args, array(
			'type'   => 'option',
			'screen' => array( 'id', $page ),
			'field'  => $option,
		) );
		self::register_field( "option:{$option}", $args );

		// Add the filter to handle retrieval (frontend only)
		if ( ! is_backend() ) {
			self::add_filter( "pre_option_{$option}", 'handle_localized_option_field', 10, 1 );
		}

		// Add action to handle updating
		self::add_action( "update_option_{$option}", 'update_unlocalized_option_field', 10, 2 );
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
	 * @param array  $args       The custom arguments for the field.
	 * 		@option string "field"       The name of the input that handles this field.
	 * 		@option string "field_id"    The id of the HTML input to target (Defaults to input name).
	 */
	public static function register_post_field( $post_type, $field_name, $args = array() ) {
		// Abort if the field name isn't allowed
		if ( ! in_array( $field_name, self::$localizable_post_fields ) ) {
			return;
		}

		// Build the args for the field and register it
		$args = wp_parse_args( $args, array(
			'key'    => "post_field:{$field_name}",
			'type'   => 'post_field',
			'screen' => array( 'post_type', $post_type ),
			'field'  => $field_name,
		) );

		// Register the field as normal
		self::register_field( "post_field.{$post_type}:{$field_name}", $args );

		if ( ! is_backend() ) {
			// Setup filtering if needed (frontend only)
			self::add_action( 'the_post', 'handle_localized_post_fields', 10, 1 );
		}

		// Add action to handle updating
		self::add_action( 'post_updated', 'update_unlocalized_post_fields', 10, 2 );
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
	 * @param array  $args      The custom arguments for the field.
	 *		@option string|array "screen"      A screen ID or property/value pair to match.
	 * 		@option string       "field"       The name of the input that handles this field.
	 * 		@option string       "field_id"    The id of the HTML input to target (Defaults to input name).
	 */
	public static function register_metadata_field( $meta_type, $meta_key, $args = array() ) {
		// Guess the screen based on available information
		if ( ! isset( $args['screen'] ) ) {
			// For posts, check for post_type
			if ( $meta_type == 'post' ) {
				// Default to general post base
				$args['screen'] = array( 'base', 'post' );

				// Specify post type if provided
				if ( isset( $args['post_type'] ) ) {
					$args['screen'] = array( 'post_type', $args['post_type'] );
				}
			}
			// For terms, check for taxonomy
			elseif ( $meta_type == 'post' ) {
				// Default to general edit-tags base
				$args['screen'] = array( 'base', 'edit-tags' );

				// Specify taxonomy if provided
				if ( isset( $args['taxonomy'] ) ) {
					$args['screen'] = array( 'taxonomy', $args['taxonomy'] );
				}
			}
		}

		// Build the args for the field and register it
		$args = wp_parse_args( $args, array(
			'type'   => "{$meta_type}meta",
			'screen' => array( 'base', "edit-{$meta_type}" ),
			'field'  => $meta_key,
		) );

		// Register the field as normal
		self::register_field( "meta.{$meta_type}:{$meta_key}", $args );

		if ( ! is_backend() ) {
			// Setup filtering (if not already)
			self::add_filter( "get_{$meta_type}_metadata", 'handle_localized_metadata_field', 10, 4 );
		}

		// Add action to handle updating
		self::add_action( "update_{$meta_type}_metadata", 'update_unlocalized_metadata_field', 10, 4 );
	}

	/**
	 * Localize term names/descriptions for a taxonomy.
	 *
	 * This adds localizer controls to name and description fields for terms,
	 * for when you don't want to localize entire terms as separate objects per language.
	 *
	 * @since 2.0.0
	 *
	 * @param string $taxonomy The taxonomy to localize term names for.
	 */
	public static function register_taxonomy( $taxonomy ) {
		// Register the name and description fields as normal
		$page = "edit-{$taxonomy}";

		self::register_field( "term.{$taxonomy}:term_name", array(
			'key'      => "term_name",
			'type'     => 'term_field',
			'screen'   => array( 'id', $page ),
			'field'    => 'name',
		) );
		self::register_field( "term.{$taxonomy}:term_description", array(
			'key'      => "term_description",
			'type'     => 'term_field',
			'screen'   => array( 'id', $page ),
			'field'    => 'description',
		) );

		// Add the filters to handle it (frontend only)
		if ( ! is_backend() ) {
			self::add_filter( "get_{$taxonomy}", 'handle_localized_term_fields', 10, 2 );
			self::add_filter( 'get_terms', 'handle_localized_terms_fields', 10, 2 );
		}

		// Add action to handle updating
		self::add_action( "edited_term", 'update_unlocalized_term_fields', 10, 3 );
	}

	// =========================
	// ! Get/Set Tools
	// =========================

	/**
	 * Get the localized version of the field.
	 *
	 * @since 2.0.0
	 *
	 * @global \wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string $key         The field key to search for.
	 * @param int    $language_id The language ID to match.
	 * @param int    $object_id   The object ID if relevent (otherwise 0).
	 *
	 * @return field|bool The localized version, false if nothing found.
	 */
	public static function get_field_value( $key, $language_id, $object_id = 0, $check_reg = true ) {
		global $wpdb;

		// Abort if check isn't bypassed and fails
		if ( $check_reg && ! isset( self::$fields_by_key[ $key ] ) ) {
			return null;
		}

		$cache_id = "$key/$object_id/$language_id";

		// Check if it's cached, return if so
		$cached = wp_cache_get( $cache_id, 'nlingual:localized', false, $found );
		if ( $found ) {
			return $cached;
		}

		$value = $wpdb->get_var( $wpdb->prepare( "
			SELECT localized_value
			FROM $wpdb->nl_localizations
			WHERE field_key = %s
			AND language_id = %d
			AND object_id = %d
		", $key, $language_id, $object_id ) );

		// Add it to the cache
		wp_cache_set( $cache_id, $value, 'nlingual:localized' );

		return $value;
	}

	/**
	 * Get all localized versions of a field.
	 *
	 * @since 2.0.0
	 *
	 * @global \wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Localizer::$fields_by_key to check if any fields are registered under this key.
	 * @uses Registry::languages() to fill out empty slots for each language as needed.
	 *
	 * @param string $key       The field key to search for.
	 * @param int    $object_id Optional. The object ID if relevent (otherwise 0).
	 * @param bool   $check_reg Optional. Wether or not to check if the field is regsitered before fetching (default TRUE).
	 *
	 * @return array The localized versions of the specified field.
	 */
	public static function get_field_values( $key, $object_id = 0, $check_reg = true ) {
		global $wpdb;

		// Abort if check isn't bypassed and fails
		if ( $check_reg && ! isset( self::$fields_by_key[ $key ] ) ) {
			return array();
		}

		$results = $wpdb->get_results( $wpdb->prepare( "
			SELECT language_id, localized_value
			FROM $wpdb->nl_localizations
			WHERE field_key = %s
			AND object_id = %d
		", $key, $object_id ) );

		$fields = array();
		foreach ( $results as $result ) {
			$fields[ $result->language_id ] = $result->localized_value;
		}

		// Fill with empty values for all languages
		foreach ( Registry::languages() as $language ) {
			if ( ! isset( $fields[ $language->id ] ) ) {
				$fields[ $language->id ] = '';
			}
		}

		return $fields;
	}

	/**
	 * Save the localized version of the field.
	 *
	 * @since 2.0.0
	 *
	 * @global \wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Localizer::$fields_by_key to check if any fields are registered under this key.
	 *
	 * @param string $key         The field key to search for.
	 * @param int    $language_id The language ID to save for.
	 * @param int    $object_id   The object ID if relevent (otherwise 0).
	 * @param string $value       The localized value of the field.
	 * @param bool   $check_reg   Optional. Wether or not to check if the field is regsitered before fetching (default TRUE).
	 */
	public static function save_field_value( $key, $language_id, $object_id, $value, $check_reg = true ) {
		global $wpdb;

		// Abort if check isn't bypassed and fails
		if ( $check_reg && ! isset( self::$fields_by_key[ $key ] ) ) {
			return;
		}

		return $wpdb->replace( $wpdb->nl_localizations, array(
			'language_id'     => $language_id,
			'object_id'       => $object_id,
			'field_key'      => $key,
			'localized_value' => $value,
		), array( '%d', '%d', '%s', '%s' ) );
	}

	// =========================
	// ! Handle/Update Hooks
	// =========================

	// ! - Options

	/**
	 * Replace an option with it's localized version if found.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_language() to get the current language.
	 * @uses Localizer::get_field_value() to retrieve the localized value.
	 *
	 * @param mixed $pre_option Value to return instead of the option value.
	 *
	 * @return field The localized version of the option.
	 */
	public static function handle_localized_option_field( $pre_option ) {
		// Get the current filter and the option based on it
		$filter = current_filter();
		$option = preg_replace( '/^pre_option_/', '', $filter );

		// Get the current language
		$language = Registry::current_language();

		// Get the localized version of the field if it exists
		if ( $value = self::get_field_value( "option:{$option}", $language->id ) ) {
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
	 * @uses Localizer::save_field_value() to save the unlocalized value.
	 *
	 * @param mixed $old_value The old value of the option (unused).
	 * @param mixed $value     The new value of the option.
	 */
	public static function update_unlocalized_option_field( $old_value, $value ) {
		// Get the current filter and the option based on it
		$filter = current_filter();
		$option = preg_replace( '/^update_option_/', '', $filter );

		// Get the default language
		$language = Registry::default_language();

		// Store this value as the version for the default language
		self::save_field_value( "option:{$option}", $language->id, 0, $value );
	}

	// ! - Posts

	/**
	 * Replace a post's fields with their localized versions if found.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_language() to get the current language.
	 * @uses Localizer::$localizable_post_fields for the whitelist of post fields.
	 * @uses Localizer::get_field_value() to retrieve the localized value.
	 *
	 * @param WP_Post $post The post object to filter.
	 */
	public static function handle_localized_post_fields( $post ) {
		// Abort if no post is specified
		if ( ! $post ) return;

		// Check if there are fields registered for this post's type, abort if not
		$fields = self::get_fields_for_screen( array( 'post_type' => $post->post_type ) );
		if ( ! $fields ) {
			return;
		}

		// Get the current language
		$language = Registry::current_language();

		// Loop through each localizable field and replace as needed
		foreach ( self::$localizable_post_fields as $field_name ) {
			// Get the localized version, replace it if found
			if ( $localized = self::get_field_value( "post_field:{$field_name}", $language->id, $post->ID ) ) {
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
	 * @uses Localizer::save_field_value() to save the unlocalized value.
	 *
	 * @param int $post_id The ID of the post being updated.
	 * @param WP_Post $post The updated post object.
	 */
	public static function update_unlocalized_post_fields( $post_id, $post ) {
		// Abort if no post is specified
		if ( ! $post ) return;

		// Check if there are fields registered for this post's type, abort if not
		$fields = self::get_fields_for_screen( array( 'post_type' => $post->post_type ) );
		if ( ! $fields ) {
			return;
		}

		// Get the current language
		$language = Registry::default_language();

		// Loop through each field, save localized value if matching field is found
		foreach ( $fields as $field ) {
			if ( property_exists( $post, $field->field ) ) {
				// Store this value as the version for the default language
				self::save_field_value( $field->key, $language->id, $post_id, $post->{$field->field} );
			}
		}
	}

	// ! - Metadata

	/**
	 * Replace a metadata value with it's localized version if found.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_language() to get the current language.
	 * @uses Localizer::get_field_value() to retrieve the localized value.
	 *
	 * @param null|array|field $pre_value The value that should be returned (single or array)
	 * @param int               $object_id The object's ID.
	 * @param string            $meta_key  The metadata key to retrieve data for.
	 *
	 * @return field The localized version of the value.
	 */
	public static function handle_localized_metadata_field( $pre_value, $object_id, $meta_key ) {
		// Get the meta type based on the current filter name
		$filter = current_filter();
		$meta_type = preg_replace( '/^get_(.+?)_metadata$/', '$1', $filter );

		// Get the current language
		$language = Registry::current_language();

		// Get the localized version of the field if it exists
		if ( $value = self::get_field_value( "meta.{$meta_type}:{$meta_key}", $language->id, $object_id ) ) {
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
	 * @uses Localizer::save_field_value() to save the unlocalized value.
	 *
	 * @param int    $meta_id The ID of updated metadata entry (ignored).
	 * @param int    $object_id The object's ID.
	 * @param string $meta_key  The metadata key to retrieve data for.
	 * @param string $meta_value The update meta value.
	 */
	public static function update_unlocalized_metadata_field( $meta_id, $object_id, $meta_key, $meta_value ) {
		// Get the meta type based on the current filter name
		$filter = current_filter();
		$meta_type = preg_replace( '/^update_(.+?)_metadata$/', '$1', $filter );

		// Get the default language
		$language = Registry::default_language();

		// Store this value as the version for the default language
		self::save_field_value( "meta.{$meta_type}:{$meta_key}", $language->id, $object_id, $meta_value );
	}

	// ! - Terms

	/**
	 * Replace a term's name and description with the localized versions if found.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_language() to get the current language.
	 * @uses Localizer::get_field_value() to retrieve the localized value.
	 *
	 * @param object $term     The term to be localized.
	 *
	 * @return object The term with localized name and description.
	 */
	public static function handle_localized_term_fields( $term ) {
		// Get the current language
		$language = Registry::current_language();

		// Get the localized version of the field if it exists
		if ( $name = self::get_field_value( "term_name", $language->id, $term->term_id ) ) {
			$term->name = $name;
		}
		if ( $description = self::get_field_value( "term_description", $language->id, $term->term_id ) ) {
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
	public static function handle_localized_terms_fields( $terms ) {
		foreach ( $terms as &$term ) {
			$term = self::handle_localized_term_fields( $term );
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
	 * @uses Localizer::save_field_value() to save the unlocalized values.
	 *
	 * @param int $term_id     The ID of the term being updated.
	 * @param int $tt_id       The term taxonomy ID of the term (unused).
	 * @param string $taxonomy The taxonomy of the term being updated.
	 */
	public static function update_unlocalized_term_fields( $term_id, $tt_id, $taxonomy ) {
		// Get the udpated term, since we aren't provided with the updated values
		$term = get_term( $term_id, $taxonomy );

		// Abort if no term is found
		if ( ! $term ) {
			return;
		}

		// Get the default language
		$language = Registry::default_language();

		// Store this value as the version for the default language
		self::save_field_value( "term_name", $language->id, $term_id, $term->name );
		self::save_field_value( "term_description", $language->id, $term_id, $term->description );
	}

	// =========================
	// ! Backend Hooks/Callbacks
	// =========================

	/**
	 * Save handler for localized fields.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @global field $pagenow The current page slug.
	 *
	 * @uses Registry::languages() to get the available languages.
	 * @uses Localizer::$registered_fields to loop through all registered fields.
	 * @uses Localizer::save_field_value() to save the localized values.
	 */
	public static function save_localized_fields() {
		global $pagenow;

		// Check if the localize fields collection and nonces are set
		if ( ! isset( $_REQUEST['nlingual_localized'] ) || ! isset( $_REQUEST['_nl_l10n_nonce'] ) ) {
			return;
		}

		// Determin object ID to use
		$object_id_keys = array(
			'post.php'      => 'post_ID',
			'term.php'      => 'tag_ID',
			'edit-tags.php' => 'tag_ID',
			'profile.php'   => 'user_id',
		);

		// Default to 0 since it's not for an object
		$object_id = 0;

		// If this screen is for an object, get the ID
		if ( isset( $object_id_keys[ $pagenow ] ) ) {
			$object_id_key = $object_id_keys[ $pagenow ];

			// Abort if no object ID is found.
			if ( ! isset( $_REQUEST[ $object_id_key ] ) ) {
				return;
			}

			$object_id = $_REQUEST[ $object_id_key ];
		}

		// Get the fields and nonces
		$localized = $_REQUEST['nlingual_localized'];
		$nonces = $_REQUEST['_nl_l10n_nonce'];

		// Get the languages
		$languages = Registry::languages();

		// Storage of fields to save
		$to_save = array();

		// Loop through registered fields
		foreach ( self::$registered_fields as $field ) {
			// Check if set, skip otherwise
			if ( ! isset( $localized[ $field->field ] ) ) {
				continue;
			}

			// Fail if nonce does
			if ( ! isset( $nonces[ $field->field ] ) || ! wp_verify_nonce( $nonces[ $field->field ], "nlingual_localize_{$field->key}" ) ) {
				cheatin();
			}

			// Loop through each localized version
			foreach ( $localized[ $field->field ] as $language_id => $value ) {
				// Fail if the language is not found
				if ( ! $languages->get( $language_id ) ) {
					wp_die( __( 'That language does not exist.', 'nlingual' ) );
				}

				// Unescape the value
				$value = stripslashes( $value );

				// Add the entry to save
				$to_save[] = array( $field->key, $language_id, $object_id, $value );
			}
		}

		// Perform the saves
		foreach ( $to_save as $save ) {
			call_user_func_array( array( get_called_class(), 'save_field_value' ), $save );
		}
	}

	/**
	 * Get the fields relevant to the current screen and add the help tab.
	 *
	 * @internal
	 *
	 * @since 2.3.1 Added check for NULL screen.
	 * @since 2.0.0
	 *
	 * @uses Localizer::$current_fields to store the fields found.
	 * @uses Localizer::get_fields_for_screen() to get the fields for the screen.
	 * @uses Documenter::setup_help_tabs() to add the Localizer help tab.
	 */
	public static function setup_localized_fields() {
		// Get the current screen
		$screen = get_current_screen();

		// Abort if no screen
		if ( ! $screen ) {
			return;
		}

		// Determin object ID to use
		$object_id_keys = array(
			'post'      => 'post',
			'term'      => 'tag_ID',
			'edit-tags' => 'tag_ID',
			'user-edit' => 'user_id',
		);

		// Default to 0 since it's not for an object
		$object_id = 0;

		// If this screen is for an object, get the ID
		if ( isset( $object_id_keys[ $screen->base ] ) ) {
			$object_id_key = $object_id_keys[ $screen->base ];

			// Abort if no object ID is found.
			if ( ! isset( $_REQUEST[ $object_id_key ] ) ) {
				return;
			}

			$object_id = $_REQUEST[ $object_id_key ];
		}

		// Now get the fields registered to this screen (by id or base)
		$fields = self::get_fields_for_screen( $screen );

		// If no fields are found, abort
		if ( ! $fields ) {
			return;
		}

		// Store the fields and object id
		self::$current_fields = $fields;
		self::$current_object_id = $object_id;

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
	 * @uses Localizer::$current_fields to get the stored fields.
	 * @uses Localizer::get_field_values() to get the localized values of each field.
	 */
	public static function do_localized_fields() {
		// Abort if no fields are found
		if ( ! $fields = self::$current_fields ) {
			return;
		}

		// Get the current object id
		$object_id = self::$current_object_id;

		// Create the entries for each fields
		$data = array();
		foreach ( $fields as $field ) {
			// Get the localized values for this field if available
			$values = self::get_field_values( $field->key, $object_id );

			// Create the nonce
			$nonce = wp_create_nonce( "nlingual_localize_{$field->key}" );

			// Create the row
			$data[] = array(
				'field'    => $field->field,
				'field_id' => $field->field_id,
				'values'   => $values,
				'nonce'    => $nonce,
			);
		}

		?>
		<script>
		nLingual.LocalizableFields.add( <?php echo json_encode( $data ); ?> );
		</script>
		<?php
	}

	// =========================
	// ! Frontend Hooks/Callbacks
	// =========================

	/**
	 * Preload the localized values in the current language.
	 *
	 * @since 2.0.0
	 *
	 * @global \wpdb $wpdb The database abstraction class instance.
	 */
	public static function preload_localized_fields() {
		global $wpdb;

		$language = Registry::current_language();

		$fields = $wpdb->get_results( "SELECT * FROM $wpdb->nl_localizations WHERE language_id = {$language->id}", ARRAY_A );
		foreach ( $fields as $field ) {
			$cache_id = "{$field['field_key']}/{$field['object_id']}/{$field['language_id']}";
			wp_cache_set( $cache_id, $field['localized_value'], 'nlingual:localized' );
		}
	}

	// =========================
	// ! Overloading
	// =========================

	/**
	 * Method overloader.
	 *
	 * @since 2.0.0
	 *
	 * @uses Localizer::get_fields_by() if the method name starts with "get_fields_by_".
	 * @uses Localizer::register_metadata() if method name is "register_[post_type]_meta".
	 *
	 * @param string $name The name of the method being called.
	 * @param array  $args The arguments for the method.
	 *
	 * @throws Exception If the method alias cannot be determined.
	 *
	 * @return mixed The result of the redirected method.
	 */
	public static function __callStatic( $name, $args ) {
		// Check if it's a get_fields_by alias
		if ( preg_match( '/^get_fields_by_(\w+)$/', $name, $matches ) ) {
			$index = $matches[1];
			array_unshift( $args, $index );
			return call_user_func_array( array( get_called_class(), 'get_fields_by' ), $args );
		}

		// Check if it's a register_metadata alias
		if ( preg_match( '/^register_(\w+)_meta_field$/', $name, $matches ) ) {
			$meta_type = $matches[1];
			array_unshift( $args, $meta_type );
			return call_user_func_array( array( get_called_class(), 'register_metadata_field' ), $args );
		}

		// Not a valid method alias, throw exception
		throw new Exception( sprintf( 'Call to unrecognized method alias %1$s::%2$s()', __CLASS__, $name ), NL_ERR_UNSUPPORTED );
	}
}
