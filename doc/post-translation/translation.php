<?php
$post_type = get_current_screen()->post_type;
$singular = strtolower( get_post_type_object( $post_type )->labels->singular_name );
$plural = strtolower( get_post_type_object( $post_type )->labels->name );
?>
The <strong>Languages & Translation</strong> box allows you to assign a language to this <?php echo $singular; ?>, from the list of languages that have been registered <a href="<?php echo admin_url('admin.php?page=nlingual-languages'); ?>" target="_blank">here</a> for use. Once you assign a language, you can also assign the translated versions of the <?php echo $singular; ?> in each of the other languages.

If you don't have a translated version for a particular language yet, you can select the option to create a new one; a copy of the current post will be created automatically for you to work from.

When you save your changes, certain fields and settings will be copied to it’s sister translations, synchronizing them. The exact fields/settings that will be synchronized is controlled <a href="<?php echo admin_url('admin.php?page=nlingual-sync'); ?>" target="_blank">here</a>, along with what will be copied when creating a new translation (usually everything).

<?php require( NL_DIR . '/doc/shared/post-sync-summary.php' ); ?>