<?php
// Get the current post type and singular/plural labels
$post_type = get_current_screen()->post_type;
if ( ! $post_type ) {
	return;
}
$singular = strtolower( get_post_type_object( $post_type )->labels->singular_name );
$plural = strtolower( get_post_type_object( $post_type )->labels->name );
?>
<title><?php esc_html_e( 'Languages & Translations', 'nlingual' ); ?></title>

<?php if ( nLingual\Registry::get( 'lock_post_language' ) ) : ?>
	<p><?php
	/* translators: %1$s = The singular name of the post type */
	_ef( 'The <strong>Languages & Translations</strong> box allows you to create/access translated versions for this %1$s in the other languages.', 'nlingual', $singular ); ?></p>
<?php else: ?>
	<p><?php
	/* translators: %1$s = The singular name of the post type, %2$s = The URL for the link. Please preserve the HTML as-is. */
	_ef( 'The <strong>Languages & Translations</strong> box allows you to assign a language for this %1$s, from the list of languages that have been registered <a href="%2$s" target="_blank">here</a> for use. Once a language is assigned, you can create/access translated versions for the other languages.', 'nlingual', $singular, esc_url( admin_url( 'admin.php?page=nlingual-languages' ) ) ); ?></p>
<?php endif; ?>

<p><?php esc_html_e( 'If a translation does not yet exist, you can click the Create button, a clone of the current post will be created in draft form, and opened in a new window or tab of you to start editing.', 'nlingual' ); ?></p>

<p><?php
/* translators: %s = The URL for the link. Please preserve the HTML as-is. */
_ef( 'When you save your changes, certain fields and settings will be copied to it’s sister translations, synchronizing them. The exact fields/settings that will be synchronized is controlled <a href="%s" target="_blank">here</a>.', 'nlingual', esc_url( admin_url( 'admin.php?page=nlingual-synchronizer' ) ) ); ?></p>

<?php require NL_PLUGIN_DIR . '/documentation/shared/post-sync-summary.php'; ?>
