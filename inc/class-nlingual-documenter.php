<?php
namespace nLingual;

/**
 * nLingual Documenter Funtionality
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

class Documenter extends Functional {
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
	 * A directory of all help info for the admin page.
	 *
	 * @since 2.0.0
	 *
	 * @access protected (static)
	 *
	 * @var array
	 */
	protected static $directory = array(
		'options' => array(
			'tabs' => array(
				'overview' => 'Overview',
			),
			'sidebar' => true,
		),
		'localizables' => array(
			'tabs' => array(
				'overview' => 'Overview',
			),
			'sidebar' => false,
		),
		'sync' => array(
			'tabs' => array(
				'overview' => 'Overview',
			),
			'sidebar' => false,
		),
		'languages' => array(
			'tabs' => array(
				'overview' => 'Overview',
			),
			'sidebar' => false,
		),
		'strings' => array(
			'tabs' => array(
				'overview' => 'Overview',
			),
			'sidebar' => false,
		),
	);

	// =========================
	// ! Hook Registration
	// =========================

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 */
	public static function register_hooks() {
	}

	// =========================
	// ! Help Tab Registration
	// =========================

	/**
	 * Setup a help tab for adding to the specified screen.
	 *
	 * @since 2.0.0
	 *
	 * @param string $screen   The screen to load help information from.
	 * @param string $hookname The hook this page will be loaded by.
	 */
	public static function add_help_tab( $screen, $hookname ) {
		static::add_action( 'load-' . $hookname, 'do_help_tabs' );
	}

	/**
	 * Setup multiple help tabs.
	 *
	 * @since 2.0.0
	 *
	 * @param array $screens The screens => hooknames to setup with.
	 */
	public static function add_help_tabs( $screens ) {
		foreach ( $screens as $screen => $hookname ) {
			static::add_help_tab( $screen, $hookname );
		}
	}

	// =========================
	// ! Help Tab Content
	// =========================

	/**
	 * Load the HTML for the specified help tab.
	 *
	 * @since 2.0.0
	 *
	 * @param string $screen The screen the tab belongs to.
	 * @param string $tab    The ID of the tab to get.
	 *
	 * @return string The HTML of the help tab.
	 */
	protected static function get_tab_content( $screen, $tab ) {
		// Sanitize JUST in case...
		$screen = sanitize_file_name( $screen );
		$tab = sanitize_file_name( $tab );

		// Get the current locale
		$locale = get_locale();

		// The directory to find the files in
		$screen_dir = NL_DIR . '/doc/' . $screen;

		// Fail if the screen's folder does not exist
		if ( ! file_exists( $screen_dir ) ) {
			return null;
		}

		// Check if a localized version exists
		$locale_dir = $screen_dir . '/' . $locale;
		if ( file_exists( $locale_dir) ) {
			$screen_dir = $locale_dir;
		}

		// The expected location of the tab's file
		$tab_file = $screen_dir . '/' . $tab . '.html';

		// Fail if the tab's file does not exist
		if ( ! file_exists( $tab_file ) ) {
			return null;
		}

		// Get the contents of the file
		$content = file_get_contents( $tab_file );

		// Run it through wpautop and return it
		return wpautop( $content );
	}

	// =========================
	// ! Help Tab Output
	// =========================

	/**
	 * Handle setting up the help tabs and sidebar for a page.
	 *
	 * @since 2.0.0
	 *
	 * @global $plugin_page The slug of the current admin page.
	 */
	public static function do_help_tabs() {
		global $plugin_page;

		// Get the un-namespaced page name
		$page = str_replace( 'nlingual-', '', $plugin_page );

		// Fail if no help info exists for this page
		if ( ! isset( static::$directory[ $page ] ) ) {
			return;
		}

		// Get the help info for this page
		$help = static::$directory[ $page ];

		// Get the screen object
		$screen = get_current_screen();

		// Add each tab defined
		foreach ( $help['tabs'] as $tab => $title ) {
			$screen->add_help_tab( array(
				'id' => $plugin_page . '-' . $tab,
				'title' => __( $title ),
				'content' => static::get_tab_content( $page, $tab ),
			) );
		}

		// Add sidebar if enabled
		if ( $help['sidebar'] ) {
			$screen->set_help_sidebar( static::get_tab_content( $page, 'sidebar' ) );
		}
	}
}