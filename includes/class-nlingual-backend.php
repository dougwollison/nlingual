<?php
/**
 * nLingual Backend Functionality
 *
 * @package nLingual
 * @subpackage Handlers
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * The Backend System
 *
 * Hooks into various backend systems to load
 * custom assets, modify the interface, and
 * add language management to relevent screens.
 *
 * @internal Used by the System.
 *
 * @since 2.0.0
 */
final class Backend extends Handler {
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
	 * @since 2.8.10 Renamed callbacks related to unlocalized locations.
	 * @since 2.8.1  Revise setup of add_post_meta_box hook.
	 * @since 2.8.0  Added page_attributes_dropdown_pages_args filter.
	 * @since 2.6.0  Added fix_localized_admin_url setup.
	 * @since 2.0.0
	 *
	 * @uses Registry::get() to retrieve enabled post types.
	 */
	public static function register_hooks() {
		// Don't do anything if not in the backend
		if ( ! is_backend() ) {
			return;
		}

		// Get supported post types
		$post_types = Registry::get( 'post_types' );

		// Redirect fixing
		self::add_hook( 'plugins_loaded', 'fix_localized_admin_url', 10, 0 );

		// Setup stuff
		self::add_hook( 'plugins_loaded', 'load_textdomain', 10, 0 );
		self::add_hook( 'plugins_loaded', 'setup_documentation', 10, 0 );
		self::add_hook( 'plugins_loaded', 'parse_default_rules', 10, 0 );

		// Plugin information
		self::add_hook( 'in_plugin_update_message-' . plugin_basename( NL_PLUGIN_FILE ), 'update_notice', 10, 1 );

		// Script/Style Enqueues
		self::add_hook( 'admin_enqueue_scripts', 'enqueue_assets', 10, 0 );

		// Theme Location Rewrites
		self::add_hook( 'init', 'register_localized_nav_menus', 999, 0 );
		self::add_hook( 'widgets_init', 'register_localized_sidebars', 999, 0 );
		self::add_hook( 'pre_set_theme_mod_nav_menu_locations', 'handle_unlocalized_nav_menus', 10, 1 );
		self::add_hook( 'pre_update_option_sidebars_widgets', 'handle_unlocalized_sidebars', 10, 1 );
		self::add_hook( 'sidebars_widgets', 'hide_unlocalized_sidebars', 10, 1 );

		// Posts Screen Interface
		self::add_hook( 'query_vars', 'add_language_var' );
		self::add_hook( 'display_post_states', 'flag_translated_pages', 10, 2 );
		self::add_hook( 'restrict_manage_posts', 'add_language_filter', 10, 0 );
		self::add_hook( 'page_attributes_dropdown_pages_args', 'maybe_set_queried_language', 10, 2 );
		foreach ( $post_types as $post_type ) {
			self::add_hook( "manage_{$post_type}_posts_columns", 'add_language_column', 15, 1 );
			self::add_hook( "manage_{$post_type}_posts_custom_column", 'do_language_column', 10, 2 );
		}

		// Quick/Bulk Edit Interfaces
		self::add_hook( 'quick_edit_custom_box', 'quick_edit_post_translation', 20, 2 );
		self::add_hook( 'bulk_edit_custom_box', 'bulk_edit_post_language', 20, 2 );

		// Post Editor Interfaces
		self::add_hook( 'add_meta_boxes', 'add_post_meta_box', 15, 1 );

		// Admin Notices
		self::add_hook( 'edit_form_top', 'synced_posts_notice', 10, 1 );
		self::add_hook( 'bulk_post_updated_messages', 'bulk_updated_sisters_messages', 20, 2 );

		// Saving Post Data
		self::add_hook( 'save_post', 'save_post_language', 10, 1 );
		self::add_hook( 'save_post', 'save_post_translations', 10, 1 );
		self::add_hook( 'edit_post', 'bulk_save_post_language', 10, 1 );

		// Menu Editor Meta Box
		self::add_hook( 'admin_head', 'add_nav_menu_meta_box', 10, 0 );

		// JavaScript Variables
		self::add_hook( 'admin_footer', 'print_javascript_vars', 10, 0 );

		// Translation creation
		self::add_hook( 'admin_post_nl_new_translation', 'new_translation', 10, 0 );
	}

	// =========================
	// ! Utilities
	// =========================

	/**
	 * Get the number of posts in a specific language.
	 *
	 * Filter by post type and status.
	 *
	 * @since 2.6.0 Fixed handling of post types and statuses for post counts.
	 * @since 2.0.0
	 *
	 * @global \wpdb $wpdb The database abstraction class instance.
	 *
	 * @param mixed        $language_id   The id of the language to get the count for.
	 * @param string|array $post_types    The post type(s) to filter by.
	 * @param string|array $post_statuses The post status(es) to filter by.
	 *
	 * @return int The number of posts found.
	 */
	public static function language_posts_count( $language_id, $post_types = null, $post_statuses = null ) {
		global $wpdb;

		$query = "
		SELECT COUNT(p.ID)
		FROM $wpdb->posts AS p
			LEFT JOIN $wpdb->nl_translations AS t
				ON p.ID = t.object_id AND t.object_type = 'post'
		";

		// Add language filter appropriately
		if ( $language_id ) {
			$query .= $wpdb->prepare( "WHERE t.language_id = %d", $language_id );
		} else {
			$query .= "WHERE t.language_id IS NULL";
		}

		// Add post_type filter if applicable
		if ( ! is_null( $post_types ) ) {
			if ( $post_types === 'any' ) {
				$post_types = get_post_types( array( 'exclude_from_search' => false ), 'names' );
			}

			$post_types = array_map( 'sanitize_key', (array) $post_types );
			$post_types = implode( "','", $post_types );

			$query .= " AND p.post_type IN ('$post_types')";
		}

		// Add post_status filter if applicable
		if ( ! is_null( $post_statuses ) ) {
			if ( $post_statuses === 'any' ) {
				$post_statuses = get_post_stati( array( 'exclude_from_search' => false ), 'names' );
			}

			$post_statuses = array_map( 'sanitize_key', (array) $post_statuses );
			$post_statuses = implode( "','", $post_statuses );

			$query .= " AND p.post_status IN ('$post_statuses')";
		}

		// Run the query and return the results
		$count = $wpdb->get_var( $query );

		return intval( $count );
	}

