<strong><?php esc_html_e( 'Also manageable:', 'nlingual' ); ?></strong>

<ul>
	<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=nlingual-languages' ) ); ?>" target="_blank"><?php esc_html_e( 'What languages to use.', 'nlingual' ); ?></a></li>
	<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=nlingual-localizables' ) ); ?>" target="_blank"><?php esc_html_e( 'What should be localizable.', 'nlingual' ); ?></a></li>
	<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=nlingual-synchronizer' ) ); ?>" target="_blank"><?php esc_html_e( 'What data should be synced between translations.', 'nlingual' ); ?></a></li>
</ul>
