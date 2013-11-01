(function($){
	// remove the counts for posts, categories and tags from the 'right now' box
	$('#dashboard_right_now .table_content tr').each(function() {
		var $cell = $(this).find('td.first');
		if ($cell.hasClass('b-posts') || $cell.hasClass('b-cats') || $cell.hasClass('b-tags')) {
			$(this).remove();
		}
	}).eq(0).addClass('first');
})(jQuery);
