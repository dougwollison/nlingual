<?php
namespace nLingual;

/**
 * nLingual Backend Functionality
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

class Backend extends Functional {
	// =========================
	// ! Properties
	// =========================

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
	// ! Methods
	// =========================

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 */
	public static function register_hooks() {
		// Theme Setup Actions
		static::add_action( 'after_setup_theme', 'register_localized_nav_menus', 999 );
		static::add_action( 'widgets_init', 'register_localized_sidebars', 999 );

		// Menu Editor Metabox
		static::add_action( 'admin_head', 'add_nav_menu_metabox' );
	}

	// =========================
	// ! Theme Setup Methods
	// =========================

	/**
	 * Shared logic for nav menu and sidebar localizing.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::is_feature_localizable() to check for support.
	 * @uses Registry::languages() to loop through all registered languages.
	 * @uses Registry::is_location_localizable() to check for support.
	 * @uses Registry::cache_set() to store the original global value.
	 *
	 * @param string $type   The type of location being localized (singular).
	 * @param string $global The global variable name to be edited.
	 */
	protected static function register_localized_locations( $type, $global ) {
		global $$global;
		$list =& $$global;

		// Abort if not supported
		if ( ! Registry::is_feature_localizable( "{$type}_locations", $list ) ) {
			return;
		}

		// Build a new nav menu list; with copies of each menu for each language
		$localized_locations = array();
		foreach ( $list as $id => $data ) {
			foreach ( Registry::languages() as $lang ) {
				// Check if this location specifically supports localizing
				if ( Registry::is_location_localizable( $type, $id ) ) {
					$new_id = $id . '-lang' . $lang->id;
					$name_postfix = ' (' . $lang->system_name . ')';
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
			}
		}

		// Cache the old version of the menus for reference
		Registry::cache_set( 'vars', $global, $list );

		// Replace the registered nav menu array with the new one
		$list = $localized_locations;
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
		static::register_localized_locations( 'nav_menu', '_wp_registered_nav_menus' );
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
		static::register_localized_locations( 'sidebar', 'wp_registered_sidebars' );
	}

	// =========================
	// ! Menu Editor Metabox
	// =========================

	/**
	 * Adds a new metabox to the menu editor for adding language links.
	 *
	 * @since 2.0.0
	 */
	public static function add_nav_menu_metabox() {
		add_meta_box(
			'add-nl_langlink', // metabox id
			__( 'Language Links', NL_TXTDMN ), // title
			array( get_called_class(), 'do_nav_menu_metabox' ), // callback
			'nav-menus', // screen
			'side' // context
		);
	}

	/**
	 * The language links metabox.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::languages() to loop through all registered languages.
	 */
	public static function do_nav_menu_metabox() {
		global $nav_menu_selected_id;
		?>
		<div class="posttypediv" id="nl_langlink">
			<p><?php _e( 'These links will go to the respective language versions of the current URL.', NL_TXTDMN );?></p>
			<div id="tabs-panel-nl_langlink-all" class="tabs-panel tabs-panel-active">
				<ul id="pagechecklist-most-recent" class="categorychecklist form-no-clear">
				<?php $i = -1; foreach ( Registry::languages() as $lang ):?>
					<li>
						<label class="menu-item-title">
							<input type="checkbox" class="menu-item-checkbox" name="menu-item[<?php echo $i?>][menu-item-object-id]" value="-1">
							<?php echo $lang->system_name?>
						</label>
						<input type="hidden" class="menu-item-type" name="menu-item[<?php echo $i?>][menu-item-type]" value="langlink">
						<input type="hidden" class="menu-item-title" name="menu-item[<?php echo $i?>][menu-item-title]" value="<?php echo $lang->native_name?>">
						<input type="hidden" class="menu-item-url" name="menu-item[<?php echo $i?>][menu-item-object]" value="<?php echo $lang->slug?>">
					</li>
				<?php $i--; endforeach;?>
				</ul>
			</div>

			<p class="button-controls">
				<span class="list-controls">
					<a href="/wp-admin/nav-menus.php?langlink-tab=all&amp;selectall=1#nl_langlink" class="select-all">Select All</a>
				</span>

				<span class="add-to-menu">
					<input type="submit"<?php wp_nav_menu_disabled_check( $nav_menu_selected_id ); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( __( 'Add to Menu' ) ); ?>" name="add-post-type-menu-item" id="submit-nl_langlink" />
					<span class="spinner"></span>
				</span>
			</p>
		</div>
		<?php
	}
}

// Initialize
Backend::init();