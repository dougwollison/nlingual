<title><?php esc_html_e( 'Translated Content Management', 'nlingual' ); ?></title>

<p><?php esc_html_e( 'In this section, you can enable certain options pertaining to how translations are handled in the admin.', 'nlingual' ); ?></p>

<p><?php esc_html_e( 'By default, posts of all languages are displayed in on the edit screens; you can uncheck the show all box so only those of a particular language show by at a time.', 'nlingual' ); ?></p>

<p><?php esc_html_e( 'Since translations are intended to be somewhat synchronized, you may want to enable synchronized trashing and/or deleting of posts, for cleaner management of content.', 'nlingual' ); ?></p>

<p><?php esc_html_e( 'Depending on the workflow of you and your editors, you may want to enforce that all new content is in the default language, and prevent languages from being changed after the fact.', 'nlingual' ); ?></p>

<?php if ( nLingual\is_patch_font_stack_needed() ) : ?>
	<p><?php esc_html_e( 'Also, if you or some of your fellow admins/editors use Google Chrome, you may need to enable the Patch Admin Font option. This will replace the use of Open Sans with Helvetica, as a workaround to the issue of arabic and chinese characters appearing as squares under Open Sans.', 'nlingual' );?></p>
<?php endif; ?>