	// =========================
	// ! Redirect Fixing
	// =========================

	/**
	 * Fix localized wp-admin URLs and redirect to the unlocalized version.
	 *
	 * This prevents login issues when accessing /en/wp-admin or similar.
	 *
	 * @since 2.9.2 Fix wp_redirect() call, specify redirected-by.
	 * @since 2.6.0
	 */
	public static function fix_localized_admin_url() {
		if ( ( $the_url = Rewriter::process_url() ) && isset( $the_url->meta['language'] ) ) {
			if ( $the_url->meta['source'] !== 'query' ) {
				if ( wp_redirect( $the_url->build(), 302, 'nLingual' ) ) {
					exit;
				}
			}
		}
	}

	// =========================
	// ! Setup Stuff
	// =========================

	/**
	 * Load the text domain.
	 *
	 * @since 2.7.0 Fix $plugin_rel_path argument.
	 * @since 2.0.0
	 */
	public static function load_textdomain() {
		// Load the textdomain
		load_plugin_textdomain( 'nlingual', false, basename( NL_PLUGIN_DIR ) . '/languages' );
	}

	/**
	 * Setup documentation for relevant screens.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::get() to retrieve a the post types list.
	 * @uses Documenter::register_help_tab() to register help tabs for each post type.
	 */
	public static function setup_documentation() {
		// Setup translation help tab for applicable post types
		$post_types = Registry::get( 'post_types' );
		foreach ( $post_types as $post_type ) {
			Documenter::register_help_tab( $post_type, 'post-translation' );
			Documenter::register_help_tab( "edit-$post_type", 'posts-translation' );
		}
	}

	/**
	 * Prepare the sync/clone rules and default values.
	 *
	 * Defaults sync rules to "none" and clone rules to "all" for each post type.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::get() to retrieve a the sync/clone rules.
	 */
	public static function parse_default_rules() {
		// Get the sync and clone rules, making sure post_type array is present for both
		$sync_rules = Registry::get_sync_rules();
		if ( ! isset( $sync_rules['post_type'] ) || ! is_array( $sync_rules['post_type'] ) ) {
			$sync_rules['post_type'] = array();
		}
		$clone_rules = Registry::get_clone_rules();
		if ( ! isset( $clone_rules['post_type'] ) || ! is_array( $clone_rules['post_type'] ) ) {
			$clone_rules['post_type'] = array();
		}

		// Loop through post types, set default rules if not already present
		foreach ( Registry::get( 'post_types' ) as $post_type ) {
			if ( ! isset( $sync_rules['post_type'][ $post_type ] ) ) {
				$sync_rules['post_type'][ $post_type ] = array(
					'post_fields' => array(),
					'post_terms'  => array(),
					'post_meta'   => '',
				);
			}
			if ( ! isset( $clone_rules['post_type'][ $post_type ] ) ) {
				$clone_rules['post_type'][ $post_type ] = array(
					'post_fields' => true,
					'post_terms'  => true,
					'post_meta'   => true,
				);
			}
		}

		// Update the Registry and save
		Registry::set( 'sync_rules', $sync_rules );
		Registry::set( 'clone_rules', $clone_rules );
		Registry::save( 'options' );
	}

	// =========================
	// ! Plugin Information
	// =========================

	/**
	 * In case of update, check for notice about the update.
	 *
	 * @since 2.8.1 Patched notice printing to account for preceding/proceeding markup.
	 * @since 2.0.0
	 *
	 * @param array $plugin The information about the plugin and the update.
	 */
	public static function update_notice( $plugin ) {
		// Get the version number that the update is for
		$version = $plugin['new_version'];

		// Check if there's a notice about the update
		$transient = "nlingual-update-notice-{$version}";
		$notice = get_transient( $transient );
		if ( $notice === false ) {
			// Hasn't been saved, fetch it from the SVN repo
			$notice = @file_get_contents( "https://plugins.svn.wordpress.org/nlingual/assets/notice-{$version}.txt" ) ?: '';

			// Save the notice
			set_transient( $transient, $notice, YEAR_IN_SECONDS );
		}

		// Print out the notice if there is one
		if ( $notice ) {
			// Since the notice is normally contained within a single div/p combo,
			// we need to close it before printing the update notice
			?>
			</p></div>
			<div class="notice inline notice-warning notice-alt">
				<?php echo apply_filters( 'the_content', $notice ); ?>
			</div>
			<div><p>
			<?php
			// Now that we've re-opened it, there will be
			// an empty div/p combo after our notice
		}
	}

	// =========================
	// ! Menu/Sidebar Localization
	// =========================

	/**
	 * Shared logic for nav menu and sidebar localizing.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::is_location_supported() to check for support.
	 * @uses Registry::languages() to loop through all registered languages.
	 *
	 * @param string $type   The type of location being localized (singular).
	 * @param string $global The global variable name to be edited.
	 */
	private static function register_localized_locations( $type, $global ) {
		$list = $GLOBALS[ $global ];

		// Cache the old version of the menus for reference
		wp_cache_set( $global, $list, 'nlingual:vars' );

		// Abort if not at all supported or none exist
		if ( ! Registry::is_location_supported( $type ) || empty( $list ) ) {
			return;
		}

		// Build a new nav menu list; with copies of each menu for each language
		$localized_locations = array();
		foreach ( $list as $id => $data ) {
			foreach ( Registry::languages() as $language ) {
				// Check if this location specifically supports localizing,
				// make localized copies
				if ( Registry::is_location_supported( $type, $id ) ) {
					$new_id = "{$id}__language_{$language->id}";
					$name_postfix = ' (' . $language->system_name . ')';
					if ( is_array( $data ) ) {
						$new_name = $data['name'] . $name_postfix;
						$localized_locations[ $new_id ] = array_merge( $data, array(
							'id' => $new_id,
							'name' => $new_name,
						) );
					} else {
						$new_name = $data . $name_postfix;
						$localized_locations[ $new_id ] = $new_name;
					}
				}
				// Otherwise, preserve it
				else {
					$localized_locations[ $id ] = $data;
				}
			}
		}

		// Replace the registered nav menu array with the new one
		$GLOBALS[ $global ] = $localized_locations;
	}

