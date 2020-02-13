jQuery(function($){
	// Show all excluded pages
	$('#wpvf_show_excluded').click(function(event){
		event.preventDefault();
		$('.hidden_excluded').show();
		$('#wpvf_show_excluded').hide();
	});
	// Show all bot visits
	$('#wpvf_show_botcounts').click(function(event){
		event.preventDefault();
		$('.hidden_bots').show();
		$('#wpvf_show_botcounts').hide();
	});
});
