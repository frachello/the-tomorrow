var eventorganiser_venue_featured_frame;
jQuery(document).ready( function($) {
	// Uploading files	
	jQuery('#eo-featured-image').on( 'click', '#eventorganiser-set-venue-thumbnail', function( event ){
		event.preventDefault();
		var attachment;
	    // If the media frame already exists, reopen it.
	    if ( eventorganiser_venue_featured_frame ) {
	    	if( eventorganiser_venue_featured_frame.options.thumbnail_id ){
	    		attachment =  wp.media.attachment( eventorganiser_venue_featured_frame.options.thumbnail_id);
	    		eventorganiser_venue_featured_frame.state().get('selection').add( attachment );	
	    	}
		   
			eventorganiser_venue_featured_frame.open();
			return;
	    }
	 
	    // Create the media frame.
	    eventorganiser_venue_featured_frame = wp.media.frames.eventorganiser_venue_featured_frame = wp.media({
	      title: jQuery( this ).data( 'uploader_title' ),
	      button: {
	        text: jQuery( this ).data( 'uploader_button_text' )
	      },
	      multiple: false, // Set to true to allow multiple files to be selected
	      thumbnail_id: eventorganiser_featured_img.id
	    });
	    
	    // When an image is selected, run a callback.
	    eventorganiser_venue_featured_frame.on( 'select', function() {
			//We set multiple to false so only get one image from the uploader 
			attachment = eventorganiser_venue_featured_frame.state().get('selection').first().toJSON();
			eventorganiser_venue_featured_frame.options.thumbnail_id = attachment.id;
			//Add image to venue 
			jQuery.post(ajaxurl, {
				action:"eo-set-venue-thumbnail", 
				eo_venue_id: $('#eo_venue_id').val(), 
				thumbnail_id: attachment.id,
				_ajax_nonce: eventorganiser_featured_img.nonce
			}, function( r ){
				if( r !== -1 && r !== 0 && r.errors === false ){
					$( '.inside', '#eo-featured-image' ).html( r.data );
					if( r.venue_id )
						$('#eo_venue_id').val(r.venue_id );
				}else {
					alert( 'Error' );
				}
			},'JSON'
			);
	    });

	    // Finally, open the modal
	    eventorganiser_venue_featured_frame.open();

    	if( eventorganiser_venue_featured_frame.options.thumbnail_id ){
    		attachment =  wp.media.attachment( eventorganiser_venue_featured_frame.options.thumbnail_id);
    		eventorganiser_venue_featured_frame.state().get('selection').add( attachment );	
    	}
	    
	  });

	$('#eo-featured-image').on( 'click', '#eventorganiser-remove-venue-thumbnail', function(e){
		e.preventDefault();
		//Add image to venue 
		jQuery.post(ajaxurl, {
			action:"eo-set-venue-thumbnail", 
			eo_venue_id: $('#eo_venue_id').val(), 
			thumbnail_id: -1, 
			_ajax_nonce: eventorganiser_featured_img.nonce
		}, function(str){
			if ( str == -1 || str === 0 ) {
				alert( 'Error' );
			} else {
				$( '.inside', '#eo-featured-image' ).html( str );
				eventorganiser_venue_featured_frame.options.thumbnail_id = false;
			}
		});
	});
} );