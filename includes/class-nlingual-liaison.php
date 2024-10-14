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
final class Liaison extends Handler {
	// =========================
	// ! Properties
	// =========================

	/**
	 * Record of added hooks.
	 *
	 * @internal Used by the Handler enable/disable methods.
	 *
	 * @since 2.6.0
	 *
	 * @var array
	 */
	protected static $implemented_hooks = array();

	// =========================
	// ! Hook Registration
	// =========================

	/**
	 * Register hooks.
	 *
	 * @since 2.5.0 Added WooCommerce compatibility.
	 * @since 2.0.0
	 */
	public static function register_hooks() {
		// Backwards compatibility
		self::add_hook( 'plugins_loaded', 'add_backwards_compatibility', 10, 0 );

		// QuickStart compatibility
		self::add_hook( 'after_setup_theme', 'add_quickstart_helpers', 10, 0 );

		// IndexPages compatibility
		self::add_hook( 'after_setup_theme', 'add_indexpages_helpers', 10, 0 );

		// WooCommerce compatibility
		self::add_hook( 'plugins_loaded', 'add_woocommerce_helpers', 10, 0 );

		// SEO Plugins compatibility
		self::add_hook( 'plugins_loaded', 'add_seo_helpers', 10, 0 );
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
		require NL_PLUGIN_DIR . '/includes/functions-compatibility.php';

		// Old split-language string support
		add_filter( 'the_title', 'nl_split_langs', 10, 1 );
		if ( ! get_option( 'nlingual_upgraded_options' ) ) {
			// Somehow the options were not converted (not taking chances),
			// hook nl_split_langs into blogname and blogdescription
			add_filter( 'option_blogname', 'nl_split_langs', 10, 1 );
			add_filter( 'option_blogdescription', 'nl_split_langs', 10, 1 );
		}

		// Old body classes
		self::add_hook( 'body_class', 'add_body_classes', 10, 1 );

		// Redirects for old filters, running them at the very end
		self::add_hook( 'nlingual_process_url', 'redirect_old_process_url_hook', PHP_INT_MAX, 2 );
		self::add_hook( 'nlingual_localize_url', 'redirect_old_localize_url_hook', PHP_INT_MAX, 4 );
		self::add_hook( 'nlingual_localize_here', 'redirect_old_localize_here_array_hook', PHP_INT_MAX, 3 );

		// Localizable terms migration utility
		self::add_hook( 'admin_notices', 'compatibility_convert_terms_notice', 10, 0 );
		self::add_hook( 'admin_init', 'compatibility_convert_terms_process', 10, 0 );
	}

	// =========================
	// ! - Old Body Class
	// =========================

	/**
	 * Add the lang class.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_language() to get the current language.
	 *
	 * @param array $classes The current list of body classes.
	 *
	 * @return array The modified list of classes.
	 */
	public static function add_body_classes( $classes ) {
		// Add language slug
		$classes[] = 'lang-' . Registry::current_language( 'slug' );

		return $classes;
	}

	// =========================
	// ! - Filter Redirects
	// =========================

	/**
	 * Run filters assigned to deprecated nLingual_process_url hook.
	 *
	 * @since 2.0.0
	 *
	 * @param URL   $the_url      The processed URL object.
	 * @param mixed $old_url_data The original URL data.
	 *
	 * @return URL The filtered URL object.
	 */
	public static function redirect_old_process_url_hook( URL $the_url, $old_url_data ) {
		// Only apply the old filter if there are hooks registered to it.
		if ( has_filter( 'nLingual_process_url' ) ) {
			/**
			 * Filter the processed URL data.
			 *
			 * @deprecated Use "nlingual_process_url" instead (case-sensitive).
			 *
			 * @param array $url_data     The updated URL data.
			 * @param mixed $old_url_data The original URL data.
			 */
			$url_data = apply_filters( 'nLingual_process_url', $the_url->dump(), $old_url_data );

			// Update with the filtered URL data
			$the_url->update( $url_data );
		}

		return $the_url;
	}

