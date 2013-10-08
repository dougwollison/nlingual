<?php
// ==================== //
//	Menus Screen Hooks  //
// ==================== //

/**
 * admin_head action.
 *
 * Adds special metabox to the nav menus screen.
 *
 * @since 1.0.0
 */
function nLingual_special_metaboxes(){
	add_meta_box(
		'add-langlink',
		'Language Links',
		'nLingual_add_langlinks',
		'nav-menus',
		'side'
	);
}
add_action('admin_head', 'nLingual_special_metaboxes');

/**
 * add-langlink metabox callback.
 *
 * Prints out a page selector like interface for adding special language links
 * (the current page in another language) to the admin menu.
 *
 * @since 1.0.0
 *
 * @global int $nav_menu_selected_id The id of the current menu being edited.
 *
 * @uses nL_languages()
 */
function nLingual_add_langlinks(){
	global $nav_menu_selected_id;

	?>
	<div class="posttypediv" id="langlink">
		<p>These links will go to the respective language versions of the current URL.</p>
		<div id="tabs-panel-langlink-all" class="tabs-panel tabs-panel-active">
			<ul id="pagechecklist-most-recent" class="categorychecklist form-no-clear">
			<?php $i = -1; foreach( nL_languages() as $lang ):?>
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
				<a href="/wp-admin/nav-menus.php?langlink-tab=all&amp;selectall=1#langlink" class="select-all">Select All</a>
			</span>

			<span class="add-to-menu">
				<input type="submit"<?php wp_nav_menu_disabled_check( $nav_menu_selected_id ); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( __( 'Add to Menu' ) ); ?>" name="add-post-type-menu-item" id="submit-langlink" />
				<span class="spinner"></span>
			</span>
		</p>
	</div>
	<?php
}