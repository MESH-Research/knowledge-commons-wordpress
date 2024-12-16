/* Toggle Front-End */
jQuery(document).ready(function($){
	$('.trigger').click(function(e){
		e.preventDefault();
		$(this).toggleClass('active').next().slideToggle('fast');
	});
});