	/**
	 * Run filters assigned to deprecated nLingual_localize_url hook.
	 *
	 * @since 2.8.4 Dropped $relocalize option, will always relocalize.
	 * @since 2.0.0
	 *
	 * @param string   $url      The new localized URL.
	 * @param string   $old_url  The original URL passed to this function.
	 * @param Language $language The language requested.
	 *
	 * @return string The filtered URL.
	 */
	public static function redirect_old_localize_url_hook( $url, $old_url, Language $language ) {
		// Only apply the old filter if there are hooks registered to it.
		if ( has_filter( 'nLingual_localize_url' ) ) {
			/**
			 * Filter the localized URL.
			 *
			 * @deprecated Use "nlingual_localize_url" instead (case-sensitive).
			 *
			 * @param string  $new_url The new localized URL.
			 * @param string  $old_url The original URL passed to this function.
			 * @param string  $lang    The slug of the language requested.
			 */
			$url = apply_filters( 'nLingual_localize_url', $url, $old_url, $language->slug, true );
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
			// Convert to data array
			$the_url = new URL( $url );

			/**
			 * Filter the $url_data of the localized current URL.
			 *
			 * @deprecated Use "nlingual_localize_here" instead (case-sensitive).
			 *
			 * @param array  $url_data The new localized URL.
			 * @param string $lang     The slug of the language requested.
			 */
			$url_data = apply_filters( 'nLingual_localize_here_array', $the_url->dump(), $language->slug );

			// Update and build the filtered URL
			$url = $the_url->update( $url_data )->build();
		}

		return $url;
	}

	// =========================
	// ! - Terms Migration
	// =========================

	/**
	 * Print notice offering migration of localizable terms if applicable.
	 *
	 * @since 2.10.0 Add translator note to message.
	 * @since 2.0.0
	 *
	 * @global \wpdb $wpdb The database abstraction class instance.
	 */
	public static function compatibility_convert_terms_notice() {
		global $wpdb;

		// Get the old separator, abort if not found
		if ( ! $separator = Registry::get( '_old_separator' ) ) {
			return;
		}

		// Check if ANY term names contain the separator
		$separator = $wpdb->esc_like( $separator );
		$terms = $wpdb->get_results( $wpdb->prepare( "SELECT term_id FROM $wpdb->terms WHERE name LIKE %s LIMIT 1", "%$separator%" ) );

		// Abort if no terms are found
		if ( ! $terms ) {
			return;
		}

		// Print the message with the upgrade link
		// translators: %s = link url, please preserve HTML
		$message = __( 'It looks like some of your terms use the old language splitting method. <a href="%s">Click here</a> to convert them to the new localized format.', 'nlingual' );

		$nonce = wp_create_nonce( 'convert-localized-terms' );
		$link = admin_url( 'admin.php?nlingual-action=convert-terms&_nlnonce=' . $nonce );
		$message = sprintf( $message, $link );
		?>
		<div class="notice notice-info">
			<p><?php echo $message; ?></p>
		</div>
		<?php
	}

