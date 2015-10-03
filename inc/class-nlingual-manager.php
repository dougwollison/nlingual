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
			__( 'Translation Options', NL_TXTDMN ), // page title
			_x( 'Translation', 'menu title', NL_TXTDMN ), // menu title
			'manage_options', // capability
			'nlingual-options', // slug
			array( static::$name, 'settings_page' ), // callback
			'dashicons-translation' // icon
		);

		add_submenu_page(
			'nlingual-options', // parent
			__( 'Manage Localizable Objects', NL_TXTDMN ), // page title
			__( 'Localizables',  NL_TXTDMN ), // menu title
			'manage_options', // capability
			'nlingual-l10s', // slug
			array( static::$name, 'settings_page' ) // callback
		);

		add_submenu_page(
			'nlingual-options', // parent
			__( 'Post Synchronization', NL_TXTDMN ), // page title
			__( 'Sync Options', NL_TXTDMN ), // menu title
			'manage_options', // capability
			'nlingual-sync', // slug
			array( static::$name, 'settings_page' ) // callback
		);

		add_submenu_page(
			'nlingual-options', // parent
			__( 'Manage Languages', NL_TXTDMN ), // page title
			_x( 'Languages', 'menu title', NL_TXTDMN ), // menu title
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
				'title' => __( 'Default Language', NL_TXTDMN ),
				'help'  => null,
				'type'  => 'select',
				'data'  => Registry::languages()->export( 'system_name' ),
			),
			'skip_default_l10n' => array(
				'title' => __( 'Skip Localization for Default Language?', NL_TXTDMN ),
				'help'  => __( 'URLs for the default language will be unmodified (e.g. /english-page/ vs /en/english-page/).', NL_TXTDMN ),
				'type'  => 'checkbox',
			),
		), 'options' );

		add_settings_section( 'redirection', __( 'Request Handling', NL_TXTDMN ), null, 'nlingual-options' );

		Settings::add_fields( array(
			'query_var' => array(
				'title' => __( 'Query Variable', NL_TXTDMN ),
				'help'  => __( 'The variable name to check for when handling language requests (recommended: "language")', NL_TXTDMN ),
				'type'  => 'input',
			),
			'redirection_method' => array(
				'title' => __( 'Redirection Method', NL_TXTDMN ),
				'help'  => __( 'What style should be used for the translated URLs?', NL_TXTDMN ),
				'type'  => 'radiolist',
				'data'  => array(
					NL_REDIRECT_USING_GET    => __( 'HTTP query (e.g. <code>%s/?%s=%s</code>)', NL_TXTDMN ),
					NL_REDIRECT_USING_PATH   => __( 'Path prefix (e.g. <code>%s/%s/</code>)', NL_TXTDMN ),
					NL_REDIRECT_USING_DOMAIN => __( 'Subdomain (e.g. <code>%s.%s</code>)', NL_TXTDMN ),
				),
			),
			'postlang_override' => array(
				'title' => __( 'Post Language Override', NL_TXTDMN ),
				'help'  => __( 'Should the requested post/page/object’s language override the one requested?', NL_TXTDMN ),
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
				'title' => __( 'Post Types', NL_TXTDMN ),
				'help'  => __( 'What post types should support language and translations?', NL_TXTDMN ),
				'type'  => 'checklist',
				'data'  => $post_types,
			),
			'localizables[nav_menu_locations]' => array(
				'title' => __( 'Menu Locations', NL_TXTDMN ),
				'help'  => __( 'Should any navigation menus have versions for each language?', NL_TXTDMN ),
				'type'  => 'checklist',
				'data'  => $nav_locations,
			),
			'localizables[sidebar_locations]' => array(
				'title' => __( 'Sidebar Locations', NL_TXTDMN ),
				'help'  => __( 'Should any widget areas have versions for each language?', NL_TXTDMN ),
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
			$sync_fields[ "sync_rules[post_type][{$post_type}]" ] = $field;
			$clone_fields[ "clone_rules[post_type][{$post_type}]" ] = $field;
		}

		Settings::add_fields( $sync_fields, 'sync' );

		add_settings_section( 'cloning', __( 'New Translations', NL_TXTDMN ), array( static::$name, 'settings_section_cloning' ), 'nlingual-sync' );

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