<?php
namespace nLingual;

/**
 * nLingual Localization API
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

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
	 * @var array
	 */
	protected static $registered_fields = array();

	/**
	 * A list of all metadata keys registered for localizing.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 * @var array
	 */
	protected static $registered_metadata = array();

	/**
	 * An index of object_keys for fields
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 * @var array
	 */
	protected static $field_object_keys = array();

	/**
	 * A list of all pages registered for localizing.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 * @var array
	 */
	protected static $pages = array( '__any__' => array() );

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

		// Calling the nlingualLocalizeFields utility
		static::add_action( 'admin_footer', 'setup_localized_strings' );
	}

	// =========================
	// ! Registration Tools
	// =========================

	/**
	 * Localize a field on an admin page.
	 *
	 * @since 2.0.0
	 *
	 * @param string $string The key the string is stored under.
	 * @param string $field  The ID of the field as it appears on the page.
	 * @param string $page   Optional The page to expect the field on.
	 */
	public static function register_field( $string, $field, $page = '__any__' ) {
		// Log the field for this string
		static::$registered_fields[ $string ] = $field;

		// Add the string to the page list, setup if it doesn't exist
		if ( ! isset( static::$pages[ $page ] ) ) {
			static::$pages[ $page ] = array();
		}
		static::$pages[ $page ][] = $string;
	}

	/**
	 * Localize a standard option field.
	 *
	 * @since 2.0.0
	 *
	 * @param string $option The field name/ID of the option.
	 * @param string $page   Optional The page to expect the field on.
	 */
	public static function register_option( $option, $page = null ) {
		if ( is_admin() ) {
			// Register the field as normal
			static::register_field( "option_{$option}", $option, $page );
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
			static::register_field( "term_{$taxonomy}_name", 'name', $page, true );
			static::register_field( "term_{$taxonomy}_description", 'description', $page, true );
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
	 * @param string $meta_type  The type of object the meta data is for.
	 * @param string $meta_key   The metadata key (and the field name/ID).
	 * @param string $page       Optional The page to expect the field on.
	 * @param string $object_key Optional The field to find the object's ID in.
	 */
	public static function register_metadata( $meta_type, $meta_key, $page = null, $object_key = null ) {
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

			// Register the field as normal
			static::register_field( "meta_{$meta_type}_{$meta_key}", $meta_key, $page, true );
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
	 * @param string $key       The string key to search for.
	 * @param int    $lang_id   The language ID to match.
	 * @param int    $object_id The object ID if relevent (otherwise 0).
	 *
	 * @return string|bool The localized version, false if nothing found.
	 */
	public static function get_string( $key, $lang_id, $object_id = 0 ) {
		global $wpdb;

		$value = $wpdb->get_var( $wpdb->prepare( "
			SELECT string_value
			FROM $wpdb->nl_strings
			WHERE string_key = %s
			AND lang_id = %d
			AND object_id = %d
		", $key, $lang_id, $object_id ) );

		return $value;
	}

	/**
	 * Save the localized version of the string.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string $key       The string key to search for.
	 * @param int    $lang_id   The language ID to save for.
	 * @param int    $object_id The object ID if relevent (otherwise 0).
	 * @param string $value     The localized value of the string.
	 */
	public static function save_string( $key, $lang_id, $object_id, $value ) {
		global $wpdb;

		$wpdb->replace( $wpdb->nl_strings, array(
			'lang_id'      => $lang_id,
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
	 * @uses Registry::current_lang() to get the current language.
	 * @uses Localizer::get_string() to retrieve the localized value.
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
		$language = Registry::current_lang();

		// Get the localized version of the string if it exists
		if ( $value = static::get_string( "option_{$option}", $language->lang_id ) ) {
			return $value;
		}

		return $pre_option;
	}

	/**
	 * Replace a term's name and description with the localized versions if found.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_lang() to get the current language.
	 * @uses Localizer::get_string() to retrieve the localized value.
	 *
	 * @param object $term The term to be localized.
	 * @param string $taxonomy The term's taxonomy.
	 *
	 * @return object The term with localized name and description.
	 */
	public static function handle_localized_term( $term, $taxonomy ) {
		// Get the current language
		$language = Registry::current_lang();

		// Get the localized version of the string if it exists
		if ( $name = static::get_string( "term_{$taxonomy}_name", $language->lang_id, $term->term_id ) ) {
			$term->name = $name;
		}
		if ( $description = static::get_string( "term_{$taxonomy}_description", $language->lang_id, $term->term_id ) ) {
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
	 * @uses Registry::current_lang() to get the current language.
	 * @uses Localizer::get_string() to retrieve the localized value.
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
		$language = Registry::current_lang();

		// Get the localized version of the string if it exists
		if ( $value = static::get_string( "meta_{$meta_type}_{$meta_key}", $language->lang_id, $object_id ) ) {
			return $value;
		}

		return $pre_value;
	}

	// =========================
	// ! Saving/Setup Callbacks
	// =========================

	/**
	 * Save handler for localized strings.
	 *
	 * @since 2.0.0
	 *
	 * @global string $pagenow The current page slug.
	 *
	 * @uses Registry::languages() to get the available languages.
	 * @uses Localizer::save_string() to save the localized values.
	 */
	public static function save_localized_strings() {
		global $pagenow;

		// Check if the localize strings collection and nonces are set
		if ( ! isset( $_REQUEST['nlingual_localized'] ) || ! isset( $_REQUEST['_nl_l10n_nonce'] ) ) {
			return;
		}

		// Determine the object ID if applicable
		switch ( $pagenow ) {
			case 'edit.php':
				$object_id = $_REQUEST['post_ID'];
				break;
			case 'edit-tags.php':
				$object_id = $_REQUEST['tag_ID'];
				break;
			case 'profile.php':
				$object_id = $_REQUEST['user_id'];
				break;
			default:
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
		foreach ( static::$registered_fields as $key => $field ) {
			// Check if set, skip otherwise
			if ( ! isset( $localized[ $field ] ) ) {
				continue;
			}

			// Fail if nonce does
			if ( ! isset( $nonces[ $field ] ) || ! wp_verify_nonce( $nonces[ $field ], "nlingual_localize_{$key}" ) ) {
				cheatin();
			}

			// Loop through each localized version
			foreach ( $localized[ $field ] as $lang_id => $value ) {
				// Fail if the language is not found
				if ( ! $languages->get( $lang_id ) ) {
					wp_die( __( 'That language does not exist.' ) );
				}

				// Add the entry to save
				$to_save[] = array( $key, $lang_id, $object_id, $value );
			}
		}

		// Perform the saves
		foreach ( $to_save as $save ) {
			call_user_func_array( array( static::$name, 'save_string' ), $save );
		}
	}

	/**
	 * Print the script for adding the localizer utility to fields.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 */
	public static function setup_localized_strings() {
		global $wpdb;
		$data = array();

		// Get the current screen
		$screen = get_current_screen();

		// Determine the object ID if applicable
		switch ( $screen->base ) {
			case 'post':
				$object_id = $_REQUEST['post'];
				break;
			case 'edit-tags':
				$object_id = $_REQUEST['tag_ID'];
				break;
			case 'user-edit':
				$object_id = $_REQUEST['user_id'];
				break;
			default:
				$object_id = 0;
		}

		// Get the languages
		$languages = Registry::languages();

		// Get all strings registered for unspecified pages
		$strings = static::$pages[ '__any__' ];

		// Get all strings registered for this specific page (based on id, failing to base.
		if ( isset( static::$pages[ $screen->id ] ) ) {
			$strings = array_merge( $strings, static::$pages[ $screen->id ] );
		} elseif ( isset( static::$pages[ $screen->base ] ) ) {
			$strings = array_merge( $strings, static::$pages[ $screen->base ] );
		}

		// Get the localized values for every string found
		$keys = implode( '\',\'', $wpdb->_escape( $strings ) );
		$results = $wpdb->get_results( $wpdb->prepare( "
			SELECT lang_id, string_key, string_value
			FROM $wpdb->nl_strings
			WHERE string_key IN ( '$keys' )
			AND object_id = %d
		", $object_id ) );
		$localized = array();
		foreach ( $results as $result ) {
			if ( ! isset( $localized[ $result->string_key ] ) ) {
				$localized[ $result->string_key ] = array();
			}
			$localized[ $result->string_key ][ $result->lang_id ] = $result->string_value;
		}

		// Create the entries for each strings
		foreach ( $strings as $key ) {
			// Get the field for the string
			$field = static::$registered_fields[ $key ];

			// Get the localized values for this string if available
			$values = isset( $localized[ $key ] ) ? $localized[ $key ] : array();

			// Create the nonce
			$nonce = wp_create_nonce( "nlingual_localize_{$key}" );

			// Create the row
			$data[] = array( $field, $values, $nonce, $object_key );
		}

		?>
		<script>
		nlingualLocalizeFields(<?php echo json_encode( $data ); ?>);
		</script>
		<?php
	}
}