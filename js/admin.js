jQuery(function($){
	$('#nLingual_languages').sortable({
		items: '.language',
		axis: 'y'
	});

	$('#nLingual_add_language').click(function(){
		$('#nLingual_languages').append($('#nLingual_language_template').html());
		$('#nLingual_languages').sortable('refresh');
	});

	$('#nLingual_languages').on('click', '.delete', function(){
		if(!confirm('Are you sure you wish to delete this language?')) return;
		$(this).parents('.language').remove();
		$('#nLingual_languages').sortable('refresh');
	});
});