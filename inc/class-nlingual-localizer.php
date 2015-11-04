<?php
/**
 * nLingual Localization API
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

namespace nLingual;

class Localizer extends Functional {
	/**
	 * The name of the class.
	 *
	 * @since 2.0.0
	 *
	 * @access protected (static)
	 *
	 * @var string
	 */
	protected static $name;

	/**
	 * A list of all fields registered for localizing.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var array
	 */
	protected static $registered_strings = array();

	/**
	 * A list of all taxonomies registered for localizing.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var array
	 */
	protected static $registered_taxonomies = array();

	/**
	 * A list of all metadata keys registered for localizing.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var array
	 */
	protected static $registered_metadata = array();

	/**
	 * An index of strings by type.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var array
	 */
	protected static $strings_by_type = array( 'option' => array() );

	/**
	 * An index of strings by page they are to appear on.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var array
	 */
	protected static $strings_by_page = array();

	/**
	 * Storage for the strings found for the current screen.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var array
	 */
	protected static $current_strings = array();

	/**
	 * Storage for the current object ID if applicable.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var int
	 */
	protected static $current_object_id = 0;

	// =========================
	// ! Property Access
	// =========================

	/**
	 * Get a string by ID.
	 *
	 * @since 2.0.0
	 *
	 * @param string $key The key of the string to retrieve.
	 *
	 * @return object|bool The retreived string, FALSE on failure.
	 */
	public static function get_string( $key ) {
		if ( isset( static::$registered_strings[ $key ] ) ) {
			return static::$registered_strings[ $key ];
		}
		return false;
	}

	/**
	 * Get a registration list.
	 *
	 * @since 2.0.0
	 *
	 * @param string $type The type of list to get (strings, taxonomies, metadata).
	 *
	 * @return string The requested list.
	 */
	public static function get_registered( $type ) {
		$property = "registered_{$type}";
		return static::$$property;
	}

	/**
	 * Get a list of strings from an index.
	 *
	 * @since 2.0.0
	 *
	 * @param string $index The index to search in ("type" or "page")
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

	// =========================
	// ! Hook Registration
	// =========================

	/**
	 * Register backend hooks.
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
	 * Localize a field on an admin page.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args The arguments for the string.
	 * 		@option string "key"         The key the string will be stored under.
	 * 		@option string "field"       The name of the HTML field that handles this string.
	 * 		@option string "field_id"    The id of the HTML field to target. (Defaults to field value)
	 *		@option string "type"        The type string this is (e.g. 'option' or an object type).
	 *		@option string "page"        The id/base of the screen this field should appear on.
	 *		@option string "title"       An descriptive name of this string.
	 *		@option string "description" The details of this string's purpose.
	 * 		@option string "input"       The field input to use ("textarea" or an <input> type).
	 */
	public static function register_field( $args ) {
		// Parse the args with the defaults
		$args = wp_parse_args( $args, array(
			'key'         => null,
			'field'       => null,
			'field_id'    => null,
			'type'        => 'option',
			'page'        => null,
			'title'       => null,
			'description' => null,
			'input'       => 'text',
		) );

		// Abort if no key is passed
		if ( is_null( $args['key'] ) ) {
			return;
		}

		// Abort if no page is passed
		if ( is_null( $args['page'] ) ) {
			return;
		}

		$key = $args['key'];

		// Assume field is the same as key if not set
		if ( is_null( $args['field'] ) ) {
			$args['field'] = $key;
		}

		// Assume field_id is the same as field if not set
		if ( is_null( $args['field_id'] ) ) {
			$args['field_id'] = $args['field'];
		}

		// Cast as object
		$string = (object) $args;

		// Add to the registry
		static::$registered_strings[ $key ] = $string;

		// Add to the type index
		$type = $args['type'];
		if ( ! isset( static::$strings_by_type[ $type ] ) ) {
			static::$strings_by_type[ $type ] = array();
		}
		static::$strings_by_type[ $type ][] = $string;

		// Add to the page index
		$type = $args['page'];
		if ( ! isset( static::$strings_by_page[ $type ] ) ) {
			static::$strings_by_page[ $type ] = array();
		}
		static::$strings_by_page[ $type ][] = $string;
	}

	/**
	 * Localize a standard option field.
	 *
	 * @since 2.0.0
	 *
	 *
	 * @param string $option The name of the option (as identified by get_option()).
	 * @param string $page   The id or base of the screen the field should be found on.
	 * @param array  $args   The custom arguments for the string.
	 * 		@option string "field"       The name of the field that handles this string.
	 * 		@option string "field_id"    The id of the HTML field to target. (Defaults to field value)
	 *		@option string "page"        The id/base of the screen this field should appear on.
	 *		@option string "title"       An descriptive name of this string.
	 *		@option string "description" The details of this string's purpose.
	 * 		@option string "input"       The field input to use ("textarea" or an <input> type).
	 */
	public static function register_option( $option, $page, $args = array() ) {
		if ( is_admin() ) {
			// Build the args for the string and register it
			$args = wp_parse_args( $args, array(
				'key'   => "option_{$option}",
				'page'  => $page,
				'field' => $option,
				'type'  => 'option',
			) );
			static::register_field( $args );
		} else {
			// Add the filter to handle it
			static::add_filter( "pre_option_{$option}", 'handle_localized_option' );
		}
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
		if ( is_admin() ) {
			// Register the name and description fields as normal
			$page = "edit-{$taxonomy}";
			$type = "term_{$taxonomy}";

			static::register_field( array(
				'key'      => "term_{$taxonomy}_name",
				'field'    => 'name',
				'page'     => $page,
				'title'    => __( 'Name' ),
				'type'     => $type,
				'input'    => 'text',
			) );
			static::register_field( array(
				'key'      => "term_{$taxonomy}_description",
				'field'    => 'description',
				'page'     => $page,
				'title'    => __( 'Description' ),
				'type'     => $type,
				'input'    => 'textarea',
			) );

			// Add the taxonomy to the registered list
			static::$registered_taxonomies[] = $taxonomy;
		} else {
			// Add the filter to handle it
			static::add_filter( "get_{$taxonomy}", 'handle_localized_term', 10, 2 );
			static::add_filter( 'get_terms', 'handle_localized_terms', 10, 2 );
		}
	}

	/**
	 * Localize a meta data field.
	 *
	 * This will assume the field name/ID is the same as the key.
	 *
	 * This will guess the $page and $object_key based on $meta_type
	 * if not provided.
	 *
	 * @since 2.0.0
	 *
	 * @param string $meta_type   The type of object the meta data is for.
	 * @param string $meta_key    The metadata key (and the field name/ID).
	 * @param array  $args   The custom arguments for the string.
	 * 		@option string "field"       The name of the field that handles this string.
	 * 		@option string "field_id"    The id of the HTML field to target. (Defaults to field value)
	 *		@option string "page"        The id/base of the screen this field should appear on.
	 *		@option string "title"       An descriptive name of this string.
	 *		@option string "description" The details of this string's purpose.
	 * 		@option string "input"       The field input to use ("textarea" or an <input> type).
	 */
	public static function register_metadata( $meta_type, $meta_key, $args = array() ) {
		if ( is_admin() ) {
			// Guess the page if not set
			if ( is_null( $page ) ) {
				if ( $meta_type == 'post' ) {
					$page = $meta_type;
				} elseif ( $meta_type == 'term' ) {
					$page = 'edit-tags';
				} else {
					$page = "{$meta_type}-edit";
				}
			}

			// Build the args for the string and register it
			$args = wp_parse_args( $args, array(
				'key'   => "meta_{$meta_type}_{$meta_key}",
				'field' => $meta_key,
				'type'  => $meta_type,
				'page'  => $page,
			) );

			// Register the field as normal
			static::register_field( $args );
		} else {
			// Register it for filtering
			if ( ! isset( static::$registered_metadata[ $type ] ) ) {
				static::$registered_metadata[ $type ] = array();
				// The filter for this meta type hasn't been setup yet
				static::add_filter( "get_{$meta_type}_metadata", 'handle_localized_metadata', 10, 4 );
			}
			static::$registered_metadata[ $type ][] = $meta;
		}
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
	 * @uses Registry::cache_get() to check if the localized value has already been fetched.
	 * @uses Registry::cache_set() to store the result for future reuse.
	 *
	 * @param string $key         The string key to search for.
	 * @param int    $language_id The language ID to match.
	 * @param int    $object_id   The object ID if relevent (otherwise 0).
	 *
	 * @return string|bool The localized version, false if nothing found.
	 */
	public static function get_string_value( $key, $language_id, $object_id = 0 ) {
		global $wpdb;

		// Check if it's cached, return if so
		if ( $cached = Registry::cache_get( 'localized', "$key-$language_id-$object_id" ) ) {
			return $cached;
		}

		$value = $wpdb->get_var( $wpdb->prepare( "
			SELECT string_value
			FROM $wpdb->nl_strings
			WHERE string_key = %s
			AND language_id = %d
			AND object_id = %d
		", $key, $language_id, $object_id ) );

		// Add it to the cache
		Registry::cache_set( 'localized', "$key-$language_id-$object_id", $value );

		return $value;
	}

	/**
	 * Get all localized versions of a string.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Registry::languages() to fill out empty slots for each language as needed.
	 *
	 * @param string $key       The string key to search for.
	 * @param int    $object_id The object ID if relevent (otherwise 0).
	 *
	 * @return array The localized versions of the specified string.
	 */
	public static function get_string_values( $key, $object_id = 0 ) {
		global $wpdb;

		$results = $wpdb->get_results( $wpdb->prepare( "
			SELECT language_id, string_value
			FROM $wpdb->nl_strings
			WHERE string_key = %s
			AND object_id = %d
		", $key, $object_id ) );

		$strings = array();
		foreach ( $results as $result ) {
			$strings[ $result->language_id ] = $result->string_value;
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
	 * @param string $key         The string key to search for.
	 * @param int    $language_id The language ID to save for.
	 * @param int    $object_id   The object ID if relevent (otherwise 0).
	 * @param string $value       The localized value of the string.
	 */
	public static function save_string_value( $key, $language_id, $object_id, $value ) {
		global $wpdb;

		return $wpdb->replace( $wpdb->nl_strings, array(
			'language_id'      => $language_id,
			'object_id'    => $object_id,
			'string_key'   => $key,
			'string_value' => $value,
		), array( '%d', '%d', '%s', '%s' ) );
	}

	// =========================
	// ! Localize Handling Filters
	// =========================

	/**
	 * Replace an option with it's localized version if found.
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
		if ( $value = static::get_string_value( "option_{$option}", $language->id ) ) {
			return $value;
		}

		return $pre_option;
	}

	/**
	 * Replace a term's name and description with the localized versions if found.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_language() to get the current language.
	 * @uses Localizer::get_string_value() to retrieve the localized value.
	 *
	 * @param object $term     The term to be localized.
	 * @param string $taxonomy The term's taxonomy.
	 *
	 * @return object The term with localized name and description.
	 */
	public static function handle_localized_term( $term, $taxonomy ) {
		// Get the current language
		$language = Registry::current_language();

		// Get the localized version of the string if it exists
		if ( $name = static::get_string_value( "term_{$taxonomy}_name", $language->id, $term->term_id ) ) {
			$term->name = $name;
		}
		if ( $description = static::get_string_value( "term_{$taxonomy}_description", $language->id, $term->term_id ) ) {
			$term->description = $description;
		}

		return $term;
	}

	/**
	 * Alias for handle_lcoalize_term() but for a collection of terms.
	 *
	 * @since 2.0.0
	 *
	 * @see Localizer::handle_localized_term() for details.
	 */
	public static function handle_localized_terms( $terms, $taxonomy ) {
		foreach ( $terms as &$term ) {
			$term = static::handle_localized_term( $term, $taxonomy[0] );
		}
		return $terms;
	}

	/**
	 * Replace a metadata value with it's localized version if found.
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
		if ( $value = static::get_string_value( "meta_{$meta_type}_{$meta_key}", $language->id, $object_id ) ) {
			return $value;
		}

		return $pre_value;
	}

	// =========================
	// ! Callbacks
	// =========================

	/**
	 * Save handler for localized strings.
	 *
	 * @since 2.0.0
	 *
	 * @global string $pagenow The current page slug.
	 *
	 * @uses Registry::languages() to get the available languages.
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
		foreach ( static::$registered_strings as $key => $string ) {
			// Check if set, skip otherwise
			if ( ! isset( $localized[ $string->field ] ) ) {
				continue;
			}

			// Fail if nonce does
			if ( ! isset( $nonces[ $string->field ] ) || ! wp_verify_nonce( $nonces[ $string->field ], "nlingual_localize_{$key}" ) ) {
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
				$to_save[] = array( $key, $language_id, $object_id, $value );
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
	 * @since 2.0.0
	 *
	 * @uses Localizer::$current_strings to store the strings found.
	 * @uses Localizer::get_strings_by_page() to get the strings for the screen.
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
		if ( $screen->id == $screen->base ) {
			// They're the same; don't want to fetch them twice
			$strings = static::get_strings_by_page( $screen->id );
		} else {
			$strings = array_merge(
				static::get_strings_by_page( $screen->id ),
				static::get_strings_by_page( $screen->base )
			);
		}

		// If no strings are found, abort
		if ( ! $strings ) {
			return;
		}

		// Store the strings and object id
		static::$current_strings = $strings;
		static::$current_object_id = $object_id;

		// Add the help tab to this screen if we can
		if ( $content = Documenter::get_tab_content( 'localize-this' ) ) {
			$screen->add_help_tab( array(
				'id'      => 'nlingual-localize-this',
				'title'   => __( 'Localize This' ),
				'content' => $content,
			) );
		}
	}

	/**
	 * Print the script for adding the localizer utility to fields.
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
	 * @uses Localizer::get_registered() if the method name starts with "get_registered_".
	 * @uses Localizer::get_strings_by() if the method name starts with "get_strings_by_".
	 * @uses Localizer::register_metadata() if method name is "register_[post_type]_meta".
	 *
	 * @param string $name The name of the method being called.
	 * @param array  $args The arguments for the method.
	 *
	 * @return mixed The result of the redirected method.
	 */
	public static function __callStatic( $name, $args ) {
		// Check if it's a get_list alias
		if ( preg_match( '/^get_registered_(\w+)$/', $name, $matches ) ) {
			$type = $matches[1];
			return static::get_registered( $type );
		}
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