	/**
	 * Proceed to convert applicable terms to the new format.
	 *
	 * Also enable their respective taxonomies if not already.
	 *
	 * @since 2.10.0 Proper escaping for mysql.
	 * @since 2.0.0
	 *
	 * @global \wpdb $wpdb The database abstraction class instance.
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
		if ( empty( $_GET['_nlnonce'] ) || ! wp_verify_nonce( $_GET['_nlnonce'], 'convert-localized-terms' ) ) {
			cheatin();
		}

		// Get the old separator, abort if not found
		if ( ! $separator = Registry::get( '_old_separator' ) ) {
			return;
		}

		// Escape % and _ characters in separator for MySQL use
		$separator_mysql = str_replace( array( '%', '_' ), array( '\\%', '\\_' ), $separator );
		$separator_mysql = '%' . $wpdb->esc_like(  $separator_mysql ) . '%';

		// Get all terms that need to be converted
		$terms = $wpdb->get_results( $wpdb->prepare(
			"
			SELECT t.name, x.description, x.term_taxonomy_id, x.term_id, x.taxonomy
			FROM $wpdb->terms AS t
				LEFT JOIN $wpdb->term_taxonomy AS x ON (t.term_id = x.term_id)
			WHERE t.name LIKE '%s'
				OR x.description LIKE '%s'
			",
			$separator_mysql,
			$separator_mysql
		) );

		// Fail if nothing is found
		if ( ! $terms ) {
			wp_die( esc_html_e( 'No terms found needing conversion.', 'nlingual' ) );
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
		Registry::set( 'taxonomies', $taxonomies );
		Registry::save( 'options' );

		wp_redirect( 'admin.php?page=nlingual-localizables&notice=nl-terms-converted' );
		exit;
	}

	/**
	 * Print notice confirming the terms were converted.
	 *
	 * @since 2.0.0
	 */
	public static function compatibility_convert_terms_success() {
		// Abort if no indication of terms being converted
		if ( ! isset( $_GET['notice'] ) || $_GET['notice'] != 'nl-terms-converted' ) {
			return;
		}

		?>
		<div class="updated">
			<p><?php esc_html_e( 'All terms found have been successfully converted, and their taxonomies have been enabled for localization.', 'nlingual' ); ?></p>
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
			// Only run these on the frontend
			if ( is_backend() ) {
				// Flag translations of index pages
				// (uses same as IndexPage's one but it can handle both implementations)
				self::add_hook( 'display_post_states', 'indexpages_flag_translations', 10, 2 );
			} else {
				// Replace the retrieved index page's ID with it's translation counterpart
				Frontend::add_hook( 'qs_helper_get_index', 'current_language_post', 10, 1 );
			}
		}

