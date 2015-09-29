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
	// ! Hook Registration
	// =========================

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 */
	public static function register_hooks() {
		// Post Changes
		static::add_filter( 'deleted_post', 'deleted_post', 10, 1 );

		// Script/Style Enqueues
		static::add_action( 'admin_enqueue_scripts', 'enqueue_assets' );

		// Theme Setup Actions
		static::add_action( 'after_setup_theme', 'register_localized_nav_menus', 999 );
		static::add_action( 'widgets_init', 'register_localized_sidebars', 999 );

		// Post Translations Meta Box
		static::add_action( 'add_meta_boxes', 'add_translations_meta_box' );

		// Menu Editor Meta Box
		static::add_action( 'admin_head', 'add_nav_menu_meta_box' );

		// JavaScript Variables
		static::add_action( 'admin_footer', 'print_javascript_vars' );
	}

	// =========================
	// ! Post Changes
	// =========================

	public static function deleted_post( $post_id ) {
		// Delete the language
		Translator::delete_post_language( $post_id );
	}

	// =========================
	// ! Script/Style Enqueues
	// =========================

	/**
	 * Enqueue necessary styles and scripts.
	 *
	 * @since 2.0.0
	 */
	public static function enqueue_assets(){
		// Get the current screen
		$screen = get_current_screen();

		// Admin styling
		wp_enqueue_style( 'nlingual-admin', plugins_url( 'css/admin.css', NL_SELF ), '2.0.0', 'screen' );

		// Admin javascript
		wp_enqueue_script( 'nlingual-admin-js', plugins_url( 'js/admin.js', NL_SELF ), array( 'jquery-ui-sortable' ), '2.0.0' );

		// Localize the javascript
		wp_localize_script( 'nlingual-admin-js', 'nlingualL10n', array(
			'TranslationTitle'            => __( 'Enter the title for this translation.', NLTXTDMN ),
			'TranslationTitlePlaceholder' => __( 'Translate to %s: %s', NLTXTDMN ),
			'NewTranslationError'         => __( 'Error creating translation, please try again later or create one manually.', NLTXTDMN ),
			'NoPostSelected'              => __( 'No post selected to edit.', NLTXTDMN ),
			'NewTranslation'              => __( '[New]', NLTXTDMN ),
		) );
	}

	// =========================
	// ! Menu/Sidebar Localization
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
	// ! Transtions Meta Box
	// =========================

	/**
	 * Add a meta box to the post edit screen.
	 *
	 * For setting language and associated translations
	 * for the enabled post types.
	 *
	 * @since 2.0.0
	 */
	public static function add_translations_meta_box() {
		$post_types = Registry::get( 'post_types' );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'nlingual_translations', // id
				__( 'Language & Translations', NLTXTDMN ), // title
				array( get_called_class(), 'do_translations_meta_box' ), // callback
				$post_type, // screen
				'side', // context
				'default' // priority
			);
		}
	}

	/**
	 * Output the content of the translations meta box.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Registry::languages() to get the langauges to loop through.
	 * @uses Translator::get_object_language() to get the post's language.
	 * @uses Translator::get_post_translations() to get the post's translations.
	 *
	 * @param WP_Post $post The post being edited.
	 */
	public static function do_translations_meta_box( $post ) {
		global $wpdb;

		// Get the language list
		$languages = Registry::languages();

		// Get the post's language
		$post_lang = Translator::get_post_language( $post->ID );

		// Get the post's translations
		$translations = Translator::get_post_translations( $post->ID );

		// Get the post's post type's object
		$post_type = get_post_type_object( $post->post_type );

		// Build the language and translation option lists
		$lang_options = array();
		$post_options = array();
		foreach ( $languages as $language ) {
			$lang_options[ $language->id ] = $language->system_name;

			// Get all posts of this type for this language (excluding the current one)
			$post_options[ $language->id ] = $wpdb->get_results( $wpdb->prepare( "
				SELECT t.object_id, p.post_title
				FROM $wpdb->nl_translations AS t
				LEFT JOIN $wpdb->posts AS p ON (t.object_id = p.ID)
				WHERE t.object_type = 'post'
				AND t.lang_id = %d
				AND t.object_id != %d
				AND p.post_type = %d
			", $language->id, $post->ID, $post->post_type ) );

			// Set translation to for this language to 0 if not present
			if ( ! isset( $translations[ $language->id ] ) ) {
				$translations[ $language->id ] = 0;
			}
		}
		?>
		<div class="nl-field nl-language-field">
			<label for="nlingual_language" class="nl-field-label"><?php _e( 'Language', NLTXTDMN ); ?></label>
			<select name="nlingual_language" id="nlingual_language" class="nl-input">
				<option value="-1"><?php _ex( '&mdash; None &mdash;', 'no language', NLTXTDMN ); ?></option>
				<?php
				// Print the options
				foreach ( $lang_options as $value => $label ) {
					$selected = $post_lang->id == $value ? 'selected' : '';
					printf( '<option value="%s" %s>%s</option>', $value, $selected, $label );
				}
				?>
			</select>
		</div>

		<?php if ( $languages->count() > 1 ) : ?>
		<h4 class="nl-heading"><?php _e( 'Translations', NLTXTDMN ); ?></h4>

		<?php foreach ( $languages as $language ) : ?>
		<div id="nlingual_lang<?php echo $language->id?>" class="nl-field nl-translation-field" data-langid="<?php echo $language->id?>">
			<label for="nlingual_translation_<?php echo $language->id; ?>">
				<?php echo $language->system_name; ?>
				<button type="button" class="button button-small nl-edit-translation" data-alt="<?php _e( 'Create', NLTXTDMN );?>" data-url="<?php echo admin_url( $post_type->_edit_link . '&amp;action=edit' );?>"><?php _e( 'Edit', NLTXTDMN );?></button>
			</label>

			<select name="nlingual_translation[<?php echo $language->id; ?>]" id="nlingual_translation_<?php echo $language->id; ?>" class="nl-input nl-translation">
				<option value="-1"><?php _ex( '&mdash; None &mdash;', 'no translation', NLTXTDMN ); ?></option>
				<option value="new" class="nl-new-translation"><?php _ef( '&mdash; New %s %s &mdash;', NLTXTDMN, $language->system_name, $post_type->labels->singular_name ); ?></option>
				<?php
				// Print the options
				foreach ( $post_options[ $language->id ] as $option ) {
					$selected = $translations[ $language->id ] == $option->object_id ? 'selected' : '';
					$label = $option->post_title;
					// If this post is already a translation of something, identify it as such.
					if ( Translator::get_post_translations( $option->object_id ) ) {
						$label = _ex( '[Taken]', NLTXTDMN ) . ' ' . $label;
					}
					printf( '<option value="%s" %s>%s</option>', $option->object_id, $selected, $label );
				}
				?>
			</select>
		</div>
		<?php endforeach; ?>

		<?php endif;
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
			'add-nl_langlink', // metabox id
			__( 'Language Links', NL_TXTDMN ), // title
			array( get_called_class(), 'do_nav_menu_meta_box' ), // callback
			'nav-menus', // screen
			'side' // context
		);
	}

	/**
	 * The language links meta box.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::languages() to loop through all registered languages.
	 */
	public static function do_nav_menu_meta_box() {
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

	// =========================
	// ! JavaScript Variables
	// =========================

	/**
	 * Print relevent variables for JavaScript.
	 *
	 * @since 2.0.0
	 */
	public static function print_javascript_vars() {
		?>
		<script>
			if(typeof admin_url === 'undefined'){
				var admin_url = '<?php echo admin_url(); ?>';
			}
			var NL_LANGUAGES = <?php echo json_encode( Registry::languages()->export() ); ?>
		</script>
		<?php
	}
}

// Initialize
Backend::init();