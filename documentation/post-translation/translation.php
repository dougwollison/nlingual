<?php
// Get the current post type and singular/plural labels
$post_type = get_current_screen()->post_type;
$singular = strtolower( get_post_type_object( $post_type )->labels->singular_name );
$plural = strtolower( get_post_type_object( $post_type )->labels->name );
?>
<title><?php _e( 'Languages & Translations', 'nlingual' ); ?></title>

<?php if ( nLingual\Registry::get( 'lock_post_language' ) ) : ?>
	<p><?php
	/* translators: %1$s = The singular name of the post type */
	_ef( 'The <strong>Languages & Translations</strong> box allows you to create/access translated versions for this %1$s in the other languages.', 'nlingual', $singular ); ?></p>
<?php else: ?>
	<p><?php
	/* translators: This uses markdown-like formatting; **bold text**, [link text](url). %1$s = The singular name of the post type, %2$s = The URL for the link. */
	echo nLingual\markitup( _f( 'The **Languages & Translations** box allows you to assign a language for this %1$s, from the list of languages that have been registered [here](%2$s) for use. Once a language is assigned, you can create/access translated versions for the other languages.', 'nlingual', $singular, admin_url( 'admin.php?page=nlingual-languages' ) ) ); ?></p>
<?php endif; ?>

<p><?php _e( 'If a translation does not yet exist, you can click the Create button, a clone of the current post will be created in draft form, and opened in a new window or tab of you to start editing.', 'nlingual' ); ?></p>

<p><?php
/* translators: This uses markdown-like formatting; [link text](url). %s = The URL for the link. */
echo nLingual\markitup( _f( 'When you save your changes, certain fields and settings will be copied to its sister translations, synchronizing them. The exact fields/settings that will be synchronized is controlled [here](%s).', 'nlingual', admin_url( 'admin.php?page=nlingual-synchronizer' ) ) ); ?></p>

<?php require NL_PLUGIN_DIR . '/documentation/shared/post-sync-summary.php'; ?>
