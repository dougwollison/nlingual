<?php
// Get the current post type and singular/plural labels
$post_type = get_current_screen()->post_type;
$singular = strtolower( get_post_type_object( $post_type )->labels->singular_name );
$plural = strtolower( get_post_type_object( $post_type )->labels->name );
?>
<title><?php _e( 'Languages & Translations', 'nlingual' ); ?></title>

<p><?php
/* translators: %s = The singular name of the post type. */
_ef( 'On this screen you can view at a glance what language every post is in, as well as what its translations are. You can also filter the list to show only %s of a particular language.', 'nlingual', $plural ); ?></p>

<?php if ( nLingual\Registry::get( 'lock_post_language' ) ) : ?>
	<p><?php
	/* translators: This uses markdown-like formatting; **bold text**. %1$s = The singular name of the post type. */
	_emf( 'The **Quick Edit** option will allow you to quickly select/change the assigned translations for a %1$s.', 'nlingual', $singular, $plural ); ?></p>
<?php else: ?>
	<p><?php
	/* translators: This uses markdown-like formatting; **bold text**. %1$s = The singular name of the post type, %2$s = The plural name of the post type. */
	_emf( 'The **Quick Edit** option will allow you to quickly select/change the language or assigned translations for a %1$s, while Bulk Edit will allow you to change the language of many %2$s at once.', 'nlingual', $singular, $plural ); ?></p>
<?php endif; ?>

<p><?php
/* translators: %1$s = The singular name of the post type. */
_ef( 'Currently, only the row for the %1$s being updated will change; you will need to refresh if you wish to see the full changes.', 'nlingual', $singular ); ?></p>

<p><?php
/* translators: This uses markdown-like formatting; [link text]^(url). %1$s = The singular name of the post type, %2$s = The URL for the link. Please preserve the HTML as-is. */
_emf( 'If you make changes to a %1$s from here, certain fields and settings will be copied to its sister translations, synchronizing them. The exact fields/settings that will be synchronized is controlled [here]^(%2$s).', 'nlingual', $singular, admin_url( 'admin.php?page=nlingual-synchronizer' ) ); ?></p>

<?php require NL_PLUGIN_DIR . '/documentation/shared/post-sync-summary.php'; ?>
