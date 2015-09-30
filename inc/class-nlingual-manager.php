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
	}

	// =========================
	// ! Utilities
	// =========================

	/**
	 * Sanitize the sync rules array
	 *
	 * @since 2.0.0
	 *
	 * @param array $rules The rules to be sanitized.
	 */
	public static function sanitize_rules( $rules ) {
		// Loop through each rule section
		foreach ( $rules as $section => &$_rules ) {
			// Loop through each rule type
			foreach ( $_rules as $type => &$values ) {
				// If values is a string...
				if ( is_string( $values ) ) {
					// Split it by line
					$values = preg_split( '/[\n\r]+/', trim( $values ), 0, PREG_SPLIT_NO_EMPTY );
					// And trim each line
					$values = array_walk( $values, 'trim' );
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
			__( 'Translation Options', NLTXTDMN ), // page title
			_x( 'Translation', 'menu title', NLTXTDMN ), // menu title
			'manage_options', // capability
			'nlingual-options', // slug
			array( static::$name, 'settings_page' ), // callback
			'dashicons-translation' // icon
		);

		add_submenu_page(
			'nlingual-options', // parent
			__( 'Manage Localizable Objects', NLTXTDMN ), // page title
			__( 'Localizables',  NLTXTDMN ), // menu title
			'manage_options', // capability
			'nlingual-l10s', // slug
			array( static::$name, 'settings_page' ) // callback
		);

		add_submenu_page(
			'nlingual-options', // parent
			__( 'Post Synchronization', NLTXTDMN ), // page title
			__( 'Sync Options', NLTXTDMN ), // menu title
			'manage_options', // capability
			'nlingual-sync', // slug
			array( static::$name, 'settings_page' ) // callback
		);

		add_submenu_page(
			'nlingual-options', // parent
			__( 'Manage Languages', NLTXTDMN ), // page title
			_x( 'Languages', 'menu title', NLTXTDMN ), // menu title
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

	// =========================
	// ! Settings Fields Setup
	// =========================

	/**
	 * Fields for the Translations page.
	 *
	 * @since 2.0.0
	 */
	protected static function setup_options_fields() {
		add_settings_section( 'default', null, null, 'nlingual-options' );

		Settings::add_fields( array(
			'default_language' => array(
				'title' => __( 'Default Language', NLTXTDMN ),
				'help'  => null,
				'type'  => 'select',
				'data'  => Registry::languages()->export( 'system_name' ),
			),
			'skip_default_l10n' => array(
				'title' => __( 'Skip Localization for Default Language?', NLTXTDMN ),
				'help'  => __( 'URLs for the default language will be unmodified (e.g. /english-page/ vs /en/english-page/).', NLTXTDMN ),
				'type'  => 'checkbox',
			),
		), 'options' );

		add_settings_section( 'redirection', __( 'Request Handling', NLTXTDMN ), null, 'nlingual-options' );

		Settings::add_fields( array(
			'query_var' => array(
				'title' => __( 'Query Variable', NLTXTDMN ),
				'help'  => __( 'The variable name to check for when handling language requests (recommended: "language")', NLTXTDMN ),
				'type'  => 'input',
			),
			'redirection_method' => array(
				'title' => __( 'Redirection Method', NLTXTDMN ),
				'help'  => __( 'What style should be used for the translated URLs?', NLTXTDMN ),
				'type'  => 'radiolist',
				'data'  => array(
					NL_REDIRECT_USING_GET    => __( 'HTTP query (e.g. <code>%s/?%s=%s</code>)', NLTXTDMN ),
					NL_REDIRECT_USING_PATH   => __( 'Path prefix (e.g. <code>%s/%s/</code>)', NLTXTDMN ),
					NL_REDIRECT_USING_DOMAIN => __( 'Subdomain (e.g. <code>%s.%s</code>)', NLTXTDMN ),
				),
			),
			'postlang_override' => array(
				'title' => __( 'Post Language Override', NLTXTDMN ),
				'help'  => __( 'Should the requested post/page/object’s language override the one requested?', NLTXTDMN ),
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

		Settings::add_fields( array(
			'post_types' => array(
				'title' => __( 'Post Types', NLTXTDMN ),
				'help'  => __( 'What post types should support language and translations?', NLTXTDMN ),
				'type'  => 'checklist',
				'data'  => $post_types,
			),
			'localizables[nav_menu_locations]' => array(
				'title' => __( 'Menu Locations', NLTXTDMN ),
				'help'  => __( 'Should any navigation menus have versions for each language?', NLTXTDMN ),
				'type'  => 'checklist',
				'data'  => $nav_locations,
			),
			'localizables[sidebar_locations]' => array(
				'title' => __( 'Sidebar Locations', NLTXTDMN ),
				'help'  => __( 'Should any widget areas have versions for each language?', NLTXTDMN ),
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
		add_settings_section( 'default', null, null, 'nlingual-sync' );

		$post_types = Registry::get( 'post_types' );
		foreach ( $post_types as $post_type ) {
			$field = array(
				'title' => get_post_type_object( $post_type )->labels->name,
				'type'  => 'sync_settings',
				'data'  => $post_type,
			);
			$sync_fields[ "sync_rules[posts][{$post_type}]" ] = $field;
			$clone_fields[ "clone_rules[posts][{$post_type}]" ] = $field;
		}

		Settings::add_fields( $sync_fields, 'sync' );

		add_settings_section( 'cloning', __( 'New Translations', NLTXTDMN ), array( static::$name, 'settings_section_cloning' ), 'nlingual-sync' );

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

			<form method="post" action="options.php">
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
	 */
	public static function settings_page_languages() {
		global $plugin_page;
		?>
		<div class="wrap">
			<h1><?php echo get_admin_page_title(); ?></h1>

			<form method="post" action="options.php">
				<?php settings_fields( $plugin_page ); ?>
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