	/**
	 * Replaces the registered nav menus with versions for each active language.
	 *
	 * @since 2.0.0
	 *
	 * @see Backend::register_localized_locations()
	 *
	 * @global array $_wp_registered_nav_menus The registered nav menus list.
	 */
	public static function register_localized_nav_menus() {
		self::register_localized_locations( 'nav_menu', '_wp_registered_nav_menus' );
	}

	/**
	 * Replaces the registered sidebars with versions for each active language.
	 *
	 * @since 2.0.0
	 *
	 * @see Backend::register_localized_locations()
	 *
	 * @global array $wp_registered_sidebars The registered sidebars list.
	 */
	public static function register_localized_sidebars() {
		self::register_localized_locations( 'sidebar', 'wp_registered_sidebars' );
	}

	/**
	 * Shared logic for handling unlocalized location fallbacks.
	 *
	 * As a fallback, make sure that the original location is
	 * set to that of the one for the default language. This way,
	 * if the location becomes unmarked for localization, the unlocalized
	 * nav items/widgets won't vanish.
	 *
	 * @since 2.8.10  Added $type argument, previously treated everything as a nav menu location.
	 * @since 2.8.9.2 Strip out localized version of no-longer-supported locations.
	 * @since 2.0.0
	 *
	 * @param array $locations The locations.
	 *
	 * @return array The filtered locations array.
	 */
	public static function handle_unlocalized_locations( $type, $locations ) {
		// Get the default language ID
		$default_language_id = Registry::default_language( 'id' );

		// Loop through the locations, check if it's a localized one
		$new_locations = array();
		foreach ( $locations as $location => $data ) {
			if ( preg_match( '/^(.+?)__language_(\d+)$/', $location, $matches ) ) {
				$unlocalized_location = $matches[1];
				$language_id = absint( $matches[2] );

				// If this location is no longer supported, drop it
				if ( ! Registry::is_location_supported( $type, $unlocalized_location ) ) {
					continue;
				}

				// If it's for the default language, update the original
				if ( $language_id == $default_language_id ) {
					$new_locations[ $unlocalized_location ] = $data;
				}
			}

			$new_locations[ $location ] = $data;
		}

		return $new_locations;
	}

	/**
	 * Handle fallbacks for unlocalized nav menus.
	 *
	 * @since 2.8.10
	 *
	 * @see Backend::handle_unlocalized_locations()
	 */
	public static function handle_unlocalized_nav_menus( $locations ) {
		return self::handle_unlocalized_locations( 'nav_menu', $locations );
	}

	/**
	 * Handle fallbacks for unlocalized sidebars.
	 *
	 * @since 2.8.10
	 *
	 * @see Backend::handle_unlocalized_locations()
	 */
	public static function handle_unlocalized_sidebars( $locations ) {
		return self::handle_unlocalized_locations( 'sidebar', $locations );
	}

	/**
	 * Clean up localized and unlocalized sidebars as needed.
	 *
	 * This will prevent the originals for localized sidebars or the versions
	 * of no-longer-localized sidebars from showing up as inactive sidebars.
	 *
	 * @since 2.8.10 Renamed from locations to sidebars for clarity.
	 * @since 2.0.0
	 *
	 * @param array $sidebars_widgets The sidebars and their widgets.
	 *
	 * @return array The filtered sidebars.
	 */
	public static function hide_unlocalized_sidebars( $sidebars_widgets ) {
		// Get the default language ID
		$language_id = Registry::default_language( 'id' );

		$new_sidebars = array();
		foreach ( $sidebars_widgets as $sidebar => $widgets ) {
			if ( Registry::is_location_supported( 'sidebar', $sidebar ) ) {
				// Sidebar is localizable, skip the original
				continue;
			} elseif ( preg_match( '/^(.+?)__language_\d+/', $sidebar, $matches )
			&& ! Registry::is_location_supported( 'sidebar', $matches[1] ) ) {
				// Original sidebar no longer localizable, skip localized versions
				continue;
			}

			$new_sidebars[ $sidebar ] = $widgets;
		}

		return $new_sidebars;
	}

	// =========================
	// ! Posts Screen Interfaces
	// =========================

	/**
	 * Register the language query var.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::get() to get the query var option.
	 *
	 * @param array $vars The whitelist of query vars.
	 *
	 * @return array The updated whitelist.
	 */
	public static function add_language_var( array $vars ) {
		if ( $query_var = Registry::get( 'query_var' ) ) {
			$vars[] = $query_var;
		}
		return $vars;
	}

	/**
	 * Filter the post states list, flagging translated versions where necessary.
	 *
	 * @since 2.6.0 Add check to make sure post's type is supported.
	 * @since 2.1.0 Bypass language_is_required option; make sure post has a language.
	 * @since 2.0.0
	 *
	 * @param array    $post_states The list of post states for the post.
	 * @param \WP_Post $post        The post in question.
	 *
	 * @return array The filtered post states list.
	 */
	public static function flag_translated_pages( array $post_states, \WP_Post $post ) {
		// Abort if not for a supported post type
		if ( ! Registry::is_post_type_supported( $post->post_type ) ) {
			return $post_states;
		}

		// Check if it's a page in another language, with a translation
		$language = Translator::get_post_language( $post->ID, 'true_value' );
		if ( $post->post_type == 'page' && $language && ! Registry::is_language_default( $language )
		&& $translation = Translator::get_post_translation( $post->ID, Registry::default_language() ) ) {
			// Check if it's a translation of the home page
			if ( $translation == get_option( 'page_on_front' ) ) {
				/* translators: %s = The name of the language */
				$post_states['page_on_front'] = _fx( '%s Front Page', 'front page translation', 'nlingual', $language->system_name );
			}
			// or the posts page
			elseif ( $translation == get_option( 'page_for_posts' ) ) {
				/* translators: %s = The name of the language */
				$post_states['page_for_posts'] = _fx( '%s Posts Page', 'front page translation', 'nlingual', $language->system_name );
			}
		}

		return $post_states;
	}

