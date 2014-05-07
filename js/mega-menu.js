jQuery(function($) {
	var mobile = $('.mobile-toggle:visible').length == 1;
	$('.mega-menu-container').each(function() {
		var container = $(this),
			theme_location = container.data('theme-location'),
			home = container.data('home');
		container.find('.menu-item-depth-0').on('mouseover', function() {
			if(container.siblings('.mobile-toggle').filter(':visible').length) {
				return true;
			}
			ajax_load_menu($(this), theme_location, home);

			if(!$(this).children('.mega-menu:empty').length)
				$(this).children('.mega-menu').stop(true, true).slideDown(300);
		}).on('mouseleave', function(){
			if(container.siblings('.mobile-toggle').filter(':visible').length) {
				return true;
			}
			if(!$(this).children('.mega-menu:empty').length)
				$(this).children('.mega-menu').stop(true, true).slideUp(300).fadeOut();
		});

		container.siblings('.mobile-toggle').on('click', function(e) {
			e.preventDefault();

			if(container.is(':visible')) {
				container.stop(true, true).slideUp();
				$(this).removeClass('open');
			} else {
				container.stop(true, true).slideDown();
				$(this).addClass('open');
			}

			return false;
		});
	});

	$(window).resize(function() {
		if($('.mobile-toggle:visible').length && !mobile) {
			$('.mega-menu-container').hide();
			$('.mega-menu-container .mega-menu').hide();
			mobile = true;
		} else if(!$('.mobile-toggle:visible').length && mobile) {
			$('.mega-menu-container').show();
			$('.mega-menu-container').removeAttr('style');
			mobile = false;
		}
	});

	var ajax_load_menu = function(el, theme_location, home) {
		if(el.find('.mega-menu').is(':empty')) {
			var id = el.attr('id').replace(/[^0-9]+/, ''),
				url = '/ajax_mega_menu/' + encodeURIComponent(theme_location) + '/' + id,
				qs = '';
			$('script[src]').each(function() {
				var match = $(this).attr('src').match(/mega-menu.js\?([^"']+)/);
				if(match)
					qs = match[1];
			});
			$.ajax({
				async: false,
				url: home + url + '?' + qs,
				dataType: 'html',
				success: function(html) {
					// make sure CF7 forms have the current URL instead of the AJAX menu url
					html = html.replace(url, window.location.pathname + window.location.search);
					// insert the HTML
					el.find('.mega-menu').replaceWith($($.parseHTML(html)).find('.mega-menu'));
					// initialize the CF7 forms
					el.find('div.wpcf7 > form').wpcf7InitForm();
				}
			});
		}
	};
});
