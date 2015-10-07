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
	 *
	 * @uses Registry::get() to retrieve enabled post types.
	 */
	public static function register_hooks() {
		// Post Changes
		static::add_filter( 'deleted_post', 'deleted_post' );

		// Script/Style Enqueues
		static::add_action( 'admin_enqueue_scripts', 'enqueue_assets' );

		// Theme Setup Actions
		static::add_action( 'after_setup_theme', 'register_localized_nav_menus', 999 );
		static::add_action( 'widgets_init', 'register_localized_sidebars', 999 );

		// Posts Screen Interfaces
		static::add_action( 'restrict_manage_posts', 'add_language_filter' );
		$post_types = Registry::get( 'post_types' );
		foreach ( $post_types as $post_type ) {
			static::add_filter( "manage_{$post_type}_posts_columns", 'add_language_column', 15 );
			static::add_action( "manage_{$post_type}_posts_custom_column", 'do_language_column', 10, 2 );
		}

		// Quick/Bulk Edit Interfaces
		static::add_action( 'quick_edit_custom_box', 'quick_edit_post_translation', 10, 2 );
		static::add_action( 'bulk_edit_custom_box', 'bulk_edit_post_language', 10, 2 );

		// Post Editor Interfaces
		static::add_action( 'add_meta_boxes', 'add_post_meta_box' );

		// Save hooks
		static::add_action( 'save_post', 'save_post_language' );
		static::add_action( 'save_post', 'save_post_translations' );

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
		wp_enqueue_script( 'nlingual-admin-js', plugins_url( 'js/admin.js', NL_SELF ), array( 'underscore', 'jquery-ui-sortable' ), '2.0.0' );

		// Localize the javascript
		wp_localize_script( 'nlingual-admin-js', 'nlingualL10n', array(
			'TranslationTitle'            => __( 'Enter the title for this translation.' ),
			'TranslationTitlePlaceholder' => __( 'Translate to %s: %s' ),
			'NewTranslationError'         => __( 'Error creating translation, please try again later or create one manually.' ),
			'NoPostSelected'              => __( 'No post selected to edit.' ),
			'NewTranslation'              => __( '[New]' ),
			'LocalizeThis'                => __( 'Localize This' ),
			'LocalizeFor'                 => __( 'Localize for %s' ),
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
					$new_id = $id . '-lang' . $lang->lang_id;
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
	// ! Posts Screen Interfaces
	// =========================

	/**
	 * Add <select> for filtering posts by language.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::is_post_type_supported() to check for support.
	 * @uses Registry::get() to retrieve the query var.
	 * @uses Registry::languages() to loop through all registered languages.
	 */
	public static function add_language_filter() {
		global $typenow, $wp_query;

		// Abort if current post type isn't supported
		if ( ! Registry::is_post_type_supported( $typenow ) ) {
			return;
		}

		// Get the query var and it's current value
		$query_var = Registry::get( 'query_var' );
		$current = $wp_query->get( $query_var );
		?>
		<select name="<?php echo $query_var; ?>" class="postform">
			<option value="-1"><?php _e( 'All Languages' ); ?></option>
			<?php
			foreach ( Registry::languages() as $language ) {
				$selected = $current == $language->lang_id;
				printf( '<option value="%s" %s>%s</option>', $language->lang_id, $selected ? 'selected' : '', $language->system_name );
			}
			?>
		</select>
		<?php
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
		$columns['nlingual'] = _x( 'Language' );
		return $columns;
	}

	/**
	 * Print the content of the language/translations column.
	 *
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

		// Start by printing out the language
		$language = Translator::get_post_language( $post_id );
		if ( ! $language ) {
			echo '<input type="hidden" class="nl-language" value="0" />';
			_e( 'None', 'no language' );
			return;
		} else {
			printf( '<input type="hidden" class="nl-language" value="%d" />', $language->lang_id );
			printf( '<strong>%s</strong>', $language->system_name );
		}

		// Now print out the translations
		$translations = Translator::get_post_translations( $post_id );
		if ( $translations ) {
			echo '<ul>';
			foreach ( $translations as $lang => $post ) {
				if ( $lang = Registry::languages()->get( $lang ) ) {
					echo '<li>';
					printf( '<input type="hidden" class="nl-translation-%d" value="%d" />', $lang->lang_id, $post );
					$link = sprintf( '<a href="%s" target="_blank">%s</a>', get_edit_post_link( $post ), get_the_title( $post ) );
					_efx( '%s: %s', 'language: title', $lang->system_name, $link );
					echo '<li>';
				}
			}
			echo '</ul>';
		}
	}

	/**
	 * Print out the quick-edit box for post language/translations.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Registry::is_post_type_supported() to check for support.
	 * @uses Registry::languages() to loop through the active languages.
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
		?>
		<br class="clear" />

		<fieldset class="inline-edit-col-left">
			<div class="inline-edit-col">
				<label>
					<span class="title"><?php _e( 'Language' );?></span>
					<select name="nlingual_language" id="nl_language">
						<option value="-1">&mdash; <?php _ex( 'None', 'no language' ); ?> &mdash;</option>
						<?php
						// Print the options
						foreach ( Registry::languages() as $language ) {
							printf( '<option value="%s">%s</option>', $language->lang_id, $language->system_name );
						}
						?>
					</select>
				</label>
			</div>
		</fieldset>

		<fieldset class="inline-edit-col-right" style="margin: 0;">
			<div class="inline-edit-col">
				<h4>Translations</h4>
				<?php foreach ( Registry::languages() as $language ) : ?>
				<label id="nl_translation_<?php echo $language->lang_id; ?>" class="nl-translation"  data-langid="<?php echo $language->lang_id; ?>">
					<span class="title"><?php echo $language->system_name;?></span>
					<select name="nlingual_translation[<?php echo $language->lang_id; ?>]">
						<option value="-1">&mdash; <?php _ex( 'None', 'no translation' ); ?> &mdash;</option>
						<?php
						// Get all posts in this language
						$posts = $wpdb->get_results( $wpdb->prepare( "
							SELECT p.ID, p.post_title
							FROM $wpdb->nl_translations AS t
							LEFT JOIN $wpdb->posts AS p ON (t.object_id = p.ID)
							WHERE t.object_type = 'post'
							AND t.lang_id = %d
							AND p.post_type = %s
						", $language->lang_id, $post_type ) );

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
		<?php

		// Nonce field for save validation
		wp_nonce_field( 'nlingual_post', '_nl_nonce', false );
	}

	/**
	 * Print out the bulk-edit box for post language.
	 *
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
		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<label>
					<span class="title"><?php _e( 'Language' );?></span>
					<select name="nlingual_language" id="nl_language">
						<option value="-1">&mdash; <?php _e( 'No Change' ); ?> &mdash;</option>
						<?php
						// Print the options
						foreach ( Registry::languages() as $language ) {
							printf( '<option value="%s">%s</option>', $language->lang_id, $language->system_name );
						}
						?>
					</select>
				</label>
			</div>
		</fieldset>
		<?php

		// Nonce field for save validation
		wp_nonce_field( 'nlingual_post', '_nl_nonce', false );
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
	 * @since 2.0.0
	 *
	 * @uses Registry:get() to retrieve the supported post types.
	 */
	public static function add_post_meta_box() {
		$post_types = Registry::get( 'post_types' );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'nlingual_translations', // id
				__( 'Language & Translations' ), // title
				array( get_called_class(), 'post_meta_box' ), // callback
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
	 * @uses Registry::languages() to get the languages to loop through.
	 * @uses Translator::get_object_language() to get the post's language.
	 * @uses Translator::get_post_translations() to get the post's translations.
	 *
	 * @param WP_Post $post The post being edited.
	 */
	public static function post_meta_box( $post ) {
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
			$lang_options[ $language->lang_id ] = $language->system_name;

			// Get all posts of this type for this language (excluding the current one)
			$post_options[ $language->lang_id ] = $wpdb->get_results( $wpdb->prepare( "
				SELECT p.ID, p.post_title
				FROM $wpdb->nl_translations AS t
				LEFT JOIN $wpdb->posts AS p ON (t.object_id = p.ID)
				WHERE t.object_type = 'post'
				AND t.lang_id = %d
				AND t.object_id != %d
				AND p.post_type = %s
			", $language->lang_id, $post->ID, $post->post_type ) );

			// Set translation to for this language to 0 if not present
			if ( ! isset( $translations[ $language->lang_id ] ) ) {
				$translations[ $language->lang_id ] = 0;
			}
		}
		?>
		<div class="nl-field nl-language-field">
			<label for="nl_language" class="nl-field-label"><?php _e( 'Language' ); ?></label>
			<select name="nlingual_language" id="nl_language" class="nl-input">
				<option value="-1">&mdash; <?php _ex( 'None', 'no language' ); ?> &mdash;</option>
				<?php
				// Print the options
				foreach ( $lang_options as $value => $label ) {
					$selected = $post_lang->lang_id == $value ? 'selected' : '';
					printf( '<option value="%s" %s>%s</option>', $value, $selected, $label );
				}
				?>
			</select>
		</div>

		<?php if ( $languages->count() > 1 ) : ?>
		<h4 class="nl-heading"><?php _e( 'Translations' ); ?></h4>
		<?php foreach ( $languages as $language ) : ?>
		<div id="nl_translation_<?php echo $language->lang_id; ?>" class="nl-field nl-translation" data-langid="<?php echo $language->lang_id?>">
			<label for="nl_translation_<?php echo $language->lang_id; ?>_input">
				<?php echo $language->system_name; ?>
				<button type="button" class="button button-small nl-edit-translation" data-url="<?php echo admin_url( $post_type->_edit_link . '&amp;action=edit' );?>"><?php _e( 'Edit' );?></button>
			</label>

			<select name="nlingual_translation[<?php echo $language->lang_id; ?>]" id="nl_translation_<?php echo $language->lang_id; ?>_input" class="nl-input">
				<option value="-1">&mdash; <?php _ex( 'None', 'no translation' ); ?> &mdash;</option>
				<option value="new" class="nl-new-translation"><?php _ef( '&mdash; New %s %s &mdash;', $language->system_name, $post_type->labels->singular_name ); ?></option>
				<?php
				// Print the options
				foreach ( $post_options[ $language->lang_id ] as $option ) {
					$selected = $translations[ $language->lang_id ] == $option->ID ? 'selected' : '';
					$label = $option->post_title;
					// If this post is already a translation of something, identify it as such.
					if ( Translator::get_post_translations( $option->ID ) ) {
						$label = _ex( '[Taken]' ) . ' ' . $label;
					}
					printf( '<option value="%s" %s>%s</option>', $option->ID, $selected, $label );
				}
				?>
			</select>
		</div>
		<?php endforeach; ?>
		<?php endif;

		// Nonce fields for save validation
		wp_nonce_field( 'nlingual_post', '_nl_nonce', false );
	}

	// =========================
	// ! Saving Post Data
	// =========================

	/**
	 * Save settings from the translations meta box.
	 *
	 * Handles language assignment, translation linking,
	 * and any enabled synchronizing with sister posts.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Translator::set_object_language() to assign/update the post's language.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public static function save_post_language( $post_id ) {
		global $wpdb;

		// Abort if doing auto save or it's a revision
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Check for the nonce and language
		if ( ! isset( $_REQUEST['_nl_nonce'] ) || ! isset( $_REQUEST['nlingual_language'] ) ) {
			return;
		}

		// Fail if nonce is invalid
		if ( ! wp_verify_nonce( $_REQUEST['_nl_nonce'], 'nlingual_post' ) ) {
			wp_die( __( 'Cheatin&#8217; uh?' ) );
		}

		// Assign the post to the language, fail if there's an error.
		if ( ! Translator::set_post_language( $post_id, $_REQUEST['nlingual_language'] ) ) {
			wp_die( __( 'That language does not exist.' ) );
		}
	}

	/**
	 * Save settings from the translations meta box.
	 *
	 * Handles translation linking, and any enabled synchronizing with sister posts.
	 *
	 * @since 2.0.0
	 *
	 * @uses Translator::set_object_translations() to assign the translations to the post.
	 * @uses Synchronizer::sync_post_with_sister() to handle post synchronizing.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public static function save_post_translations( $post_id ) {
		global $wpdb;

		// Abort if doing auto save or it's a revision
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Check for the nonce and translations list
		if ( ! isset( $_REQUEST['_nl_nonce'] )
		|| ! isset( $_REQUEST['nlingual_translation'] )
		|| ! is_array( $_REQUEST['nlingual_translation'] ) ) {
			return;
		}

		// Fail if nonce is invalid
		if ( ! wp_verify_nonce( $_REQUEST['_nl_nonce'], 'nlingual_post' ) ) {
			wp_die( __( 'Cheatin&#8217; uh?' ) );
		}

		// Assign the translations, fail if there's an error
		if ( ! Translator::set_post_translations( $post_id, $_REQUEST['nlingual_translation'] ) ) {
			wp_die( __( 'Error saving translations; one or more languages do not exist.' ) );
		}

		// Unhook this and the save language filter to prevent
		// infinite loop and uncessary language assignemnt
		static::remove_action( 'save_post', 'save_post_language' );
		static::remove_action( 'save_post', 'save_post_translations' );

		// Now synchronize the post's translations
		Synchronizer::sync_post_with_sisters( $post_id );

		// Rehook now that we're done
		static::add_action( 'save_post', 'save_post_language' );
		static::add_action( 'save_post', 'save_post_translations' );
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
			__( 'Language Links' ), // title
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
			<p><?php _e( 'These links will go to the respective language versions of the current URL.' );?></p>
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
			var NL_DEFAULT_LANG = <?php echo Registry::get( 'default_lang' ); ?>;
			var NL_LANGUAGES = <?php echo json_encode( Registry::languages()->export() ); ?>;
		</script>
		<?php
	}
}