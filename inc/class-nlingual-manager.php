<?php
namespace nLingual;

/**
 * nLingual Manager Funtionality
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

class Manager extends Functional {
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

	// =========================
	// ! Hook Registration
	// =========================

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 */
	public static function register_hooks() {
		// Settings & Pages
		static::add_action( 'admin_init', 'register_settings' );
		static::add_action( 'admin_menu', 'add_menu_pages' );

		// Language saving
		static::add_action( 'admin_init', 'save_languages' );
	}

	// =========================
	// ! Utilities
	// =========================

	/**
	 * Sanitize the sync rules.
	 *
	 * @since 2.0.0
	 *
	 * @param array $rules The rules to be sanitized.
	 *
	 * @return array The sanitized rules.
	 */
	public static function sanitize_rules( $rules ) {
		// Loop through each object type
		foreach ( $rules as $object_type => $type_rules ) {
			// Loop through each object subtype
			foreach ( $type_rules as $subtype => $ruleset ) {
				// Loop through each rule set
				foreach ( $ruleset as $rule => $values ) {
					// If values is a string...
					if ( is_string( $values ) ) {
						// Split it by line
						$values = (array) preg_split( '/[\n\r]+/', trim( $values ), 0, PREG_SPLIT_NO_EMPTY );
						// Convert to TRUE if it contains * wildcard
						if ( in_array( '*', $values ) ) {
							$values = true;
						}

						$rules[ $object_type ][ $subtype ][ $rule ] = $values;
					}
				}
			}
		}

		return $rules;
	}

	// =========================
	// ! Settings Page Setup
	// =========================

	/**
	 * Register admin pages.
	 *
	 * @since 2.0.0
	 */
	public static function add_menu_pages() {
		add_utility_page(
			__( 'Translation Options' ), // page title
			_x( 'Translation', 'menu title' ), // menu title
			'manage_options', // capability
			'nlingual-options', // slug
			array( static::$name, 'settings_page' ), // callback
			'dashicons-translation' // icon
		);

		add_submenu_page(
			'nlingual-options', // parent
			__( 'Manage Localizable Objects' ), // page title
			__( 'Localizables',  NL_TXTDMN ), // menu title
			'manage_options', // capability
			'nlingual-l10s', // slug
			array( static::$name, 'settings_page' ) // callback
		);

		add_submenu_page(
			'nlingual-options', // parent
			__( 'Post Synchronization' ), // page title
			__( 'Sync Options' ), // menu title
			'manage_options', // capability
			'nlingual-sync', // slug
			array( static::$name, 'settings_page' ) // callback
		);

		add_submenu_page(
			'nlingual-options', // parent
			__( 'Manage Languages' ), // page title
			_x( 'Languages', 'menu title' ), // menu title
			'manage_options', // capability
			'nlingual-languages', // slug
			array( static::$name, 'settings_page_languages' ) // callback
		);
	}

	// =========================
	// ! Settings Registration
	// =========================

	/**
	 * Register the settings/fields for the admin pages.
	 *
	 * @since 2.0.0
	 */
	public static function register_settings() {
		// Translation Options
		Settings::register( array(
			'default_language'   => 'intval',
			'skip_default_l10n'  => 'intval',
			'query_var'          => null,
			'redirection_method' => null,
			'postlang_override'  => null,
		), 'options' );

		static::setup_options_fields();

		// Localizables Options
		Settings::register( array(
			'post_types'   => null,
			'localizables' => null,
		), 'l10s' );

		static::setup_l10s_fields();

		// Sync Options
		Settings::register( array(
			'sync_rules'  => array( static::$name, 'sanitize_rules' ),
			'clone_rules' => array( static::$name, 'sanitize_rules' ),
		), 'sync' );

		static::setup_sync_fields();
	}

	/**
	 * Save languages from the manager.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 */
	public static function save_languages() {
		global $wpdb;

		// Abort if not saving for the language manager page
		if ( ! isset( $_POST['option_page'] ) || $_POST['option_page'] != 'nlingual-languages' ) {
			return;
		}

		// Fail if nonce does
		check_admin_referer( 'nlingual-languages-options' );
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'nlingual-languages-options' ) ) {
			wp_die( __( 'Cheatin&#8217; uh?' ), 403 );
		}

		// Get the languages
		$languages = $_POST['nlingual_languages'];

		// The fields to check
		$fields = array(
			'system_name' => '%s',
			'native_name' => '%s',
			'short_name'  => '%s',
			'locale_name' => '%s',
			'iso_code'    => '%s',
			'slug'        => '%s',
		);
		$formats = array_values( $fields );

		// Loop through languages and update/insert
		$i = 0;
		foreach ( $languages as $id => $lang ) {
			// If delete option is present, go straight to deleting it
			if ( isset( $lang['delete'] ) && $lang['delete'] ) {
				if ( $id > 0 ) {
					$wpdb->delete( $wpdb->nl_languages, array( 'lang_id' => $id ), array( '%d' ) );
				}
				continue;
			}

			// Ensure all fields are set
			foreach ( $fields as $field => $format ) {
				if ( ! isset( $lang[ $field ] ) || empty( $lang[ $field ] ) ) {
					add_settings_error(
						'nlingual-languages',
						'nl_language',
						__( 'One or more languages were incomplete and were not saved.' ),
						'error'
					);
					break;
				} else {
					$entry[ $field ] = $lang[ $field ];
				}
			}

			// Default active to 0
			$formats[] = '%d';
			if ( isset( $lang['active'] ) ) {
				$entry['active'] = $lang['active'];
			} else {
				$entry['active'] = 0;
			}

			// Add list_order
			$formats[] = '%d';
			$entry['list_order'] = $i;
			$i++;

			if ( $id > 0 ) {
				// Assume existing language; update
				$wpdb->update( $wpdb->nl_languages, $entry, array( 'lang_id' => $id ), $formats, array( '%d' ) );
			} else {
				// Assume new language; insert
				$wpdb->insert( $wpdb->nl_languages, $entry, $formats );
			}
		}

		// Check for setting errors; add an "updated" message if none are found
		if ( ! count( get_settings_errors() ) ) {
			add_settings_error('general', 'settings_updated', __('Settings saved.'), 'updated');
		}
		set_transient('settings_errors', get_settings_errors(), 30);

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
	 * @since 2.0.0
	 */
	protected static function setup_options_fields() {
		// Build the previews for the URLs
		$domain = parse_url( home_url(), PHP_URL_HOST );

		// The default language URL samples
		$url_format = '<span class="nl-preview nl-url-preview %s" data-included="%s" data-excluded="%s"></span>';
		$url_previews =
			sprintf( $url_format, NL_REDIRECT_USING_GET, "$domain/sample-page/?%v=%l", "$domain/sample-page/" ).
			sprintf( $url_format, NL_REDIRECT_USING_PATH, "$domain/%l/sample-page/", "$domain/sample-page/" ).
			sprintf( $url_format, NL_REDIRECT_USING_DOMAIN, "%l.$domain/sample-page/", "$domain/sample-page/" );

		// The redirection method previews
		$redirect_format = '<span class="nl-preview nl-redirect-preview %s" data-format="%s"></span>';
		$redirect_previews =
			sprintf( $redirect_format, NL_REDIRECT_USING_GET, "$domain/?%v=%l" ).
			sprintf( $redirect_format, NL_REDIRECT_USING_PATH, "$domain/%l/" ).
			sprintf( $redirect_format, NL_REDIRECT_USING_DOMAIN, "%l.$domain" );

		// The general setting fields
		add_settings_section( 'default', null, null, 'nlingual-options' );
		Settings::add_fields( array(
			'default_language' => array(
				'title' => __( 'Default Language' ),
				'help'  => null,
				'type'  => 'select',
				'data'  => Registry::languages()->export( 'system_name' ),
			),
			'skip_default_l10n' => array(
				'title' => __( 'Skip Localization for Default Language?' ),
				'help'  => __( 'URLs for the default language will be unmodified.' ) .
					' <span class="nl-previews">' . _f( 'Example: %s', $url_previews ) . '</span>',
				'type'  => 'checkbox',
			),
		), 'options' );

		// The request/redirection setting fields
		add_settings_section( 'redirection', __( 'Request Handling' ), null, 'nlingual-options' );
		Settings::add_fields( array(
			'query_var' => array(
				'title' => __( 'Query Variable' ),
				'help'  => __( 'The variable name to use for when requesting/filtering by language (recommended: "language")' ),
				'type'  => 'input',
			),
			'redirection_method' => array(
				'title' => __( 'Redirection Method' ),
				'help'  => __( 'What style should be used for the translated URLs?' ) .
					' <span class="nl-previews">' . _f( 'Preview: %s', $redirect_previews ) . '</span>',
				'type'  => 'radiolist',
				'data'  => array(
					NL_REDIRECT_USING_GET    => __( 'HTTP query' ),
					NL_REDIRECT_USING_PATH   => __( 'Path prefix' ),
					NL_REDIRECT_USING_DOMAIN => __( 'Subdomain' ),
				),
			),
			'postlang_override' => array(
				'title' => __( 'Post Language Override' ),
				'help'  => __( 'Should the requested post/page/object’s language override the one requested?' ),
				'type'  => 'checkbox',
			),
		), 'options', 'redirection' );
	}

	/**
	 * Fields for the Localizables page.
	 *
	 * @since 2.0.0
	 */
	protected static function setup_l10s_fields() {
		add_settings_section( 'default', null, null, 'nlingual-l10s' );

		// Build the post types list
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

		// Get the original nav menus list
		$nav_locations = Registry::cache_get( 'vars', '_wp_registered_nav_menus' );

		// Build the original sidebars list
		$sidebars = Registry::cache_get( 'vars', 'wp_registered_sidebars' );
		foreach ( $sidebars as &$sidebar ) {
			$sidebar = $sidebar['name'];
		}

		// Add the fields
		Settings::add_fields( array(
			'post_types' => array(
				'title' => __( 'Post Types' ),
				'help'  => __( 'What post types should support language and translations?' ),
				'type'  => 'checklist',
				'data'  => $post_types,
			),
			'localizables[nav_menu_locations]' => array(
				'title' => __( 'Menu Locations' ),
				'help'  => __( 'Should any navigation menus have versions for each language?' ),
				'type'  => 'checklist',
				'data'  => $nav_locations,
			),
			'localizables[sidebar_locations]' => array(
				'title' => __( 'Sidebar Locations' ),
				'help'  => __( 'Should any widget areas have versions for each language?' ),
				'type'  => 'checklist',
				'data'  => $sidebars,
			),
		), 'l10s' );
	}

	/**
	 * Fields for the Sync Options page.
	 *
	 * @since 2.0.0
	 */
	protected static function setup_sync_fields() {
		// Build the fields list based on the supported post types
		$post_types = Registry::get( 'post_types' );
		foreach ( $post_types as $post_type ) {
			$field = array(
				'title' => get_post_type_object( $post_type )->labels->name,
				'type'  => 'sync_settings',
				'data'  => $post_type,
			);
			$sync_fields[ "sync_rules[post_type][{$post_type}]" ] = $field;
			$clone_fields[ "clone_rules[post_type][{$post_type}]" ] = $field;
		}

		// The post synchronizing setting fields
		add_settings_section( 'default', null, null, 'nlingual-sync' );
		Settings::add_fields( $sync_fields, 'sync' );

		// The post cloning setting fields
		add_settings_section( 'cloning', __( 'New Translations' ), array( static::$name, 'settings_section_cloning' ), 'nlingual-sync' );
		Settings::add_fields( $clone_fields, 'sync', 'cloning' );
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
			<h1><?php echo get_admin_page_title(); ?></h1>
			<?php settings_errors(); ?>
			<form method="post" action="options.php" id="<?php echo $plugin_page; ?>-form">
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
	 * @since 2.0.0
	 *
	 * @uses inc/presets.php For loading the preset languages.
	 *
	 * @global $plugin_page The slug of the current admin page.
	 */
	public static function settings_page_languages() {
		global $plugin_page;
		?>
		<div class="wrap">
			<h1><?php echo get_admin_page_title(); ?></h1>
			<?php settings_errors(); ?>
			<form method="post" action="options.php" id="<?php echo $plugin_page; ?>-form">
				<?php settings_fields( $plugin_page ); ?>
				<div id="nl_lang_controls">
					<select id="nl_lang_preset">
						<option value=""><?php _e( 'Custom Language' ); ?></option>
					</select>
					<button type="button" id="nl_lang_add" class="button"><?php _e( 'Add Language' ); ?></button>
				</div>
				<table id="nlingual_languages" class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col" class="nl-lang-system_name"><?php _e( 'System Name' ); ?></th>
							<th scope="col" class="nl-lang-native_name"><?php _e( 'Native Name' ); ?></th>
							<th scope="col" class="nl-lang-short_name"><?php _e( 'Short Name' ); ?></th>
							<th scope="col" class="nl-lang-locale_name"><?php _e( 'Locale' ); ?></th>
							<th scope="col" class="nl-lang-iso_code"><?php _e( 'ISO' ); ?></th>
							<th scope="col" class="nl-lang-slug"><?php _e( 'Slug' ); ?></th>
							<th scope="col" class="nl-lang-active"><?php _e( 'Active?' ); ?></th>
							<td class="nl-lang-delete"><?php _e( 'Delete?' ); ?></td>
						</tr>
					</thead>
					<tbody id="nl_lang_list">
					</tbody>
				</table>
				<script type="text/template" id="nl_lang_row">
					<tr>
						<td class="nl-lang-system_name">
							<input type="text" name="nlingual_languages[%lang_id%][system_name]" value="%system_name%" />
						</td>
						<td class="nl-lang-native_name">
							<input type="text" name="nlingual_languages[%lang_id%][native_name]" value="%native_name%" />
						</td>
						<td class="nl-lang-short_name">
							<input type="text" name="nlingual_languages[%lang_id%][short_name]" value="%short_name%" />
						</td>
						<td class="nl-lang-locale_name">
							<input type="text" name="nlingual_languages[%lang_id%][locale_name]" value="%locale_name%" />
						</td>
						<td class="nl-lang-iso_code">
							<input type="text" name="nlingual_languages[%lang_id%][iso_code]" value="%iso_code%" />
						</td>
						<td class="nl-lang-slug">
							<input type="text" name="nlingual_languages[%lang_id%][slug]" value="%slug%" />
						</td>
						<td class="nl-lang-active">
							<input type="checkbox" name="nlingual_languages[%lang_id%][active]" value="1" />
						</td>
						<td scope="row" class="nl-lang-delete">
							<input type="checkbox" name="nlingual_languages[%lang_id%][delete]" value="1" />
						</td>
					</tr>
				</script>
				<script>
					<?php $presets = require( NL_DIR . '/inc/nlingual-presets.php' ); ?>
					var NL_PRESETS = <?php echo json_encode( $presets );?>
				</script>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Output for the cloning options section.
	 *
	 * @sicne 2.0.0
	 */
	public static function settings_section_cloning() {
		?>
		<p>When creating a new translation of an existing post (i.e. a clone), what details should be cloned?</p>
		<?php
	}
}