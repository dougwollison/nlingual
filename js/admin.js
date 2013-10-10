jQuery(function($){
	// ========================= //
	// Post Editor Functionality //
	// ========================= //
	$('.edit-translation').click(function(e){
		e.preventDefault();
		var post = $(this).prev('select.nL-translations').val();
		if(parseInt(post,10) < 1) return alert(nLingual_l10n.NoPostSelected);
		var url = $(this).attr('href').replace('%d', post);
		window.open(url);
	});

	// =========================== //
	// Settings Page Functionality //
	// =========================== //

	var $languages = $('#nLingual_languages');

	var language_order = function(){
		$languages.find('tbody tr').each(function(i){
			$('.list_order',this).val(i);
		});
	}

	$languages.find('tbody').sortable({
		items: 'tr',
		axis: 'y',
		stop: language_order,
		helper: function(e, ui){
			ui.children().each(function(){
				$(this).width($(this).width());
			});
			return ui;
		}
	});

	var lang_id = -1;

	$('#nLingual_add_language').click(function(){
		var preset = $('#nLingual_language_preset').val();
		var $new_row = $($('#nLingual_language_template').html());

		$new_row.find('input').each(function(){
			$(this).attr('name', $(this).attr('name').replace('[-1]', '['+lang_id+']'));
		});
		$new_row.find('.language-default input[type="radio"]').val(lang_id);
		$new_row.find('.language-delete input[type="checkbox"]').val(lang_id);

		if(preset){ // Load with selected preset data
			var lang = nLingual_preset_languages[preset];
			$new_row.find('.language-system_name input').val(lang.system_name);
			$new_row.find('.language-native_name input').val(lang.native_name);
			$new_row.find('.language-short_name input').val(lang.short_name);
			$new_row.find('.language-mo input').val(lang.mo);
			$new_row.find('.language-slug input').val(preset);
			$new_row.find('.language-iso input').val(preset);
		}

		lang_id--;

		$languages.find('tbody').append($new_row);
		$languages.find('tbody').sortable('refresh');
		language_order();
	});

	$languages.on('change', '.language-system_name input', function(){
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