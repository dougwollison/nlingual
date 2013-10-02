<?php
// ======================== //
//	Save/Delete Post Hooks  //
// ======================== //

/*
 * Save post hook for saving and updating translation links
 */
add_action('save_post', 'nLingual_save_post', 999);
function nLingual_save_post($post_id){
	global $wpdb;

	// Abort if doing auto save or it's a revision
	if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	elseif(wp_is_post_revision($post_id)) return;

	$post_type = $_POST['post_type'];

	// Abort if they don't have permission to edit posts/pages
	if($post_type == 'page' && !current_user_can('edit_page', $post_id)) return;
	elseif($post_type == 'page' && !current_user_can('edit_page', $post_id)) return;

	// Set the language if nLingual_language nonce is verified
	if(isset($_POST['nL_lang']) && wp_verify_nonce($_POST['nL_lang'], 'nLingual_set_language') && isset($_POST['language'])){
		nL_set_post_lang($post_id, $_POST['language']);
	}

	// Update translations if nLingual_translations nonce is verified
	if(isset($_POST['nL_link']) && wp_verify_nonce($_POST['nL_link'], 'nLingual_set_translations') && isset($_POST['translations'])){
		nL_associate_posts($post_id, $_POST['translations']);
	}

	// Loop through the sync options, and syncronize the fields with it's associated posts
	$associated = nL_associated_posts($post_id);

	if($data_fields = nL_sync_rules($post_type, 'data')){
		$ids = implode(',', $associated);
		$post = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID = %d", $post_id));

		$changes = array();
		foreach($data_fields as $field){
			$changes[] = $wpdb->prepare("$field = %s", $post->$field);
		}
		$changes = implode(',', $changes);

		// Run the update
		$wpdb->query("UPDATE $wpdb->posts SET $changes WHERE ID IN($ids)");
	}
	if($taxonomies = nL_sync_rules($post_type, 'tax')){
		foreach($taxonomies as $taxonomy){
			$terms = get_the_terms($post_id, $taxonomy);
			if(is_object($terms)) continue; // invalid taxonomy, abort

			if(is_array($terms)){
				$terms = array_map(function($term){
					return intval($term->term_id);
				}, $terms);
			}else{
				$terms = null;
			}

			foreach($associated as $id){
				wp_set_object_terms($id, $terms, $taxonomy);
			}
		}
	}
	if($meta_fields = nL_sync_rules($post_type, 'meta')){
		foreach($meta_fields as $field){
			$data = get_post_meta($post_id, $field, true);
			foreach($associated as $id){
				update_post_meta($id, $field, $data);
			}
		}
	}
}

/*
 * Delete post hook for deleting translation links or the sister posts (depending on settings)
 */
add_action('deleted_post', 'nLingual_deleted_post');
function nLingual_deleted_post($post_id){
	// Delete the language link
	delete_post_lang($post_id);
	
	if(nL_get_option('delete_sisters')){
		foreach(nL_associated_posts($post_id) as $post_id){
			wp_delete_post($post_id, true);
		}
	}
}

/*
 * Trash post hook for moving sister posts to the trash
 */
add_action('trashed_post', 'nLingual_trashed_post');
function nLingual_trashed_post($post_id){
	foreach(nL_associated_posts($post_id) as $post_id){
		wp_trash_post($post_id);
	}
}

/*
 * Untrash post hook for restoring sister posts to the trash
 */
add_action('untrashed_post', 'nLingual_untrashed_post');
function nLingual_untrashed_post($post_id){
	foreach(nL_associated_posts($post_id) as $post_id){
		wp_untrash_post($post_id);
	}
}

/*
 * Bulk edit interception
 */
add_action('admin_init', 'nLingual_bulk_edit');
function nLingual_bulk_edit(){
	if(isset($_GET['bulk_edit'])
	&& isset($_GET['nL_lang'])
	&& $_GET['language'] != '-1'
	&& wp_verify_nonce($_GET['nL_lang'], 'nLingual_set_language')){
		foreach((array) $_GET['post'] as $post_id){
			nL_set_post_lang($post_id, $_GET['language']);
		}
	}
}