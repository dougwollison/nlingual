<?php
// ===================== //
//	Editor Screen Hooks  //
// ===================== //

/*
 * Add posts filter for language
 */
add_action('restrict_manage_posts', 'nLingual_manage_post_language_filter');
function nLingual_manage_post_language_filter(){
	global $typenow;
	if(nL_post_type_supported($typenow)):
		$selected = isset($_REQUEST['language']) ? $_REQUEST['language'] : '';
		?>
		<select name="language" class="postform">
			<option value=""><?php _e('Show all languages', NL_TXTDMN)?></option>
			<?php
			foreach(nL_languages() as $lang){
			    echo '<option value="'.$lang->slug.'"'.($_GET['language'] == $lang->slug ? ' selected' : '').'>'.$lang->system_name.'</option>';
			}
			?>
		</select>
	<?php endif;
}

/*
 * Add language column to editor tables
 */
foreach(nL_post_types() as $post_type){
	add_filter("manage_{$post_type}_posts_columns", 'nL_add_language_column', 999);
	add_action("manage_{$post_type}_posts_custom_column", 'nL_do_language_column', 10, 2);
}
function nL_add_language_column($columns){
	$columns['language'] = __('Language', NL_TXTDMN);

	return $columns;
}
function nL_do_language_column($column, $post_id){
	if($column == 'language'){
		// Print out the language it's in
		$lang = nL_get_post_lang($post_id);

		printf('<input type="hidden" value="%s">', $lang);

		if(!$lang){
			_e('None', NL_TXTDMN);
			return;
		}

		printf('<strong>%s</strong>', nL_get_lang('name', $lang));

		if($associated = nL_associated_posts($post_id)){
			foreach($associated as $lang => $pid){
				printf('<br>%s: <a href="%s">%s</a> | <a href="%s">View</a>',
					nL_get_lang('name', $lang),
					admin_url("/post.php?post=$pid&action=edit"),
					get_the_title($pid),
					get_permalink($pid)
				);
			}
		}
	}
}

/*
 * Quick edit field for language
 */
add_action('quick_edit_custom_box', 'nLingual_quick_edit_box', 10, 2);
function nLingual_quick_edit_box($column, $post_type){
	if(!nL_post_type_supported($post_type)) return;

	if($column == 'language'): wp_nonce_field('nLingual_set_language', 'nL_lang');
	?>
    <fieldset class="inline-edit-col-right inline-edit-<?php echo $post_type?>">
      <div class="inline-edit-col column-<?php echo $column ?>">
        <label class="inline-edit-group">
        	<?php _e('Language', NL_TXTDMN)?>
        	<select name="language">
				<option value="-1"><?php _e('None', NL_TXTDMN)?></option>
			<?php foreach(nL_languages() as $lang):?>
				<option value="<?php echo $lang->slug?>"><?php echo $lang->system_name?></option>
			<?php endforeach;?>
			</select>
        </label>
      </div>
    </fieldset>
    <?php
    endif;
}

add_action('bulk_edit_custom_box', 'nLingual_bulk_edit_box', 10, 2);
function nLingual_bulk_edit_box($column, $post_type){
	if(!nL_post_type_supported($post_type)) return;

	if($column == 'language'): wp_nonce_field('nLingual_set_language', 'nL_lang');
	?>
    <fieldset class="inline-edit-col-right inline-edit-<?php echo $post_type?>">
      <div class="inline-edit-col column-<?php echo $column ?>">
        <label class="inline-edit-group">
        	<?php _e('Language', NL_TXTDMN)?>
        	<select name="language">
				<option value="-1">&mdash; <?php _e('No change', NL_TXTDMN)?> &mdash;</option>
			<?php foreach(nL_languages() as $lang):?>
				<option value="<?php echo $lang->slug?>"><?php echo $lang->system_name?></option>
			<?php endforeach;?>
			</select>
        </label>
      </div>
    </fieldset>
    <?php
    endif;
}