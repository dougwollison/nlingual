<title><?php esc_html_e( 'Request and Redirection Handling', 'nlingual' ); ?></title>

<p><?php esc_html_e( 'You can customize key to look under for getting the language requested in fetching objects. This can be customized for purposes like conflict prevention with other translation systems, or just personal taste.', 'nlingual' ); ?></p>

<p><?php esc_html_e( 'When someone visits the site, the language to be served is determined by the following checks, each overriding the previous if matched:', 'nlingual' ); ?></p>

<ol>
	<li><?php esc_html_e( 'The visitor’s language according to their browser.', 'nlingual' ); ?></li>
	<?php if ( nLingual\Registry::can_use_rewrites() ) : ?>
		<li><?php esc_html_e( 'The language specified in the URL (based on the scheme specified by the Redirection Method)', 'nlingual' ); ?></li>
	<?php endif; ?>
	<li><?php esc_html_e( 'The GET/POST query argument if specified.', 'nlingual' ); ?></li>
	<li><?php esc_html_e( '(Optional) The language of the requested object if it differs from the on previously detected.', 'nlingual' ); ?></li>
</ol>

<p><?php esc_html_e( 'If the language cannot be determined by any of the above means, the language specified as the default will be used.', 'nlingual' ); ?></p>

<p><?php esc_html_e( 'For SEO purposes, it is recommended you enable the Language is Required option, which will tell the system to treat all unassigned posts as belonging to the default language. Otherwise, posts without a language can be viewed from multiple URLs, causing duplicated content on your site.', 'nlingual' ); ?></p>

<p><?php _e( 'Regarding SEO, nLingual by default uses "temporary" (HTTP 302) redirects to handle localized URLs. Best practices suggest using "permanent" (HTTP 301) redirects instead. <strong>Caution is advised when using permanent redirects, as they can cause issues if the URLs or the settings responsible for them are changed after the fact.</strong>', 'nlingual' ); ?></p>
