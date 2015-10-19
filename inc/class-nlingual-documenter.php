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
	 * A directory of all help tabs available.
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
				'requests' => 'Request Handling',
			),
			'sidebar' => true,
		),
		'localizables' => array(
			'tabs' => array(
				'overview' => 'Overview',
			),
			'sidebar' => true,
		),
		'sync' => array(
			'tabs' => array(
				'overview' => 'Overview',
				'cloning' => 'Cloning Rules',
			),
			'sidebar' => true,
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
				'taxonomies' => 'Taxonomies',
			),
			'sidebar' => false,
		),
		'post-translation' => array(
			'tabs' => array(
				'translation' => 'Translation',
			),
			'sidebar' => true,
		),
		'term-localization' => array(
			'tabs' => array(
				'localization' => 'Localization',
			),
			'sidebar' => true,
		),
	);

	/**
	 * An index of screens registered for help tabs.
	 *
	 * @access protected (static)
	 *
	 * @var array
	 */
	protected static $registered_screens = array();

	// =========================
	// ! Hook Registration
	// =========================

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 */
	public static function register_hooks() {
		static::add_action( 'current_screen', 'setup_help_tab' );
	}

	// =========================
	// ! Help Tab Registration
	// =========================

	/**
	 * Register a help tab for a screen.
	 *
	 * @since 2.0.0
	 *
	 * @uses Documenter::$registered_screens to store the screen and tab IDs.
	 *
	 * @param string $screen The screen ID to add the tab to.
	 * @param string $tab    The tab ID to add to the screen.
	 */
	public static function register_help_tab( $screen, $tab ) {
		static::$registered_screens[ $screen ] = $tab;
	}

	/**
	 * Register help tabs for multiple screens.
	 *
	 * @since 2.0.0
	 *
	 * @uses Documenter::register_help_tab() to register each screen/tab.
	 *
	 * @param string $screens An array of screen=>tab IDs to register.
	 */
	public static function register_help_tabs( $screens ) {
		foreach ( $screens as $screen => $tab ) {
			static::register_help_tab( $screen, $tab );
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
	 * @param string $tabset The set the tab belongs to.
	 * @param string $tab    The ID of the tab to get.
	 *
	 * @return string The HTML of the help tab.
	 */
	protected static function get_tab_content( $tabset, $tab ) {
		// Sanitize JUST in case...
		$tabset = sanitize_file_name( $tabset );
		$tab = sanitize_file_name( $tab );

		// Get the current locale
		$locale = get_locale();

		// The directory to find the files in
		$tabset_dir = NL_DIR . '/doc/' . $tabset;

		// Fail if the screen's folder does not exist
		if ( ! file_exists( $tabset_dir ) ) {
			return null;
		}

		// Check if a localized version exists
		$locale_dir = $tabset_dir . '/' . $locale;
		if ( file_exists( $locale_dir) ) {
			$tabset_dir = $locale_dir;
		}

		// The expected location of the tab's file
		$tab_file = $tabset_dir . '/' . $tab . '.php';

		// Fail if the tab's file does not exist
		if ( ! file_exists( $tab_file ) ) {
			return null;
		}

		// Get the contents of the file
		ob_start();
		include( $tab_file );
		$content = ob_get_clean();

		// Run it through wpautop and return it
		return wpautop( $content );
	}

	// =========================
	// ! Help Tab Output
	// =========================

	/**
	 * Handle setup of the help tab for the current screen.
	 *
	 * @since 2.0.0
	 *
	 * @uses Documenter::$registered_screens to get the tab set ID.
	 * @uses Documenter::$directory to retrieve the help tab settings.
	 */
	public static function setup_help_tab() {
		// Get the screen object
		$screen = get_current_screen();

		// Abort if no help tab is registered for this screen
		if ( ! isset( static::$registered_screens[ $screen->id ] ) ) {
			return;
		}

		// Get the help tabset
		$help_id = static::$registered_screens[ $screen->id ];

		// Fail if no matching help tab exists
		if ( ! isset( static::$directory[ $help_id ] ) ) {
			return;
		}

		// Get the help info for this page
		$help = static::$directory[ $help_id ];

		// Add each tab defined
		foreach ( $help['tabs'] as $tab => $title ) {
			$screen->add_help_tab( array(
				'id' => $plugin_page . '-' . $tab,
				'title' => __( $title ),
				'content' => static::get_tab_content( $help_id, $tab ),
			) );
		}

		// Add sidebar if enabled
		if ( $help['sidebar'] ) {
			$screen->set_help_sidebar( static::get_tab_content( $help_id, 'sidebar' ) );
		}
	}
}