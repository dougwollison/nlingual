jQuery(function($){
	var $langauges = $('#nLingual_languages');

	$langauges.find('tbody').sortable({
		items: 'tr',
		axis: 'y',
		stop: function(event, ui){
			$langauges.find('tbody tr').each(function(i){
				$('.list_order',this).val(i);
			});
		}
	});

	var lang_id = -1;

	$('#nLingual_add_language').click(function(){
		var $new_row = $($('#nLingual_language_template').html());

		$new_row.find('input[type="text"]').each(function(){
			$(this).attr('name', $(this).attr('name').replace('-1', lang_id));
		});
		$new_row.find('.language-delete input').val(lang_id);

		lang_id--;

		$langauges.find('tbody').append($new_row);
		$langauges.find('tbody').sortable('refresh');
	});

	$('#erase_translations').click(function(e){
		if(!confirm(nLingual_l10n.EraseDataConfirm))
			e.preventDefault();
	});
});