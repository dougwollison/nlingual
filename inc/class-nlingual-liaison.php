<?php
/**
 * nLingual Liaison Functionality
 *
 * @package nLingual
 * @subpackage Handlers
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * The Liaison System
 *
 * Adds compatibility with older versions of nLingual,
 * along with select 3rd party systems, namely those
 * written by Doug Wollison.
 *
 * @internal Used by the System.
 *
 * @since 2.0.0
 */

class Liaison extends Handler {
	// =========================
	// ! Hook Registration
	// =========================

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 */
	public static function register_hooks() {
		// Backwards compatibility
		static::add_action( 'plugins_loaded', 'add_backwards_compatibility', 10, 0 );

		// QuickStart compatibility
		static::add_action( 'after_setup_theme', 'add_quickstart_helpers', 10, 0 );

		// IndexPages compatibility
		static::add_action( 'after_setup_theme', 'add_indexpages_helpers', 10, 0 );
	}

	// =========================
	// ! Backwards Compatibility
	// =========================

	/**
	 * Check if backwards compatibility is needed, setup necessary hooks.
	 *
	 * @since 2.0.0
	 */
	public static function add_backwards_compatibility() {
		// Abort if backwards_compatible isn't enabled
		if ( ! Registry::get( 'backwards_compatible' ) ) {
			return;
		}

		// Load the old template functions
		require( NL_PLUGIN_DIR . '/inc/functions-compatibility.php' );

		// Localizable terms migration utility
		static::add_action( 'admin_notices', 'compatibility_convert_terms_notice', 10, 0 );
		static::add_action( 'admin_init', 'compatibility_convert_terms_process', 10, 0 );
	}

	// =========================
	// ! - Hook Redirects
	// =========================

	// =========================
	// ! - Terms Migration
	// =========================

	/**
	 * Print notice offering migration of localizable terms if applicable.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 */
	public static function compatibility_convert_terms_notice() {
		global $wpdb;

		// Get the old options, check for a separator, abort if none found
		$old_options = get_option( '__old-nLingual-options', array() );
		if ( ! isset( $old_options['separator'] ) || ! $old_options['separator'] ) {
			return;
		}

		// Check if ANY term names contain the separator
		$separator = $wpdb->esc_like( $separator );
		$terms = $wpdb->get_results( $wpdb->prepare( "SELECT term_id, name FROM $wpdb->terms WHERE name LIKE %s", "%$separator%" ) );

		// Abort if no terms are found
		if ( ! $terms ) {
			return;
		}

		// Print the message with the upgrade link
		$message = __( 'It looks like some of your terms use the old language splitting method. <a href="%s">Click here</a> to convert them to the new localized format.' );
		$nonce = wp_create_nonce( 'convert-localized-terms' );
		$link = admin_url( 'admin.php?nlingual-action=convert-terms&_nlnonce=' . $nonce );
		$message = sprintf( $message, $link );
		?>
		<div class="notice">
			<p><?php echo $message; ?></p>
		</div>
		<?php
	}

