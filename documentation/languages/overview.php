<?php namespace nLingual; ?>
<p><?php _e( 'Here you can register the languages that shall be used by your site. You can select from a list of existing languages, or create a definition from scratch.' ); ?></p>

<p><?php _e( 'Every language has a number of fields that must be specified:' ); ?></p>

<ul>
	<li><strong><?php _e( 'System Name:' );?></strong> <?php _e( 'the name of the language in your native tongue for easy recognition.' );?></li>
	<li><strong><?php _e( 'Native Name:' );?></strong> <?php _e( 'the name of the language as it should appear to a native speaker.' );?></li>
	<li><strong><?php _e( 'Short Name:' );?></strong> <?php _e( 'a shorthand name (usually native) of the language, which may be used by your custom theme.' );?></li>
	<li><strong><?php _e( 'Locale:' );?></strong> <?php _e( 'the name of the GetText localization file to load for this language.' );?></li>
	<li><strong><?php _e( 'ISO Code:' );?></strong> <?php _e( 'the ISO 639-1 code for the language (two letters).' );?></li>
	<li><strong><?php _e( 'Slug:' );?></strong> <?php _e( 'Used for specifying the language in the URL. Usually the same as the ISO code but can be different if desired.' );?></li>
</ul>

<p><?php _e( 'Registered languages can be deactivated, hiding them from being publicly accessible, and excluding them from the All Languages filter in the post listing. The language will still however be available for assigning posts and translations to.' ); ?></p>

<p><?php _e( 'To delete languages, check off the delete box and and then click Save Changes.' ); ?></p>