	/**
	 * Add <select> for filtering posts by language.
	 *
	 * @since 2.10.0 Escaping touch-ups.
	 * @since 2.6.0 Default post type/status to any.
	 * @since 2.4.0 Show all languages as filtering options.
	 * @since 2.2.0 Fixed handling of string vs array for $current.
	 * @since 2.0.0
	 *
	 * @uses Registry::is_post_type_supported() to check for support.
	 * @uses Registry::get() to retrieve the query var.
	 * @uses Registry::languages() to loop through all registered languages.
	 * @uses Backend::language_posts_count() to get the post count for each language.
	 */
	public static function add_language_filter() {
		global $typenow, $wp_query;

		// Abort if current post type isn't supported
		if ( ! Registry::is_post_type_supported( $typenow ) ) {
			return;
		}

		// Get the post type and post status for the query
		$post_type = $wp_query->get( 'post_type' ) ?: 'any';
		$post_status = $wp_query->get( 'post_status' ) ?: 'any';

		// Get the query var and it's current value
		$query_var = Registry::get( 'query_var' );
		$current = $wp_query->get( $query_var );

		// If current is an array, use the first one
		if ( is_array( $current ) ) {
			$current = reset( $current );
		}
		?>
		<select name="<?php echo esc_attr( $query_var ); ?>" class="postform">
			<option value="-1"><?php esc_html_e( 'All Languages', 'nlingual' ); ?></option>
			<?php
			$count = Backend::language_posts_count( 0, $post_type, $post_status );
			printf(
				'<option value="%s" %s>%s (%s)</option>',
				0,
				selected( $current, '0', false ),
				esc_html__( 'No Language', 'nlingual' ),
				$count
			);
			foreach ( Registry::languages() as $language ) {
				$selected = $current == $language->slug;
				$count = Backend::language_posts_count( $language->id, $post_type, $post_status );
				printf(
					'<option value="%s" %s>%s (%d)</option>',
					esc_attr( $language->slug ),
					selected( $selected, true, false ),
					esc_html( $language->system_name ),
					$count
				);
			}
			?>
		</select>
		<?php
	}

	/**
	 * Filter the query args, adding language if applicable.
	 *
	 * @since 2.8.0
	 *
	 * @param array    $args The WP_Query arguments to filter.
	 * @param \WP_Post $post The post for context.
	 *
	 * @return array The filtered query args.
	 */
	public static function maybe_set_queried_language( $args, $post ) {
		if ( ! Registry::get( 'show_all_languages' ) && Registry::is_post_type_supported( $post->post_type ) ) {
			$query_var = Registry::get( 'query_var' );
			if ( $language = Translator::get_post_language( $post ) ) {
				$args[ $query_var ] = array( $language->id, '0' );
			}
		}

		return $args;
	}

	/**
	 * Add the language/translations column to the post edit screen.
	 *
	 * @since 2.0.0
	 *
	 * @param array $columns The list of columns.
	 *
	 * @return array The modified list of columns.
	 */
	public static function add_language_column( $columns ) {
		$columns['nlingual'] = __( 'Language', 'nlingual' );
		return $columns;
	}

