jQuery(function($) {
	$('#fix_wp_menu_form').on('submit', function(e) {
		e.preventDefault();
		var form = $(this);

		if(confirm('Are you SURE? This will overwrite the existing WP menu.')) {
			form.find('.success').remove();

			var params = {
				action: 'mega_menu_fix_wp_menu',
				theme_location: form.find('select').val()
			}
			$.post(ajaxurl, params, function(data) {
				$('<p class="success"></p>').html(data).appendTo(form);
			});
		}
		return false;
	});
});
