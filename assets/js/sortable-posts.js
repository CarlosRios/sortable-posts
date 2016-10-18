jQuery(document).ready(function($)
{	
	var list 		= $('body.sortable-posts .wp-list-table #the-list'),
		rows		= list.find('tr'),
		statusBox	= $( '#sortable-posts-status' ),
		statusMsg	= statusBox.find( '#sortable-posts-status-message' );

	// Create helper so row columns maintain their width.
	var sortablePostsFixHelper = function(e, ui)
	{
		ui.children().each(function() {
			$(this).width($(this).width());
		});
		return ui;
	};

	// Make list sortable.
	list.sortable({
		handle: '.column-sortable-posts-order',
		placeholder: 'sortable-posts-placeholder',
		helper: sortablePostsFixHelper,
		forcePlaceholderSize: true,
		forceHelperSize: true,
		start: function(e, ui ) {
			ui.placeholder.height(ui.helper.outerHeight());
		},
	}).disableSelection();

	// Update order.
	list.on( 'sortupdate', function( event, ui )
	{
		var order = $(this).sortable( 'toArray' );

		$.ajax({
			type: 'post',
			url: WP_API_Settings.root + 'sortable-posts/update',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', WP_API_Settings.nonce );
			},
			data: {
				order: order,
				start: WP_API_Settings.start,
				object_type: WP_API_Settings.obj_type,
				post_type: jQuery('.post_type_page').val(),
				taxonomy: WP_API_Settings.taxonomy,
				taxonomy_term: WP_API_Settings.taxonomy_term
			}
		}).done( function( response ) {

			// Update position in the row.
			rows.each( function()
			{
				var id = $(this).attr('id'),
					index = $(this).index( '#' . id ),
					numberContainer = $(this).find('.sortable-posts-order-position');

				numberContainer.html( (WP_API_Settings.start * 1) + index );
			});

			// Parse the json
			var data = $.parseJSON( response );

			// Inject the json into message element
			statusMsg.html( data.message );

			// Add classes to the status
			statusBox.addClass( 'updated sp-visible animated fadeInUp' );

		}).fail( function( response ) {

			// Parse the json for the error
			var data = $.parseJSON( response.responseText );
			statusMsg.html( data.message );

			// Add classes to the status
			statusBox.addClass( 'error sp-visible animated fadeInUp' );

		}).always( function( response ) {

			// Remove classes and fade out
			setTimeout(function() {
				statusBox.removeClass( 'fadeInUp' ).addClass( 'fadeOutDown' );
			}, 4000 );

			// Remove all classes and hide the status box
			setTimeout(function() {
				statusBox.removeClass();
			}, 4800 );
		
		});

	});

});