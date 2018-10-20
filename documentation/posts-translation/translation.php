<?php
// Get the current post type and singular/plural labels
$post_type = get_current_screen()->post_type;
$singular = strtolower( get_post_type_object( $post_type )->labels->singular_name );
$plural = strtolower( get_post_type_object( $post_type )->labels->name );
?>
<title><?php _e( 'Languages & Translations', 'nlingual' ); ?></title>

<p><?php
/* translators: %s = The singular name of the post type. */
_ef( 'On this screen you can view at a glance what language every post is in, as well as what it’s translations are. You can also filter the list to show only %s of a particular language.', 'nlingual', $plural ); ?></p>

<p><?php
/* translators: This uses markdown-style formatting; **bold text**. %1$s = The singular name of the post type, %2$s = The plural name of the post type. */
echo nLingual\markitup( _f( 'The **Quick Edit** option will allow you to quickly change the language or assign translations to a %1$s, while Bulk Edit will allow you to change the language of many %2$s at once. Currently, only the row for the %1$s being updated will change; you will need to refresh if you wish to see the full changes.', 'nlingual', $singular, $plural ) ); ?></p>

<p><?php
/* translators: This uses markdown-style formatting; [link text](url). %1$s = The singular name of the post type, %2$s = The URL for the link. Please preserve the HTML as-is. */
echo nLingual\markitup( _f( 'If you make changes to a %1$s from here, certain fields and settings will be copied to it’s sister translations, synchronizing them. The exact fields/settings that will be synchronized is controlled [here](%2$s).', 'nlingual', $singular, admin_url( 'admin.php?page=nlingual-synchronizer' ) ) ); ?></p>

<?php require NL_PLUGIN_DIR . '/documentation/shared/post-sync-summary.php'; ?>
