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

		// Old split-language string support
		static::add_filter( 'the_title', 'maybe_split_langs_for_title', 10, 2 );
		if ( ! get_option( '_nlingual_options_converted' ) ) {
			// Somehow the options were not converted (not taking chances),
			// hook nl_split_langs into blogname and blogdescription
			add_filter( 'option_blogname', 'nl_split_langs', 10, 1 );
			add_filter( 'option_blogdescription', 'nl_split_langs', 10, 1 );
		}

		// Redirects for old filters, running them at the very end
		static::add_filter( 'nlingual_process_url', 'redirect_old_process_url_hook', PHP_INT_MAX, 2 );
		static::add_filter( 'nlingual_localize_url', 'redirect_old_localize_url_hook', PHP_INT_MAX, 4 );
		static::add_filter( 'nlingual_localize_here', 'redirect_old_localize_here_array_hook', PHP_INT_MAX, 3 );

		// Localizable terms migration utility
		static::add_action( 'admin_notices', 'compatibility_convert_terms_notice', 10, 0 );
		static::add_action( 'admin_init', 'compatibility_convert_terms_process', 10, 0 );
	}

	// =========================
	// ! - Split-Language
	// =========================

	/**
	 * Filter the post title through nl_split_langs if applicable.
	 *
	 * @since 2.0.0
	 *
	 * @param string $title   The title to filter.
	 * @param int    $post_id Optional. The ID of the post this is for.
	 *
	 * @return string The filtered option value.
	 */
	public static function maybe_split_langs_for_title( $title, $post_id = null ) {
		// If a post ID was specified (should have been),
		// don't bother if it doesn't support translation
		if ( $post_id && Registry::is_post_type_supported( get_post_type( $post_id ) ) ) {
			return $title;
		}

		return nl_split_langs( $title );
	}

	// =========================
	// ! - Filter Redirects
	// =========================

	/**
	 * Run filters assigned to deprecated nLingual_process_url hook.
	 *
	 * @since 2.0.0
	 *
	 * @param array $url_data     The updated URL data.
	 * @param array $old_url_data The original URL data.
	 *
	 * @return array The filtered URL data.
	 */
	public static function redirect_old_process_url_hook( $url_data, $old_url_data ) {
		// Only apply the old filter if there are hooks registered to it.
		if ( has_filter( 'nLingual_process_url' ) ) {
			/**
			 * Filter the new localized URL.
			 *
			 * @deprecated Use "nlingual_process_url" instead (case-sensitive).
			 *
			 * @param array $url_data     The updated URL data.
			 * @param array $old_url_data The original URL data.
			 */
			$url_data = apply_filters( 'nLingual_process_url', $url_data, $old_url_data );
		}

		return $url_data;
	}

	/**
	 * Run filters assigned to deprecated nLingual_localize_url hook.
	 *
	 * @since 2.0.0
	 *
	 * @param string   $url        The new localized URL.
	 * @param string   $old_url    The original URL passed to this function.
	 * @param Langauge $language   The language requested.
	 * @param bool     $relocalize Whether or not to forcibly relocalize the URL.
	 *
	 * @return string The filtered URL.
	 */
	public static function redirect_old_localize_url_hook( $url, $old_url, Language $language, $relocalize ) {
		// Only apply the old filter if there are hooks registered to it.
		if ( has_filter( 'nLingual_localize_url' ) ) {
			/**
			 * Filter the $url_data array.
			 *
			 * @deprecated Use "nlingual_localize_url" instead (case-sensitive).
			 *
			 * @param string  $new_url    The new localized URL.
			 * @param string  $old_url    The original URL passed to this function.
			 * @param string  $lang       The slug of the language requested.
			 * @param bool    $relocalize Whether or not to forcibly relocalize the URL.
			 */
			$url = apply_filters( 'nLingual_localize_url', $url, $old_url, $language->slug, $relocalize );
		}

		return $url;
	}

	/**
	 * Run filters assigned to deprecated nLingual_localize_here_array hook.
	 *
	 * @since 2.0.0
	 *
	 * @param string   $url      The new URL.
	 * @param array    $url_data The parsed URL data.
	 * @param Language $language The desired language to localize for.
	 *
	 * @return string The filtered URL.
	 */
	public static function redirect_old_localize_here_array_hook( $url, $old_url, Language $language ) {
		// Only apply the old filter if there are hooks registered to it.
		if ( has_filter( 'nLingual_localize_here_array' ) ) {
			// Conver to data array
			$url_data = Rewriter::parse_url( $url );

			/**
			 * Filter the $url_data array.
			 *
			 * @deprecated Use "nlingual_localize_here" instead (case-sensitive).
			 *
			 * @param array  $url_data The new localized URL.
			 * @param string $lang     The slug of the language requested.
			 */
			$url_data = apply_filters( 'nLingual_localize_here_array', $url_data, $language->slug );

			// Build the filtered URL
			$url = Rewriter::build_url( $url_data );
		}

		return $url;
	}

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
		$separator = $wpdb->esc_like( $old_options['separator'] );
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

		// Get the old options, check for a separator, abort if none found
		$old_options = get_option( '__old-nLingual-options', array() );
		if ( ! isset( $old_options['separator'] ) || ! $old_options['separator'] ) {
			wp_die( _e( 'No language separator found, unable to convert terms.' ) );
		}

		// Escape % and _ characters in separator for MySQL use
		$separator_mysql = str_replace( array( '%', '_' ), array( '\\%', '\\_' ), $old_options['separator'] );

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
