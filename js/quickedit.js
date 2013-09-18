(function($) {
	// we create a copy of the WP inline edit post function
	var wp_inline_edit = inlineEditPost.edit;
	// and then we overwrite the function with our own code
	inlineEditPost.edit = function( id ) {
		// "call" the original WP edit function
		// we don't want to leave WordPress hanging
		wp_inline_edit.apply( this, arguments );

		// get the post ID
		var post_id = 0;
		if(typeof( id ) == 'object')
			post_id = parseInt(this.getId(id));

		if(post_id > 0){
			// define the edit row
			var post_row = $('#post-' + post_id);
			var edit_row = $('#edit-' + post_id);

			// get the data
			var lang = $('.column-language input', post_row).val();

			if(!lang) return;

			// populate the data
			$('select[name="language"]', edit_row).val(lang);
		}
	};
})(jQuery);