	/**
	 * Print the content of the language/translations column.
	 *
	 * @since 2.10.0 Fix "None" string localization, add missing text domain to other strings.
	 * @since 2.9.2 Restructure to list each language rather than each translation,
	 *              with a create link for missing translations.
	 * @since 2.1.0 Added bypass of language_is_required.
	 * @since 2.0.0
	 *
	 * @uses Registry::is_post_type_supported() to check for support.
	 * @uses Translator::get_object_language() to get the post's language.
	 * @uses Translator::get_object_translations() to get the post's translations.
	 * @uses Registry::languages() to retrieve/validate each translation's language.
	 *
	 * @param string $column  The ID of the current column.
	 * @param int    $post_id The current post.
	 */
	public static function do_language_column( $column, $post_id ) {
		// Abort if not the right column
		if ( $column != 'nlingual' ) {
			return;
		}

		// Abort if post's type not supported
		if ( ! Registry::is_post_type_supported( get_post_type( $post_id ) ) ) {
			return;
		}

		// Add a dead nonce field for use by the inlineEdit hook
		printf( '<input type="hidden" class="nl-nonce" value="%s" />', esc_attr( wp_create_nonce( 'update-post_' . $post_id ) ) );

		// Start by printing out the language
		$language = Translator::get_post_language( $post_id, 'true_value' );
		if ( ! $language ) {
			echo '<input type="hidden" class="nl-language" value="0" />';
			echo esc_html_x( 'None', 'no language', 'nlingual' );
			return;
		}

		printf( '<input type="hidden" class="nl-language" value="%d" />', $language->id );
		printf( '<strong>%s</strong>', esc_html( $language->system_name ) );

		// Print links to either existing translations or Translate This actions.
		$links = array();
		foreach ( Registry::languages() as $other_language ) {
			if ( $language->id === $other_language->id ) {
				continue;
			}

			$translation = Translator::get_post_translation( $post_id, $other_language );

			$link = sprintf( '<input type="hidden" class="nl-translation-%d" value="%d" />', $other_language->id, $translation );
			if ( $translation ) {
				$title = get_the_title( $translation );
				// Edit or view link depending on permissions
				if ( $edit_link = get_edit_post_link( $translation ) ) {
					$link .= sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $edit_link ), esc_html( $title ?: __( '(no title)', 'nlingual' ) ) );
				} else {
					$link .= sprintf( '<a href="%s" target="_blank">%s</a>', get_permalink( $translation ), esc_html( $title ?: __( '(no title)', 'nlingual' ) ) );
				}
			} elseif ( $translation_link = get_translate_post_link( $post_id, $other_language->id ) ) {
				$link .= sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $translation_link ), esc_html__( '[Create translation]', 'nlingual' ) );
			} else {
				$link .= esc_html__( '[No translation]', 'nlingual' );
			}

			/* translators: %1$s = The name of the language, %2$s = The title of the post, wrapped in a link */
			$links[] = esc_html( _fx( '%1$s: %2$s', 'language: title', 'nlingual', $other_language->system_name, $link ) );
		}
		if ( $links ) {
			echo '<ul><li>' . implode( '</li><li>', $links ) . '</li></ul>';
		}
	}

	// =========================
	// ! Quick/Bulk Edit Interfaces
	// =========================

	/**
	 * Print out the quick-edit box for post language/translations.
	 *
	 * @since 2.0.0
	 *
	 * @global \wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Registry::is_post_type_supported() to check for support.
	 * @uses Registry::languages() to loop through the active languages.
	 * @uses Registry::get() to get the language_is_required; need for "None" option.
	 *
	 * @param string $column    The column this box corresponds to.
	 * @param string $post_type The post type this is for.
	 */
	public static function quick_edit_post_translation( $column, $post_type ) {
		global $wpdb;

		// Abort if not the correct column
		if ( $column != 'nlingual' ) {
			return;
		}

		// Or if the post type isn't supported
		if ( ! Registry::is_post_type_supported( $post_type ) ) {
			return;
		}

		// Get the languages list
		$languages = Registry::languages();
		?>
		<div class="nl-translations-manager clear">
			<hr />
			<fieldset class="nl-fieldset">
				<input type="hidden" name="_nl_nonce" class="nl-nonce" />
				<?php if ( ! Registry::get( 'lock_post_language' ) ) : ?>
					<div class="inline-edit-col nl-manage-language">
						<label>
							<span class="title"><?php esc_html_e( 'Language', 'nlingual' ); ?></span>
							<select name="nlingual_language" class="nl-input nl-language-input">
								<?php if ( ! Registry::get( 'language_is_required' ) ) : ?>
									<option value="0">&mdash; <?php _ex( 'None', 'no language', 'nlingual' ); ?> &mdash;</option>
								<?php endif; ?>
								<?php
								// Print the options
								foreach ( $languages as $language ) {
									printf( '<option value="%s">%s</option>', $language->id, $language->system_name );
								}
								?>
							</select>
						</label>
					</div>
				<?php endif; ?>
				<div class="inline-edit-col nl-manage-translations">
					<?php foreach ( $languages as $language ) : ?>
						<label class="nl-translation-field nl-translation-<?php echo $language->id; ?>" title="<?php
							/* translators: %s = The name of the language */
							_ef( 'Assign %s Translation', 'nlingual', $language->system_name ); ?>" data-nl_language="<?php echo $language->id; ?>">
							<span class="title"><?php echo $language->system_name; ?></span>
							<select name="nlingual_translation[<?php echo $language->id; ?>]" class="nl-input nl-translation-input">
								<option value="0">&mdash; <?php _ex( 'None', 'no translation', 'nlingual' ); ?> &mdash;</option>
								<?php
								// Get all posts in this language
								$posts = $wpdb->get_results( $wpdb->prepare( "
									SELECT p.ID, p.post_title
									FROM $wpdb->nl_translations AS t
									LEFT JOIN $wpdb->posts AS p ON (t.object_id = p.ID)
									WHERE t.object_type = 'post'
									AND t.language_id = %d
									AND p.post_type = %s
									ORDER BY p.post_date DESC
								", $language->id, $post_type ) );

								// Print the options
								foreach ( $posts as $option ) {
									printf( '<option value="%s">%s</option>', $option->ID, $option->post_title );
								}
								?>
							</select>
						</label>
					<?php endforeach; ?>
				</div>
			</fieldset>
		</div>
		<?php
	}

	/**
	 * Print out the bulk-edit box for post language.
	 *
	 * @since 2.6.0 Modified "No Change" value, added "None" when language isn't required.
	 * @since 2.0.0
	 *
	 * @uses Registry::is_post_type_supported() to check for support.
	 * @uses Registry::languages() to loop through the active languages.
	 *
	 * @param string $column    The column this box corresponds to.
	 * @param string $post_type The post type this is for.
	 */
	public static function bulk_edit_post_language( $column, $post_type ) {
		// Abort if not the correct column
		if ( $column != 'nlingual' ) {
			return;
		}

		// Or if the post type isn't supported
		if ( ! Registry::is_post_type_supported( $post_type ) ) {
			return;
		}

		// Or if posts are locked to their assigned language
		if ( Registry::get( 'lock_post_language' ) ) {
			return;
		}

		// Get the languages list
		$languages = Registry::languages();
		?>
		<div class="nl-bulk-language-manager clear">
			<hr />
			<fieldset class="nl-fieldset">
				<div class="inline-edit-col">
					<label>
						<span class="title"><?php esc_html_e( 'Language', 'nlingual' ); ?></span>
						<select name="nlingual_bulk_language" id="nl_language">
							<option value="">&mdash; <?php _e( 'No Change', 'nlingual' ); ?> &mdash;</option>
							<?php if ( ! Registry::get( 'language_is_required' ) ) : ?>
								<option value="0">&mdash; <?php _ex( 'None', 'no language', 'nlingual' ); ?> &mdash;</option>
							<?php endif; ?>
							<?php
							// Print the options
							foreach ( $languages as $language ) {
								printf( '<option value="%s">%s</option>', $language->id, $language->system_name );
							}
							?>
						</select>
					</label>
				</div>
			</fieldset>
		</div>
		<?php

		// Nonce fields for save validation
		wp_nonce_field( 'bulk-posts', '_nl_nonce', false );
	}

	// =========================
	// ! Post Editor Interfaces
	// =========================

	/**
	 * Add a meta box to the post edit screen.
	 *
	 * For setting language and associated translations
	 * for the enabled post types.
	 *
	 * @since 2.8.1 Revised to run on add_meta_boxes_$post_type, accept $post argument.
	 * @since 2.0.0
	 *
	 * @uses Registry:get() to retrieve the supported post types.
	 * @uses Backend::post_meta_box() as the callback to build the metabox.
	 *
	 * @param string $post_type The post type being added for.
	 */
	public static function add_post_meta_box( $post_type ) {
		// Abort if post type is not supported
		if ( ! Registry::is_post_type_supported( $post_type ) ) {
			return;
		}

		add_meta_box(
			'nlingual_translations', // id
			__( 'Language & Translations', 'nlingual' ), // title
			array( __CLASS__, 'post_meta_box' ), // callback
			$post_type, // screen
			'side', // context
			'default' // priority
		);
	}

	/**
	 * Output the content of the translations meta box.
	 *
	 * @since 2.8.0 Add lock_post_language option usage.
	 * @since 2.6.0 Dropped post selection for translation fields,
	 *              now uses simpler Create button that opens in new window.
	 * @since 2.1.0 Added bypass of language_is_required.
	 * @since 2.0.0
	 *
	 * @global \wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Registry::languages() to get the languages to loop through.
	 * @uses Translator::get_object_language() to get the post's language.
	 * @uses Translator::get_post_translations() to get the post's translations.
	 * @uses Registry::get() to get the language_is_required; need for "None" option.
	 *
	 * @param \WP_Post $post The post being edited.
	 */
	public static function post_meta_box( $post ) {
		global $wpdb;

		// Get the required language option
		$language_is_required = Registry::get( 'language_is_required' );

		// Get the force default language option
		$lock_post_language = Registry::get( 'lock_post_language' );

		// Get the language list
		$languages = Registry::languages();

		// Get the post's language
		$post_language = Translator::get_post_language( $post->ID, 'true value' );

		// Get the post's translations
		$translations = Translator::get_post_translations( $post->ID );

		// Get the post's post type's object
		$post_type = get_post_type_object( $post->post_type );

		// Build the language and translation option lists
		$language_options = array();
		$post_options = array();
		foreach ( $languages as $language ) {
			$language_options[ $language->id ] = $language->system_name;

			// Get all posts of this type for this language (excluding the current one)
			$post_options[ $language->id ] = $wpdb->get_results( $wpdb->prepare( "
				SELECT p.ID, p.post_title
				FROM $wpdb->nl_translations AS t
				LEFT JOIN $wpdb->posts AS p ON (t.object_id = p.ID)
				WHERE t.object_type = 'post'
				AND t.language_id = %d
				AND t.object_id != %d
				AND p.post_type = %s
				ORDER BY p.post_date DESC
			", $language->id, $post->ID, $post->post_type ) );

			// Set translation to for this language to 0 if not present
			if ( ! isset( $translations[ $language->id ] ) ) {
				$translations[ $language->id ] = 0;
			}
		}
		?>
		<div class="nl-translation-manager">
			<?php if ( $lock_post_language ) : $post_language = $post_language ?: Registry::default_language(); ?>
				<input type="hidden" name="nlingual_language" id="nl_language" class="nl-input nl-language-input" value="<?php echo $post_language->id; ?>">
				<strong><?php esc_html_e( 'Language:', 'nlingual' ); ?></strong>
				<em><?php echo $post_language->system_name; ?></em>
			<?php else: ?>
				<div class="nl-field nl-manage-language">
					<label for="nl_language" class="screen-reader-text"><?php esc_html_e( 'Language', 'nlingual' ); ?></label>
					<select name="nlingual_language" id="nl_language" class="nl-input nl-language-input">
						<?php if ( ! $language_is_required ) : ?>
							<option value="0">&mdash; <?php _ex( 'Select Language', 'no language', 'nlingual' ); ?> &mdash;</option>
						<?php endif; ?>
						<?php
						// Print the options
						foreach ( $language_options as $value => $label ) {
							$selected = ( $post_language && $post_language->id == $value ) ? 'selected' : '';
							if ( $language_is_required && ! $post_language->id && Registry::is_language_default( $value ) ) {
								$selected = 'selected';
							}

							printf( '<option value="%s" %s>%s</option>', $value, $selected, $label );
						}
						?>
					</select>
				</div>
			<?php endif; ?>

			<div class="nl-manage-translations">
				<?php if ( $languages->count() > 1 ) : ?>
					<h4 class="screen-reader-text"><?php esc_html_e( 'Translations', 'nlingual' ); ?></h4>
					<?php foreach ( $languages as $language ) : ?>
						<div class="nl-field nl-translation-field nl-translation-<?php echo $language->id; ?>" data-nl_language="<?php echo $language->id; ?>">
							<input type="hidden" name="nlingual_translation[<?php echo $language->id; ?>]" class="nl-input nl-translation-input" value="<?php echo $translations[ $language->id ]; ?>" />
							<label for="nl_translation_<?php echo $language->id; ?>_input">
								<?php
								/* translators: %s = language name */
								_ef( '%s Translation:', 'nlingual', $language->system_name );
								 ?>
								<button type="button" class="button button-small button-primary nl-add-translation"><?php esc_html_e( 'Create', 'nlingual' ); ?></button>
								<button type="button" class="button button-small nl-edit-translation" data-url="<?php echo esc_url( admin_url( $post_type->_edit_link . '&action=edit' ) ); ?>"><?php esc_html_e( 'Edit', 'nlingual' ); ?></button>
							</label>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php

		// Nonce fields for save validation
		wp_nonce_field( 'update-post_' . $post->ID, '_nl_nonce', false );
	}

	// =========================
	// ! Saving Post Data
	// =========================

	/**
	 * Save language settings from the translations meta box.
	 *
	 * Also works for the quick-edit interface if _nl_nonce was included.
	 *
	 * @since 2.0.0
	 *
	 * @uses Translator::set_object_language() to assign/update the post's language.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public static function save_post_language( $post_id ) {
		// Abort if doing auto save or it's a revision
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Abort if the nonce doesn't exist/check out, or if the language isn't provided
		if ( ! isset( $_POST['_nl_nonce'] )
		|| ! wp_verify_nonce( $_POST['_nl_nonce'], 'update-post_' . $post_id )
		|| ! isset( $_POST['nlingual_language'] ) ) {
			return;
		}

		// Assign the post to the language, fail if there's an error
		try {
			Translator::set_post_language( $post_id, $_POST['nlingual_language'] );
		} catch ( Exception $e ) {
			wp_die( __( 'Error assigning language: the selected language does not exist.', 'nlingual' ) );
		}
	}

	/**
	 * Save translation assignments from the translations meta box.
	 *
	 * Also works for the quick-edit interface if _nl_nonce was included.
	 *
	 * @since 2.0.0
	 *
	 * @uses Translator::set_object_translations() to assign the translations to the post.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public static function save_post_translations( $post_id ) {
		// Abort if doing auto save or it's a revision
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Abort if the nonce fails, or if the translations aren't provided
		if ( ! isset( $_POST['_nl_nonce'] )
		|| ! wp_verify_nonce( $_POST['_nl_nonce'], 'update-post_' . $post_id )
		|| ! isset( $_POST['nlingual_translation'] )
		|| ! is_array( $_POST['nlingual_translation'] )
		|| empty( $_POST['nlingual_translation'] ) ) {
			return;
		}

		// Assign the translations, fail if there's an error
		try {
			Translator::set_post_translations( $post_id, $_POST['nlingual_translation'] );
		} catch ( Exception $e ) {
			wp_die( __( 'Error assigning translations: one or more languages do not exist.', 'nlingual' ) );
		}
	}

	/**
	 * Save the language sent via the bulk-edit interface.
	 *
	 * @since 2.6.0 Now accepts 0 for language option.
	 * @since 2.0.0
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public static function bulk_save_post_language( $post_id ) {
		// Abort if not a bulk edit (nonce fails), not one of
		// the intended posts, or no language was provided
		if ( ! isset( $_REQUEST['bulk_edit'] )
		|| ! isset( $_REQUEST['_nl_nonce'] )
		|| ! wp_verify_nonce( $_REQUEST['_nl_nonce'], 'bulk-posts' )
		|| ! isset( $_REQUEST['post'] )
		|| ! in_array( $post_id, (array) $_REQUEST['post'] )
		|| ! isset( $_REQUEST['nlingual_bulk_language'] )
		|| $_REQUEST['nlingual_bulk_language'] === '' ) {
			return;
		}

		// Assign the post to the language, fail if there's an error
		try {
			Translator::set_post_language( $post_id, $_REQUEST['nlingual_bulk_language'] );
		} catch ( Exception $e ) {
			wp_die( __( 'Error assigning language: the selected language does not exist.', 'nlingual' ) );
		}
	}

	// =========================
	// ! Admin Notices
	// =========================

	/**
	 * Print notice about sister posts being synchronized during update.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Post $post The current post being edited.
	 */
	public static function synced_posts_notice( \WP_Post $post ) {
		// page or "post"?
		if ( $post->post_type == 'page' ) {
			$message = __( 'The translations of this page have been updated accordingly.', 'nlingual' );
		} else {
			$message = __( 'The translations of this post have been updated accordingly.', 'nlingual' );
		}

		// Check that...
		if (
			// The post was updated in some manner
			isset( $_GET['message'] ) && in_array( $_GET['message'], array( 1, 4, 6, 8, 9, 10 ) )
			// And the post type supports translation
			&& Registry::is_post_type_supported( $post->post_type )
			// And the post type has syncronization rules specified
			&& Registry::get_post_sync_rules( $post->post_type )
			// And the post has sister translations
			&& Translator::get_post_translations( $post->ID )
		) : ?>
		<div class="notice notice-info is-dimissable">
			<p><?php echo $message; ?></p>
		</div>
		<?php endif;
	}

	/**
	 * Print notice about any sister posts being updated/trashed/deleted.
	 *
	 * @since 2.6.0 Remove post_status check for trash/untrash messages.
	 * @since 2.3.1 Added check for NULL screen.
	 * @since 2.0.0
	 *
	 * @param array $bulk_messages Arrays of messages per post type.
	 * @param array $bulk_counts   Array of item counts for each message.
	 *
	 * @return array The filtered message arrays.
	 */
	public static function bulk_updated_sisters_messages( $bulk_notices, $bulk_counts ) {
		// Get the current screen
		$screen = get_current_screen();

		// Abort if no screen or current post type is not supported
		if ( ! $screen || ! Registry::is_post_type_supported( $screen->post_type ) ) {
			return $bulk_notices;
		}

		// Create the addendums
		$updated_addendum   = __( 'Any associated translations have been synchronized accordingly.', 'nlingual' );
		$deleted_addendum   = __( 'Any associated translations have also been deleted.', 'nlingual' );
		$trashed_addendum   = __( 'Any associated translations have also been moved.', 'nlingual' );
		$untrashed_addendum = __( 'Any associated translations have also been restored.', 'nlingual' );

		// Add addendums to every set of messages
		foreach ( $bulk_notices as &$notices ) {
			// Get the rules for this post type
			$sync_rules = Registry::get_post_sync_rules( $screen->post_type );

			// If any rules exist, add updated addendum
			if ( array_filter( $sync_rules ) ) {
				$notices['updated'] .= ' ' . $updated_addendum;
			}

			// If trash_sister_posts is enabled, add trashed/untrashed addendum
			if ( Registry::get( 'trash_sister_posts' ) ) {
				$notices['trashed'] .= ' ' . $trashed_addendum;
				$notices['untrashed'] .= ' ' . $untrashed_addendum;
			}

			// If delete_sister_posts is enabled, add deleted addendum
			if ( Registry::get( 'delete_sister_posts' ) ) {
				$notices['deleted'] .= ' ' . $deleted_addendum;
			}
		}

		return $bulk_notices;
	}

	// =========================
	// ! Menu Editor Meta Box
	// =========================

	/**
	 * Adds a new metabox to the menu editor for adding language links.
	 *
	 * @since 2.0.0
	 */
	public static function add_nav_menu_meta_box() {
		add_meta_box(
			'add-nl_language_link', // metabox id
			__( 'Language Links', 'nlingual' ), // title
			array( __CLASS__, 'do_nav_menu_meta_box' ), // callback
			'nav-menus', // screen
			'side' // context
		);
	}

	/**
	 * The language links meta box.
	 *
	 * @since 2.10.0 Fix esc_attr_e() usage.
	 * @since 2.0.0
	 *
	 * @uses Registry::languages() to loop through all active registered languages.
	 */
	public static function do_nav_menu_meta_box() {
		global $nav_menu_selected_id;
		?>
		<div class="posttypediv" id="nl_language_link">
			<p><?php esc_html_e( 'These links will go to the respective language versions of the current URL.', 'nlingual' ); ?></p>
			<div id="tabs-panel-nl_language_link-all" class="tabs-panel tabs-panel-active">
				<ul id="pagechecklist-most-recent" class="categorychecklist form-no-clear">
				<?php $i = -1; foreach ( Registry::languages() as $language ) : ?>
					<li>
						<label class="menu-item-title">
							<input type="checkbox" class="menu-item-checkbox" name="menu-item[<?php echo $i; ?>][menu-item-object-id]" value="-1">
							<?php echo $language->system_name; ?>
							<?php if ( ! $language->active ) _ex( '[Inactive]', 'language inactive', 'nlingual' ); ?>
						</label>
						<input type="hidden" class="menu-item-type" name="menu-item[<?php echo $i; ?>][menu-item-type]" value="nl_language_link">
						<input type="hidden" class="menu-item-title" name="menu-item[<?php echo $i; ?>][menu-item-title]" value="<?php echo esc_attr( $language->native_name ); ?>">
						<input type="hidden" class="menu-item-url" name="menu-item[<?php echo $i; ?>][menu-item-object]" value="<?php echo esc_attr( $language->slug ); ?>">
					</li>
				<?php $i--; endforeach; ?>
				</ul>
			</div>

			<p class="button-controls">
				<span class="list-controls">
					<a href="/wp-admin/nav-menus.php?nl_language_link-tab=all&amp;selectall=1#nl_language_link" class="select-all">Select All</a>
				</span>

				<span class="add-to-menu">
					<input type="submit"<?php wp_nav_menu_disabled_check( $nav_menu_selected_id ); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu', 'nlingual' ); ?>" name="add-post-type-menu-item" id="submit-nl_language_link" />
					<span class="spinner"></span>
				</span>
			</p>
		</div>
		<?php
	}

	// =========================
	// ! Script/Style Enqueues
	// =========================

	/**
	 * Enqueue necessary styles and scripts.
	 *
	 * @since 2.10.0 Add translator notes. Explicitly enqueue in header.
	 * @sicne 2.8.8 Add admin_post, for nl_new_translation call.
	 * @since 2.6.0 Updated to use plugin version for CSS/JS files.
	 * @since 2.0.0
	 */
	public static function enqueue_assets() {
		// Admin styling
		wp_enqueue_style( 'nlingual-admin', plugins_url( 'css/admin.css', NL_PLUGIN_FILE ), array(), NL_PLUGIN_VERSION, 'screen' );

		// Admin javascript
		wp_enqueue_script( 'nlingual-admin-js', plugins_url( 'js/admin.min.js', NL_PLUGIN_FILE ), array( 'backbone', 'jquery-ui-sortable' ), NL_PLUGIN_VERSION, false );

		// Localize the javascript
		wp_localize_script( 'nlingual-admin-js', 'nlingualL10n', array(
			'admin_post'                  => admin_url( 'admin-post.php' ),
			'TranslationTitle'            => __( 'Enter the title for this translation.', 'nlingual' ),
			// translators: %1$s = language name, %2$s = post title
			'TranslationTitlePlaceholder' => __( '[Needs %1$s Translation]: %2$s', 'nlingual' ),
			'NewTranslationError'         => __( 'Error creating translation, please try again later or create one manually.', 'nlingual' ),
			'NoPostSelected'              => __( 'No post selected to edit.', 'nlingual' ),
			'NewTranslation'              => __( '[New]', 'nlingual' ),
			'LocalizeThis'                => __( 'Localize This', 'nlingual' ),
			// translators: %s = language name
			'LocalizeFor'                 => __( 'Localize for %s', 'nlingual' ),
		) );
	}

	// =========================
	// ! JavaScript Variables
	// =========================

	/**
	 * Print relevent variables for JavaScript.
	 *
	 * @since 2.10.0 Use wp_json_encode().
	 * @since 2.0.0
	 *
	 * @uses Registry::get() to retrieve the default language.
	 * @uses Registry::languages() to get and export the list of languages.
	 */
	public static function print_javascript_vars() {
		?>
		<script>
			if ( typeof admin_url === 'undefined' ) {
				var admin_url = '<?php echo admin_url(); ?>';
			}
			nLingual.default_language = <?php echo Registry::default_language( 'id' ); ?>;
			nLingual.Languages.add( <?php echo wp_json_encode( Registry::languages()->dump() ); ?> );
		</script>
		<?php
	}

	// =========================
	// ! Translation Creation
	// =========================

	/**
	 * Create a clone of the requested post in the requested lanuage.
	 *
	 * Will redirect to the edit screen for the new translation.
	 *
	 * @since 2.9.3 Fix permission check.
	 * @since 2.9.2 Add permissions check, redirected-by for wp_redirect().
	 * @since 2.6.0
	 *
	 * @uses Registry::languages() to validate the language requested.
	 * @uses Synchronizer::clone_post() to create the cloned post.
	 */
	public static function new_translation() {
		$data = $_REQUEST;

		// Fail if no post/language id is passed
		if ( ! isset( $data['post_id'] )
		|| ! isset( $data['translation_language_id'] ) ) {
			wp_die( __( 'Error creating translation: post and or language ID not specified.', 'nlingual' ) );
		}

		// Fail if post does not exist
		$post = get_post( $data['post_id'] );
		if ( ! $post ) {
			wp_die( __( 'Error creating translation: specified post not found.', 'nlingual' ) );
		}

		// Check permissions, must be able to create posts
		$post_type_obj = get_post_type_object( $post->post_type );
		if ( ! current_user_can( $post_type_obj->cap->create_posts ) ) {
			wp_die( __( 'You are now allowed to create a translation for this post.', 'nlingual' ) );
		}

		// If post_language is not passed, use what already exists, fail if not found
		if ( isset( $data['post_language_id'] ) ) {
			$post_language = Registry::get_language( $data['post_language_id'] );

			// Ensure the post is in the correct language
			Translator::set_post_language( $post, $post_language );
		} else {
			$post_language = Translator::get_post_language( $post->ID, 'true value' );
		}

		// Fail if post language does not exist
		if ( ! $post_language ) {
			wp_die( __( 'Error creating translation: original language not found.', 'nlingual' ) );
		}

		// Fail if translation language does not exist
		$translation_language = Registry::get_language( $data['translation_language_id'] );
		if ( ! $translation_language ) {
			wp_die( __( 'Error creating translation: requested language does not exist.', 'nlingual' ) );
		}

		// Fail if the post already has a translation
		if ( Translator::get_post_translation( $post, $translation_language ) ) {
			wp_die( __( 'Error creating translation: Translation already exists.', 'nlingual' ) );
		}

		// Create the translated clone
		$translation = Synchronizer::clone_post( $post, $translation_language );

		// Fail if error creating translation
		if ( ! $translation ) {
			wp_die( __( 'Error creating translation: unable to create clone.', 'nlingual' ) );
		}

		/**
		 * Fires when a translation clone is successfully created.
		 *
		 * @since 2.2.0
		 *
		 * @param \WP_Post $translation          The translation clone of the post.
		 * @param \WP_Post $post                 The original post.
		 * @param Language $translation_language The language the clone is for.
		 */
		do_action( 'nlingual_new_translation', $translation, $post, $translation_language );

		// Get the edit link for this new translation
		$edit_link = get_edit_post_link( $translation->ID, 'raw' );

		// Redirect to the edit screen
		if ( wp_redirect( $edit_link, 302, 'nLingual' ) ) {
			exit;
		}
	}
}
