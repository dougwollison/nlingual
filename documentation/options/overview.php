<title><?php _e( 'Overview', 'nlingual' ); ?></title>

<p><?php _e( 'The controls here affect the basic functionalities of site translation, both on the front-end and the back-end.', 'nlingual' ); ?></p>

<p><?php _e( 'To control what site the language falls back to in for visitors who prefer and unsupported or undetected language, select a language as the Default Language.', 'nlingual' ); ?> <?php _e( 'If your site uses languages that have their own handling of date formats, check off the Localized Date box and/or the Patch WP_Locale box, depending on what your setup is.', 'nlingual' ); ?></p>

<?php if ( get_option( 'nlingual_upgraded' ) ) : ?>
<p><?php _e( 'Since you upgraded from nLingual 1, you may need use of the Backwards Compatibility tools. These are various hooks and functions that themes and plugins built for the older version of nLingual may need, and are enabled by default. It is highly recommended you see about getting these updated to use the newer code.', 'nlingual' ); ?></p>
<?php endif; ?>