		// Order manager feature adjustments
		if ( current_theme_supports( 'quickstart-order_manager' ) ) {
			// Set language appropriately
			self::add_hook( 'nlingual_pre_set_queried_language', 'quickstart_order_manager_language', 10, 2 );
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
	 * @param mixed     $pre_value The value to use instead of the determined one.
	 * @param \WP_Query $query     The query being modified.
	 *
	 * @return mixed The default language ot use.
	 */
	public static function quickstart_order_manager_language( $pre_value, $query ) {
		// Don't bother on comment queries
		if ( is_a( $query, 'WP_Comment_Query' ) ) {
			return $pre_value;
		}

		// Get the context and the post type
		$context = $query->get( 'qs-context' );
		$post_type = $query->get( 'post_type' );

		// If multiple post types, not the order manager context, or post type isn't supported, abort
		if ( is_array( $post_type ) || $context != 'order-manager' || ! Registry::is_post_type_supported( $post_type ) ) {
			return $pre_value;
		}

		// Get the sync rules for the post type
		$rules = Registry::get_post_sync_rules( $post_type, 'post_fields' );
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
	 * @since 2.6.0 Added support for term index pages.
	 * @since 2.0.0
	 *
	 * @uses Frontend::current_language_post() on the qs_helper_get_index filter.
	 */
	public static function add_indexpages_helpers() {
		// Abort if IndexPages isn't present
		if ( ! class_exists( 'IndexPages\System' ) ) {
			return;
		}

		// Only run these on the frontend
		if ( is_backend() ) {
			// Flag translations of index pages
			self::add_hook( 'display_post_states', 'indexpages_flag_translations', 10, 2 );

			// Add notice of the page being a translation of an index page
			self::add_hook( 'edit_form_after_title', 'indexpages_translation_notice', 10, 1 );

			// Filter the pages for the dropdown to assign index pages
			self::add_hook( 'get_pages', 'indexpages_filter_pages_for_dropdown', 10, 2 );
		} else {
			// Replace the retrieved index/term page's ID with it's current language counterpart
			Frontend::add_hook( 'indexpages_get_index_page', 'current_language_post', 10, 1 );
			Frontend::add_hook( 'indexpages_get_term_page', 'current_language_post', 10, 1 );

			// Replace the retrieved index/term page's ID with it's default language counterpart
			Frontend::add_hook( 'indexpages_is_index_page', 'default_language_post', 10, 1 );
			Frontend::add_hook( 'indexpages_is_term_page', 'default_language_post', 10, 1 );
		}
	}

	/**
	 * Filter the post states list, flagging translated indexes where necessary.
	 *
	 * This will also work for the QuickStart version of this utility.
	 *
	 * @since 2.6.0 Add check to make sure post's type is supported.
	 * @since 2.0.0
	 *
	 * @param array    $post_states The list of post states for the post.
	 * @param \WP_Post $post        The post in question.
	 *
	 * @return array The filtered post states list.
	 */
	public static function indexpages_flag_translations( array $post_states, \WP_Post $post ) {
		// Abort if the post's type is not supported
		if ( ! Registry::is_post_type_supported( $post->post_type ) ) {
			return $post_states;
		}

		// Determine which function to use (IndexPages' Registry::is_index_page() or QuickStart's is_index_page())
		if ( class_exists( 'IndexPages\Registry' ) ) {
			$function = array( 'IndexPages\Registry', 'is_index_page' );
		} elseif ( function_exists( 'is_index_page' ) ) {
			$function = 'is_index_page';
		} else {
			// Somehow neither are available, abort
			return $post_states;
		}

		// If it's a page and not in the default language...
		$language = Translator::get_post_language( $post->ID );
		if ( $post->post_type == 'page' && ! Registry::is_language_default( $language ) ) {
			$translation = Translator::get_post_translation( $post->ID, Registry::default_language() );

			// Check if the original is an assigned index page (other than post), get associated post type
			if ( ( $post_type = call_user_func( $function, $translation ) ) && $post_type !== 'post' ) {
				$post_type_obj = get_post_type_object( $post_type );

				// Check if the index_page_translation label exists, use that
				if ( property_exists( $post_type_obj->labels, 'index_page_translation' ) ) {
					$label = sprintf( $post_type_obj->labels->index_page_translation, $language->system_name );
				} else {
					// Use generic one otherwise
					/* translators: %1$s = The name of the language, %2$s = The (likely plural) name of the post type. */
					$label = _fx( '%1$s %2$s Page', 'index page translation', 'nlingual', $language->system_name, $post_type_obj->label );
				}

				$post_states[ "page_for_{$post_type}_posts"] = $label;
			}
		}

		return $post_states;
	}

	/**
	 * Print a notice about the current page being a translation of an index page.
	 *
	 * Unlike WordPress for the Posts page, it will not disabled the editor.
	 *
	 * @since 2.6.0 Add check to make sure post's type is supported.
	 * @since 2.1.1 Fixed namespace scope typo accessing IndexPages\Registry.
	 * @since 2.0.0
	 *
	 * @param \WP_Post $post The post in question.
	 */
	public static function indexpages_translation_notice( \WP_Post $post ) {
		// Abort if not a page or not a localizable post type
		if ( $post->post_type != 'page' || ! Registry::is_post_type_supported( $post->post_type ) ) {
			return;
		}

		$language = Translator::get_post_language( $post->ID );

		// Abort if not a page, or in the default language
		if ( Registry::is_language_default( $language ) ) {
			return;
		}

		$translation = Translator::get_post_translation( $post->ID, Registry::default_language() );

		// Abort if the original is not an index page
		if ( ! $post_type = \IndexPages\Registry::is_index_page( $translation ) ) {
			return;
		}

		// Get the plural labe to use
		$label = strtolower( get_post_type_object( $post_type )->label );

		// Default notice type/message
		$notice_type = 'info';
		$notice_text = esc_html( _fx( 'You are currently editing a translation of the page that shows your latest %s.', 'index page translation', 'nlingual', $label ) );

		// If the post type doesn't explicitly support index-pages, mention content may not be displayed.
		if ( ! post_type_supports( $post_type, 'index-page' ) ) {
			$notice_type = 'warning';
			// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			$notice_text .= ' <em>' . esc_html__( 'Your current theme may not display the content you write here.', 'index-pages' ) . '</em>';
		}

		printf( '<div class="notice notice-%s inline"><p>%s</p></div>', esc_attr( $notice_type ), $notice_text );
	}

	/**
	 * Filter the pages for the dropdown menu used to assign index pages.
	 *
	 * @since 2.6.0
	 *
	 * @param array $pages The list of page objects to filter.
	 * @param array $args  The arguments for get_pages().
	 */
	public static function indexpages_filter_pages_for_dropdown( $pages, $args ) {
		// Bail if pages are not supported for tranlsation
		if ( ! Registry::is_post_type_supported( 'page' ) ) {
			return $pages;
		}

		// Bail if the index-pages plugin context isn't specified
		if ( ! isset( $args['plugin-context'] ) || $args['plugin-context'] != 'index-pages' ) {
			return $pages;
		}

		$filtered_pages = array();

		// Loop through and only add those that are in the default language
		foreach ( $pages as $page ) {
			$language = Translator::get_post_language( $page );
			// If it has no language or the default language, add it
			if ( ! $language || Registry::is_language_default( $language ) ) {
				$filtered_pages[] = $page;
			}
		}

		return $filtered_pages;
	}

	// =========================
	// ! WooCommerce Helpers
	// =========================

	/**
	 * Check if WooCommerce is active, setup necessary helpers.
	 *
	 * @since 2.5.0
	 */
	public static function add_woocommerce_helpers() {
		// Abort if WooCommerce isn't present
		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		// Add WC endpoint support to localize_here
		self::add_hook( 'nlingual_localize_here', 'woocommerce_localize_endpoint', 10, 1 );

		// Replace the retrieved WC page's ID with it's current language counterpart
		$pages = array( 'shop', 'cart', 'checkout', 'terms', 'myaccount' );
		foreach ( $pages as $page ) {
			Frontend::add_hook( 'woocommerce_get_' . $page . '_page_id', 'current_language_post', 10, 1 );
		}
	}

	/**
	 * Fix localization of pages with WC endpoints
	 *
	 * @since 2.5.0
	 *
	 * @global \WP $wp The WordPress environment object.
	 *
	 * @param string $url The new URL.
	 */
	public static function woocommerce_localize_endpoint( $url ) {
		global $wp;

		// Get the available endpoints
		$wc_endpoints = \WC()->query->get_query_vars();

		foreach ( $wc_endpoints as $query_var => $endpoint ) {
			if ( isset( $wp->query_vars[ $query_var ] ) ) {
				$url = wc_get_endpoint_url( $endpoint, $wp->query_vars[ $query_var ], $url );
				break;
			}
		}

		return $url;
	}

	// =========================
	// ! SEO Plugin Helpers
	// =========================

	/**
	 * Check if certian SEO plugins are active, setup necessary helpers.
	 *
	 * @since 2.9.1
	 */
	public static function add_seo_helpers() {
		// All-in-one SEO, disable language redirect for sitemaps
		if ( function_exists( 'aioseo' ) ) {
			self::add_hook( 'nlingual_maybe_redirect_language', 'aioseo_skip_redirect_for_sitemap', 10, 1 );
		}
	}

	/**
	 * Filter the language redirect URL, disable if a sitemap request.
	 *
	 * @since 2.9.1
	 *
	 * @param bool $redirect_url The URL to redirect to.
	 *
	 * @return string|bool False if a sitemap request, the original URL otherwise.
	 */
	public static function aioseo_skip_redirect_for_sitemap( $redirect_url ) {
		if ( get_query_var( 'aiosp_sitemap_path' ) ) {
			return false;
		}

		return $redirect_url;
	}
}
