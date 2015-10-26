<?php
/**
 * nLingual Manager Funtionality
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

namespace nLingual;

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

		// Custom settings saving
		static::add_action( 'admin_init', 'save_languages' );
		static::add_action( 'admin_init', 'save_strings' );
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
	 *
	 * @uses Manager::settings_page() for general options page output.
	 * @uses Manager::settings_page_languages() for language manager output.
	 * @uses Manager::settings_page_strings() for strings editor output.
	 */
	public static function add_menu_pages() {
		// Main Options page
		$options_page_hook = add_utility_page(
			__( 'Translation Options' ), // page title
			_x( 'Translation', 'menu title' ), // menu title
			'manage_options', // capability
			'nlingual-options', // slug
			array( static::$name, 'settings_page' ), // callback
			'dashicons-translation' // icon
		);

		// Localizable Objects manager
		$localizables_page_hook = add_submenu_page(
			'nlingual-options', // parent
			__( 'Manage Localizable Objects' ), // page title
			__( 'Localizables',  NL_TXTDMN ), // menu title
			'manage_options', // capability
			'nlingual-localizables', // slug
			array( static::$name, 'settings_page' ) // callback
		);

		// Sync/Clone Rules manager
		$sync_page_hook = add_submenu_page(
			'nlingual-options', // parent
			__( 'Post Synchronization' ), // page title
			__( 'Sync Options' ), // menu title
			'manage_options', // capability
			'nlingual-sync', // slug
			array( static::$name, 'settings_page' ) // callback
		);

		// Languages manager
		$languages_page_hook = add_submenu_page(
			'nlingual-options', // parent
			__( 'Manage Languages' ), // page title
			_x( 'Languages', 'menu title' ), // menu title
			'manage_options', // capability
			'nlingual-languages', // slug
			array( static::$name, 'settings_page_languages' ) // callback
		);

		// Strings manager
		$strings_page_hook = add_submenu_page(
			'nlingual-options', // parent
			__( 'Manage Localized Strings' ), // page title
			_x( 'Strings', 'menu title' ), // menu title
			'manage_options', // capability
			'nlingual-strings', // slug
			array( static::$name, 'settings_page_strings' ) // callback
		);

		// Setup the help tabs for each page
		Documenter::register_help_tabs( array(
			$options_page_hook	    => 'options',
			$localizables_page_hook => 'localizables',
			$sync_page_hook		    => 'sync',
			$languages_page_hook    => 'languages',
			$strings_page_hook	    => 'strings',
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
		// Translation Options
		Settings::register( array(
			'default_language'   => 'intval',
			'show_all_languages' => 'intval',
			'skip_default_l10n'  => 'intval',
			'query_var'          => null,
			'redirection_method' => null,
			'postlanguage_override'  => null,
		), 'options' );

		static::setup_options_fields();

		// Localizables Options
		Settings::register( array(
			'post_types'   => null,
			'taxonomies'   => null,
			'localizables' => null,
		), 'localizables' );

		static::setup_localizables_fields();

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
			cheatin();
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
		foreach ( $languages as $id => $language ) {
			// If delete option is present, go straight to deleting it
			if ( isset( $language['delete'] ) && $language['delete'] ) {
				if ( $id > 0 ) {
					$wpdb->delete( $wpdb->nl_languages, array( 'language_id' => $id ), array( '%d' ) );
				}
				continue;
			}

			// Ensure all fields are set
			foreach ( $fields as $field => $format ) {
				if ( ! isset( $language[ $field ] ) || empty( $language[ $field ] ) ) {
					add_settings_error(
						'nlingual-languages',
						'nl_language',
						__( 'One or more languages were incomplete and were not saved.' ),
						'error'
					);
					break;
				} else {
					$entry[ $field ] = $language[ $field ];
				}
			}

			// Default active to 0
			$formats[] = '%d';
			if ( isset( $language['active'] ) ) {
				$entry['active'] = $language['active'];
			} else {
				$entry['active'] = 0;
			}

			// Add list_order
			$formats[] = '%d';
			$entry['list_order'] = $i;
			$i++;

			if ( $id > 0 ) {
				// Assume existing language; update
				$wpdb->update( $wpdb->nl_languages, $entry, array( 'language_id' => $id ), $formats, array( '%d' ) );
			} else {
				// Assume new language; insert
				$wpdb->insert( $wpdb->nl_languages, $entry, $formats );
			}
		}

		// Check for setting errors; add an "updated" message if none are found
		if ( ! count( get_settings_errors() ) ) {
			add_settings_error( 'nlingual-languages', 'settings_updated', __( 'Languages saved.' ), 'updated' );
		}
		set_transient( 'settings_errors', get_settings_errors(), 30);

		// Return to settings page
		$redirect = add_query_arg( 'settings-updated', 'true',  wp_get_referer() );
		wp_redirect( $redirect );
		exit;
	}

	/**
	 * Save strings from the manager.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Localizer::save_string_value() to save each updated string value.
	 */
	public static function save_strings() {
		// Abort if not saving for the language manager page
		if ( ! isset( $_POST['option_page'] ) || $_POST['option_page'] != 'nlingual-strings' ) {
			return;
		}

		// Fail if nonce does
		check_admin_referer( 'nlingual-strings-options' );
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'nlingual-strings-options' ) ) {
			cheatin();
		}

		// Get the strings to save
		$strings = $_POST['nlingual_strings'];

		// Loop through each string and save for each language and object ID
		foreach ( $strings as $string => $localized ) {
			foreach ( $localized as $language_id => $objects ) {
				foreach ( $objects as $object_id => $value ) {
					Localizer::save_string_value( $string, $language_id, $object_id, $value );
				}
			}
		}

		// Check for setting errors; add an "updated" message if none are found
		if ( ! count( get_settings_errors() ) ) {
			add_settings_error( 'nlingual-strings', 'settings_updated', __( 'Strings saved.' ), 'updated' );
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
	 * @since 2.0.0
	 *
	 * @uses Settings::add_fields() to define the controls on the page.
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
			'show_all_languages' => array(
				'title' => __( 'Show All Languages?' ),
				'help'  => __( 'Should objects of all languages be listed by default in the admin?' ),
				'type'  => 'checkbox',
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
			'postlanguage_override' => array(
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
	 *
	 * @uses Registry::cache_get() to retrieve original nav menus and sidebars.
	 * @uses Settings::add_fields() to define the controls on the page.
	 */
	protected static function setup_localizables_fields() {
		add_settings_section( 'default', null, null, 'nlingual-localizables' );

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

		// Build the taxonomies list
		$taxonomies = array();
		foreach ( get_taxonomies( array(
			'show_ui' => true,
		), 'objects' ) as $taxonomy ) {
			// Automatically skip attachments
			if ( $taxonomy->name == 'attachment' ) {
				continue;
			}
			$taxonomies[ $taxonomy->name ] = $taxonomy->labels->name;
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
			'taxonomies' => array(
				'title' => __( 'Taxonomies' ),
				'help'  => __( 'What taxonomies should support name and description localization?' ),
				'type'  => 'checklist',
				'data'  => $taxonomies,
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
		), 'localizables' );
	}

	/**
	 * Fields for the Sync Options page.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::get() to retrieve the enabled post types.
	 * @uses Registry::add_fields() to add the sycn/clone controls for the page.
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
			<h2><?php echo get_admin_page_title(); ?></h2>
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
	 * @global $plugin_page The slug of the current admin page.
	 *
	 * @uses inc/presets.php For loading the preset languages.
	 */
	public static function settings_page_languages() {
		global $plugin_page;
		?>
		<div class="wrap">
			<h2><?php echo get_admin_page_title(); ?></h2>
			<?php settings_errors( $plugin_page ); ?>
			<form method="post" action="options.php" id="<?php echo $plugin_page; ?>-form">
				<?php settings_fields( $plugin_page ); ?>
				<div id="nl_language_controls">
					<select id="nl_language_preset">
						<option value=""><?php _e( 'Custom Language' ); ?></option>
					</select>
					<button type="button" id="nl_language_add" class="button"><?php _e( 'Add Language' ); ?></button>
				</div>
				<table id="nlingual_languages" class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col" class="nl-language-system_name"><?php _e( 'System Name' ); ?></th>
							<th scope="col" class="nl-language-native_name"><?php _e( 'Native Name' ); ?></th>
							<th scope="col" class="nl-language-short_name"><?php _e( 'Short Name' ); ?></th>
							<th scope="col" class="nl-language-locale_name"><?php _e( 'Locale' ); ?></th>
							<th scope="col" class="nl-language-iso_code"><?php _e( 'ISO' ); ?></th>
							<th scope="col" class="nl-language-slug"><?php _e( 'Slug' ); ?></th>
							<th scope="col" class="nl-language-active"><?php _e( 'Active?' ); ?></th>
							<td class="nl-language-delete"><?php _e( 'Delete?' ); ?></td>
						</tr>
					</thead>
					<tbody id="nl_language_list">
					</tbody>
				</table>
				<script type="text/template" id="nl_language_row">
					<tr>
						<td class="nl-language-system_name">
							<input type="text" name="nlingual_languages[%language_id%][system_name]" value="%system_name%" />
						</td>
						<td class="nl-language-native_name">
							<input type="text" name="nlingual_languages[%language_id%][native_name]" value="%native_name%" />
						</td>
						<td class="nl-language-short_name">
							<input type="text" name="nlingual_languages[%language_id%][short_name]" value="%short_name%" />
						</td>
						<td class="nl-language-locale_name">
							<input type="text" name="nlingual_languages[%language_id%][locale_name]" value="%locale_name%" />
						</td>
						<td class="nl-language-iso_code">
							<input type="text" name="nlingual_languages[%language_id%][iso_code]" value="%iso_code%" />
						</td>
						<td class="nl-language-slug">
							<input type="text" name="nlingual_languages[%language_id%][slug]" value="%slug%" />
						</td>
						<td class="nl-language-active">
							<input type="checkbox" name="nlingual_languages[%language_id%][active]" value="1" />
						</td>
						<td scope="row" class="nl-language-delete">
							<input type="checkbox" name="nlingual_languages[%language_id%][delete]" value="1" />
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
	 * Output for the strings management page.
	 *
	 * Offers a central place to manage localized versions
	 * of option and term name/description strings.
	 *
	 * @since 2.0.0
	 *
	 * @global $plugin_page The slug of the current admin page.
	 *
	 * @uses Manager::print_strings_table() to print the editors for each string.
	 * @uses Localizer::get_string() to get string objects.
	 */
	public static function settings_page_strings() {
		global $plugin_page;
		?>
		<div class="wrap">
			<h2><?php _e( 'Manage Localized Taxonomies' ); ?></h2>
			<?php settings_errors( $plugin_page ); ?>
			<form method="post" action="options.php" id="<?php echo $plugin_page; ?>-form">
				<?php settings_fields( $plugin_page ); ?>

				<?php if ( $strings = Localizer::get_strings_by_type( 'option' ) ) : ?>
				<h3><?php _e( 'Options & Settings' ); ?></h3>
				<table class="form-table nl-option-strings">
					<tbody>
					<?php foreach ( $strings as $string ) : ?>
						<tr>
							<th scope="row"><?php echo $string->title; ?></th>
							<td>
								<?php static::print_strings_table( $string, get_option( $string->field ) );?>
								<p class="description"><?php echo $string->description; ?></p>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<?php endif; ?>

				<?php if ( $taxonomies = Localizer::get_registered_taxonomies() ) : ?>
				<h2><?php _e( 'Manage Localized Taxonomies' ); ?></h2>
				<?php foreach ( $taxonomies as $taxonomy ) : $taxonomy_obj = get_taxonomy( $taxonomy ); ?>
					<h3><?php echo $taxonomy_obj->labels->name; ?></h3>
					<?php
					// Get the terms
					$terms = get_terms( $taxonomy, array(
						'orderby' => 'id',
						'hide_empty' => false,
					) );

					// Get the strings
					$name_string = Localizer::get_string( "term_{$taxonomy}_name" );
					$desc_string = Localizer::get_string( "term_{$taxonomy}_description" );

					foreach ( $terms as $term ) : ?>
					<hr />
					<h4><?php echo $term->name; ?></h4>
					<table class="form-table nl-taxonomy-strings">
						<tbody>
							<tr>
								<th scope="row"><?php _e( 'Name' ); ?></th>
								<td>
									<?php static::print_strings_table( $name_string, $term->name, $term->term_id );?>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Description' ); ?></th>
								<td>
									<?php static::print_strings_table( $desc_string, $term->description, $term->term_id );?>
								</td>
							</tr>
						</tbody>
					</table>
					<?php endforeach; ?>
				<?php endforeach; ?>
				<?php endif; ?>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Print a strings editor table.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::languages() to loop through each language.
	 * @uses Registry::get() to retrieve the default language ID.
	 * @uses Localizer::get_string_values() to get the available values for the current string.
	 * @uses Settings::build_field() to build the input/textarea for editing the string.
	 *
	 * @param object $string      The string settings object.
	 * @param string $unlocalized Optional The unlocalized value of the string.
	 * @param int    $object_id   Optional The object id to get values for (default 0).
	 */
	protected static function print_strings_table( $string, $unlocalized, $object_id = 0 ) {
		$default_language = Registry::get( 'default_language' );
		$localized = Localizer::get_string_values( $string->key, $object_id );
		?>
		<table class="nl-strings-table">
			<thead class="screen-reader-text">
				<th><?php _e( 'Language' ); ?></th>
				<th><?php _e( 'Localized Value' ); ?></th>
			</thead>
			<tbody>
				<?php foreach ( Registry::languages() as $language ) : ?>
				<tr>
					<?php
					$is_default_language = $language->id == $default_language;

					$id = sprintf( '%s-%d-%d', $string->key, $language->id, $object_id );
					$name = sprintf( 'nlingual_strings[%s][%d][%d]', $string->key, $language->id, $object_id );

					if ( $is_default_language ) {
						$value = $unlocalized;
					} else {
						$value = $localized[ $language->id ];
					}

					// Build the field parameters
					$field = array(
						'name' => $is_default_language ? '' : $name,
						'type' => $string->input ?: 'text',
						'data' => array(
							'id' => $id,
							'readonly' => $is_default_language,
						),
					);
					?>
					<th scope="row">
						<label for="<?php echo $id; ?>"><?php echo $language->system_name; ?></label>
					</th>
					<td>
						<?php echo Settings::build_field( $field, $value ); ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Output for the cloning options section.
	 *
	 * @sicne 2.0.0
	 */
	public static function settings_section_cloning() {
		?>
		<p><?php _e( 'When creating a new translation of an existing post (i.e. a clone), what details should be cloned?' ); ?></p>
		<?php
	}
}