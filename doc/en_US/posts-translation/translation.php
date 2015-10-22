<?php
$post_type = get_current_screen()->post_type;
$singular = strtolower( get_post_type_object( $post_type )->labels->singular_name );
$plural = strtolower( get_post_type_object( $post_type )->labels->name );
?>
On this screen you can view at a glance what language every post is in, as well as what it's translations are. You can also filter the list to show only <?php echo $plural; ?> of a particular language.

The <strong>Quick Edit</strong> option will allow you to quickly change the language or assign translations to a <?php echo $singular; ?>, while Bulk Edit will allow you to change the language of many <?php echo $plural; ?> at once.

If you make changes to a <?php echo $singular; ?> from here, certain fields and settings will be copied to it’s sister translations, synchronizing them. The exact fields/settings that will be synchronized is controlled <a href="<?php echo admin_url('admin.php?page=nlingual-sync'); ?>" target="_blank">here</a>.

<?php require( dirname( __DIR__ ) . '/shared/post-sync-summary.php' ); ?>