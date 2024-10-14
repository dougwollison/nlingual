<?php
/**
 * nLingual Management Funtionality
 *
 * @package nLingual
 * @subpackage Handlers
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * The Manager System
 *
 * Hooks into the backend to add the interfaces for
 * managing the configuration of nLingual.
 *
 * @internal Used by the System.
 *
 * @since 2.0.0
 */
final class Manager extends Handler {
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
	 * @since 2.0.0
	 */
	public static function register_hooks() {
		// Don't do anything if not in the backend
		if ( ! is_backend() ) {
			return;
		}

		// Settings & Pages
		self::add_hook( 'admin_init', 'register_settings', 10, 0 );
		self::add_hook( 'admin_menu', 'add_menu_pages', 10, 0 );

		// Custom settings saving
		self::add_hook( 'admin_init', 'save_languages', 10, 0 );
	}

	// =========================
	// ! Settings Page Setup
	// =========================

	/**
	 * Register admin pages.
	 *
	 * @since 2.0.0
	 *
	 * @uses Manager::settings_page() for general options page output.
	 * @uses Manager::settings_page_languages() for language manager output.
	 * @uses Manager::settings_page_strings() for strings editor output.
	 * @uses Documenter::register_help_tabs() to register help tabs for all screens.
	 */
	public static function add_menu_pages() {
		// Main Options page
		$options_page_hook = add_menu_page(
			__( 'Translation Options', 'nlingual' ), // page title
			_x( 'Translation', 'menu title', 'nlingual' ), // menu title
			'manage_options', // capability
			'nlingual-options', // slug
			array( __CLASS__, 'settings_page' ), // callback
			'dashicons-translation', // icon
			90 // Postion; after settings
		);

		// Languages manager
		$languages_page_hook = add_submenu_page(
			'nlingual-options', // parent
			__( 'Manage Languages', 'nlingual' ), // page title
			_x( 'Languages', 'menu title', 'nlingual' ), // menu title
			'manage_options', // capability
			'nlingual-languages', // slug
			array( __CLASS__, 'settings_page_languages' ) // callback
		);

		// Localizable Objects manager
		$localizables_page_hook = add_submenu_page(
			'nlingual-options', // parent
			__( 'Manage Localizable Objects', 'nlingual' ), // page title
			__( 'Localizables', 'nlingual' ), // menu title
			'manage_options', // capability
			'nlingual-localizables', // slug
			array( __CLASS__, 'settings_page' ) // callback
		);

		// Sync/Clone Rules manager
		$synchronizer_page_hook = add_submenu_page(
			'nlingual-options', // parent
			__( 'Post Synchronizer', 'nlingual' ), // page title
			__( 'Synchronizer', 'nlingual' ), // menu title
			'manage_options', // capability
			'nlingual-synchronizer', // slug
			array( __CLASS__, 'settings_page_synchronizer' ) // callback
		);

		// Setup the help tabs for each page
		Documenter::register_help_tabs( array(
			$options_page_hook	    => 'options',
			$localizables_page_hook => 'localizables',
			$synchronizer_page_hook => 'synchronizer',
			$languages_page_hook    => 'languages',
		) );
	}

	// =========================
	// ! Settings Registration
	// =========================

	/**
	 * Register the settings/fields for the admin pages.
	 *
	 * @since 2.0.0
	 *
	 * @uses Settings::register() to register the settings.
	 * @uses Manager::setup_options_fields() to add fields to the main options page.
	 * @uses Manager::setup_localizables_fields() to add fields to the localizlables page.
	 * @uses Manager::setup_sync_fields() to add fields to the sync options page.
	 */
	public static function register_settings() {
		foreach ( array( 'options', 'localizables', 'synchronizer' ) as $group ) {
			register_setting( 'nlingual-' . $group, 'nlingual_options', array( __CLASS__, 'update_options' ) );
			call_user_func( array( __CLASS__, "setup_{$group}_fields" ) );
		}
	}

	// =========================
	// ! Settings Saving
	// =========================

