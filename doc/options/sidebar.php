<?php namespace nLingual; ?>
<strong><?php _e( 'Also manageable:' ); ?></strong>

<ul>
	<li><a href="<?php echo admin_url( 'admin.php?page=nlingual-localizables' ); ?>" target="_blank"><?php _e( 'What should be localizable.' ); ?></a></li>
	<li><a href="<?php echo admin_url( 'admin.php?page=nlingual-sync' ); ?>" target="_blank"><?php _e( 'Synchronizing and cloning options.' ); ?></a></li>
	<li><a href="<?php echo admin_url( 'admin.php?page=nlingual-languages' ); ?>" target="_blank"><?php _e( 'What languages to use.' ); ?></a></li>
</ul>
