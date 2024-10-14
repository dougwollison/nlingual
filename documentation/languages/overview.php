<title><?php esc_html_e( 'Overview', 'nlingual' ); ?></title>

<p><?php esc_html_e( 'Here you can register the languages to be used by your site. You can select from a list of existing languages, or create a definition from scratch.', 'nlingual' ); ?></p>

<p><?php esc_html_e( 'Every language has a number of fields that must be specified:', 'nlingual' ); ?></p>

<ul>
	<li><strong><?php esc_html_e( 'System Name:', 'nlingual' ); ?></strong> <?php esc_html_e( 'The name of the language in your native tongue for easy recognition.', 'nlingual' ); ?></li>
	<li><strong><?php esc_html_e( 'Native Name:', 'nlingual' ); ?></strong> <?php esc_html_e( 'The name of the language as it should appear to a native speaker.', 'nlingual' ); ?></li>
	<li><strong><?php esc_html_e( 'Short Name:', 'nlingual' ); ?></strong> <?php esc_html_e( 'A shorthand name (usually native) of the language, which may be used by your custom theme.', 'nlingual' ); ?></li>
	<li><strong><?php esc_html_e( 'Locale:', 'nlingual' ); ?></strong> <?php esc_html_e( 'The name of the GetText localization file to load for this language.', 'nlingual' ); ?></li>
	<li><strong><?php esc_html_e( 'Code(s):', 'nlingual' ); ?></strong> <?php esc_html_e( 'A comma/space separated list of accepted values for the HTTP Accept-Language header.', 'nlingual' ); ?></li>
	<li><strong><?php esc_html_e( 'ISO Code:', 'nlingual' ); ?></strong> <?php esc_html_e( 'The ISO 639-1 code for the language (two letters).', 'nlingual' ); ?></li>
	<li><strong><?php esc_html_e( 'Slug:', 'nlingual' ); ?></strong> <?php esc_html_e( 'Used for specifying the language in the URL. Usually the same as the ISO code but can be different if desired.', 'nlingual' ); ?></li>
	<li><strong><?php esc_html_e( 'Text Direction:', 'nlingual' ); ?></strong> <?php esc_html_e( 'The direction of text the language should use (left-to-right or right-to-left).', 'nlingual' ); ?></li>
</ul>

<p><?php esc_html_e( 'Registered languages can be deactivated by unchecking the Active box. This hides them from from being publicly accessible, and excluding them from the All Languages filter in the post listing. The language will still however be available for assigning posts and translations to it.', 'nlingual' ); ?></p>

<p><?php esc_html_e( 'To delete languages, check the delete box before clicking Save Changes.', 'nlingual' ); ?></p>