	/**
	 * Merge the updated options with the rest before saving.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $value The options being updated.
	 *
	 * @return mixed The merged/sanitized options.
	 */
	public static function update_options( $updated_options ) {
		$all_options = get_option( 'nlingual_options', array() );

		return array_merge( $all_options, $updated_options );
	}

	/**
	 * Save languages from the manager.
	 *
	 * @since 2.10.0   Ensure _wpnonce is set. Fallback to empty nlingual_languages.
	 * @since 2.9.1.1 Rewrite field checking, make accept_code optional.
	 * @since 2.7.0   Added downloading of WordPress language files for each language.
	 * @since 2.0.0
	 *
	 * @global \wpdb $wpdb The database abstraction class instance.
	 */
	public static function save_languages() {
		global $wpdb;

		// Abort if not saving for the language manager page
		if ( ! isset( $_POST['option_page'] ) || $_POST['option_page'] != 'nlingual-languages' ) {
			return;
		}

		// Fail if nonce does
		check_admin_referer( 'nlingual-languages-options' );
		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'nlingual-languages-options' ) ) {
			cheatin();
		}

		// Get the languages
		$languages = $_POST['nlingual_languages'] ?? array();

		// The fields whitelist + required status
		$fields = array(
			'system_name' => true,
			'native_name' => true,
			'short_name'  => true,
			'locale_name' => true,
			'accept_code' => false,
			'iso_code'    => true,
			'slug'        => true,
		);

		// Loop through languages and update/insert
		$i = 0;
		foreach ( $languages as $id => $language ) {
			// If delete option is present, go straight to deleting it
			if ( isset( $language['delete'] ) && $language['delete'] ) {
				Translator::delete_language( $id );
				Registry::languages()->remove( $id );
				continue;
			}

			$entry = array();

			// Populate entry
			foreach ( $fields as $field => $required ) {
				// Abort if a required field missing
				if ( $required && empty( $language[ $field ] ) ) {
					add_settings_error(
						'nlingual-languages',
						'nl_language',
						__( 'One or more languages were incomplete and were not saved.', 'nlingual' ),
						'error'
					);
					break 2;
				}

				if ( isset( $language[ $field ] ) ) {
					$entry[ $field ] = $language[ $field ];
				}
			}

			// Sanitize the slug
			$language['slug'] = sanitize_title( $language['slug'] );

			// Default active to 0
			$entry['active'] = 0;
			if ( isset( $language['active'] ) ) {
				$entry['active'] = $language['active'];
			}

			// Default text direction to ltr if not set, rtl if not explictly ltr
			$entry['direction'] = 'ltr';
			if ( isset( $language['direction'] ) && $language['direction'] != 'ltr' ) {
				$entry['direction'] = 'rtl';
			}

			// Add list_order
			$entry['list_order'] = $i;
			$i++;

			// If the language already exists, update it
			if ( $language = Registry::get_language( $id ) ) {
				$language->update( $entry );
			} else {
				// Assume new language and add
				Registry::languages()->add( $entry );
			}

			// Download the language pack if applicable and possible (not default US english)
			if ( $entry['locale_name'] != 'en' && $entry['locale_name'] != 'en_US' ) {
				require_once ABSPATH . 'wp-admin/includes/translation-install.php';
				if ( ! wp_download_language_pack( $entry['locale_name'] ) ) {
					add_settings_error(
						'nlingual-languages',
						'nl_language',
						/* translators: %1$s = The locale code, %2$s = The language name. */
						_f( 'Unable to download the WordPress language file "%1$s" for the language "%$2s".  Please install it manually.', 'nlingual', $entry['locale_name'], $entry['system_name'] ),
						'notice'
					);
				}
			}
		}

		// Save the registry
		Registry::save( 'languages' );

		// Check for setting errors; add an "updated" message if none are found
		if ( ! count( get_settings_errors() ) ) {
			add_settings_error( 'nlingual-languages', 'settings_updated', __( 'Languages saved.', 'nlingual' ), 'updated' );
		}
		set_transient( 'settings_errors', get_settings_errors(), 30 );

		// Return to settings page
		$redirect = add_query_arg( 'settings-updated', 'true',  wp_get_referer() );
		wp_redirect( $redirect );
		exit;
	}

	// =========================
	// ! Settings Fields Setup
	// =========================

	/**
	 * Fields for the Translations page.
	 *
	 * @since 2.10.0 Use wp_parse_url().
	 * @since 2.8.0 Add lock_post_language option control.
	 * @since 2.4.0 Only list active languages as options for Default Language,
	 *              also deprecate patch_font_stack option.
	 * @since 2.0.0
	 *
	 * @global \WP_Rewrite $wp_rewrite The rewrite API.
	 *
	 * @uses Registry::language() to get the list of registered languages.
	 * @uses Languages::export() to export the language list to an array.
	 * @uses Settings::add_fields() to define the controls on the page.
	 */
	private static function setup_options_fields() {
		global $wp_rewrite;

		/**
		 * General Settings
		 */
		$general_settings = array(
			'default_language' => array(
				'title' => __( 'Default Language', 'nlingual' ),
				'help'  => null,
				'type'  => 'select',
				'data'  => Registry::languages( 'active' )->pluck( 'system_name' ),
			),
			'localize_date' => array(
				'title' => __( 'Localize date format?', 'nlingual' ),
				'help'  => __( 'Run localization on the date format defined under General Settings. Useful if any languages you use require custom date formats.', 'nlingual' ),
				'type'  => 'checkbox',
			),
			'patch_wp_locale' => array(
				'title' => __( 'Patch <code>WP_Locale</code>?', 'nlingual' ),
				'help'  => __( 'Replaced the Date/Time localization system with one using your Theme’s translation files instead (front-end only).', 'nlingual' ),
				'type'  => 'checkbox',
			),
		);

		// If this is an upgraded install, offer the backwards compatibility option
		if ( get_option( 'nlingual_upgraded' ) ) {
			$general_settings['backwards_compatible'] = array(
				'title' => __( 'Backwards Compatibility', 'nlingual' ),
				'help'  => __( 'Include support for old template functions, and features like language splitting.', 'nlingual' ),
				'type'  => 'checkbox',
			);
		}

		// Add the section and fields
		add_settings_section( 'default', null, null, 'nlingual-options' );
		Settings::add_fields( $general_settings, 'options' );

		/**
		 * Content Management Settings
		 */
		$management_settings = array(
			'show_all_languages' => array(
				'title' => __( 'Show All Languages?', 'nlingual' ),
				'help'  => __( 'Should objects of all languages be listed by default in the admin?', 'nlingual' ),
				'type'  => 'checkbox',
			),
			'trash_sister_posts' => array(
				'title' => __( 'Trash Sister Translations?', 'nlingual' ),
				'help'  => __( 'When trashing or restoring an object, should its translations be trashed/restored as well?', 'nlingual' ),
				'type'  => 'checkbox',
			),
			'delete_sister_posts' => array(
				'title' => __( 'Delete Sister Translations?', 'nlingual' ),
				'help'  => __( 'When deleting an object (that’s in the Trash), should its translations be deleted as well?', 'nlingual' ),
				'type'  => 'checkbox',
			),
			'lock_post_language' => array(
				'title' => __( 'Lock Post Language?', 'nlingual' ),
				'help'  => __( 'Should new posts always be in the default language (except when creating a translation), and all posts locked to the language they were created for?', 'nlingual' ),
				'type'  => 'checkbox',
			),
		);

		// Add font patching option (if needed)
		if ( is_patch_font_stack_needed() ) {
			$management_settings['patch_font_stack'] = array(
				'title' => __( 'Patch Admin Font?', 'nlingual' ),
				'help'  => __( 'Replace the font used in the WordPress admin to better display characters like Arabic and Chinese.', 'nlingual' ),
				'type'  => 'checkbox',
			);
		}

		// Add the sections and fields
		add_settings_section( 'management', __( 'Translated Content Management', 'nlingual' ), null, 'nlingual-options' );
		Settings::add_fields( $management_settings, 'options', 'management' );

		/**
		 * Request/Redirection Previews
		 */

		// Build the previews for the URLs
		$domain = wp_parse_url( home_url(), PHP_URL_HOST );

		// The default language URL samples
		$url_format = '<span class="nl-preview nl-url-preview nl-redirect-%s" data-included="%s" data-excluded="%s"></span>';
		$url_previews =
			sprintf( $url_format, 'get', "$domain/sample-page/?%v=%l", "$domain/sample-page/" ).
			sprintf( $url_format, 'path', "$domain/%l/sample-page/", "$domain/sample-page/" ).
			sprintf( $url_format, 'domain', "%l.$domain/sample-page/", "$domain/sample-page/" );

		// The redirection method previews
		$redirect_format = '<span class="nl-preview nl-redirect-preview nl-redirect-%s" data-format="%s"></span>';
		$redirect_previews =
			sprintf( $redirect_format, 'get', "$domain/?%v=%l" ).
			sprintf( $redirect_format, 'path', "$domain/%l/" ).
			sprintf( $redirect_format, 'domain', "%l.$domain" );

		// The post language override URL samples
		$override_format = '<span class="nl-preview nl-override-preview nl-redirect-%s" data-on="%s" data-off="%s"></span>';
		$override_previews =
			sprintf( $override_format, 'get',
				"$domain/french-page/?%v=en > $domain/french-page/?%v=fr",
				"$domain/french-page/?%v=en > $domain/english-page/?%v=en" ).
			sprintf( $override_format, 'path',
				"$domain/en/french-page/ > $domain/fr/french-page/",
				"$domain/en/french-page/ > $domain/en/english-page/" ).
			sprintf( $override_format, 'domain',
				"en.$domain/french-page/ > fr.$domain/french-page/" ,
				"en.$domain/french-page/ > en.$domain/english-page/" );

		/**
		 * Request/Redirection Settings
		 */
		$redirection_settings = array(
			'query_var' => array(
				'title' => __( 'Query Variable', 'nlingual' ),
				'help'  => __( 'The variable name for <code>WP_Query</code> to use when filtering by language.', 'nlingual' ),
				'type'  => 'text',
			),
			'url_rewrite_method' => array(
				'title' => __( 'URL Scheme', 'nlingual' ),
				'help'  => __( 'What style should be used for the translated URLs?', 'nlingual' ) .
					/* translators: %s = The HTML code to be appended */
					'<br /> <span class="nl-previews">' . _f( 'Preview: %s', 'nlingual', $redirect_previews ) . '</span>',
				'type'  => 'radiolist',
				'data'  => array(
					'path'   => __( 'Path prefix', 'nlingual' ),
					'domain' => __( 'Subdomain', 'nlingual' ),
				),
			),
			'use_accepted_language'  => array(
				'title' => __( 'Use visitor’s browser language?', 'nlingual' ),
				'help'  => __( 'Unless requesting the page in another language, visitors will be redirected to the language their browser uses, if available.', 'nlingual' ),
				'type'  => 'checkbox',
			),
			'skip_default_l10n' => array(
				'title' => __( 'Skip Localization for Default Language?', 'nlingual' ),
				'help'  => __( 'URLs for the default language will be unmodified.', 'nlingual' ) .
					/* translators: %s = The HTML code to be appended */
					'<br /> <span class="nl-previews">' . _f( 'Example: %s', 'nlingual', $url_previews ) . '</span>',
				'type'  => 'checkbox',
			),
			'post_language_override' => array(
				'title' => __( 'Post Language Override', 'nlingual' ),
				'help'  => __( 'Should the language of the requested post take precedence in the event of a language mismatch?', 'nlingual' ) .
					/* translators: %s = The HTML code to be appended */
					'<br /> <span class="nl-previews">' . _f( 'Example: %s', 'nlingual', $override_previews ) . '</span>',
				'type'  => 'checkbox',
			),
			'language_is_required' => array(
				'title' => __( 'Language is Required', 'nlingual' ),
				'help'  => __( 'Should all supported objects have a language? (Default will be assumed)', 'nlingual' ),
				'type'  => 'checkbox',
			),
			'redirection_permanent' => array(
				'title' => __( 'Permanently Redirect URLs?', 'nlingual' ),
				'help'  => __( 'Use "permanent" (HTTP 301) instead of "temporary" (HTTP 302) redirects?', 'nlingual' ),
				'type'  => 'checkbox',
			),
		);

		// If rewrites can't be used, replace the rewrite field with a notice
		if ( ! Registry::can_use_rewrites() ) {
			$redirection_settings['url_rewrite_method'] = array(
				'title' => __( 'URL Scheme', 'nlingual' ),
				'type'  => 'notice',
				'data'  => __( 'You have permalinks disabled; translated URLs will use the HTTP query method, using the <strong>query variable</strong> set above.', 'nlingual' ),
				'help'  => '<span class="nl-previews">' . _f( 'Preview: %s', 'nlingual', $redirect_previews ) . '</span>',
			);
		}

		// Add the sections and fields
		add_settings_section( 'redirection', __( 'Request and Redirection Handling', 'nlingual' ), null, 'nlingual-options' );
		Settings::add_fields( $redirection_settings, 'options', 'redirection' );
	}

	/**
	 * Fields for the Localizables page.
	 *
	 * @since 2.1.0 Removed attachment skip from taxonomy loop.
	 * @since 2.0.0
	 *
	 * @uses Settings::add_fields() to define the controls on the page.
	 */
	private static function setup_localizables_fields() {
		add_settings_section( 'default', null, null, 'nlingual-localizables' );

		/**
		 * Post Types
		 */

		// Build the list
		$post_types = array();
		foreach ( get_post_types( array(
			'show_ui' => true,
		), 'objects' ) as $post_type ) {
			// Automatically skip attachments
			if ( $post_type->name == 'attachment' ) {
				continue;
			}
			$post_types[ $post_type->name ] = $post_type->labels->name;
		}

		// Add if we have any
		if ( $post_types ) {
			$fields['post_types'] = array(
				'title' => __( 'Post Types', 'nlingual' ),
				'help'  => __( 'What post types should support language and translations?', 'nlingual' ),
				'type'  => 'checklist',
				'data'  => $post_types,
			);
		}

		/**
		 * Taxonomies
		 */

		// Build the list
		$taxonomies = array();
		foreach ( get_taxonomies( array(
			'show_ui' => true,
		), 'objects' ) as $taxonomy ) {
			$taxonomies[ $taxonomy->name ] = $taxonomy->labels->name;
		}

		// Add if we have any
		if ( $taxonomies ) {
			$fields['taxonomies'] = array(
				'title' => __( 'Taxonomies', 'nlingual' ),
				'help'  => __( 'What taxonomies should support name and description localization?', 'nlingual' ),
				'type'  => 'checklist',
				'data'  => $taxonomies,
			);
		}

		/**
		 * Nav Menus
		 */

		// Get the original list
		$nav_locations = wp_cache_get( '_wp_registered_nav_menus', 'nlingual:vars' );

		// Add if we have any
		if ( $nav_locations ) {
			$fields['nav_menu_locations'] = array(
				'title' => __( 'Menu Locations', 'nlingual' ),
				'help'  => __( 'Should any navigation menus have versions for each language?', 'nlingual' ),
				'type'  => 'checklist',
				'data'  => $nav_locations,
			);
		}

		/**
		 * Sidebars
		 */

		// Get the original list and convert to appropriate format
		$sidebars = wp_cache_get( 'wp_registered_sidebars', 'nlingual:vars' );
		foreach ( $sidebars as &$sidebar ) {
			$sidebar = $sidebar['name'];
		}

		// Add if we have any
		if ( $sidebars ) {
			$fields['sidebar_locations'] = array(
				'title' => __( 'Sidebar Locations', 'nlingual' ),
				'help'  => __( 'Should any widget areas have versions for each language?', 'nlingual' ),
				'type'  => 'checklist',
				'data'  => $sidebars,
			);
		}

		// Add the fields
		Settings::add_fields( $fields, 'localizables' );
	}

	/**
	 * Fields for the Sync/Clone Rules page.
	 *
	 * @since 2.3.3 Added check for post type's existence.
	 * @since 2.0.0
	 *
	 * @uses Registry::get() to retrieve the enabled post types.
	 * @uses Registry::add_fields() to add the sycn/clone controls for the page.
	 */
	private static function setup_synchronizer_fields() {
		// Abort if no post types are registered
		$post_types = Registry::get( 'post_types' );
		if ( ! $post_types ) {
			return;
		}

		// Build the fields list based on the supported post types
		foreach ( $post_types as $post_type ) {
			// Ensure post type exists
			if ( post_type_exists( $post_type ) ) {
				$sync_fields[ "sync_rules[post_type][{$post_type}]" ] = array(
					'title' => get_post_type_object( $post_type )->labels->name,
					'type'  => 'sync_settings',
					'data'  => array(
						'post_type' => $post_type,
						'allowed_fields' => Synchronizer::get_allowed_post_fields( 'sync' ),
					),
				);
				$clone_fields[ "clone_rules[post_type][{$post_type}]" ] = array(
					'title' => get_post_type_object( $post_type )->labels->name,
					'type'  => 'sync_settings',
					'data'  => array(
						'post_type' => $post_type,
						'allowed_fields' => Synchronizer::get_allowed_post_fields( 'clone' ),
					),
				);
			}
		}

		// The post synchronizing setting fields
		add_settings_section( 'default', null, null, 'nlingual-synchronizer' );
		Settings::add_fields( $sync_fields, 'synchronizer' );

		// The post cloning setting fields
		add_settings_section( 'cloning', __( 'New Translations', 'nlingual' ), array( __CLASS__, 'settings_section_cloning' ), 'nlingual-synchronizer' );
		Settings::add_fields( $clone_fields, 'synchronizer', 'cloning' );
	}

	// =========================
	// ! Settings Page Output
	// =========================

	/**
	 * Output for generic settings page.
	 *
	 * @since 2.0.0
	 *
	 * @global $plugin_page The slug of the current admin page.
	 */
	public static function settings_page() {
		global $plugin_page;
		?>
		<div class="wrap">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<?php settings_errors(); ?>
			<form method="post" action="options.php" id="<?php echo esc_attr( $plugin_page ); ?>-form">
				<?php settings_fields( $plugin_page ); ?>
				<?php do_settings_sections( $plugin_page ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Output for the language management page.
	 *
	 * @since 2.10.0 Use wp_json_encode().
	 * @since 2.0.0
	 *
	 * @global $plugin_page The slug of the current admin page.
	 *
	 * @uses inc/presets.php For loading the preset languages.
	 */
	public static function settings_page_languages() {
		global $plugin_page;
		?>
		<div class="wrap">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<?php settings_errors( $plugin_page ); ?>
			<form method="post" action="options.php" id="<?php echo esc_attr( $plugin_page ); ?>-form">
				<?php settings_fields( $plugin_page ); ?>
				<div id="nl_language_controls">
					<select id="nl_language_preset">
						<option value=""><?php esc_html_e( '&mdash; Custom Language &mdash;', 'nlingual' ); ?></option>
					</select>
					<button type="button" id="nl_language_add" class="button"><?php esc_html_e( 'Add Language', 'nlingual' ); ?></button>
				</div>
				<table id="nlingual_languages" class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col" class="nl-language-list_order"><?php esc_html_e( 'List Order', 'nlingual' ); ?></th>
							<th scope="col" class="nl-language-system_name"><?php esc_html_e( 'System Name', 'nlingual' ); ?></th>
							<th scope="col" class="nl-language-native_name"><?php esc_html_e( 'Native Name', 'nlingual' ); ?></th>
							<th scope="col" class="nl-language-short_name"><?php esc_html_e( 'Short Name', 'nlingual' ); ?></th>
							<th scope="col" class="nl-language-locale_name"><?php esc_html_e( 'Locale', 'nlingual' ); ?></th>
							<th scope="col" class="nl-language-accept_code"><?php esc_html_e( 'Code(s)', 'nlingual' ); ?></th>
							<th scope="col" class="nl-language-iso_code"><?php esc_html_e( 'ISO', 'nlingual' ); ?></th>
							<th scope="col" class="nl-language-slug"><?php esc_html_e( 'Slug', 'nlingual' ); ?></th>
							<th scope="col" class="nl-language-direction"><?php esc_html_e( 'Text Direction', 'nlingual' ); ?></th>
							<th scope="col" class="nl-language-active"><?php esc_html_e( 'Active?', 'nlingual' ); ?></th>
							<td class="nl-language-delete"><?php esc_html_e( 'Delete?', 'nlingual' ); ?></td>
						</tr>
					</thead>
					<tbody id="nl_language_list">
					</tbody>
				</table>
				<script type="text/template" id="nl_language_row">
					<tr>
						<td class="nl-language-list_order">
							<i class="handle dashicons dashicons-sort"></i>
						</td>
						<td class="nl-language-system_name">
							<input type="text" name="nlingual_languages[%id%][system_name]" value="%system_name%" />
						</td>
						<td class="nl-language-native_name">
							<input type="text" name="nlingual_languages[%id%][native_name]" value="%native_name%" />
						</td>
						<td class="nl-language-short_name">
							<input type="text" name="nlingual_languages[%id%][short_name]" value="%short_name%" />
						</td>
						<td class="nl-language-locale_name">
							<input type="text" name="nlingual_languages[%id%][locale_name]" value="%locale_name%" />
						</td>
						<td class="nl-language-accept_code">
							<input type="text" name="nlingual_languages[%id%][accept_code]" value="%accept_code%" />
						</td>
						<td class="nl-language-iso_code">
							<input type="text" name="nlingual_languages[%id%][iso_code]" value="%iso_code%" maxlength="2" />
						</td>
						<td class="nl-language-slug">
							<input type="text" name="nlingual_languages[%id%][slug]" value="%slug%" maxlength="100" />
						</td>
						<td class="nl-language-direction">
							<label title="<?php esc_html_e( 'Left to Right', 'nlingual' ); ?>"><input type="radio" name="nlingual_languages[%id%][direction]" value="ltr" />&rsaquo;&para;</label>
							<label title="<?php esc_html_e( 'Right to Left', 'nlingual' ); ?>"><input type="radio" name="nlingual_languages[%id%][direction]" value="rtl" />&para;&lsaquo;</label>
						</td>
						<td class="nl-language-active">
							<input type="checkbox" name="nlingual_languages[%id%][active]" value="1" />
						</td>
						<td scope="row" class="nl-language-delete">
							<input type="checkbox" name="nlingual_languages[%id%][delete]" value="1" />
						</td>
					</tr>
				</script>
				<script>
					<?php $presets = require NL_PLUGIN_DIR . '/includes/presets-languages.php'; ?>
					nLingual.presets = <?php echo wp_json_encode( $presets ); ?>
				</script>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Output for generic settings page.
	 *
	 * @since 2.0.0
	 *
	 * @global $plugin_page The slug of the current admin page.
	 */
	public static function settings_page_synchronizer() {
		global $plugin_page;
		?>
		<div class="wrap">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<?php settings_errors(); ?>
			<form method="post" action="options.php" id="<?php echo esc_attr( $plugin_page ); ?>-form">
				<?php settings_fields( $plugin_page ); ?>
				<?php do_settings_sections( $plugin_page ); ?>
				<p><small>
					<sup>1</sup> <?php esc_html_e( 'includes creation/publication date, modified date, and their GMT versions', 'nlingual' ); ?><br />
					<sup>2</sup> <?php esc_html_e( 'will NOT apply to trashing or untrashing via normal means', 'nlingual' ); ?><br />
					<sup>3</sup> <?php esc_html_e( 'will use counterpart translation if found', 'nlingual' ); ?><br />
					<sup>4</sup> <?php esc_html_e( 'includes pingback status', 'nlingual' ); ?><br />
					<sup>5</sup> <?php esc_html_e( 'includes excerpt and filtered content data', 'nlingual' ); ?>
				</small></p>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Output for the cloning options section.
	 *
	 * @since 2.0.0
	 */
	public static function settings_section_cloning() {
		?>
		<p><?php esc_html_e( 'When creating a new translation of an existing post (i.e. a clone), what details should be cloned?', 'nlingual' ); ?></p>
		<?php
	}
}
