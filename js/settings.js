jQuery(function($){
	$('#nLingual_languages tbody').sortable({
		items: 'tr',
		axis: 'y'
	});

	var lang_id = -1;

	$('#nLingual_add_language').click(function(){
		var $new_row = $($('#nLingual_language_template').html());

		$new_row.find('input[type="text"]').each(function(){
			$(this).attr('name', $(this).attr('name').replace('-1', lang_id));
		});
		$new_row.find('.language-delete input').val(lang_id);

		lang_id--;

		$('#nLingual_languages tbody').append($new_row);
		$('#nLingual_languages tbody').sortable('refresh');
	});

	$('#nLingual_languages').on('click', '.delete', function(){
		if(!confirm(nLingual_l10n.DeleteLangConfirm)) return;
		$(this).parents('tr').remove();
		$('#nLingual_languages tbody').sortable('refresh');
	});

	$('#erase_translations').click(function(e){
		if(!confirm(nLingual_l10n.EraseDataConfirm))
			e.preventDefault();
	});
});