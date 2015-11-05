jQuery(document).ready(function($)
{	
	var list = $('body.sortable-posts .wp-list-table #the-list'),
		rows = list.find('tr');

	// Make list sortable.
	list.sortable({
		handle: '.column-sortable-posts-order',
		placeholder: 'sortable-posts-placeholder',
		helper: function( e, ui ) {
			ui.children().each(function() {
				$(this).width( $(this).width() );
			});
			return ui;
		},
		forcePlaceholderSize: true,
		forceHelperSize: true,
		start: function(e, ui ) {
			ui.placeholder.height(ui.helper.outerHeight());
		},
	});

	// Update order.
	list.on( 'sortupdate', function( event, ui )
	{
		var order = $(this).sortable( 'toArray' );

		$.ajax({
			type: 'post',
			url: sortablePosts.ajaxurl,
			data: {
				action: 'sortable_posts_update_order',
				order: order,
				start: sortablePosts.start
			},
		});

		// Update position number in row.
		rows.each( function()
		{
			var id = $(this).attr('id'),
				index = $(this).index( '#' . id ),
				numberContainer = $(this).find('.sortable-posts-order-position');

			numberContainer.html( (sortablePosts.start * 1) + index );
		});

	});
});