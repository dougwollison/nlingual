<title><?php esc_html_e( 'Localize This', 'nlingual' ); ?></title>

<p><?php
/* translators: %s = the translation icon HTML */
$format = esc_attr__( 'Use the %s widget to add, view, and edit localized versions of the text in the associated field.', 'nlingual' );
printf( $format, '<i class="dashicons dashicons-translation"></i>' );
?></p>

<p><?php esc_html_e( 'When the site is viewed in a particular language and the data controlled by these fields is displayed, the version for that language will be used instead, provided it exists (it will fallback to the original value otherwise).', 'nlingual' ); ?></p>
