<title><?php _e( 'Overview', 'nlingual' ); ?></title>

<p><?php _e( 'From here you can manage what details are synchronized between posts of the same translation group. These includes post fields, terms from select taxonomies, and custom fields you specify. When a post in a group is updated, any information that is specified here will be copied over to it’s sister posts.', 'nlingual' ); ?></p>

<p><?php
/* translators: This uses markdown-like formatting; **bold text**. */
echo nLingual\markitup( __( '**Caution:** these sync rules cannot be disabled on a per-post basis; either all post of a particular type have these details synchronized or none of them do.', 'nlingual' ) ); ?></p>
