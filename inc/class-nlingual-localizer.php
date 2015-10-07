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
	protected static $fields = array();

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
	 * @param string $page   The page to expect the field on.
	 */
	public static function register_field( $string, $field, $page = '__any__' ) {
		// Log the key/field pair
		static::$fields[ $string ] = $field;

		// Add the key to the page list, setup if it doesn't exist
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
	 * @param string $page   The page to expect the field on.
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

	// =========================
	// ! Retriever Tools
	// =========================

	/**
	 * Get the localized version of the string.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string $key     The string key to search for.
	 * @param int    $lang_id The language ID to match.
	 *
	 * @return string|bool The localized version, false if nothing found.
	 */
	public static function get_string( $key, $lang_id ) {
		global $wpdb;

		$value = $wpdb->get_var( $wpdb->prepare( "
			SELECT string_value FROM $wpdb->nl_strings
			WHERE string_key = %s AND lang_id = %d
		", $key, $lang_id ) );

		return $value;
	}

	// =========================
	// ! Callbacks and Filters
	// =========================

	/**
	 * Replace an option with it's localized version if found.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Registry::current_lang() to get the current language.
	 *
	 * @param mixed $pre_option Value to return instead of the option value.
	 *
	 * @return string The localized version of the option.
	 */
	public static function handle_localized_option( $pre_option ) {
		global $wpdb;

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
	 * Save handler for localized strings.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Registry::languages() to get the available languages.
	 */
	public static function save_localized_strings() {
		global $wpdb;

		// Check if the localize strings collection and nonces are set
		if ( ! isset( $_REQUEST['nlingual_localized'] ) || ! isset( $_REQUEST['_nl_l10n_nonce'] ) ) {
			return;
		}

		// Get the strings and nonces
		$localized = $_REQUEST['nlingual_localized'];
		$nonces = $_REQUEST['_nl_l10n_nonce'];

		// Get the languages
		$languages = Registry::languages();

		// Setup for SQL inserts
		$inserts = array();

		// Loop through registered strings
		foreach ( static::$fields as $key => $field ) {
			// Check if set, skip otherwise
			if ( ! isset( $localized[ $field ] ) ) {
				continue;
			}

			// Fail if nonce does
			if ( ! isset( $nonces[ $field ] ) || ! wp_verify_nonce( $nonces[ $field ], "nlingual_localize_{$key}" ) ) {
				wp_die( __( 'Cheatin&#8217; uh?' ) );
			}

			// Loop through each localized version
			foreach ( $localized[ $field ] as $lang_id => $value ) {
				// Fail if the language is not found
				if ( ! $languages->get( $lang_id ) ) {
					wp_die( __( 'That language does not exist.', NL_TXTDMN ) );
				}

				// Add the row value set
				$inserts[] = $wpdb->prepare( "(%d, %s, %s)", $lang_id, $key, $value );
			}
		}

		// Run the inserts
		$wpdb->query( "REPLACE INTO $wpdb->nl_strings (lang_id, string_key, string_value) VALUES " . implode( ',', $inserts ) );
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

		// Get the languages
		$languages = Registry::languages();

		// Get all strings registered for this page (or "any" page)
		$strings = static::$pages[ '__any__' ];
		if ( isset( static::$pages[ $screen->id ] ) ) {
			$strings = array_merge( $strings, static::$pages[ $screen->id ] );
		}

		// Get the localized values for every string found
		$keys = implode( '\',\'', $wpdb->_escape( $strings ) );
		$results = $wpdb->get_results( "
			SELECT lang_id, string_key, string_value
			FROM $wpdb->nl_strings
			WHERE string_key IN ( '$keys' )
		" );
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
			$field = static::$fields[ $key ];

			// Get the localized values for this string if available
			$values = isset( $localized[ $key ] ) ? $localized[ $key ] : array();

			// Create the nonce
			$nonce = wp_create_nonce( "nlingual_localize_{$key}" );

			// Create the row
			$data[] = array( $field, $values, $nonce );
		}

		?>
		<script>
		nlingualLocalizeFields(<?php echo json_encode( $data ); ?>);
		</script>
		<?php
	}
}