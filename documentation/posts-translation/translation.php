<?php
// Get the current post type and singular/plural labels
$post_type = get_current_screen()->post_type;
$singular = strtolower( get_post_type_object( $post_type )->labels->singular_name );
$plural = strtolower( get_post_type_object( $post_type )->labels->name );
?>
<title><?php _e( 'Languages & Translations', 'nlingual' ); ?></title>

<p><?php
/* Translators: %s = The singular name of the post type. */
_ef( 'On this screen you can view at a glance what language every post is in, as well as what it’s translations are. You can also filter the list to show only %s of a particular language.', 'nlingual', $plural ); ?></p>

<p><?php
/* Translators: %1$s = The singular name of the post type, %2$s = The plural name of the post type. */
_ef( 'The <strong>Quick Edit</strong> option will allow you to quickly change the language or assign translations to a %1$s, while Bulk Edit will allow you to change the language of many %2$s at once. Currently, only the row for the %1$s being updated will change; you will need to refresh if you wish to see the full changes.', 'nlingual', $singular, $plural ); ?></p>

<p><?php
/* Translators: %1$s = The singular name of the post type, %2$s = The URL for the link. Please preserve the HTML as-is. */
_ef( 'If you make changes to a %1$s from here, certain fields and settings will be copied to it’s sister translations, synchronizing them. The exact fields/settings that will be synchronized is controlled <a href="%2$s" target="_blank">here</a>.', 'nlingual', $singular, admin_url( 'admin.php?page=nlingual-synchronizer' ) ); ?></p>

<?php require( dirname( __DIR__ ) . '/shared/post-sync-summary.php' ); ?>
