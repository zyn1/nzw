jQuery(document).ready(function($){

	jQuery(".back-to-top").click(function() {
		$('html,body').animate({scrollTop : 0}, 400);
		return false;
	});

	jQuery('.fix-btn').click(function() {
		$(this).fadeOut().siblings('.fix-bar').slideDown();
		$('#fix_name').focus();
		return false;
	});
	jQuery('.fix-close').click(function() {
		$(this).parent().parent('.fix-bar').fadeOut().siblings('.fix-btn').fadeIn();
		return false;
	});

	var $pic_array = $('.delay-loading');
	var loading_distance = $(window).scrollTop() + $(window).height() + 200;
	$(document).ready(function($) {
		$pic_array.each(function(i) {
			if($(this).offset().top <= loading_distance) {
				$(this).css({'background-image':$(this).attr('data-url')});
				$pic_array.splice(i,0);
			}
		});
	});

	$(window).scroll(function() {
		if($pic_array.length) {
			loading_distance = $(this).scrollTop() + $(this).height() + 200;
			$pic_array.each(function(i) {
				if($(this).offset().top <= loading_distance) {
					$(this).css({'background-image':$(this).attr('data-url')});
					$pic_array.splice(i,0);
				}
			});
		}
	});
});