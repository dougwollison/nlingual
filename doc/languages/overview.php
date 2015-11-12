<?php namespace nLingual; ?>
<p><?php _e( 'Here you can register the languages that shall be used by your site. You can select from a list of existing languages, or create a definition from scratch.' ); ?></p>

<p><?php _e( 'Every language has a number of fields that must be specified:' ); ?></p>

<ul>
	<li><?php _e( '<strong>System Name:</strong> the name of the language in your native tongue for easy recognition.' );?></li>
	<li><?php _e( '<strong>Native Name:</strong> the name of the language as it should appear to a native speaker.' );?></li>
	<li><?php _e( '<strong>Short Name:</strong> a shorthand name (usually native) of the language, which may be used by your custom theme.' );?></li>
	<li><?php _e( '<strong>Locale:</strong> the name of the GetText localization file to load for this language.' );?></li>
	<li><?php _e( '<strong>ISO Code:</strong> the ISO 639-1 code for the language (two letters).' );?></li>
	<li><?php _e( '<strong>Slug:</strong> Used for specifying the language in the URL. Usually the same as the ISO code but can be different if desired.' );?></li>
</ul>

<p><?php _e( 'Registered languages can be deactivated, hiding them from being publicly accessible, and excluding them from the All Languages filter in the post listing. The language will still however be available for assigning posts and translations to.' ); ?></p>

<p><?php _e( 'To delete languages, check off the delete box and and then click Save Changes.' ); ?></p>