	/**
	 * Proceed to convert applicable terms to the new format.
	 *
	 * Also enable their respective taxonomies if not already.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Installer::convert_split_string() to handle split term names/descriptions.
	 */
	public static function compatibility_convert_terms_process() {
		global $wpdb;

		// Only proceed if action is correct
		if ( ! isset( $_GET['nlingual-action'] ) || $_GET['nlingual-action'] != 'convert-terms' ) {
			return;
		}

		// Fail if nonce does
		if ( ! wp_verify_nonce( $_GET['_nlnonce'], 'convert-localized-terms' ) ) {
			nLingual\cheatin();
		}

		// Get the old separator
		$separator = get_option( 'nlingual-old_separator' );

		// Fail if no separator is present
		if ( ! $separator ) {
			wp_die( _e( 'No language separator found, unable to convert terms.' ) );
		}

		// Escape % and _ characters in separator for MySQL use
		$separator_mysql = str_replace( array( '%', '_' ), array( '\\%', '\\_' ), $separator );

		// Get all terms that need to be converted
		$terms = $wpdb->get_results( "
			SELECT t.name, x.description, x.term_taxonomy_id, x.term_id, x.taxonomy
			FROM $wpdb->terms AS t
				LEFT JOIN $wpdb->term_taxonomy AS x ON (t.term_id = x.term_id)
			WHERE t.name LIKE '%$separator_mysql%'
				OR x.description LIKE '%$separator_mysql%'
		" );

		// Fail if nothing is found
		if ( ! $terms ) {
			wp_die( _e( 'No terms found needing conversion.' ) );
		}

		// Start a list of taxonomies that needed localization
		$taxonomies = array();

		// Loop through each term name, convert and store
		foreach ( $terms as $term ) {
			// add taxonomy to list
			$taxonomies[] = $term->taxonomy;

			$unlocalized_name = Installer::convert_split_string( $term->name, 'term_name', $term->term_id );
			$unlocalized_description = Installer::convert_split_string( $term->description, 'term_description', $term->term_id );

			// Update the values in the database with unlocalized versions
			$wpdb->update( $wpdb->terms, array(
				'name' => $unlocalized_name,
			), array(
				'term_id' => $term->term_id,
			) );
			$wpdb->update( $wpdb->term_taxonomy, array(
				'description' => $unlocalized_description,
			), array(
				'term_taxonomy_id' => $term->term_taxonomy_id,
			) );
		}

		// Now ensure all those taxonomies are registered for localization
		$taxonomies = array_merge( $taxonomies, Registry::get( 'taxonomies' ) );
		$taxonomies = array_unique( $taxonomies );
		update_option( 'nlingual_taxonomies', $taxonomies );

		wp_redirect( 'admin.php?page=nlingual-localizables&notice=nl-terms-converted' );
		exit;
	}

	/**
	 * Print notice confirming the terms were converted.
	 *
	 * @since 2.0.0
	 */
	public static function nl_compatibility_convert_terms_success() {
		// Abort if no indication of terms being converted
		if ( ! isset( $_GET['notice'] ) || $_GET['notice'] != 'nl-terms-converted' ) {
			return;
		}

		?>
		<div class="updated">
			<p><?php _e( 'All terms found have been successfully converted, and their taxonomies have been enabled for localization.' ); ?></p>
		</div>
		<?php
	}

	// =========================
	// ! QuickStart Helpers
	// =========================

	/**
	 * Check if QuickStart is active, setup necessary helpers.
	 *
	 * @since 2.0.0
	 *
	 * @uses Frontend::current_language_post() on the qs_helper_get_index filter.
	 */
	public static function add_quickstart_helpers() {
		// Abort if QuickStart isn't present
		if ( ! function_exists( 'QuickStart' ) ) {
			return;
		}

		// Custom index page feature adjustments
		if ( current_theme_supports( 'quickstart-index_page' ) ) {
			// Replace the retrieved index page's ID with it's translation counterpart
			Frontend::add_filter( 'qs_helper_get_index', 'current_language_post', 10, 1 );
		}

		// Order manager feature adjustments
		if ( current_theme_supports( 'quickstart-order_manager' ) ) {
			// Set language appropriately
			static::maybe_add_filter( 'nlingual_pre_set_queried_language', 'quickstart_order_manager_language', 10, 2 );
		}
	}

	/**
	 * Set queried language to default (and un-assigned) for certain QuickStart queries.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::is_post_type_supported() to check for post type support.
	 * @uses Registry::get_rules() to check for menu_order synchronizing.
	 * @uses Registry::default_language() to get the default language slug.
	 *
	 * @param mixed    $pre_value The value to use instead of the determined one.
	 * @param WP_Query $query     The query being modified.
	 *
	 * @return mixed The default language ot use.
	 */
	public static function quickstart_order_manager_language( $pre_value, \WP_Query $query ) {
		// Get the context and the post type
		$context = $query->get( 'qs-context' );
		$post_type = $query->get( 'post_type' );

		// If multiple post types, not the order manager context, or post type isn't supported, abort
		if ( is_array( $post_type ) || $context != 'order-manager' || ! Registry::is_post_type_supported( $post_type ) ) {
			return $pre_value;
		}

		// Get the sync rules for the post type
		$rules = Registry::get_rules( 'sync', 'post_type', $post_type, 'post_fields' );
		// If menu_order isn't set included in the sync rules, abort
		if ( ! in_array( 'menu_order', $rules ) ) {
			return $pre_value;
		}

		// Set the value to the default language (and no language)
		$pre_value = array( Registry::default_language()->slug, '0' );

		return $pre_value;
	}

	// =========================
	// ! IndexPages Helpers
	// =========================

	/**
	 * Check if IndexPages is active, setup necessary helpers.
	 *
	 * @since 2.0.0
	 *
	 * @uses Frontend::current_language_post() on the qs_helper_get_index filter.
	 */
	public static function add_indexpages_helpers() {
		// Abort if IndexPages isn't present
		if ( ! class_exists( 'IndexPages\System' ) ) {
			return;
		}

		// Replace the retrieved index page's ID with it's current language counterpart
		Frontend::add_filter( 'indexpages_get_index_page', 'current_language_post', 10, 1 );

		// Replace the retrieved index page's ID with it's default language counterpart
		Frontend::add_filter( 'indexpages_is_index_page', 'default_language_post', 10, 1 );
	}
}
