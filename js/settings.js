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
	
	$langauges.on('change', '.language-system_name input', function(){
		var text = $(this).val();
		var parent = $(this).parents('tr');
		
		var native_name = $('.language-native_name input',parent);
		var short_name = $('.language-short_name input',parent);
		var mo = $('.language-mo input',parent);
		var slug = $('.language-slug input',parent);
		var iso = $('.language-iso input',parent);
		
		// Default values based on system_name
		if(!native_name.val()) native_name.val(text);
		if(!short_name.val()) short_name.val(text.substr(0, 2));
		if(!mo.val()) mo.val(text.replace(/[^\w-]+/,'').toLowerCase());
		if(!slug.val()) slug.val(text.substr(0, 2).toLowerCase());
		if(!iso.val()) iso.val(text.substr(0, 2).toLowerCase());
	});

	$('#erase_translations').click(function(e){
		if(!confirm(nLingual_l10n.EraseDataConfirm))
			e.preventDefault();
	});
});